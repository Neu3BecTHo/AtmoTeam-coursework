

function submitPollVote(pollId, postId) {
    const pollContainer = document.querySelector(`[data-poll-id="${pollId}"]`);
    const selectedOptions = pollContainer.querySelectorAll('input:checked');
    
    if (selectedOptions.length === 0) {
        showNotification('Выберите вариант ответа', 'error');
        return;
    }
    
    const optionIds = Array.from(selectedOptions).map(input => input.value);
    
    fetch('/poll/vote', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            poll_id: pollId,
            option_ids: optionIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {

            const pollContainer = document.querySelector(`[data-poll-id="${pollId}"]`);
            pollContainer.innerHTML = renderPoll(data.poll);
            
            showNotification('Ваш голос учтен!', 'success');
        } else {
            showNotification(data.error || 'Ошибка голосования', 'error');
        }
    })
    .catch(error => {
        
        showNotification('Ошибка сети', 'error');
    });
}

function renderPoll(poll) {
    const inputType = 'radio';
    const name = `poll_${poll.id}`;
    
    let html = `
        <div class="poll-container" data-poll-id="${poll.id}">
            <div class="poll-question">${poll.question}</div>
            <div class="poll-options">
    `;
    
    poll.options.forEach(option => {
        const isChecked = poll.user_votes.includes(option.id);
        html += `
            <div class="poll-option" data-option-id="${option.id}">
                <label class="poll-option-label">
                    <input type="${inputType}" 
                           name="${name}" 
                           value="${option.id}" 
                           ${isChecked ? 'checked' : ''}
                           ${poll.has_user_voted ? 'disabled' : ''}>
                    <span class="poll-option-text">${option.text}</span>
                </label>
                <div class="poll-results">
                    <div class="poll-bar" style="width: ${option.percentage}%"></div>
                    <span class="poll-percentage">${option.percentage}%</span>
                    <span class="poll-votes">${option.votes_count} голосов</span>
                </div>
            </div>
        `;
    });
    
    html += `
            </div>
            <div class="poll-footer">
                <span class="poll-total-votes">Всего голосов: ${poll.total_votes}</span>
                ${!poll.has_user_voted ? `<button class="btn-vote">Голосовать</button>` : ''}
            </div>
        </div>
    `;
    
    return html;
}

