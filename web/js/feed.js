

let posts = [];
let currentFeedType = 'following';
let pollInterval = null;
let feedOffset = 0;
let isLoadingFeed = false;
let hasMorePosts = true;

function setupLazyLoading() {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                const src = img.dataset.src;
                
                if (src && !img.src) {
                    img.src = src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            }
        });
    }, {
        rootMargin: '50px 0px',
        threshold: 0.01
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

function createLazyImage(src, alt, className = '') {
    const img = document.createElement('img');
    img.dataset.src = src;
    img.alt = alt || '';
    img.className = `lazy-image ${className}`;

    img.style.cssText = `
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        min-height: 200px;
        transition: opacity 0.3s ease;
    `;
    
    return img;
}

function createOptimizedLazyImage(src, alt, className = '') {
    const img = document.createElement('img');

    if (typeof getOptimizedImageUrl === 'function') {
        img.dataset.src = getOptimizedImageUrl(src);
    } else {
        img.dataset.src = src;
    }
    
    img.alt = alt || '';
    img.className = `lazy-image optimized-image ${className}`;

    img.style.cssText = `
        background: #f3f4f6;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        min-height: 200px;
        transition: opacity 0.3s ease;
    `;
    
    return img;
}

function startPolling() {
    if (pollInterval) return;

    const isMobile = window.innerWidth < 768;
    const pollIntervalMs = isMobile ? 10000 : 3000; // 10с на мобильных, 3с на десктопе
    
    pollInterval = setInterval(async () => {
        if (document.visibilityState === 'hidden') return;
        
        try {
            const response = await fetch(`/api/poll?last_check=${lastCheck}`);
            const data = await response.json();
            
            lastCheck = data.timestamp;

            if (data.success && data.posts && data.posts.length > 0) {
                data.posts.forEach(post => {
                    if (!posts.find(p => p.id === post.id)) {
                        addPostToFeed(post, true);
                        posts.unshift(post);
                    }
                });
            }

            if (data.likes) {
                data.likes.forEach(like => updatePostLikes(like));
            }

            if (data.comments && data.comments.length > 0) {
                data.comments.forEach(comment => addCommentToPost(comment));
            }
        } catch (error) {
            
        }
    }, pollIntervalMs);

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            lastCheck = 0;
        }
    });
}

function switchFeed(type) {
    currentFeedType = type;
    feedOffset = 0;
    hasMorePosts = true;
    
    document.querySelectorAll('.feed-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.type === type);
    });
    
    posts = [];
    document.getElementById('posts-container').innerHTML = '';
    loadPosts();
}

async function loadPosts(append = false) {
    if (isLoadingFeed || !hasMorePosts) {
        return;
    }
    
    try {
        isLoadingFeed = true;
        const spinner = document.getElementById('feed-spinner');
        if (spinner) spinner.classList.remove('hidden');

        if (!currentUserId) {
            showEmptyStateForGuest();
            return;
        }
        
        const response = await fetch(`/feed/get-posts?type=${currentFeedType}&offset=${feedOffset}&limit=3`);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const result = await response.json();

        if (result.html !== undefined) {
            if (result.count === 0) {
                hasMorePosts = false;

                const existingPosts = document.querySelectorAll('.post-card');
                if (posts.length === 0 && existingPosts.length === 0) showEmptyState();
            } else {
                if (append) {

                    const container = document.getElementById('posts-container');
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = result.html;
                    
                    while (tempDiv.firstChild) {
                        container.appendChild(tempDiv.firstChild);
                    }
                    
                    initializePosts();

                    feedOffset += result.count;
                } else {

                    posts = [result.html]; // Сохраняем HTML для совместимости
                    renderPosts();

                    feedOffset = result.count;
                }
            }
        } 

        else if (Array.isArray(result)) {
            if (result.length === 0) {
                hasMorePosts = false;

                const existingPosts = document.querySelectorAll('.post-card');
                if (posts.length === 0 && existingPosts.length === 0) showEmptyState();
            } else {
                if (append) {
                    posts.push(...result);

                    feedOffset += result.length;
                } else {
                    posts = result;

                    feedOffset = result.length;
                }
                renderPosts();
            }
        } else {
            
            if (!append) showEmptyState();
        }
    } catch (error) {
        
        if (!append) showEmptyState();
    } finally {
        isLoadingFeed = false;
        const spinner = document.getElementById('feed-spinner');
        if (spinner) spinner.classList.add('hidden');
    }
}

