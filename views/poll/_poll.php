<?php

use yii\helpers\Html;
use yii\helpers\Url;

$userId = Yii::$app->user->id;
$isGuest = Yii::$app->user->isGuest;
$hasVoted = !$isGuest && $poll->hasUserVoted($userId);
$userVotes = $hasVoted ? $poll->getUserVotes($userId) : [];
$totalVotes = $poll->getTotalVotes();
$allowMultiple = (int)($poll->multiple_votes ?? 0) === 1;
$inputType = $allowMultiple ? 'checkbox' : 'radio';
$postIdValue = (int)($postId ?? 0);
?>

<div class="poll-widget <?= $hasVoted ? 'voted' : '' ?>" data-poll-id="<?= $poll->id ?>">
    <div class="poll-widget-question"><?= Html::encode($poll->question) ?></div>
    
    <div class="poll-widget-options">
        <?php foreach ($poll->options as $option): 
            $votesCount = $option->votes_count ?? $option->getVotesCount();
            $percentage = $totalVotes > 0 ? round(($votesCount / $totalVotes) * 100, 1) : 0;
            $isChecked = in_array($option->id, $userVotes);
        ?>
            <div class="poll-widget-option <?= $isChecked ? 'is-selected' : '' ?>" data-option-id="<?= $option->id ?>">
                <label class="poll-widget-label">
                    <input type="<?= $inputType ?>" 
                        name="poll_<?= $poll->id ?><?= $allowMultiple ? '[]' : '' ?>" 
                        value="<?= $option->id ?>" 
                        <?= $isChecked ? 'checked' : '' ?>
                        <?= $hasVoted ? 'disabled' : '' ?>>
                    <span class="poll-widget-text"><?= Html::encode($option->text) ?></span>
                </label>
                
                <?php if ($hasVoted): ?>
                    <div class="poll-widget-results">
                        <div class="poll-widget-bar" style="width: <?= $percentage ?>%"></div>
                        <span class="poll-widget-percentage"><?= $percentage ?>%</span>
                        <span class="poll-widget-votes"><?= number_format($votesCount) ?> <?= Yii::t('app', 'гол.') ?></span>
                        <?php if ($isChecked): ?>
                            <span class="poll-widget-checked"><?= Yii::t('app', '✓ Ваш голос') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="poll-widget-footer">
        <span class="poll-widget-total">📊 <?= number_format($totalVotes) ?> <?= Yii::t('app', 'гол.') ?></span>
        
        <?php if (!$isGuest): ?>
            <?php if (!$hasVoted): ?>
                <button class="poll-widget-vote-btn" onclick="submitPollVote(<?= $poll->id ?>, <?= $postIdValue ?>)">
                    <?= Yii::t('app', '🗳️ Голосовать') ?>
                </button>
            <?php else: ?>
                <button class="poll-widget-cancel-btn" onclick="cancelPollVote(<?= $poll->id ?>, <?= $postIdValue ?>)">
                    <?= Yii::t('app', '🔄 Отменить') ?>
                </button>
            <?php endif; ?>
        <?php else: ?>
            <div class="poll-widget-login">
                <a href="<?= Url::to(['site/login']) ?>"><?= Yii::t('app', 'Войдите') ?></a><?= Yii::t('app', ', чтобы голосовать') ?>
            </div>
        <?php endif; ?>
    </div>
</div>