document.addEventListener('DOMContentLoaded', function() {

    loadInitialProfilePosts();
    
    const postCards = document.querySelectorAll('.post-card');
    postCards.forEach(card => {
        const postId = card.dataset.postId;
        const pollContainer = card.querySelector('.post-poll');
        
        if (pollContainer && postId) {

            fetch(`/post/get?id=${postId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.post.poll) {
                        pollContainer.innerHTML = renderPoll(data.post.poll);
                        pollContainer.style.display = 'block';

                        const voteBtn = pollContainer.querySelector('.btn-vote');
                        if (voteBtn) {
                            voteBtn.addEventListener('click', () => submitPollVote(data.post.poll.id, postId));
                        }
                    }
                })
                .catch(error => {});
        }
    });
});

let cropImage = null;
let cropScale = 1;
let cropOffsetX = 0;
let cropOffsetY = 0;
let isDragging = false;
let dragStartX = 0;
let dragStartY = 0;

function openAvatarCropper(input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        cropImage = new Image();
        cropImage.onload = function() {
            cropScale = 1;
            cropOffsetX = 0;
            cropOffsetY = 0;
            renderCropCanvas();
            
            const modal = document.getElementById('avatar-crop-modal');
            if (modal) modal.classList.remove('hidden');
        };
        cropImage.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
    
function closeAvatarCropper() {
    const modal = document.getElementById('avatar-crop-modal');
    if (modal) modal.classList.add('hidden');
    cropImage = null;

    const input = document.getElementById('avatar-input');
    if (input) input.value = '';
}

function renderCropCanvas() {
    const canvas = document.getElementById('crop-canvas');
    if (!canvas || !cropImage) return;
    
    const ctx = canvas.getContext('2d');
    const size = 320;

    ctx.clearRect(0, 0, size, size);

    ctx.fillStyle = '#1a1a2e';
    ctx.fillRect(0, 0, size, size);

    const imgAspect = cropImage.width / cropImage.height;
    const canvasAspect = 1;
    
    let drawWidth, drawHeight;
    if (imgAspect > canvasAspect) {
        drawHeight = size / cropScale;
        drawWidth = drawHeight * imgAspect;
    } else {
        drawWidth = size / cropScale;
        drawHeight = drawWidth / imgAspect;
    }
    
    const centerX = size / 2;
    const centerY = size / 2;

    ctx.drawImage(
        cropImage,
        centerX - drawWidth / 2 + cropOffsetX,
        centerY - drawHeight / 2 + cropOffsetY,
        drawWidth,
        drawHeight
    );

    ctx.beginPath();
    ctx.arc(centerX, centerY, size / 2 - 2, 0, Math.PI * 2);
    ctx.strokeStyle = 'rgba(59, 130, 246, 0.8)';
    ctx.lineWidth = 3;
    ctx.stroke();

    ctx.beginPath();
    ctx.rect(0, 0, size, size);
    ctx.arc(centerX, centerY, size / 2 - 2, 0, Math.PI * 2, true);
    ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    ctx.fill();
}

function applyAvatarCrop() {
    const canvas = document.getElementById('crop-canvas');
    if (!canvas || !cropImage) return;

    const finalCanvas = document.createElement('canvas');
    finalCanvas.width = 400;
    finalCanvas.height = 400;
    const ctx = finalCanvas.getContext('2d');
    
    const size = 400;
    const imgAspect = cropImage.width / cropImage.height;
    const canvasAspect = 1;
    
    let drawWidth, drawHeight;
    if (imgAspect > canvasAspect) {
        drawHeight = size / cropScale;
        drawWidth = drawHeight * imgAspect;
    } else {
        drawWidth = size / cropScale;
        drawHeight = drawWidth / imgAspect;
    }
    
    ctx.drawImage(
        cropImage,
        size / 2 - drawWidth / 2 + cropOffsetX * (size / 320),
        size / 2 - drawHeight / 2 + cropOffsetY * (size / 320),
        drawWidth,
        drawHeight
    );

    ctx.globalCompositeOperation = 'destination-in';
    ctx.beginPath();
    ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
    ctx.fill();
    ctx.globalCompositeOperation = 'source-over';

    const preview = document.getElementById('avatar-preview');
    if (preview) {
        preview.src = finalCanvas.toDataURL('image/png');
        preview.style.border = '2px solid var(--primary-500)';
    }

    const hiddenInput = document.getElementById('cropped-avatar-input');
    if (hiddenInput) {
        hiddenInput.value = finalCanvas.toDataURL('image/png');
    }
    
    closeAvatarCropper();
}

async function likePost(postId, button) {
    if (!window.currentUserId) {
        showNotification('Войдите, чтобы поставить лайк', 'error');
        return;
    }
    
    try {
        const response = await postWithCsrf('/api/post/like', { post_id: postId });
        const result = await response.json();
        
        if (result.success) {

            if (!button) {
                button = document.querySelector(`[data-post-id="${postId}"] .btn-like`);
                if (!button) {

                    button = document.querySelector(`#post-modal .btn-like`);
                }
            }
            
            if (button) {
                const isLiked = button.classList.contains('liked');
                button.classList.toggle('liked', !isLiked);
                
                const likeSpan = button.querySelector('span:last-child');
                if (likeSpan) likeSpan.textContent = result.likes_count;
                
                const heartSpan = button.querySelector('span:first-child');
                if (heartSpan) heartSpan.textContent = result.liked ? '❤️' : '🤍';
            }

            const postEl = document.querySelector(`[data-post-id="${postId}"]`);
            if (postEl) {
                const likesCountEl = postEl.querySelector('.likes-count');
                if (likesCountEl) {
                    likesCountEl.textContent = `${result.likes_count} лайков`;
                }
            }
        }
    } catch (error) {
    }
}

async function toggleComments(postId) {
    const modal = document.getElementById('post-modal');
    const body = document.getElementById('post-modal-body');
    const commentsList = document.getElementById('modal-comments-list');
    
    modal.classList.remove('hidden');
    modal.classList.add('show');
    body.innerHTML = '<div class="post-modal-loading"><div class="spinner"><div class="spinner-ring"></div><div class="spinner-ring"></div><div class="spinner-ring"></div></div></div>';
    commentsList.innerHTML = '';
    
    try {
        const response = await fetch(`/post/modal?id=${postId}`);
        const html = await response.text();
        body.innerHTML = html;

        await loadComments(postId, 'modal-comments-list');

        setTimeout(() => document.getElementById('modal-comment-input')?.focus(), 100);
    } catch (error) {
        showNotification('Ошибка загрузки поста', 'error');
        closePostModal();
    }
}

function closePostModal() {
    const modal = document.getElementById('post-modal');
    if (modal) modal.classList.add('hidden');
}

function closeProfilePostModal() {
    const modal = document.getElementById('post-modal');
    if (modal) modal.classList.add('hidden');
}

async function loadComments(postId, listId = 'modal-comments-list') {
    try {
        const response = await fetch(`/post/comments?id=${postId}`);
        const html = await response.text();
        
        const commentsList = document.getElementById(listId);
        if (!commentsList) return;
        
        commentsList.innerHTML = html;

        commentsList.querySelectorAll('.btn-delete-comment').forEach(btn => {
            btn.addEventListener('click', () => {
                const commentId = btn.dataset.commentId;
                const postId = btn.dataset.postId;
                if (typeof window.deleteComment === 'function') {
                    window.deleteComment(commentId, postId);
                }
            });
        });
        
        commentsList.querySelectorAll('.btn-edit-comment').forEach(btn => {
            btn.addEventListener('click', () => {
                const commentId = btn.dataset.commentId;
                const postId = btn.dataset.postId;
                if (typeof window.editComment === 'function') {
                    window.editComment(commentId, postId);
                }
            });
        });
    } catch (error) {
        const commentsList = document.getElementById(listId);
        if (commentsList) commentsList.innerHTML = '<p>Ошибка загрузки комментариев</p>';
    }
}

function updateModalLike(button, postId) {

    const isLiked = button.classList.contains('liked');
    button.classList.toggle('liked', !isLiked);
}

function updateModalSave(button, postId) {

    const isSaved = button.classList.contains('saved');
    button.classList.toggle('saved', !isSaved);
}

function updateModalRepost(button, postId) {

    const isReposted = button.classList.contains('reposted');
    button.classList.toggle('reposted', !isReposted);
}

async function submitModalComment(postId) {
    const input = document.getElementById('modal-comment-input');
    const content = input.value.trim();
    
    if (!content) {
        showNotification('Напишите комментарий', 'error');
        return;
    }
    
    if (!window.currentUserId) {
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

            await loadComments(postId, 'modal-comments-list');
        } else {
            showNotification(result.error || 'Ошибка отправки комментария', 'error');
        }
    } catch (error) {
        showNotification('Ошибка отправки комментария', 'error');
    }
}