function renderPosts() {
    const container = document.getElementById('posts-container');

    const existingPosts = container.querySelectorAll('.post-card');
    if (existingPosts.length === 0) {
        container.innerHTML = '';
    }
    
    if (posts.length === 0 && existingPosts.length === 0) {
        showEmptyState();
        return;
    }

    if (typeof posts[0] === 'string') {
        container.innerHTML = posts.join('');
        initializePosts();
    } else {

        posts.forEach(post => {
            appendPostToContainer(post, false);
        });
    }
}

function showEmptyState() {
    const container = document.getElementById('posts-container');

    const existingPosts = container.querySelectorAll('.post-card');
    if (existingPosts.length > 0) {
        return; // Не перезаписываем существующие посты
    }
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">📝</div>
            <p>Нет постов</p>
        </div>
    `;
}

function initializePostHandlers() {

    document.querySelectorAll('.post-card').forEach(postCard => {
        const postId = postCard.dataset.postId;

        const likeBtn = postCard.querySelector('.btn-like');
        if (likeBtn) {
            likeBtn.addEventListener('click', () => handleLike(postId));
        }


        const saveBtn = postCard.querySelector('.btn-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => handleSave(postId));
        }

        const repostBtn = postCard.querySelector('.btn-repost');
        if (repostBtn) {
            repostBtn.addEventListener('click', () => toggleRepost(postId));
        }

        const pollContainer = postCard.querySelector('.poll-container');
        if (pollContainer) {
            const pollId = pollContainer.dataset.pollId;
            const voteBtn = pollContainer.querySelector('.btn-vote');
            if (voteBtn) {
                voteBtn.addEventListener('click', () => submitPollVote(pollId, postId));
            }
        }

        const deleteBtn = postCard.querySelector('.btn-delete-post');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                if (typeof window.deletePost === 'function') {
                    window.deletePost(postId);
                }
            });
        }
    });
}
    
function showEmptyStateForGuest() {
    const container = document.getElementById('posts-container');
    container.innerHTML = `
        <div class="empty-state">
            <div class="empty-icon">🔒</div>
            <h3>Добро пожаловать в Social!</h3>
            <p>Войдите или зарегистрируйтесь, чтобы увидеть посты</p>
            <div class="auth-buttons">
                <a href="/login" class="btn btn-primary">Войти</a>
                <a href="/register" class="btn btn-secondary">Регистрация</a>
            </div>
        </div>
    `;
}

function createPostHTML(post) {
    const template = document.getElementById('post-template');
    const clone = template.content.cloneNode(true);
    
    const postCard = clone.querySelector('.post-card');
    const authorAvatar = clone.querySelector('.author-avatar');
    const authorName = clone.querySelector('.author-name');
    const postTime = clone.querySelector('.post-time');
    const postContent = clone.querySelector('.post-content');
    const postImage = clone.querySelector('.post-image img');
    const likesBtn = clone.querySelector('.post-action.btn-like');
    const commentBtn = clone.querySelector('.post-action.btn-comment-toggle');
    const saveBtn = clone.querySelector('.post-action.btn-save');
    const repostBtn = clone.querySelector('.post-action.btn-repost');
    const likesCount = clone.querySelector('.likes-count');
    const commentsCount = clone.querySelector('.comments-count');
    const repostsCount = clone.querySelector('.reposts-count');
    const deleteBtn = clone.querySelector('.btn-delete-post');
    const postImageImg = postImage.querySelector('img');
    if (post.image || post.image_url) {
        postImageImg.src = post.image_url || post.image;
        postImage.style.display = 'block';
    } else {
        postImage.style.display = 'none';
    }

    const postPoll = clone.querySelector('.post-poll');
    if (post.poll) {
        const pollHTML = renderPoll(post.poll);
        postPoll.innerHTML = pollHTML;
        postPoll.style.display = 'block';

        const voteBtn = postPoll.querySelector('.btn-vote');
        if (voteBtn) {
            voteBtn.addEventListener('click', () => submitPollVote(post.poll.id, post.id));
        }
    }
    const likeBtn = clone.querySelector('.post-action.btn-like');
    likeBtn.dataset.postId = post.id;
    if (post.is_liked) {
        likeBtn.classList.add('liked');
    }
    likeBtn.addEventListener('click', () => handleLike(post.id));

    
    const saveActionBtn = clone.querySelector('.post-action.btn-save');
    saveActionBtn.dataset.postId = post.id;
    if (post.is_saved) {
        saveActionBtn.classList.add('saved');
    }
    saveActionBtn.addEventListener('click', () => handleSave(post.id));
    
    const repostActionBtn = clone.querySelector('.post-action.btn-repost');
    repostActionBtn.dataset.postId = post.id;
    if (post.is_reposted) {
        repostActionBtn.classList.add('reposted');
    }
    repostActionBtn.addEventListener('click', () => toggleRepost(post.id));
    
    if (prepend) {
        const emptyState = container.querySelector('.empty-state');
        if (emptyState) emptyState.remove();
        container.insertBefore(clone, container.firstChild);
        
        const postEl = container.firstChild;
        postEl.style.animation = 'slideDown 0.3s ease';
    } else {
        container.appendChild(clone);
    }
}

async function appendPostToContainer(post, prepend = false) {
    try {

        const response = await fetch(`/post/get-html?id=${post.id}`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const html = await response.text();
        
        const container = document.getElementById('posts-container');
        if (prepend) {
            container.insertAdjacentHTML('afterbegin', html);
        } else {
            container.insertAdjacentHTML('beforeend', html);
        }

        initializePosts();
        
    } catch (error) {

        window.location.reload();
    }
}

function updatePostStats(postEl, post) {
    const likesEl = postEl.querySelector('.likes-count');
    const commentsEl = postEl.querySelector('.comments-count');
    const repostsEl = postEl.querySelector('.reposts-count');
    
    likesEl.textContent = `${post.likes_count || 0} лайков`;
    commentsEl.textContent = `${post.comments_count || 0} комментариев`;
    if (repostsEl) {
        repostsEl.textContent = `${post.reposts_count || 0} репостов`;
    }
}

function updatePostLikes(data) {
    const postEl = document.querySelector(`[data-post-id="${data.post_id}"]`);
    if (!postEl) return;
    
    const likeBtn = postEl.querySelector('.btn-like');
    if (!likeBtn) return;
    const isLiked = likeBtn.classList.contains('liked');
    
    if (data.user_id === currentUserId) {
        if (data.action === 'like' && !isLiked) {
            likeBtn.classList.add('liked');
        } else if (data.action === 'unlike' && isLiked) {
            likeBtn.classList.remove('liked');
        }
    }
    
    fetch(`/feed/get-posts`).then(r => r.json()).then(result => {
        const posts = result.html ? [] : result; // Handle HTML format
        const post = posts.find(p => p.id == data.post_id);
        if (post) {
            const likesEl = postEl.querySelector('.likes-count');
            if (likesEl) likesEl.textContent = `${post.likes_count} лайков`;
        }
    });
}

function toggleComments(postId) {
    openPostModal(postId);
}

let currentModalPostId = null;

async function openPostModal(postId) {
    currentModalPostId = postId;
    const modal = document.getElementById('post-modal');
    const body = document.getElementById('post-modal-body');
    const commentsList = document.getElementById('modal-comments-list');
    
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

        const post = {
            id: postId,
            author: {
                username: authorName,
                avatar: avatarSrc
            },
            content: content
        };
        
        body.innerHTML = `
            <div class="post-modal-header">
                <img src="${post.author?.avatar || `https://api.dicebear.com/7.x/avataaars/svg?seed=${post.author?.id||1}`}" class="post-modal-avatar" alt="avatar">
                <div>
                    <div class="post-modal-author">${post.author?.username || 'Аноним'}</div>
                    <div class="post-modal-time">${post.timeAgo || ''}</div>
                </div>
            </div>
            <div class="post-modal-text">${escapeHtml(post.content || '')}</div>
            ${post.image_url ? `<img src="${post.image_url}" class="post-modal-image" alt="Post image">` : ''}
            <div class="post-modal-comments">
                <div class="post-modal-comments-header">Комментарии</div>
                <div id="modal-comments-list"></div>
                <div class="post-modal-comment-form">
                    <textarea id="modal-comment-input" placeholder="Написать комментарий..." rows="2"></textarea>
                    <button onclick="submitModalComment(${post.id})">Отправить</button>
                </div>
            </div>
        `;

        await loadModalComments(postId);

        setTimeout(() => document.getElementById('modal-comment-input')?.focus(), 100);
        
    } catch (error) {
        
        body.innerHTML = '<p>Ошибка загрузки</p>';
    }
}

function closePostModal() {
    const modal = document.getElementById('post-modal');
    modal.classList.remove('show');
    modal.classList.add('hidden');
    currentModalPostId = null;
}

async function loadModalComments(postId) {
    try {
        const response = await fetch(`/post/comments?id=${postId}`);
        const html = await response.text();
        
        const commentsList = document.getElementById('modal-comments-list');
        if (commentsList) {
            commentsList.innerHTML = html;

            commentsList.querySelectorAll('.btn-delete-comment, .btn-edit-comment').forEach(btn => {
                btn.replaceWith(btn.cloneNode(true));
            });

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
        }
    } catch (error) {
        
    }
}

async function submitModalComment(postId) {
    const input = document.getElementById('modal-comment-input');
    const content = input.value.trim();
    
    if (!content) {
        showNotification('Напишите комментарий', 'error');
        return;
    }
    
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
            await loadModalComments(postId);

            const postEl = document.querySelector(`[data-post-id="${postId}"]`);
            if (postEl) {
                const commentsEl = postEl.querySelector('.comments-count');
                if (commentsEl) {
                    const currentCount = parseInt(commentsEl.textContent) || 0;
                    commentsEl.textContent = `${currentCount + 1} комментариев`;
                }
            }
        } else {
            showNotification(result.error || 'Ошибка отправки комментария', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка сети', 'error');
    }
}

async function sendModalComment() {
    const input = document.getElementById('modal-comment-input');
    const content = input.value.trim();
    if (!content || !currentModalPostId) return;
    
    try {
        const response = await postWithCsrf('/api/comment/create', { post_id: currentModalPostId, content });
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            await loadModalComments(currentModalPostId);

            const postEl = document.querySelector(`[data-post-id="${currentModalPostId}"]`);
            if (postEl) {
                const countEl = postEl.querySelector('.comments-count');
                if (countEl) {
                    const current = parseInt(countEl.textContent) || 0;
                    countEl.textContent = `${current + 1} комментариев`;
                }
            }
        } else {
            alert(result.error || 'Ошибка');
        }
    } catch (error) {
        
    }
}

function updateModalLike(btn, postId) {
    btn.classList.toggle('liked');
    const postEl = document.querySelector(`[data-post-id="${postId}"]`);
    if (postEl) {
        const likeBtn = postEl.querySelector('.btn-like');
        if (likeBtn) likeBtn.classList.toggle('liked');
    }
}

function updateModalSave(btn, postId) {
    btn.classList.toggle('saved');
    const postEl = document.querySelector(`[data-post-id="${postId}"]`);
    if (postEl) {
        const saveBtn = postEl.querySelector('.btn-save');
        if (saveBtn) saveBtn.classList.toggle('saved');
    }
}

function updateModalRepost(btn, postId) {
    btn.classList.toggle('reposted');
    const postEl = document.querySelector(`[data-post-id="${postId}"]`);
    if (postEl) {
        const repostBtn = postEl.querySelector('.btn-repost');
        if (repostBtn) repostBtn.classList.toggle('reposted');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modalInput = document.getElementById('modal-comment-input');
    if (modalInput) {
        modalInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendModalComment();
            }
        });
    }
});


function renderComment(container, comment, postId) {
    const div = document.createElement('div');
    div.className = 'comment';
    div.dataset.commentId = comment.id;

    const isAuthor = window.currentUserId && comment.user_id == window.currentUserId;
    const editedMark = comment.updated_at && comment.updated_at !== comment.created_at ? ' <span class="edited-mark">(ред.)</span>' : '';
    
    div.innerHTML = `
        <img class="comment-avatar" src="${comment.author?.avatar || 
            `https://api.dicebear.com/7.x/avataaars/svg?seed=${comment.author?.id || 1}`}" alt="avatar">
        <div class="comment-content">
            <div class="comment-header">
                <div class="comment-author">${comment.author?.username || 'Аноним'}</div>
                ${isAuthor ? `
                    <div class="comment-actions">
                        <button class="btn-edit-comment" title="Редактировать">✏️</button>
                        <button class="btn-delete-comment" title="Удалить">🗑️</button>
                    </div>
                ` : ''}
            </div>
            <div class="comment-text">${comment.content}</div>${editedMark}
            <div class="comment-time">${comment.timeAgo || 'только что'}</div>
        </div>
    `;

    if (isAuthor) {
        div.querySelector('.btn-edit-comment').addEventListener('click', () => editComment(comment.id, postId));
        div.querySelector('.btn-delete-comment').addEventListener('click', () => {
            if (typeof window.deleteComment === 'function') {
                window.deleteComment(comment.id, postId);
            }
        });
    }
    
    container.appendChild(div);
}

function addCommentToPost(data) {
    const postEl = document.querySelector(`[data-post-id="${data.post_id}"]`);
    if (!postEl) return;
    
    const section = postEl.querySelector('.comments-section');
    const list = postEl.querySelector('.comments-list');
    
    if (section && list && section.style.display !== 'none') {

        const emptyState = list.querySelector('.empty-comments');
        if (emptyState) {
            emptyState.remove();
        }
        renderComment(list, data, data.post_id);
    }
    
    const countEl = postEl.querySelector('.comments-count');
    if (countEl) {
        const currentCount = parseInt(countEl.textContent) || 0;
        countEl.textContent = `${currentCount + 1} комментариев`;
    }
}

async function sendComment(postId) {
    const postEl = document.querySelector(`[data-post-id="${postId}"]`);
    if (!postEl) return;
    const input = postEl.querySelector('.comment-input');
    if (!input) return;
    const content = input.value.trim();
    
    if (!content) return;
    
    try {
        const response = await fetch('/api/comment/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, content })
        });
        
        const result = await response.json();
        if (result.success) {
            input.value = '';
            const list = postEl.querySelector('.comments-list');
            if (list) {

                const emptyState = list.querySelector('.empty-comments');
                if (emptyState) {
                    emptyState.remove();
                }
                renderComment(list, result.comment);
            }
            
            const countEl = postEl.querySelector('.comments-count');
            if (countEl) {
                const currentCount = parseInt(countEl.textContent) || 0;
                countEl.textContent = `${currentCount + 1} комментариев`;
            }
        }
    } catch (error) {
        
    }
}

async function voteInPoll(pollId, optionIds) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('/api/vote', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                poll_id: pollId,
                option_ids: optionIds
            })
        });
        
        const result = await response.json();
        if (result.success) {

            const pollEl = document.querySelector(`[data-poll-id="${pollId}"]`);
            if (pollEl) {
                pollEl.outerHTML = renderPoll(result.poll);
            }
        } else {
            showNotification(result.error || 'Ошибка голосования', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка голосования', 'error');
    }
}

function renderPoll(poll) {
    const inputType = (poll.allow_multiple || poll.multiple_votes) ? 'checkbox' : 'radio';
    const name = `poll_${poll.id}`;
    
    let html = `
        <div class="poll-container ${poll.has_user_voted ? 'voted' : ''}" data-poll-id="${poll.id}">
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

