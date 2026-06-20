<?php
/**
 * Модальное окно загрузки истории
 */
?>
<div class="story-upload-modal" id="story-upload-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">📸 <?= Yii::t('app', 'Добавить историю') ?></h3>
            <button class="modal-close" onclick="hideStoryUpload()" aria-label="<?= Yii::t('app', 'Закрыть') ?>">&times;</button>
        </div>
        <div class="modal-body">
            <div class="upload-area" id="upload-area">
                <div class="upload-placeholder">
                    <div class="upload-icon">📸</div>
                    <p><?= Yii::t('app', 'Нажмите для выбора изображения') ?></p>
                    <p class="upload-hint"><?= Yii::t('app', 'или перетащите файл сюда') ?></p>
                </div>
                <input type="file" id="story-image-input" accept="image/*" style="display: none;">
                <img id="preview-image" class="preview-image" style="display: none;">
            </div>
            <div class="story-form" id="story-form" style="display: none;">
                <textarea id="story-caption"
                          class="story-caption-input"
                          placeholder="<?= Yii::t('app', 'Добавьте подпись (необязательно)') ?>"
                          maxlength="200"
                          rows="3"></textarea>
                <div class="story-info">
                    <span class="info-icon">ℹ️</span>
                    <span class="info-text"><?= Yii::t('app', 'История будет доступна 24 часа') ?></span>
                </div>
                <div class="form-actions">
                    <button class="btn-cancel" onclick="hideStoryUpload()"><?= Yii::t('app', 'Отмена') ?></button>
                    <button class="btn-upload" onclick="uploadStory()"><?= Yii::t('app', 'Опубликовать') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* ============================================
   Story Upload Modal
   ============================================ */

.story-upload-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.story-upload-modal.show {
    display: flex;
}

.story-upload-modal .modal-content {
    background: var(--surface-0);
    border-radius: var(--radius-xl);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    transform: translateY(-20px);
    transition: transform var(--transition-normal);
}

.story-upload-modal.show .modal-content {
    transform: translateY(0);
}

.story-upload-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-5);
    border-bottom: 1px solid var(--border-primary);
    background: var(--surface-50);
}

.story-upload-modal .modal-title {
    font-size: var(--text-xl);
    font-weight: var(--font-semibold);
    margin: 0;
}

.story-upload-modal .modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--text-tertiary);
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.story-upload-modal .modal-close:hover {
    background: var(--surface-100);
    color: var(--text-primary);
}

.story-upload-modal .modal-body {
    padding: var(--space-5);
}

/* Upload Area */
.upload-area {
    border: 2px dashed var(--border-secondary);
    border-radius: var(--radius-lg);
    padding: var(--space-10);
    text-align: center;
    background: var(--surface-50);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.upload-area:hover {
    border-color: var(--primary-500);
    background: var(--primary-50);
}

.upload-area.dragover {
    border-color: var(--primary-500);
    background: var(--primary-50);
    transform: scale(1.02);
}

.upload-icon {
    font-size: 48px;
    margin-bottom: var(--space-4);
    opacity: 0.5;
}

.upload-hint {
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

/* Preview Image */
.preview-image {
    max-width: 100%;
    max-height: 300px;
    border-radius: var(--radius-lg);
    margin-top: var(--space-4);
}

/* Story Form */
.story-caption-input {
    width: 100%;
    padding: var(--space-4);
    border: 2px solid var(--border-primary);
    border-radius: var(--radius-lg);
    background: var(--surface-0);
    color: var(--text-primary);
    font-size: var(--text-base);
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    margin-bottom: var(--space-4);
}

.story-caption-input:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.story-caption-input::placeholder {
    color: var(--text-tertiary);
    font-style: italic;
}

/* Story Info */
.story-info {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-3);
    background: var(--surface-50);
    border-radius: var(--radius-lg);
    margin: var(--space-4) 0;
    font-size: var(--text-sm);
    color: var(--text-secondary);
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: var(--space-3);
    justify-content: flex-end;
    margin-top: var(--space-4);
}

.btn-cancel {
    padding: var(--space-2) var(--space-5);
    background: var(--surface-100);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-cancel:hover {
    background: var(--surface-200);
    transform: translateY(-1px);
}

.btn-upload {
    padding: var(--space-2) var(--space-5);
    background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
    color: white;
    border: none;
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.btn-upload:hover {
    background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
    transform: translateY(-1px);
    box-shadow: var(--shadow-sm);
}

/* Responsive */
@media (max-width: 480px) {
    .story-upload-modal .modal-body {
        padding: var(--space-4);
    }
    
    .upload-area {
        padding: var(--space-6);
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-cancel,
    .btn-upload {
        width: 100%;
        justify-content: center;
    }
}
</style>