function closePostModal() {
    const modal = document.getElementById('post-modal');
    if (modal) modal.classList.add('hidden');
}

function closeProfilePostModal() {
    const modal = document.getElementById('profile-post-modal');
    if (modal) modal.classList.add('hidden');
}

async function editComment(commentId, postId) {
    const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!commentEl) return;
    
    const textEl = commentEl.querySelector('.comment-text');
    if (!textEl) return;
    
    const currentText = textEl.textContent.trim();
    
    const editForm = document.createElement('div');
    editForm.className = 'comment-edit-form';
    editForm.innerHTML = `
        <input type="text" class="comment-edit-input" value="${currentText.replace(/"/g, '&quot;')}" maxlength="1000">
        <div class="comment-edit-actions">
            <button class="btn-save-edit">Сохранить</button>
            <button class="btn-cancel-edit">Отмена</button>
        </div>
    `;
    
    textEl.style.display = 'none';
    textEl.parentNode.insertBefore(editForm, textEl.nextSibling);
    
    const input = editForm.querySelector('.comment-edit-input');
    input.focus();
    
    editForm.querySelector('.btn-save-edit').addEventListener('click', async () => {
        const newText = input.value.trim();
        if (!newText) {
            showNotification('Комментарий не может быть пустым', 'error');
            return;
        }
        
        try {
            const response = await postWithCsrf('/api/comment/edit', {
                comment_id: commentId,
                content: newText
            });
            
            const result = await response.json();
            
            if (result.success) {
                textEl.textContent = newText + ' <span class="edited-mark">(ред.)</span>';
                textEl.style.display = 'block';
                editForm.remove();
            } else {
                showNotification(result.error || 'Ошибка редактирования комментария', 'error');
            }
        } catch (error) {
            showNotification('Ошибка редактирования комментария', 'error');
        }
    });
    
    editForm.querySelector('.btn-cancel-edit').addEventListener('click', () => {
        textEl.style.display = 'block';
        editForm.remove();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            editForm.querySelector('.btn-save-edit').click();
        } else if (e.key === 'Escape') {
            editForm.querySelector('.btn-cancel-edit').click();
        }
    });
}