function handlePollVote(pollId) {
    const pollEl = document.querySelector(`[data-poll-id="${pollId}"]`);
    if (!pollEl) return;
    
    const selectedOptions = Array.from(pollEl.querySelectorAll('input:checked'))
        .map(input => parseInt(input.value));
    
    if (selectedOptions.length === 0) {
        showNotification('Выберите хотя бы один вариант', 'error');
        return;
    }
    
    voteInPoll(pollId, selectedOptions);
}

function updatePollDisplay(poll) {
    const pollEl = document.querySelector(`[data-poll-id="${poll.id}"]`);
    if (!pollEl) return;

    poll.options.forEach(option => {
        const optionEl = pollEl.querySelector(`[data-option-id="${option.id}"]`);
        if (optionEl) {
            const percentageEl = optionEl.querySelector('.poll-percentage');
            const votesEl = optionEl.querySelector('.poll-votes');
            const barEl = optionEl.querySelector('.poll-bar');
            
            if (percentageEl) percentageEl.textContent = `${option.percentage}%`;
            if (votesEl) votesEl.textContent = `${option.votes_count} голосов`;
            if (barEl) barEl.style.width = `${option.percentage}%`;
        }
    });

    const totalVotesEl = pollEl.querySelector('.poll-total-votes');
    if (totalVotesEl) {
        totalVotesEl.textContent = `Всего голосов: ${poll.total_votes}`;
    }

    if (poll.has_user_voted) {
        const checkboxes = pollEl.querySelectorAll('input[type="checkbox"], input[type="radio"]');
        checkboxes.forEach(cb => cb.disabled = true);
        
        const voteBtn = pollEl.querySelector('.btn-vote');
        if (voteBtn) voteBtn.style.display = 'none';
    }
}

