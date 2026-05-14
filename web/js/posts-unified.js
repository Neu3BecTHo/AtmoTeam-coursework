


let currentUserId = null;


function closePostModal() {
    const modal = document.getElementById('post-modal');
    if (modal) modal.classList.add('hidden');
}

function closeProfilePostModal() {
    const modal = document.getElementById('profile-post-modal');
    if (modal) modal.classList.add('hidden');
}

async function toggleComments(postId) {

    const isProfilePage = window.location.pathname.includes('/profile') || document.getElementById('profile-post-modal');
    const modalId = isProfilePage ? 'profile-post-modal' : 'post-modal';
    const bodyId = isProfilePage ? 'profile-post-modal-body' : 'post-modal-body';
    const commentsListId = isProfilePage ? 'profile-modal-comments-list' : 'modal-comments-list';
    
    const modal = document.getElementById(modalId);
    const body = document.getElementById(bodyId);
    const commentsList = document.getElementById(commentsListId);
    
    if (!modal || !body || !commentsList) {
        
        
        return;
    }
    
    modal.classList.remove('hidden');
    modal.classList.add('show');
    body.innerHTML = '<div class="post-modal-loading"><div class="spinner"><div class="spinner-ring"></div><div class="spinner-ring"></div><div class="spinner-ring"></div></div></div>';
    commentsList.innerHTML = '';
    
    try {
        const postEl = document.querySelector(`[data-post-id="${postId}"]`);
        if (!postEl) {
            body.innerHTML = '<p>Пост не найден</p>';
            return;
        }
        
        const authorEl = postEl.querySelector('.author-name');
        const authorName = authorEl ? authorEl.textContent.trim() : 'Аноним';
        const contentEl = postEl.querySelector('.post-content');
        const content = contentEl ? contentEl.textContent.trim() : '';
        const avatarEl = postEl.querySelector('.author-avatar');
        const avatarSrc = avatarEl ? avatarEl.src : '';
        
        body.innerHTML = `
            <div class="post-modal-header">
                <img src="${avatarSrc}" class="post-modal-avatar" alt="avatar">
                <div>
                    <div class="post-modal-author">${authorName}</div>
                    <div class="post-modal-time"></div>
                </div>
            </div>
            <div class="post-modal-content">${content}</div>
        `;
        
        await loadComments(postId, commentsListId);
    } catch (error) {
        
        body.innerHTML = '<p>Не удалось загрузить комментарии.</p>';
    }
}

async function loadComments(postId, commentsListId = 'modal-comments-list') {
    try {
        const response = await fetch(`/api/comments/${postId}`);
        const comments = await response.json();
        
        const commentsList = document.getElementById(commentsListId);
        if (!commentsList) return;
        
        if (comments.length === 0) {
            commentsList.innerHTML = '<p>Нет комментариев</p>';
        } else {
            commentsList.innerHTML = comments.map(comment => {
                const isAuthor = currentUserId && comment.user_id == currentUserId;
                const editedMark = comment.updated_at && comment.updated_at !== comment.created_at ? ' <span class="edited-mark">(ред.)</span>' : '';

                let authorName = 'Аноним';
                if (comment.author) {
                    if (typeof comment.author === 'string') {
                        authorName = comment.author;
                    } else if (comment.author.username) {
                        authorName = comment.author.username;
                    } else if (comment.author.name) {
                        authorName = comment.author.name;
                    }
                }

                let timeAgo = '';
                if (comment.timeAgo) {
                    timeAgo = comment.timeAgo;
                } else if (comment.time_ago) {
                    timeAgo = comment.time_ago;
                } else if (comment.created_at) {
                    timeAgo = 'только что';
                }
                
                return `
                    <div class="comment" data-comment-id="${comment.id}">
                        <div class="comment-header">
                            <strong>${authorName}</strong>
                            <span class="comment-time">${timeAgo}</span>${editedMark}
                            ${isAuthor ? `
                                <div class="comment-actions">
                                    <button class="btn-edit-comment" onclick="editComment(${comment.id}, ${postId})" title="Редактировать">✏️</button>
                                    <button class="btn-delete-comment" onclick="deleteComment(${comment.id}, ${postId})" title="Удалить">🗑️</button>
                                </div>
                            ` : ''}
                        </div>
                        <div class="comment-text">${escapeHtml(comment.content || '')}</div>
                    </div>
                `;
            }).join('');
        }
    } catch (error) {
        
    }
}