window.handleLike = likePost;
window.toggleComments = toggleComments;

document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('crop-canvas');
    if (canvas) {
        canvas.addEventListener('mousedown', (e) => {
            isDragging = true;
            dragStartX = e.clientX - cropOffsetX;
            dragStartY = e.clientY - cropOffsetY;
        });
        
        window.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            cropOffsetX = e.clientX - dragStartX;
            cropOffsetY = e.clientY - dragStartY;
            renderCropCanvas();
        });
        
        window.addEventListener('mouseup', () => {
            isDragging = false;
        });
    }

    const scaleInput = document.getElementById('crop-scale');
    if (scaleInput) {
        scaleInput.addEventListener('input', (e) => {
            cropScale = parseFloat(e.target.value);
            renderCropCanvas();
        });
    }
});

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            if (preview) {
                preview.src = e.target.result;
                preview.style.border = '2px solid var(--primary-500)';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab + '-tab').style.display = 'block';
    });
});

async function loadInitialProfilePosts() {
    const userId = window.profileUserId;
    
    if (!userId) {
        return;
    }
    
    const grid = document.getElementById('user-posts');
    
    if (!grid) {
        return;
    }
    
    try {
        const response = await fetch(`/profile/${userId}/posts?offset=0`);
        const result = await response.json();

        if (result.html !== undefined) {
            if (result.count === 0) {

                grid.innerHTML = '<div class="empty-profile"><div class="empty-icon">📝</div><p>Пока нет постов</p></div>';
                return;
            } else {

                grid.innerHTML = result.html;
            }
        } else {

            if (result.length === 0) {
                grid.innerHTML = '<div class="empty-profile"><div class="empty-icon">📝</div><p>Пока нет постов</p></div>';
                return;
            }
            
            result.forEach(post => {
                const card = document.createElement('div');
                card.className = 'post-card';
                card.dataset.postId = post.id;
                card.innerHTML = `
                    <div class="post-header">
                        <div class="post-author">
                            <img src="${post.user.avatar_url || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + post.user.username}" alt="avatar" class="author-avatar">
                            <div class="author-info">
                                <span class="author-name">${post.user.username}</span>
                                <span class="post-time">${post.time_ago || 'Только что'}</span>
                            </div>
                        </div>
                        ${post.is_owner ? `<button class="btn-delete-post" onclick="deletePost(${post.id})">🗑️</button>` : ''}
                    </div>
                    <div class="post-content">${post.content}</div>
                    ${post.image_url ? `<img src="${post.image_url}" alt="post image" class="post-image">` : ''}
                    <div class="post-actions">
                        <button class="btn-action ${post.is_liked ? 'liked' : ''}" onclick="handleLike(${post.id})">
                            <span>${post.is_liked ? '❤️' : '🤍'}</span>
                            <span>${post.likes_count || 0}</span>
                        </button>
                        <button class="btn-action" onclick="toggleComments(${post.id})">
                            <span>💬</span>
                            <span>${post.comments_count || 0}</span>
                        </button>
                        <button class="btn-action" onclick="toggleRepost(${post.id})">
                            <span>🔄</span>
                            <span>${post.reposts_count || 0}</span>
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        if (loadMoreSentinel) {
            const postCount = result.html !== undefined ? result.count : result.length;
            loadMoreSentinel.dataset.offset = postCount;
        }

        initializePosts();
        
    } catch (error) {
    }
}

const loadMoreSentinel = document.getElementById('load-more-sentinel');
const loadMoreSpinner = document.getElementById('load-more-spinner');

if (loadMoreSentinel && 'IntersectionObserver' in window) {
    let isLoading = false;
    
    const observer = new IntersectionObserver(async (entries) => {
        const entry = entries[0];
        if (!entry.isIntersecting || isLoading) return;
        
        const offset = loadMoreSentinel.dataset.offset;
        const userId = window.profileUserId;
        if (!userId) return;
        
        isLoading = true;
        if (loadMoreSpinner) loadMoreSpinner.classList.remove('hidden');
        
        try {
            const response = await fetch(`/profile/${userId}/posts?offset=${offset}`);
            const result = await response.json();

            if (result.html !== undefined) {
                if (result.count === 0) {
                    observer.disconnect();
                    if (loadMoreSpinner) loadMoreSpinner.classList.add('hidden');
                    return;
                } else {

                    const grid = document.getElementById('user-posts');
                    grid.innerHTML += result.html;

                    const newOffset = parseInt(offset) + result.count;
                    loadMoreSentinel.dataset.offset = newOffset;
                }
            } else {

                if (result.length === 0) {
                    observer.disconnect();
                    if (loadMoreSpinner) loadMoreSpinner.classList.add('hidden');
                    return;
                }
                
                const grid = document.getElementById('user-posts');
                result.forEach(post => {
                    const card = document.createElement('div');
                    card.className = 'profile-post-card';
                    card.dataset.postId = post.id;
                    const isLiked = post.is_liked;
                    card.innerHTML = `
                        <div class="post-header-small">
                            <div class="post-author-small">
                                <img src="${post.author?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${post.author?.id||1}`}" alt="avatar" class="author-avatar-small">
                                <div class="author-info-small">
                                    <span class="author-name-small">${post.author?.username || 'Аноним'}</span>
                                    <span class="post-time-small">${post.timeAgo || ''}</span>
                                </div>
                            </div>
                            ${window.currentUserId == post.user_id ? `<button class="btn-delete-post" onclick="deletePost(${post.id})" title="Удалить">🗑️</button>` : ''}
                        </div>
                        <a href="/post/view?id=${post.id}" class="post-content-link">
                            <p class="post-content-preview">${post.content || ''}</p>
                        </a>
                        <div class="post-actions-small">
                            <button class="btn-action-small ${isLiked ? 'liked' : ''}" onclick="likePost(${post.id}, this)">
                                <span>${isLiked ? '❤️' : '🤍'}</span>
                                <span>${post.likes_count || 0}</span>
                            </button>
                            <a href="/post/view?id=${post.id}" class="btn-action-small">
                                <span>💬</span>
                                <span>${post.comments_count || 0}</span>
                            </a>
                        </div>
                    `;
                    grid.appendChild(card);
                });

                const newOffset = parseInt(offset) + result.length;
                loadMoreSentinel.dataset.offset = newOffset;
            }

            initializePosts();
        } catch (error) {
        } finally {
            isLoading = false;
            if (loadMoreSpinner) loadMoreSpinner.classList.add('hidden');
        }
    }, { rootMargin: '100px' });
    
    observer.observe(loadMoreSentinel);
}

async function toggleFollow(userId) {
    const btn = document.getElementById('follow-btn');
    const isFollowing = btn.classList.contains('following');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    try {
        const response = await fetch(`/profile/${userId}/${action}`);
        const result = await response.json();
        
        if (result.success) {
            btn.classList.toggle('following');
            btn.textContent = result.following ? 'Отписаться' : 'Подписаться';

            const followersCount = document.getElementById('followers-count');
            if (followersCount) {
                followersCount.textContent = result.followers_count;
            }
        } else {
            showNotification(result.error || 'Ошибка', 'error');
        }
    } catch (error) {
        
    }
}

async function toggleFollowUser(btn, userId) {
    const isFollowing = btn.classList.contains('following');
    const action = isFollowing ? 'unfollow' : 'follow';
    
    try {
        const response = await fetch(`/profile/${userId}/${action}`);
        const result = await response.json();
        
        if (result.success) {
            btn.classList.toggle('following');
            btn.textContent = result.following ? 'Отписаться' : 'Подписаться';
        } else {
            showNotification(result.error || 'Ошибка', 'error');
        }
    } catch (error) {
        
    }
}

function confirmDelete() {
    if (typeof window.showDeleteModal === 'function') {
        window.showDeleteModal('Вы уверены, что хотите удалить аккаунт? Это действие нельзя отменить!', async () => {
            showNotification('Функция удаления в разработке', 'info');
        });
    } else {
        showNotification('Ошибка: функция модального окна не доступна', 'error');
    }
}



function showBlockModal(userId, username) {
    blockTargetUserId = userId;
    const modal = document.getElementById('block-modal');
    const nameEl = document.getElementById('block-modal-username');
    const confirmBtn = document.getElementById('block-confirm-btn');
    
    if (nameEl) {
        nameEl.textContent = username;
    }
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
    }
    
    if (confirmBtn) {
        confirmBtn.onclick = () => {
            if (blockTargetUserId) {
                doBlockUser(blockTargetUserId);
            }
        };
    } else {
        
    }
}
    
function hideBlockModal() {
    const modal = document.getElementById('block-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('show');
    }
    blockTargetUserId = null;
}

document.addEventListener('click', (e) => {
    const modal = document.getElementById('block-modal');
    if (modal && e.target === modal) {
        hideBlockModal();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hideBlockModal();
    }
});

async function doBlockUser(userId) {
    hideBlockModal();
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'Content-Type': 'application/json' };
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
        
        const response = await fetch('/api/profile/block', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Пользователь заблокирован', 'success');
            updateBlockButton(userId, true);
        } else {
            showNotification(result.error || 'Ошибка блокировки', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка блокировки', 'error');
    }
}

async function unblockUser(userId) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'Content-Type': 'application/json' };
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
        
        const response = await fetch('/api/profile/unblock', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ user_id: userId })
        });
        
        const result = await response.json();
        if (result.success) {
            showNotification('Пользователь разблокирован', 'success');
            updateBlockButton(userId, false);
        } else {
            showNotification(result.error || 'Ошибка разблокировки', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка разблокировки', 'error');
    }
}

function updateBlockButton(userId, isBlocked) {
    const btn = document.getElementById('block-btn');
    if (!btn) return;
    
    const username = btn.dataset.username || '';
    
    if (isBlocked) {
        btn.textContent = '✅ Разблокировать';
        btn.onclick = () => unblockUser(userId);
    } else {
        btn.textContent = '🚫 Заблокировать';
        btn.onclick = () => showBlockModal(userId, username);
    }
}