async function editComment(commentId, postId) {
    const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!commentEl) return;
    const textEl = commentEl.querySelector('.comment-text');
    if (!textEl) return;
    const currentText = textEl.textContent;

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
        const newContent = input.value.trim();
        if (!newContent || newContent === currentText) {
            cancelEdit(commentEl);
            return;
        }
        
        try {
            const response = await fetch('/api/comment/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId, content: newContent })
            });
            
            const result = await response.json();
            if (result.success) {
                textEl.textContent = newContent;
                textEl.insertAdjacentHTML('afterend', ' <span class="edited-mark">(ред.)</span>');
                cancelEdit(commentEl);
            } else {
                showNotification(result.error || 'Ошибка редактирования', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка редактирования', 'error');
        }
    });
    
    editForm.querySelector('.btn-cancel-edit').addEventListener('click', () => cancelEdit(commentEl));
}

function cancelEdit(commentEl) {
    const editForm = commentEl.querySelector('.comment-edit-form');
    if (editForm) editForm.remove();
    commentEl.querySelector('.comment-text').style.display = 'block';
}




function updatePublishButton() {
    const textarea = document.getElementById('post-content');
    const btnPublish = document.getElementById('btn-publish');
    const hasContent = textarea.value.trim().length > 0;
    const hasImage = selectedImage !== null;
    const pollData = getPollData();
    const hasValidPoll = pollData !== null;
    
    btnPublish.disabled = !hasContent && !hasImage && !hasValidPoll;
}

