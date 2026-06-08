<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

/**
 * User model
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string|null $auth_key
 * @property string|null $access_token
 * @property string|null $password_reset_token
 * @property int|null $email_verified_at
 * @property string|null $verification_token
 * @property string|null $avatar
 * @property string|null $bio
 * @property string|null $location
 * @property string|null $website
 * @property int $status
 * @property int $is_blocked
 * @property int $is_private
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public $avatarFile;
    public $newPassword;
    public $password;

    public static function tableName()
    {
        return '{{%user}}';
    }

    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules()
    {
        return [
            [['username', 'email', 'password'], 'required', 'on' => 'register'],
            ['password', 'string', 'min' => 6, 'on' => 'register'],
            ['username', 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/', 'message' => 'Только буквы, цифры и _', 'on' => 'register'],
            ['username', 'string', 'min' => 3, 'max' => 32, 'on' => 'register'],
            ['email', 'email', 'on' => 'register'],
            [['username', 'email'], 'unique', 'on' => 'register'],
            [['access_token'], 'string', 'max' => 255],
            ['access_token', 'unique', 'on' => 'api_update'],
            ['public_key', 'safe'],
            ['key_updated_at', 'safe'],

            ['username', 'string', 'max' => 32, 'on' => 'update'],
            ['email', 'email', 'on' => 'update'],
            [['username', 'email'], 'unique', 'on' => 'update'],
            ['bio', 'string', 'max' => 500, 'on' => 'update'],
            ['location', 'string', 'max' => 100, 'on' => 'update'],
            ['website', 'url', 'on' => 'update'],
            ['website', 'string', 'max' => 255, 'on' => 'update'],
            ['avatarFile', 'file', 'extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'], 'maxSize' => 2 * 1024 * 1024, 'on' => 'update'],
            ['newPassword', 'string', 'min' => 6, 'on' => 'update'],
            ['is_private', 'boolean', 'on' => 'update'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['register'] = ['username', 'email', 'password'];
        $scenarios['update'] = ['username', 'email', 'bio', 'location', 'website', 'avatar', 'is_private', 'newPassword'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Имя пользователя',
            'email' => 'Email',
            'password' => 'Пароль',
            'password_hash' => 'Хеш пароля',
            'auth_key' => 'Ключ авторизации',
            'access_token' => 'Токен доступа',
            'password_reset_token' => 'Токен сброса пароля',
            'email_verified_at' => 'Email подтверждён',
            'verification_token' => 'Токен подтверждения',
            'avatar' => 'Аватар',
            'bio' => 'О себе',
            'location' => 'Местоположение',
            'website' => 'Веб-сайт',
            'status' => 'Статус',
            'is_blocked' => 'Заблокирован',
            'is_private' => 'Приватный профиль',
            'created_at' => 'Создан',
            'updated_at' => 'Обновлён',
            'avatarFile' => 'Файл аватара',
            'newPassword' => 'Новый пароль',
        ];
    }

    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert && $this->scenario === 'register') {
            $this->setPassword($this->password);
        }

        $this->uploadAvatar();

        if ($this->newPassword) {
            $this->setPassword($this->newPassword);
        }

        return true;
    }

    private function uploadAvatar()
    {
        if (!$this->avatarFile instanceof UploadedFile) {
            return;
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/avatars/');
        FileHelper::createDirectory($uploadDir, 0755);

        if ($this->avatar && file_exists(Yii::getAlias('@webroot') . $this->avatar)) {
            @unlink(Yii::getAlias('@webroot') . $this->avatar);
        }

        $filename = 'avatar_' . $this->id . '_' . time() . '.' . $this->avatarFile->extension;
        
        if ($this->avatarFile->saveAs($uploadDir . $filename)) {
            $this->avatar = '/uploads/avatars/' . $filename;
        }

        $this->avatarFile = null;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        Yii::$app->cache->delete("user_stats_{$this->id}");
        
        if (isset($changedAttributes['is_blocked'])) {
            Yii::$app->cache->delete('popular_users');
            Yii::$app->cache->delete('active_users');
        }
    }

    public function afterDelete()
    {
        parent::afterDelete();

        Yii::$app->cache->delete("user_stats_{$this->id}");
        Yii::$app->cache->delete('popular_users');
        Yii::$app->cache->delete('active_users');
    }

    public function getAvatarUrl(): string
    {
        return $this->avatar ?: 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $this->id;
    }

    public function getPosts()
    {
        return $this->hasMany(Post::class, ['user_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    public function getLikes()
    {
        return $this->hasMany(Like::class, ['user_id' => 'id']);
    }

    public function getComments()
    {
        return $this->hasMany(Comment::class, ['user_id' => 'id']);
    }

    public function getFollowers()
    {
        return $this->hasMany(User::class, ['id' => 'follower_id'])
            ->viaTable('follow', ['following_id' => 'id']);
    }

    public function getFollowing()
    {
        return $this->hasMany(User::class, ['id' => 'following_id'])
            ->viaTable('follow', ['follower_id' => 'id']);
    }

    public function isFollowedBy($userId): bool
    {
        return Follow::isFollowing($userId, $this->id);
    }

    public function getFollowStats(): array
    {
        return [
            'following' => Follow::getFollowingCount($this->id),
            'followers' => Follow::getFollowersCount($this->id),
        ];
    }

    public function isPrivate(): bool
    {
        return (bool) $this->is_private;
    }

    public function canViewProfile($viewerId = null): bool
    {
        if (!$this->isPrivate()) {
            return true;
        }
        if (!$viewerId || $this->id === $viewerId) {
            return $viewerId === $this->id;
        }
        return Follow::isFollowing($viewerId, $this->id);
    }

    public function canInteractWith($userId): bool
    {
        if ($this->hasBlocked($userId)) {
            return false;
        }
        $otherUser = static::findOne($userId);
        if ($otherUser && $otherUser->hasBlocked($this->id)) {
            return false;
        }
        return true;
    }

    public function blockUser($blockedUserId)
    {
        return Block::block($this->id, $blockedUserId);
    }

    public function unblockUser($blockedUserId)
    {
        return Block::unblock($this->id, $blockedUserId);
    }

    public function hasBlocked($userId): bool
    {
        return Block::isBlocked($this->id, $userId);
    }

    public function getBlockedUsers($limit = 20, $offset = 0)
    {
        return Block::getBlockedUsers($this->id, $limit, $offset);
    }

    public function getCachedStats()
    {
        return Yii::$app->cache->getOrSet("user_stats_{$this->id}", function () {
            return [
                'posts_count' => Post::find()->where(['user_id' => $this->id])->count(),
                'followers_count' => Follow::getFollowersCount($this->id),
                'following_count' => Follow::getFollowingCount($this->id),
                'likes_received' => Like::find()
                    ->innerJoin('post', 'like.post_id = post.id')
                    ->where(['post.user_id' => $this->id])
                    ->count(),
                'comments_received' => Comment::find()
                    ->innerJoin('post', 'comment.post_id = post.id')
                    ->where(['post.user_id' => $this->id])
                    ->count(),
            ];
        }, 900);
    }

    public static function getPopularUsers($limit = 10)
    {
        return Yii::$app->cache->getOrSet('popular_users', function () use ($limit) {
            return self::find()
                ->select(['user.*', 'COUNT(DISTINCT follow.follower_id) as followers_count'])
                ->leftJoin('follow', 'follow.following_id = user.id')
                ->where(['user.is_blocked' => 0])
                ->groupBy('user.id')
                ->orderBy(['followers_count' => SORT_DESC, 'user.created_at' => SORT_ASC])
                ->limit($limit)
                ->all();
        }, 3600);
    }

    public static function getActiveUsers($limit = 10)
    {
        return Yii::$app->cache->getOrSet('active_users', function () use ($limit) {
            return self::find()
                ->select(['user.*', 'COUNT(DISTINCT post.id) as posts_count'])
                ->leftJoin('post', 'post.user_id = user.id')
                ->where(['user.is_blocked' => 0])
                ->andWhere(['>', 'post.created_at', time() - 86400 * 7])
                ->groupBy('user.id')
                ->having(['>', 'posts_count', 0])
                ->orderBy(['posts_count' => SORT_DESC])
                ->limit($limit)
                ->all();
        }, 1800);
    }

    // IdentityInterface
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString();
        return $this->access_token;
    }

    public static function findByAccessToken($token)
    {
        return static::findOne(['access_token' => $token, 'status' => 10]);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return self::findByAccessToken($token);
    }

    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email]);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return null;
    }

    public function validateAuthKey($authKey)
    {
        return false;
    }

    public function validatePassword($password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function getPassword()
    {
        return $this->password_hash;
    }

    public function getPublicKey(): ?string
    {
        return $this->public_key;
    }

    public function deleteWithContent()
    {
        $transaction = Yii::$app->db->beginTransaction();
        
        try {
            // Удаляем все сообщения пользователя
            $messages = Message::find()
                ->where(['sender_id' => $this->id])
                ->orWhere(['receiver_id' => $this->id])
                ->all();
            foreach ($messages as $message) {
                $message->delete();
            }
            
            // Удаляем все уведомления
            Notification::deleteAll(['user_id' => $this->id]);
            
            // Удаляем все блокировки
            Block::deleteAll(['blocker_id' => $this->id]);
            Block::deleteAll(['blocked_id' => $this->id]);
            
            // Удаляем все истории
            $stories = Story::find()->where(['user_id' => $this->id])->all();
            foreach ($stories as $story) {
                $story->delete();
            }
            
            // Удаляем самого пользователя (триггернет beforeDelete)
            $this->delete();
            
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    public function fields()
    {
        $fields = parent::fields();
        $fields['public_key'] = 'public_key';
        $fields['avatar_url'] = 'avatarUrl';
        return $fields;
    }

    public function beforeDelete()
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        
        // Удаление аватара
        if ($this->avatar) {
            $path = Yii::getAlias('@webroot' . $this->avatar);
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        // Удаление всех постов пользователя (с их изображениями)
        $posts = Post::find()->where(['user_id' => $this->id])->all();
        foreach ($posts as $post) {
            $post->delete();
        }
        
        return true;
    }
}