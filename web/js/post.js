// ==================== Single Post Page ====================
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('comment-input');
    const charCounter = document.querySelector('.comment-char-counter');
    
    if (textarea && charCounter) {
        function updateCharCount() {
            const length = textarea.value.length;
            charCounter.textContent = length + '/1000';
            charCounter.style.color = length > 900 ? '#ef4444' : length > 800 ? '#f59e0b' : 'inherit';
        }
        textarea.addEventListener('input', updateCharCount);
        updateCharCount();
        
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const postId = window.postId || document.querySelector('[data-post-id]')?.dataset.postId;
                if (postId && typeof submitComment === 'function') submitComment(postId);
            }
        });
    }
});

// ==================== Share ====================
function sharePost() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({ title: document.title, url }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(() => {
            showNotification('Ссылка скопирована!', 'success');
        }).catch(() => {
            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            showNotification('Ссылка скопирована!', 'success');
        });
    }
}

// ==================== Focus Comment ====================
function focusComment() {
    const ta = document.getElementById('comment-input');
    if (ta) {
        ta.focus();
        ta.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

// ==================== Submit Comment (page-specific) ====================
async function submitComment(postId) {
    const ta = document.getElementById('comment-input');
    const content = ta?.value.trim();
    if (!content) {
        showNotification('Напишите комментарий', 'error');
        return;
    }
    if (!window.currentUserId) {
        showNotification('Войдите, чтобы оставить комментарий', 'error');
        return;
    }

    try {
        const res = await postWithCsrf('/api/comment/create', { post_id: postId, content });
        const data = await res.json();
        if (data.success) {
            ta.value = '';
            const charCounter = document.querySelector('.comment-char-counter');
            if (charCounter) charCounter.textContent = '0/1000';
            location.reload();
        } else {
            showNotification(data.error || 'Ошибка', 'error');
        }
    } catch (error) {
        showNotification('Ошибка сети', 'error');
    }
}

// ==================== Exports ====================
window.sharePost = sharePost;
window.focusComment = focusComment;
window.submitComment = submitComment;