async function sendComment(postId) {
    const postEl = document.querySelector(`[data-post-id="${postId}"]`);
    if (!postEl) return;
    
    const input = postEl.querySelector('.comment-input');
    const content = input.value.trim();
    
    if (!content) return;
    
    if (!currentUserId) {
        showNotification('Войдите, чтобы оставить комментарий', 'error');
        return;
    }
    
    try {
        const response = await postWithCsrf('/api/comment/create', {
            post_id: postId,
            content: content
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            await loadComments(postId);

            const countEl = postEl.querySelector('.comments-count');
            if (countEl) {
                const currentCount = parseInt(countEl.textContent) || 0;
                countEl.textContent = `${currentCount + 1} комментариев`;
            }
        } else {
            showNotification(result.error || 'Ошибка отправки комментария', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка отправки комментария', 'error');
    }
}


async function submitProfileModalComment() {
    const input = document.getElementById('profile-modal-comment-input');
    const content = input.value.trim();
    
    if (!content) return;
    
    if (!currentUserId) {
        showNotification('Войдите, чтобы оставить комментарий', 'error');
        return;
    }

    const modalBody = document.getElementById('profile-post-modal-body');
    const postEl = modalBody.querySelector('.post-modal-content');
    const postId = postEl ? postEl.dataset.postId : null;
    
    if (!postId) {
        showNotification('Ошибка: не удалось определить ID поста', 'error');
        return;
    }
    
    try {
        const response = await postWithCsrf('/api/comment/create', {
            post_id: postId,
            content: content
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';

            await loadComments(postId, 'profile-modal-comments-list');
        } else {
            showNotification(result.error || 'Ошибка отправки комментария', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка отправки комментария', 'error');
    }
}

async function editComment(commentId, postId) {
    const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!commentEl) return;
    
    const textEl = commentEl.querySelector('.comment-text');
    if (!textEl) return;
    
    const currentText = textEl.textContent.trim();
    
    const newInput = document.createElement('textarea');
    newInput.className = 'comment-edit-input';
    newInput.value = currentText;
    newInput.style.cssText = `
        width: 100%;
        min-height: 60px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
        font-family: inherit;
        font-size: 14px;
    `;
    
    textEl.style.display = 'none';
    textEl.parentNode.insertBefore(newInput, textEl.nextSibling);
    newInput.focus();
    
    const saveBtn = document.createElement('button');
    saveBtn.textContent = 'Сохранить';
    saveBtn.className = 'btn-save-comment';
    saveBtn.style.cssText = `
        margin-top: 8px;
        padding: 6px 12px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    `;
    
    const cancelBtn = document.createElement('button');
    cancelBtn.textContent = 'Отмена';
    cancelBtn.className = 'btn-cancel-comment';
    cancelBtn.style.cssText = `
        margin-top: 8px;
        margin-left: 8px;
        padding: 6px 12px;
        background: #6b7280;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    `;
    
    const buttonContainer = document.createElement('div');
    buttonContainer.appendChild(saveBtn);
    buttonContainer.appendChild(cancelBtn);
    newInput.parentNode.insertBefore(buttonContainer, newInput.nextSibling);
    
    const saveEdit = async () => {
        const newText = newInput.value.trim();
        if (!newText) return;
        
        try {
            const response = await postWithCsrf('/api/comment/update', {
                comment_id: commentId,
                content: newText
            });
            
            const result = await response.json();
            
            if (result.success) {
                textEl.textContent = newText;
                textEl.style.display = 'block';
                newInput.remove();
                buttonContainer.remove();

                const header = commentEl.querySelector('.comment-header');
                if (!header.querySelector('.edited-mark')) {
                    const editedMark = document.createElement('span');
                    editedMark.className = 'edited-mark';
                    editedMark.textContent = ' (ред.)';
                    editedMark.style.cssText = 'color: #6b7280; font-size: 12px;';
                    header.appendChild(editedMark);
                }
            } else {
                showNotification(result.error || 'Ошибка редактирования', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка редактирования', 'error');
        }
    };
    
    const cancelEdit = () => {
        textEl.style.display = 'block';
        newInput.remove();
        buttonContainer.remove();
    };
    
    saveBtn.addEventListener('click', saveEdit);
    cancelBtn.addEventListener('click', cancelEdit);
    
    newInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.ctrlKey) {
            saveEdit();
        } else if (e.key === 'Escape') {
            cancelEdit();
        }
    });
}


function initPostForm() {
    const textarea = document.getElementById('post-content');
    const charCount = document.getElementById('char-count');
    const btnPublish = document.getElementById('btn-publish');
    const imageInput = document.getElementById('post-image');
    
    if (!textarea) return;
    
    textarea.addEventListener('input', () => {
        const len = textarea.value.length;
        if (charCount) charCount.textContent = `${len}/2000`;
        if (btnPublish) btnPublish.disabled = len === 0 && !selectedImage;
        if (charCount) charCount.style.color = len > 1900 ? '#ef4444' : '#6b7280';
    });
    
    if (imageInput) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                selectedImage = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.getElementById('image-preview');
                    if (preview) preview.src = e.target.result;
                    const container = document.getElementById('image-preview-container');
                    if (container) container.style.display = 'block';
                };
                reader.readAsDataURL(file);
                if (btnPublish) btnPublish.disabled = false;
            }
        });
    }
    
    if (btnPublish) {
        btnPublish.addEventListener('click', async () => {
            const content = textarea.value.trim();
            if (!content && !selectedImage) return;
            
            try {
                const formData = new FormData();
                formData.append('content', content);
                if (selectedImage) {
                    formData.append('image', selectedImage);
                }
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                if (csrfToken) {
                    formData.append('_csrf', csrfToken);
                }
                
                const response = await fetch('/api/post/create', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    textarea.value = '';
                    if (charCount) charCount.textContent = '0/2000';
                    removeSelectedImage();
                    if (btnPublish) btnPublish.disabled = true;
                    showNotification('Пост опубликован!', 'success');

                    window.location.reload();
                } else {
                    showNotification(result.error || 'Ошибка публикации', 'error');
                }
            } catch (error) {
                
                showNotification('Ошибка сети', 'error');
            }
        });
    }
}

