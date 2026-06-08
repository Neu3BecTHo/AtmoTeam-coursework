<!-- Generic Modal Component -->
<div id="generic-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="generic-modal-title">⚠️ Уведомление</h3>
            <button class="modal-close" onclick="hideGenericModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon" id="generic-modal-icon"></div>
            <p id="generic-modal-text">Сообщение</p>
            <div id="generic-modal-details" class="modal-details hidden">
                <pre id="generic-modal-details-text"></pre>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" id="generic-modal-cancel" onclick="hideGenericModal()">Закрыть</button>
            <button class="btn-primary" id="generic-modal-confirm" style="display: none;">Подтвердить</button>
            <button class="btn-danger" id="generic-modal-danger" style="display: none;">Удалить</button>
        </div>
    </div>
</div>

<style>
.modal-icon { font-size: 48px; text-align: center; margin-bottom: var(--space-4); }
.modal-details {
    margin-top: var(--space-4);
    padding: var(--space-3);
    background: var(--surface-50);
    border-radius: var(--radius-lg);
    max-height: 200px;
    overflow-y: auto;
    font-size: var(--text-sm);
    border-left: 3px solid var(--border-primary);
}
.modal-details pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: var(--font-mono);
    font-size: var(--text-xs);
    color: var(--text-secondary);
}
.modal-type-error .modal-header { border-bottom-color: var(--error); }
.modal-type-error .modal-icon { color: var(--error); }
.modal-type-success .modal-header { border-bottom-color: var(--success); }
.modal-type-success .modal-icon { color: var(--success); }
.modal-type-warning .modal-header { border-bottom-color: var(--warning); }
.modal-type-warning .modal-icon { color: var(--warning); }
.modal-type-info .modal-header { border-bottom-color: var(--primary-500); }
.modal-type-info .modal-icon { color: var(--primary-500); }
</style>

<script>
let genericModalCallback = null;

function showGenericModal(options = {}) {
    const modal = document.getElementById('generic-modal');
    const title = document.getElementById('generic-modal-title');
    const text = document.getElementById('generic-modal-text');
    const icon = document.getElementById('generic-modal-icon');
    const cancelBtn = document.getElementById('generic-modal-cancel');
    const confirmBtn = document.getElementById('generic-modal-confirm');
    const dangerBtn = document.getElementById('generic-modal-danger');
    const details = document.getElementById('generic-modal-details');
    const detailsText = document.getElementById('generic-modal-details-text');
    
    const {
        title: modalTitle = 'Уведомление',
        message = '',
        type = 'info',
        showCancel = true,
        cancelText = 'Закрыть',
        showConfirm = false,
        confirmText = 'Подтвердить',
        confirmCallback = null,
        showDanger = false,
        dangerText = 'Удалить',
        dangerCallback = null,
        details: modalDetails = null
    } = options;
    
    title.textContent = modalTitle;
    text.textContent = message;
    
    const icons = { error: '❌', success: '✅', warning: '⚠️', info: 'ℹ️', question: '❓' };
    icon.textContent = icons[type] || icons.info;
    
    modal.classList.remove('modal-type-error', 'modal-type-success', 'modal-type-warning', 'modal-type-info', 'modal-type-question');
    modal.classList.add(`modal-type-${type}`);
    
    cancelBtn.style.display = showCancel ? 'inline-flex' : 'none';
    cancelBtn.textContent = cancelText;
    
    confirmBtn.style.display = showConfirm ? 'inline-flex' : 'none';
    confirmBtn.textContent = confirmText;
    if (showConfirm && confirmCallback) {
        genericModalCallback = confirmCallback;
        confirmBtn.onclick = () => { hideGenericModal(); genericModalCallback?.(); };
    }
    
    dangerBtn.style.display = showDanger ? 'inline-flex' : 'none';
    dangerBtn.textContent = dangerText;
    if (showDanger && dangerCallback) {
        genericModalCallback = dangerCallback;
        dangerBtn.onclick = () => { hideGenericModal(); genericModalCallback?.(); };
    }
    
    if (modalDetails) {
        details.classList.remove('hidden');
        detailsText.textContent = typeof modalDetails === 'string' ? modalDetails : JSON.stringify(modalDetails, null, 2);
    } else {
        details.classList.add('hidden');
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function hideGenericModal() {
    const modal = document.getElementById('generic-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('show');
        document.body.style.overflow = '';
        genericModalCallback = null;
    }
}

function showErrorModal(message, details = null) {
    showGenericModal({ title: '❌ Ошибка', message, type: 'error', details });
}
function showSuccessModal(message) {
    showGenericModal({ title: '✅ Успешно', message, type: 'success' });
}
function showWarningModal(message) {
    showGenericModal({ title: '⚠️ Предупреждение', message, type: 'warning' });
}
function showConfirmModal(message, onConfirm, options = {}) {
    showGenericModal({
        title: options.title || '❓ Подтверждение',
        message,
        type: options.type || 'question',
        showCancel: true,
        cancelText: options.cancelText || 'Отмена',
        showConfirm: true,
        confirmText: options.confirmText || 'Подтвердить',
        confirmCallback: onConfirm
    });
}
function showDeleteModal(message, onConfirm) {
    showGenericModal({
        title: '🗑️ Удаление',
        message: message || 'Вы уверены, что хотите удалить этот элемент?',
        type: 'warning',
        showCancel: true,
        cancelText: 'Отмена',
        showDanger: true,
        dangerText: 'Удалить',
        dangerCallback: onConfirm
    });
}

document.addEventListener('click', (e) => {
    const modal = document.getElementById('generic-modal');
    if (modal?.classList.contains('show') && e.target === modal) hideGenericModal();
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') hideGenericModal();
});

window.showGenericModal = showGenericModal;
window.hideGenericModal = hideGenericModal;
window.showErrorModal = showErrorModal;
window.showSuccessModal = showSuccessModal;
window.showWarningModal = showWarningModal;
window.showConfirmModal = showConfirmModal;
window.showDeleteModal = showDeleteModal;
</script>