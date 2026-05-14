<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\Post;
use app\models\Comment;
use app\models\Like;
use app\models\Follow;
use app\models\Notification;
use app\models\Repost;
use app\models\SavedPost;
use app\models\Poll;
use app\models\PollOption;
use app\models\PollVote;
use app\models\User;
use app\components\ApiValidator;
use app\components\RateLimiter;

class FeedController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'create-post' => ['POST'],
                    'like' => ['POST'],
                    'comment' => ['POST'],
                    'update-comment' => ['POST'],
                    'delete-comment' => ['POST'],
                    'repost' => ['POST'],
                    'vote' => ['POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['create-post', 'like', 'comment', 'update-comment', 'delete-comment', 'repost', 'vote'])) {
            $this->enableCsrfValidation = false;
        }

        if (!Yii::$app->user->isGuest) {
            \app\models\OnlineUser::updateActivity(Yii::$app->user->id);
        }
        
        return parent::beforeAction($action);
    }

    public function actionIndex()
    {
        $type = Yii::$app->request->get('type', 'all');
        $this->view->title = 'Лента';

        $storiesByUser = [];
        if (!Yii::$app->user->isGuest) {
            $stories = \app\models\Story::getFollowingStories(Yii::$app->user->id);
            foreach ($stories as $story) {
                $storiesByUser[$story->user_id][] = $story;
            }
        }

        return $this->render('index', [
            'posts' => [], // Пустой массив, посты загружаются через API
            'type' => $type,
            'storiesByUser' => $storiesByUser
        ]);
    }

    public function actionGetPosts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $limit = (int) Yii::$app->request->get('limit', 3);
        $offset = (int) Yii::$app->request->get('offset', 0);
        $since = (int) Yii::$app->request->get('since', 0);
        $type = Yii::$app->request->get('type', 'following');
        
        $query = Post::find()
            ->with(['user', 'poll.options'])
            ->orderBy(['created_at' => SORT_DESC]);

        if ($type === 'following' && !Yii::$app->user->isGuest) {

            $followingIds = Follow::getFollowingIds(Yii::$app->user->id);
            $followingIds[] = Yii::$app->user->id; // Добавляем свои посты
            $query->andWhere(['user_id' => $followingIds]);
        }

        
        if ($since > 0) {
            $query->andWhere(['>', 'created_at', $since]);
        }
        
        $posts = $query->limit($limit)->offset($offset)->all();

        $posts = array_filter($posts, function($post) {
            return $post->user->canViewProfile(Yii::$app->user->id);
        });
        
        $userId = Yii::$app->user->id;

        $html = '';
        foreach ($posts as $post) {
            $html .= $this->renderPartial('/post/_post_card', ['post' => $post]);
        }
        
        return ['html' => $html, 'count' => count($posts)];
    }

    public function actionCreatePost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkPostLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }
        
        $content = Yii::$app->request->post('content');

        $validation = ApiValidator::validatePostData(['content' => $content]);
        if ($validation !== true) {
            return ApiValidator::error(implode(', ', $validation));
        }
        
        $post = new Post([
            'user_id' => Yii::$app->user->id,
            'content' => $content,
        ]);

        $post->imageFile = UploadedFile::getInstanceByName('image');
        
        if ($post->imageFile && !$post->validate()) {
            return ['success' => false, 'error' => 'Некорректное изображение: ' . implode(', ', $post->getErrors('imageFile'))];
        }

        $pollQuestion = Yii::$app->request->post('poll_question');
        $pollMultiple = Yii::$app->request->post('poll_multiple', 0);
        $pollOptionsRaw = Yii::$app->request->post('poll_options', '');

        $pollOptions = [];
        if (is_array($pollOptionsRaw)) {
            $pollOptions = $pollOptionsRaw;
        } elseif (is_string($pollOptionsRaw) && !empty(trim($pollOptionsRaw))) {
            $pollOptions = array_map('trim', explode(',', $pollOptionsRaw));
        }
        
        if ($pollQuestion && !empty($pollOptions)) {

            $pollData = [
                'question' => $pollQuestion,
                'multiple_votes' => $pollMultiple,
                'options' => $pollOptions
            ];
            
            $pollValidation = ApiValidator::validatePollData($pollData);
            if ($pollValidation !== true) {
                return ApiValidator::error(implode(', ', $pollValidation));
            }
        }
        
        if ($post->save()) {

            $this->parseMentions($content, $post);

            if ($pollQuestion && !empty($pollOptions)) {
                $poll = new \app\models\Poll([
                    'post_id' => $post->id,
                    'user_id' => Yii::$app->user->id,
                    'question' => $pollQuestion,
                    'multiple_votes' => $pollMultiple ? 1 : 0,
                ]);
                
                if ($poll->save()) {
                    foreach ($pollOptions as $optionText) {
                        if (!empty(trim($optionText))) {
                            $option = new \app\models\PollOption([
                                'poll_id' => $poll->id,
                                'text' => trim($optionText),
                            ]);
                            $option->save();
                        }
                    }
                }
            }
            
            $postData = $post->toArray();
            $postData['image_url'] = $post->getImageUrl();
            return ['success' => true, 'post' => $postData];
        }
        
        return ['success' => false, 'error' => 'Ошибка сохранения'];
    }

    public function actionLike()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $rateLimitCheck = RateLimiter::checkLikeLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $rawData = Yii::$app->request->getRawBody();
        $data = $rawData ? json_decode($rawData, true) : [];
        $postId = $data['post_id'] ?? Yii::$app->request->post('post_id');

        if (!$postId || !is_numeric($postId)) {
            return ['success' => false, 'error' => 'Не указан post_id'];
        }
        
        $post = Post::findOne($postId);
        
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }

        $userId = Yii::$app->user->id;
        $existing = Like::findOne(['post_id' => $postId, 'user_id' => $userId]);

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $like = new Like([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
            $like->save();
            $liked = true;

            if ($post->user_id != $userId) {
                Notification::create(
                    $post->user_id,
                    Notification::TYPE_LIKE,
                    $userId,
                    $post->id
                );
            }
        }
        
        $post->refresh();
        
        return [
            'success' => true,
            'liked' => $liked,
            'likes_count' => $post->likes_count,
        ];
    }

    public function actionGetComments($post_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $comments = Comment::find()
            ->where(['post_id' => $post_id])
            ->with('user')
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        
        return array_map(function($comment) {
            $data = $comment->toArray();

            $data['author'] = [
                'id' => $comment->user->id,
                'username' => $comment->user->username,
                'avatar' => $comment->user->getAvatarUrl(),
            ];

            $data['timeAgo'] = Yii::$app->formatter->asRelativeTime($comment->created_at);
            return $data;
        }, $comments);
    }

    public function actionComment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkCommentLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $postId = $data['post_id'] ?? Yii::$app->request->post('post_id');
        $content = $data['content'] ?? Yii::$app->request->post('content');
        
        $post = Post::findOne($postId);
        if (!$post) {
            return ApiValidator::error('Пост не найден');
        }

        $validation = ApiValidator::validateCommentData(['post_id' => $postId, 'content' => $content]);
        if ($validation !== true) {
            return ApiValidator::error(implode(', ', $validation));
        }
        
        $comment = new Comment([
            'post_id' => $postId,
            'user_id' => Yii::$app->user->id,
            'content' => $content,
        ]);
        
            if ($comment->save()) {
            $post->comments_count = Comment::find()->where(['post_id' => $postId])->count();
            $post->save(false);

            if ($post->user_id != Yii::$app->user->id) {
                Notification::create(
                    $post->user_id,
                    Notification::TYPE_COMMENT,
                    Yii::$app->user->id,
                    $post->id
                );
            }

            $user = User::findOne($comment->user_id);
            $commentData = $comment->toArray();
            $commentData['author'] = [
                'id' => $user->id,
                'username' => $user->username,
                'avatar' => $user->getAvatarUrl(),
            ];
            $commentData['timeAgo'] = 'только что';
            
            return ['success' => true, 'comment' => $commentData];
        }
        
        return ['success' => false, 'error' => 'Ошибка сохранения'];
    }

    
    public function actionUpdateComment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $commentId = $data['comment_id'] ?? Yii::$app->request->post('comment_id');
        $content = $data['content'] ?? Yii::$app->request->post('content');
        
        $comment = Comment::findOne($commentId);
        
        if (!$comment) {
            return ['success' => false, 'error' => 'Комментарий не найден'];
        }
        
        if ($comment->user_id !== Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нет прав для редактирования'];
        }
        
        if (empty($content) || strlen($content) > 1000) {
            return ['success' => false, 'error' => 'Некорректный комментарий'];
        }
        
        $comment->content = $content;
        
        if ($comment->save(false)) {
            return ['success' => true, 'comment' => $comment->toArray()];
        }
        
        return ['success' => false, 'error' => 'Ошибка сохранения'];
    }

    
    public function actionDeleteComment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $commentId = $data['comment_id'] ?? Yii::$app->request->post('comment_id');
        
        $comment = Comment::findOne($commentId);
        
        if (!$comment) {
            return ['success' => false, 'error' => 'Комментарий не найден'];
        }
        
        if ($comment->user_id !== Yii::$app->user->id) {
            return ['success' => false, 'error' => 'Нет прав для удаления'];
        }
        
        $postId = $comment->post_id;
        
        if ($comment->delete()) {

            $post = Post::findOne($postId);
            if ($post) {
                $post->comments_count = Comment::find()->where(['post_id' => $postId])->count();
                $post->save(false);
            }
            
            return ['success' => true];
        }
        
        return ['success' => false, 'error' => 'Ошибка удаления'];
    }

    
    public function actionRepost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkApiLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $postId = $data['post_id'] ?? Yii::$app->request->post('post_id');
        
        $post = Post::findOne($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }
        
        $result = Repost::toggle(Yii::$app->user->id, $postId);
        
        if ($result['reposted']) {

            if ($post->user_id !== Yii::$app->user->id) {
                $notification = new Notification([
                    'user_id' => $post->user_id,
                    'from_user_id' => Yii::$app->user->id,
                    'type' => 'repost',
                    'post_id' => $postId,
                ]);
                $notification->save();
            }
        }
        
        return ['success' => true, 'reposted' => $result['reposted'], 'reposts_count' => $result['reposts_count']];
    }

    
    public function actionVote()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $rateLimitCheck = RateLimiter::checkVoteLimit();
        if ($rateLimitCheck !== true) {
            return $rateLimitCheck;
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $pollId = $data['poll_id'] ?? Yii::$app->request->post('poll_id');
        $optionIds = $data['option_ids'] ?? Yii::$app->request->post('option_ids', []);
        
        if (!is_array($optionIds) || empty($optionIds)) {
            return ApiValidator::error('Выберите вариант');
        }
        
        $poll = Poll::findOne($pollId);
        if (!$poll) {
            return ['success' => false, 'error' => 'Опрос не найден'];
        }

        if (!PollVote::vote($pollId, $optionIds, Yii::$app->user->id)) {
            return ['success' => false, 'error' => 'Ошибка голосования'];
        }

        $poll = Poll::findOne($pollId);
        $pollData = $poll->toArray();

        $pollData['has_user_voted'] = $poll->hasUserVoted(Yii::$app->user->id);
        $pollData['user_votes'] = $poll->getUserVotes(Yii::$app->user->id);
        $pollData['total_votes'] = $poll->getTotalVotes();
        $pollData['multiple_votes'] = $poll->multiple_votes;

        $pollData['options'] = array_map(function($option) use ($pollData) {
            $optionData = $option->toArray();
            $optionData['votes_count'] = $option->getVotesCount();
            $optionData['percentage'] = $pollData['total_votes'] > 0 
                ? round(($optionData['votes_count'] / $pollData['total_votes']) * 100, 1) 
                : 0;
            return $optionData;
        }, $poll->options);
        
        return ['success' => true, 'poll' => $pollData];
    }

    public function actionPoll($last_check = 0)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $lastCheck = (int) $last_check;
        $now = time();
        $userId = Yii::$app->user->id;

        $newPosts = Post::find()
            ->with('user')
            ->where(['>', 'created_at', $lastCheck])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $newComments = Comment::find()
            ->with('user')
            ->where(['>', 'created_at', $lastCheck])
            ->all();
        
        return [
            'timestamp' => $now,
            'posts' => array_map(function($post) use ($userId) {
                $data = $post->toArray();
                $data['is_liked'] = $userId ? $post->isLikedBy($userId) : false;
                return $data;
            }, $newPosts),
            'comments' => array_map(function($c) { return $c->toArray(); }, $newComments),
            'online_count' => \app\models\OnlineUser::getOnlineCount(),
        ];
    }

    
    public function actionGetNotifications()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $notifications = Notification::getUnread(Yii::$app->user->id, 10);

        return [
            'success' => true,
            'notifications' => array_map(function($n) {
                return [
                    'id' => $n->id,
                    'text' => $n->getText(),
                    'icon' => $n->getIcon(),
                    'from_user' => $n->fromUser ? [
                        'id' => $n->fromUser->id,
                        'username' => $n->fromUser->username,
                        'avatar' => $n->fromUser->getAvatarUrl(),
                    ] : null,
                    'post_id' => $n->post_id,
                    'created_at' => $n->created_at,
                    'timeAgo' => Yii::$app->formatter->asRelativeTime($n->created_at),
                ];
            }, $notifications),
            'unread_count' => Notification::getUnreadCount(Yii::$app->user->id),
        ];
    }

    
    public function actionMarkNotificationsRead()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $notificationId = $data['id'] ?? Yii::$app->request->post('id');

        if ($notificationId) {

            $notification = Notification::findOne($notificationId);
            if ($notification && $notification->user_id === Yii::$app->user->id) {
                $notification->is_read = 1;
                $notification->save(false);
                return ['success' => true];
            }
            return ['success' => false, 'error' => 'Уведомление не найдено'];
        }

        Notification::markAllAsRead(Yii::$app->user->id);

        return ['success' => true];
    }

    
    public function actionSavePost()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $postId = $data['post_id'] ?? Yii::$app->request->post('post_id');

        $post = Post::findOne($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Пост не найден'];
        }

        $result = SavedPost::toggle(Yii::$app->user->id, $postId);

        return ['success' => true] + $result;
    }

    
    public function actionSavedPosts()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (Yii::$app->user->isGuest) {
            return ['success' => false, 'error' => 'Не авторизован'];
        }

        $posts = SavedPost::getUserSavedPosts(Yii::$app->user->id);

        $userId = Yii::$app->user->id;

        return array_map(function($post) use ($userId) {
            $data = $post->toArray();
            $data['is_liked'] = $post->isLikedBy($userId);
            $data['is_saved'] = true;
            return $data;
        }, $posts);
    }

    
    private function parseMentions($content, $post)
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                $user = User::findByUsername($username);
                if ($user && $user->id != Yii::$app->user->id) {
                    Notification::create(
                        $user->id,
                        Notification::TYPE_MENTION,
                        Yii::$app->user->id,
                        $post->id,
                        Yii::$app->user->identity->username . ' упомянул(а) вас в посте'
                    );
                }
            }
        }
    }
}