function initPostForm() {
    const textarea = document.getElementById('post-content');
    const charCount = document.getElementById('char-count');
    const btnPublish = document.getElementById('btn-publish');
    const imageInput = document.getElementById('post-image');

    if (!textarea) return;
    
    textarea.addEventListener('input', () => {
        const len = textarea.value.length;
        charCount.textContent = `${len}/2000`;
        btnPublish.disabled = len === 0 && !selectedImage;
        charCount.style.color = len > 1900 ? '#ef4444' : '#6b7280';
    });
    
    if (imageInput) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                selectedImage = file;
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('image-preview').src = e.target.result;
                    document.getElementById('image-preview-container').style.display = 'block';
                };
                reader.readAsDataURL(file);
                btnPublish.disabled = false;
            }
        });
    }
    
    btnPublish.addEventListener('click', async () => {
        const content = textarea.value.trim();
        if (!content && !selectedImage) return;

        if (!validatePoll()) {
            return;
        }
        
        try {

            if (!validatePoll()) {
                return;
            }
            
            const formData = new FormData();
            formData.append('content', content);
            if (selectedImage) {
                formData.append('image', selectedImage);
            }

            const pollData = getPollData();
            if (pollData) {
                formData.append('poll_question', pollData.question);
                formData.append('poll_multiple', pollData.multiple_votes ? '1' : '0');

                formData.append('poll_options', pollData.options.join(','));
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
                charCount.textContent = '0/2000';
                removeSelectedImage();
                removePoll(); // Очищаем опрос
                btnPublish.disabled = true;
                appendPostToContainer(result.post, true);
                showNotification('Пост опубликован!', 'success');
            } else {
                showNotification(result.error || 'Ошибка публикации', 'error');
            }
        } catch (error) {
            
            showNotification('Ошибка сети', 'error');
        }
    });
}

