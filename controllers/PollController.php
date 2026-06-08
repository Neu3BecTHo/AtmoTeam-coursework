<?php

namespace app\controllers;

use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use app\components\ApiValidator;
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

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $data = ApiValidator::getRequestData();
        $pollId = $data['poll_id'] ?? null;
        $optionIds = $data['option_ids'] ?? [];

        if (!$pollId || empty($optionIds)) {
            return ['success' => false, 'error' => 'Неверные данные'];
        }

        $poll = Poll::findOne($pollId);
        if (!$poll) {
            return ['success' => false, 'error' => 'Опрос не найден'];
        }

        $userId = Yii::$app->user->id;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // 1. Получаем старые голоса пользователя
            $oldVotes = PollVote::find()
                ->where(['poll_id' => $pollId, 'user_id' => $userId])
                ->all();

            // 2. Уменьшаем счётчики для старых голосов
            foreach ($oldVotes as $oldVote) {
                $oldOption = PollOption::findOne($oldVote->poll_option_id);
                if ($oldOption) {
                    $oldOption->votes_count--;
                    $oldOption->save(false);
                }
                $oldVote->delete();
            }

            // 3. Добавляем новые голоса и увеличиваем счётчики
            foreach ($optionIds as $optionId) {
                $option = PollOption::findOne($optionId);
                if (!$option || $option->poll_id != $pollId) {
                    throw new \Exception('Неверный вариант ответа');
                }

                // Увеличиваем счётчик голосов варианта
                $option->votes_count++;
                $option->save(false);

                // Создаём запись о голосе
                $vote = new PollVote();
                $vote->poll_id = $pollId;
                $vote->poll_option_id = $optionId;
                $vote->user_id = $userId;
                $vote->created_at = time();
                $vote->save();
            }

            $transaction->commit();

            // 4. Возвращаем обновлённые данные
            return $this->preparePollResponse($poll);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function preparePollResponse($poll)
    {
        $userId = Yii::$app->user->id;
        $totalVotes = PollVote::find()->where(['poll_id' => $poll->id])->count();
        $options = PollOption::find()->where(['poll_id' => $poll->id])->all();
        $userVotes = PollVote::find()
            ->where(['poll_id' => $poll->id, 'user_id' => $userId])
            ->select('poll_option_id')
            ->column();

        $optionsData = [];
        foreach ($options as $option) {
            $optionsData[] = [
                'id' => $option->id,
                'text' => $option->text,
                'votes_count' => $option->votes_count,
                'percentage' => $totalVotes > 0 
                    ? round(($option->votes_count / $totalVotes) * 100, 1) 
                    : 0,
            ];
        }

        return [
            'success' => true,
            'poll' => [
                'id' => $poll->id,
                'question' => $poll->question,
                'multiple_votes' => (bool)$poll->multiple_votes,
                'has_user_voted' => !empty($userVotes),
                'user_votes' => $userVotes,
                'total_votes' => $totalVotes,
                'options' => $optionsData,
            ]
        ];
    }

    public function actionCancelVote()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $authCheck = ApiValidator::requireAuth();
        if ($authCheck !== true) {
            return $authCheck;
        }

        $data = ApiValidator::getRequestData();
        $pollId = $data['poll_id'] ?? null;

        if (!$pollId) {
            return ['success' => false, 'error' => 'Неверные данные'];
        }

        $poll = Poll::findOne($pollId);
        if (!$poll) {
            return ['success' => false, 'error' => 'Опрос не найден'];
        }

        $userId = Yii::$app->user->id;

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Получаем старые голоса пользователя
            $oldVotes = PollVote::find()
                ->where(['poll_id' => $pollId, 'user_id' => $userId])
                ->all();

            // Уменьшаем счётчики для старых голосов
            foreach ($oldVotes as $oldVote) {
                $oldOption = PollOption::findOne($oldVote->poll_option_id);
                if ($oldOption && $oldOption->votes_count > 0) {
                    $oldOption->votes_count--;
                    $oldOption->save(false);
                }
                $oldVote->delete();
            }

            $transaction->commit();

            return $this->preparePollResponse($poll);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
