<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\models\Poll;
use app\models\PollOption;
use app\models\PollVote;
use Yii;

class PollController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'vote' => ['post'],
                ],
            ],
        ];
    }

    public function actionVote()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $data = json_decode(Yii::$app->request->getRawBody(), true);
        $pollId = $data['poll_id'] ?? null;
        $optionIds = $data['option_ids'] ?? [];
        
        if (!$pollId || empty($optionIds)) {
            return ['success' => false, 'error' => 'Неверные данные'];
        }
        
        $poll = Poll::findOne($pollId);
        if (!$poll) {
            return ['success' => false, 'error' => 'Опрос не найден'];
        }
        
        $transaction = Yii::$app->db->beginTransaction();
        
        try {

            PollVote::deleteAll(['poll_id' => $pollId, 'user_id' => Yii::$app->user->id]);

            foreach ($optionIds as $optionId) {
                $option = PollOption::findOne($optionId);
                if (!$option || $option->poll_id != $pollId) {
                    throw new \Exception('Неверный вариант ответа');
                }
                
                $vote = new PollVote();
                $vote->poll_id = $pollId;
                $vote->poll_option_id = $optionId;
                $vote->user_id = Yii::$app->user->id;
                $vote->created_at = time();
                
                if (!$vote->save()) {
                    throw new \Exception('Ошибка сохранения голоса');
                }
            }
            
            $transaction->commit();

            $post = $poll->post;
            $post->updateStats();
            
            return [
                'success' => true,
                'poll' => $poll->toArray([], ['options', 'userVotes']),
                'post' => $post->toArray()
            ];
            
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