function removeSelectedImage() {
    selectedImage = null;
    document.getElementById('image-preview').src = '';
    document.getElementById('image-preview-container').style.display = 'none';
    document.getElementById('post-image').value = '';
    const textarea = document.getElementById('post-content');
    document.getElementById('btn-publish').disabled = textarea.value.trim().length === 0;
}


async function submitPollVote(pollId, postId) {
    const pollContainer = document.querySelector(`[data-poll-id="${pollId}"]`);
    const selectedOptions = pollContainer.querySelectorAll('input:checked');
    
    if (selectedOptions.length === 0) {
        showNotification('Выберите вариант ответа', 'error');
        return;
    }
    
    const optionIds = Array.from(selectedOptions).map(input => parseInt(input.value));
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const response = await fetch('/api/vote', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                poll_id: pollId,
                option_ids: optionIds
            })
        });
        
        const data = await response.json();
        
        if (data.success) {

            const pollContainer = document.querySelector(`[data-poll-id="${pollId}"]`);
            if (pollContainer) {
                pollContainer.outerHTML = renderPoll(data.poll);
            }

            const postCard = document.querySelector(`[data-post-id="${postId}"]`);
            if (postCard) {
                updatePostStats(postCard, data.post || {});
            }
            
            showNotification('Ваш голос учтен!', 'success');
        } else {
            showNotification(data.error || 'Ошибка голосования', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка сети', 'error');
    }
}

