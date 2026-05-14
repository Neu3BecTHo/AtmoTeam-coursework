<?php
use yii\helpers\Html;
use yii\helpers\Url;

$hasVoted = !Yii::$app->user->isGuest && $poll->hasUserVoted(Yii::$app->user->id);
$userVotes = $hasVoted ? $poll->getUserVotes(Yii::$app->user->id) : [];
$totalVotes = $poll->getTotalVotes();
$allowMultiple = isset($poll->multiple_votes) ? $poll->multiple_votes : false;
$inputType = $allowMultiple ? 'checkbox' : 'radio';
?>

<div class="poll-container <?= $hasVoted ? 'voted' : '' ?>" data-poll-id="<?= $poll->id ?>">
    <div class="poll-question"><?= Html::encode($poll->question) ?></div>
    <div class="poll-options">
        <?php foreach ($poll->options as $option): ?>
            <?php 
            $votesCount = $option->getVotesCount();
            $percentage = $totalVotes > 0 ? round(($votesCount / $totalVotes) * 100, 1) : 0;
            $isChecked = in_array($option->id, $userVotes);
            ?>
            <div class="poll-option" data-option-id="<?= $option->id ?>">
                <label class="poll-option-label">
                    <input type="<?= $inputType ?>" 
                           name="poll_<?= $poll->id ?><?= $allowMultiple ? '[]' : '' ?>" 
                           value="<?= $option->id ?>" 
                           <?= $isChecked ? 'checked' : '' ?>
                           <?= $hasVoted ? 'disabled' : '' ?>>
                    <span class="poll-option-text"><?= Html::encode($option->text) ?></span>
                </label>
                <div class="poll-results" <?= !$hasVoted ? 'style="display: none;"' : '' ?>>
                    <div class="poll-bar" style="width: <?= $percentage ?>%"></div>
                    <span class="poll-percentage"><?= $percentage ?>%</span>
                    <span class="poll-votes"><?= $votesCount ?> голосов</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="poll-footer">
        <span class="poll-total-votes">Всего голосов: <?= $totalVotes ?></span>
        <?php if (!$hasVoted && !Yii::$app->user->isGuest): ?>
            <button class="btn-vote" onclick="submitPollVote(<?= $poll->id ?>, <?= $postId ?? 0 ?>)">Голосовать</button>
        <?php endif; ?>
    </div>
</div>
