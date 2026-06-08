<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $name;
?>

<div class="error-container">
    <div class="error-content">
        <div class="error-icon"><?= $this->title === '404' ? '🔍' : ($this->title === '403' ? '🚫' : '⚠️') ?></div>
        
        <h1 class="error-code"><?= Html::encode($this->title) ?></h1>
        
        <div class="error-message">
            <?= nl2br(Html::encode($message)) ?>
        </div>
        
        <div class="error-actions">
            <a href="<?= Url::to(['/feed/index']) ?>" class="btn-primary">
                🏠 На главную
            </a>
            <button onclick="history.back()" class="btn-secondary">
                ◀ Назад
            </button>
            <button onclick="location.reload()" class="btn-secondary">
                🔄 Обновить
            </button>
        </div>
        
        <?php if ($this->title === '404'): ?>
            <div class="error-suggestions">
                <p>Возможно, вы искали:</p>
                <div class="suggestion-links">
                    <a href="<?= Url::to(['/feed/index']) ?>">📱 Лента новостей</a>
                    <a href="<?= Url::to(['/search/index']) ?>">🔍 Поиск</a>
                    <a href="<?= Url::to(['/profile/view']) ?>">👤 Мой профиль</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.error-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-6);
}

.error-content {
    text-align: center;
    max-width: 500px;
    margin: 0 auto;
}

.error-icon {
    font-size: var(--text-6xl);
    margin-bottom: var(--space-4);
    opacity: 0.7;
}

.error-code {
    font-size: var(--text-6xl);
    font-weight: var(--font-bold);
    color: var(--error);
    margin-bottom: var(--space-4);
}

.error-message {
    font-size: var(--text-lg);
    color: var(--text-secondary);
    margin-bottom: var(--space-8);
    padding: var(--space-4);
    background: var(--surface-50);
    border-radius: var(--radius-xl);
    border-left: 4px solid var(--error);
    text-align: left;
}

.error-actions {
    display: flex;
    gap: var(--space-3);
    justify-content: center;
    margin-bottom: var(--space-8);
    flex-wrap: wrap;
}

.btn-primary,
.btn-secondary {
    padding: var(--space-3) var(--space-6);
    border-radius: var(--radius-lg);
    font-size: var(--text-base);
    font-weight: var(--font-medium);
    text-decoration: none;
    cursor: pointer;
    transition: all var(--transition-fast);
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
    color: white;
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--surface-100);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
}

.btn-secondary:hover {
    background: var(--surface-200);
    transform: translateY(-2px);
}

.error-suggestions {
    padding-top: var(--space-6);
    border-top: 1px solid var(--border-primary);
}

.error-suggestions p {
    color: var(--text-secondary);
    margin-bottom: var(--space-3);
}

.suggestion-links {
    display: flex;
    gap: var(--space-4);
    justify-content: center;
    flex-wrap: wrap;
}

.suggestion-links a {
    color: var(--primary-600);
    text-decoration: none;
    font-size: var(--text-sm);
    transition: color var(--transition-fast);
}

.suggestion-links a:hover {
    color: var(--primary-700);
    text-decoration: underline;
}

@media (max-width: 768px) {
    .error-code { font-size: var(--text-4xl); }
    .error-message { font-size: var(--text-base); }
    .error-actions { flex-direction: column; }
    .btn-primary, .btn-secondary { justify-content: center; }
}
</style>