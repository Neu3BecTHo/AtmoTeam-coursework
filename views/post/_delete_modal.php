<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">🗑️ Подтверждение удаления</h3>
            <button class="modal-close" onclick="hideDeleteModal()" aria-label="Закрыть">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-icon">⚠️</div>
            <p id="delete-modal-text">Вы уверены, что хотите удалить этот элемент?</p>
            <p class="modal-warning">Это действие нельзя отменить!</p>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary" onclick="hideDeleteModal()">❌ Отмена</button>
            <button class="btn-danger" id="delete-modal-confirm">🗑️ Да, удалить</button>
        </div>
    </div>
</div>

<style>
#delete-modal .modal-content {
    max-width: 400px;
    text-align: center;
}
#delete-modal .modal-icon {
    font-size: 48px;
    margin-bottom: var(--space-3);
}
#delete-modal .modal-warning {
    font-size: var(--text-sm);
    color: var(--error);
    margin-top: var(--space-2);
    font-weight: var(--font-medium);
}
#delete-modal .btn-secondary,
#delete-modal .btn-danger {
    padding: var(--space-2) var(--space-5);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition-fast);
}
#delete-modal .btn-secondary {
    background: var(--surface-100);
    color: var(--text-primary);
    border: 1px solid var(--border-primary);
}
#delete-modal .btn-secondary:hover {
    background: var(--surface-200);
    transform: translateY(-1px);
}
#delete-modal .btn-danger {
    background: var(--error);
    color: white;
    border: none;
}
#delete-modal .btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}
</style>