function removeSelectedImage() {
    selectedImage = null;
    const container = document.getElementById('image-preview-container');
    if (container) container.style.display = 'none';
    const imageInput = document.getElementById('post-image');
    if (imageInput) imageInput.value = '';
}


let selectedImage = null;

function initializePosts() {
    currentUserId = window.currentUserId || null;

    document.querySelectorAll('.post-card').forEach(card => {
        const postId = card.dataset.postId;
        if (!postId) return;

        const likeBtn = card.querySelector('.btn-action.btn-like, .btn-like');
        if (likeBtn) {
            likeBtn.addEventListener('click', () => handleLike(postId));
        }
        
        const commentBtn = card.querySelector('.post-action.btn-comment-toggle, .btn-comment-toggle');
        if (commentBtn) {
            commentBtn.addEventListener('click', () => toggleComments(postId));
        }
        
        const saveBtn = card.querySelector('.post-action.btn-save, .btn-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => handleSave(postId));
        }
        
        const repostBtn = card.querySelector('.post-action.btn-repost, .btn-repost');
        if (repostBtn) {
            repostBtn.addEventListener('click', () => toggleRepost(postId));
        }

        const commentForm = card.querySelector('.comment-form');
        if (commentForm) {
            commentForm.addEventListener('submit', (e) => {
                e.preventDefault();
                sendComment(postId);
            });
        }
    });
}


async function toggleFollow(userId) {
    const btn = document.getElementById('follow-btn');
    const isFollowing = btn.classList.contains('following');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    try {
        const response = await postWithCsrf(`/api/user/${action}`, {
            user_id: userId
        });
        
        const result = await response.json();
        
        if (result.success) {
            btn.classList.toggle('following', !isFollowing);
            btn.textContent = isFollowing ? 'Подписаться' : 'Отписаться';
            showNotification(result.message || (isFollowing ? 'Вы отписались' : 'Вы подписались'), 'success');
        } else {
            showNotification(result.error || 'Ошибка', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка', 'error');
    }
}

let blockTargetUserId = null;

function showBlockModal(userId, username) {
    blockTargetUserId = userId;
    const modal = document.getElementById('block-modal');
    const nameEl = document.getElementById('block-modal-username');
    
    if (modal && nameEl) {
        nameEl.textContent = username;
        modal.classList.remove('hidden');
        modal.classList.add('show');
    } else {
        
    }
}


async function viewStory(storyId) {
    try {
        const response = await fetch(`/api/story/view?id=${storyId}`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const html = await response.text();
        
        const modal = document.getElementById('story-view-modal');
        const content = document.getElementById('story-view-content');
        
        if (modal && content) {
            content.innerHTML = html;
            modal.style.display = 'flex';
            modal.classList.remove('hidden');
            modal.classList.add('show');
        }
    } catch (error) {
        
        showNotification('Ошибка загрузки истории', 'error');
    }
}

function hideStoryView() {
    const modal = document.getElementById('story-view-modal');
    const content = document.getElementById('story-view-content');
    
    if (modal && content) {
        modal.style.display = 'none';
        modal.classList.add('hidden');
        modal.classList.remove('show');
        content.innerHTML = '';
    }
}

function closeCommentsModal() {
    const isProfilePage = window.location.pathname.includes('/profile') || document.getElementById('profile-post-modal');
    if (isProfilePage) {
        closeProfilePostModal();
    } else {
        closePostModal();
    }
}

window.toggleComments = toggleComments;
window.editComment = editComment;
window.submitProfileModalComment = submitProfileModalComment;
window.closePostModal = closePostModal;
window.closeProfilePostModal = closeProfilePostModal;
window.toggleFollow = toggleFollow;
window.showBlockModal = showBlockModal;
window.viewStory = viewStory;
window.hideStoryView = hideStoryView;
window.closeCommentsModal = closeCommentsModal;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializePosts();
    });
} else {
    initializePosts();
}
