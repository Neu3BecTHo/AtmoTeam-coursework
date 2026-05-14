

function toggleLike(postId) {
    if (!window.currentUserId) {
        showNotification('Войдите, чтобы поставить лайк', 'error');
        return;
    }
    
    const btn = document.getElementById('like-btn');
    const icon = document.getElementById('like-icon');
    const text = document.getElementById('like-text');
    
    fetch('/api/post/like', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ post_id: postId })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            btn.classList.toggle('liked', result.liked);
            if (icon) icon.textContent = result.liked ? '❤️' : '🤍';
            if (text) text.textContent = result.liked ? 'Нравится' : 'Нравится';

            const statsBar = document.querySelector('.post-stats-bar');
            if (statsBar) {
                const likesSpan = statsBar.querySelector('span:first-child');
                if (likesSpan) likesSpan.textContent = `${result.likes_count} лайков`;
            }
        }
    })
    .catch(err => );
}

function focusComment() {
    const textarea = document.getElementById('comment-input');
    if (textarea) {
        textarea.focus();
        textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function sharePost() {
    const url = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: url
        }).catch(() => {});
    } else {

        navigator.clipboard.writeText(url).then(() => {
            if (typeof showNotification === 'function') {
                showNotification('Ссылка скопирована!', 'success');
            } else {
                alert('Ссылка скопирована!');
            }
        }).catch(() => {

            const input = document.createElement('input');
            input.value = url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            document.body.removeChild(input);
            if (typeof showNotification === 'function') {
                showNotification('Ссылка скопирована!', 'success');
            }
        });
    }
}

async function submitComment(postId) {
    const textarea = document.getElementById('comment-input');
    if (!textarea) return;
    
    const content = textarea.value.trim();
    if (!content) return;
    
    try {
        const response = await fetch('/api/comment/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ post_id: postId, content })
        });
        
        const result = await response.json();
        if (result.success) {
            textarea.value = '';

            const list = document.getElementById('comments-list');
            if (list) {
                const noComments = list.querySelector('.no-comments');
                if (noComments) noComments.remove();
                
                const comment = result.comment;
                const avatar = comment.author?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${comment.author?.id || comment.user_id}`;
                const username = comment.author?.username || 'Аноним';
                
                const div = document.createElement('div');
                div.className = 'comment-item';
                div.dataset.commentId = comment.id;
                div.innerHTML = `
                    <img src="${avatar}" class="comment-avatar-full">
                    <div class="comment-body">
                        <div class="comment-header">
                            <a href="/profile/${comment.user_id}" class="comment-author-name">${username}</a>
                            <span class="comment-time">только что</span>
                        </div>
                        <p class="comment-text">${escapeHtml(comment.content)}</p>
                    </div>
                `;
                list.insertBefore(div, list.firstChild);
            }

            const statsBar = document.querySelector('.post-stats-bar');
            if (statsBar) {
                const commentsSpan = statsBar.querySelector('span:nth-child(2)');
                if (commentsSpan) {
                    const current = parseInt(commentsSpan.textContent) || 0;
                    commentsSpan.textContent = `${current + 1} комментариев`;
                }
            }
            
            if (typeof showNotification === 'function') {
                showNotification('Комментарий добавлен!', 'success');
            }
        } else {
            showNotification(result.error || 'Ошибка отправки комментария', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка отправки комментария', 'error');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('comment-input');
    if (textarea) {
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const postId = textarea.closest('.comments-section-full')?.dataset?.postId;
                if (postId) {
                    submitComment(parseInt(postId));
                }
            }
        });
    }
});