function addPoll() {
    const pollContainer = document.getElementById('poll-container');
    const addPollBtn = document.querySelector('.btn-add-poll');
    
    if (pollContainer.style.display === 'none') {
        pollContainer.style.display = 'block';
        addPollBtn.classList.add('active');
        addPollBtn.textContent = '📊';
    } else {
        removePoll();
    }
    
    updatePublishButton();
}

function removePoll() {
    const pollContainer = document.getElementById('poll-container');
    const addPollBtn = document.querySelector('.btn-add-poll');
    
    pollContainer.style.display = 'none';
    addPollBtn.classList.remove('active');
    addPollBtn.textContent = '📊';

    document.getElementById('poll-question').value = '';
    document.getElementById('poll-multiple').checked = false;

    const optionsContainer = document.getElementById('poll-options');
    optionsContainer.innerHTML = `
        <div class="poll-option-input">
            <input type="text" placeholder="Вариант ответа 1..." maxlength="100" class="option-input">
            <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
        </div>
        <div class="poll-option-input">
            <input type="text" placeholder="Вариант ответа 2..." maxlength="100" class="option-input">
            <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
        </div>
    `;
    
    updatePublishButton();
}

function addPollOption() {
    const optionsContainer = document.getElementById('poll-options');
    const currentOptions = optionsContainer.querySelectorAll('.poll-option-input').length;
    
    if (currentOptions >= 10) {
        showNotification('Максимум 10 вариантов ответа', 'error');
        return;
    }
    
    const optionDiv = document.createElement('div');
    optionDiv.className = 'poll-option-input';
    optionDiv.innerHTML = `
        <input type="text" placeholder="Вариант ответа ${currentOptions + 1}..." maxlength="100" class="option-input">
        <button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button>
    `;
    
    optionsContainer.appendChild(optionDiv);

    const input = optionDiv.querySelector('.option-input');
    input.addEventListener('input', updatePublishButton);
    
    updatePublishButton();
}

