<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">🗑️ Удалить?</h3>
            <button class="modal-close" onclick="hideDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="delete-modal-text">Вы уверены, что хотите удалить?</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="hideDeleteModal()">Отмена</button>
            <button class="btn-danger" id="delete-modal-confirm">Удалить</button>
        </div>
    </div>
</div>