function removeOption(button) {
    const optionsContainer = document.getElementById('poll-options');
    const options = optionsContainer.querySelectorAll('.poll-option-input');
    
    if (options.length <= 2) {
        showNotification('Минимум 2 варианта ответа', 'error');
        return;
    }
    
    button.parentElement.remove();
    updatePublishButton();
}

function getPollData() {
    const pollContainer = document.getElementById('poll-container');
    
    if (pollContainer.style.display === 'none') {
        return null;
    }
    
    const question = document.getElementById('poll-question').value.trim();
    const multiple = document.getElementById('poll-multiple').checked;
    const options = [];
    
    document.querySelectorAll('.option-input').forEach(input => {
        const value = input.value.trim();
        if (value) {
            options.push(value);
        }
    });
    
    if (!question || options.length < 2) {
        return null;
    }
    
    return {
        question: question,
        multiple_votes: multiple,
        options: options
    };
}

function validatePoll() {
    const pollData = getPollData();
    
    if (!pollData) {
        return true; // Опрос не добавлен, это нормально
    }
    
    if (pollData.question.length > 255) {
        showNotification('Вопрос опроса слишком длинный (максимум 255 символов)', 'error');
        return false;
    }
    
    if (pollData.options.length < 2) {
        showNotification('Минимум 2 варианта ответа', 'error');
        return false;
    }
    
    if (pollData.options.length > 10) {
        showNotification('Максимум 10 вариантов ответа', 'error');
        return false;
    }
    
    for (const option of pollData.options) {
        if (option.length === 0) {
            showNotification('Все варианты ответа должны быть заполнены', 'error');
            return false;
        }
        if (option.length > 100) {
            showNotification('Вариант ответа слишком длинный (максимум 100 символов)', 'error');
            return false;
        }
    }
    
    return true;
}

document.addEventListener('DOMContentLoaded', () => {

    const urlParams = new URLSearchParams(window.location.search);
    currentFeedType = urlParams.get('type') || 'following';

    const filterButtons = document.querySelectorAll('.feed-filter');
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            const type = button.dataset.type;

            filterButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            switchFeed(type);
        });
    });

    loadPosts(false);

    initPostForm();

    setupInfiniteScroll();

    initializePosts();
});

window.handleLike = handleLike;
window.handleSave = handleSave;
window.toggleComments = toggleComments;
window.toggleRepost = toggleRepost;

function setupInfiniteScroll() {

    const feedSentinel = document.getElementById('feed-sentinel');
    if (feedSentinel && 'IntersectionObserver' in window) {
        const feedObserver = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting && !isLoadingFeed && hasMorePosts) {
                loadPosts(true);
            }
        }, { rootMargin: '200px' });
        feedObserver.observe(feedSentinel);
    }
}
function removeSelectedImage() {
    selectedImage = null;
    document.getElementById('image-preview').src = '';
    document.getElementById('image-preview-container').style.display = 'none';
    document.getElementById('post-image').value = '';
    const textarea = document.getElementById('post-content');
    document.getElementById('btn-publish').disabled = textarea.value.trim().length === 0;
}

