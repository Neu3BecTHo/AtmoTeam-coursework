// ==================== Utilities ====================
function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

function postWithCsrf(url, body, isJson = true) {
    const t = document.querySelector('meta[name="csrf-token"]')?.content;
    const h = {};
    if (isJson) h['Content-Type'] = 'application/json';
    if (t) h['X-CSRF-Token'] = t;
    return fetch(url, { method: 'POST', headers: h, body: isJson ? JSON.stringify(body) : body });
}

// ==================== Notification Icons ====================
function getNotificationIcon(type) {
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    return icons[type] || 'ℹ️';
}

// ==================== Show Notification (with CSS classes) ====================
function showNotification(msg, type = 'info') {
    // Создаём контейнер, если его нет
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.className = 'notification-container';
        document.body.appendChild(container);
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span class="notification-icon">${getNotificationIcon(type)}</span>
        <span class="notification-text">${escapeHtml(msg)}</span>
        <button class="notification-close" aria-label="${window.translations.close}">×</button>
    `;
    container.appendChild(notification);

    setTimeout(() => notification.classList.add('notification-enter'), 10);
    const timeout = setTimeout(() => closeNotification(notification), 5000);

    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        clearTimeout(timeout);
        closeNotification(notification);
    });
    notification.addEventListener('click', () => {
        clearTimeout(timeout);
        closeNotification(notification);
    });

    function closeNotification(notif) {
        if (!notif?.parentNode) return;
        notif.classList.add('notification-exit');
        setTimeout(() => notif.remove(), 300);
    }
}

// ==================== Modal ====================
let deleteModalCallback = null;

function showDeleteModal(text, onConfirm) {
    const m = document.getElementById('delete-modal');
    const t = document.getElementById('delete-modal-text');
    const b = document.getElementById('delete-modal-confirm');
    if (t) t.textContent = text || window.translations.delete_question;
    deleteModalCallback = onConfirm;
    if (b && !b.hasAttribute('data-handler-attached')) {
        b.onclick = function () {
            hideDeleteModal();
            if (deleteModalCallback) deleteModalCallback();
        };
        b.setAttribute('data-handler-attached', 'true');
    }
    if (m) {
        m.classList.remove('hidden');
        m.classList.add('show');
    }
}

function hideDeleteModal() {
    const m = document.getElementById('delete-modal');
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('show');
    }
}

// ==================== Like ====================
async function handleLike(postId, btn) {
  if (!window.currentUserId) {
    showNotification(window.translations.login_to_like, "error");
    return;
  }

  if (btn) {
    btn.style.opacity = "0.6";
    btn.style.pointerEvents = "none";
  }

  try {
    const response = await postWithCsrf("/api/post/like", { post_id: postId });
    const result = await response.json();

    if (result.success) {
      // Обновляем ВСЕ карточки поста на странице
      const allPostCards = document.querySelectorAll(
        `.post-card[data-post-id="${postId}"]`,
      );

      allPostCards.forEach((card) => {
        const likeBtn = card.querySelector(".btn-like, .post-action.btn-like");
        if (likeBtn) {
          const iconSpan = likeBtn.querySelector(".action-icon");
          const countSpan = likeBtn.querySelector(".action-count");
          likeBtn.classList.toggle("liked", result.liked);
          if (iconSpan) iconSpan.textContent = result.liked ? "❤️" : "🤍";
          if (countSpan) countSpan.textContent = result.likes_count;
        }
      });

      showNotification(
        result.liked ? window.translations.like_added : window.translations.like_removed,
        "success",
      );
    }
  } catch (error) {
    console.error("Like error:", error);
    showNotification(window.translations.error, "error");
  } finally {
    if (btn) {
      btn.style.opacity = "";
      btn.style.pointerEvents = "";
    }
  }
}

// ==================== Save ====================
async function handleSave(postId) {
  if (!window.currentUserId) {
    showNotification(window.translations.login_to_save, "error");
    return;
  }

  try {
    const response = await postWithCsrf("/api/post/save", { post_id: postId });
    const result = await response.json();

    if (result.success) {
      // Обновляем ВСЕ карточки поста на странице
      const allPostCards = document.querySelectorAll(
        `.post-card[data-post-id="${postId}"]`,
      );

      allPostCards.forEach((card) => {
        const saveBtn = card.querySelector(".btn-save, .post-action.btn-save");
        if (saveBtn) {
          const iconSpan = saveBtn.querySelector(".action-icon");
          saveBtn.classList.toggle("saved", result.saved);
          if (iconSpan) iconSpan.textContent = result.saved ? "🔖" : "📌";
        }
      });

      showNotification(
        result.saved ? window.translations.saved : window.translations.removed_from_saved,
        "success",
      );
    }
  } catch (error) {
    console.error("Save error:", error);
    showNotification(window.translations.error, "error");
  }
}

// ==================== Repost ====================
async function toggleRepost(postId) {
  if (!window.currentUserId) {
    showNotification(window.translations.login_to_repost, "error");
    return;
  }

  try {
    const response = await postWithCsrf("/api/repost", { post_id: postId });
    const result = await response.json();

    if (result.success) {
      // Обновляем ВСЕ карточки поста на странице
      const allPostCards = document.querySelectorAll(
        `.post-card[data-post-id="${postId}"]`,
      );

      allPostCards.forEach((card) => {
        const repostBtn = card.querySelector(
          ".btn-repost, .post-action.btn-repost",
        );
        if (repostBtn) {
          const countSpan = repostBtn.querySelector(".action-count");
          repostBtn.classList.toggle("reposted", result.reposted);
          if (countSpan) countSpan.textContent = result.reposts_count;
        }
      });

      showNotification(
        result.reposted ? window.translations.repost_made : window.translations.repost_cancelled,
        "success",
      );
    }
  } catch (error) {
    console.error("Repost error:", error);
    showNotification(window.translations.error, "error");
  }
}

function removePostIfInContainer(postElement, containerId, emptyMessage) {
    const card = postElement.closest('.post-card');
    if (!card) return;
    const container = document.getElementById(containerId);
    if (container && container.contains(card)) {
        card.remove();
        if (!container.querySelector('.post-card')) {
            container.innerHTML = `<div class="empty-profile"><div class="empty-icon">🔖</div><p>${emptyMessage}</p></div>`;
        }
    }
}

function refreshTab(tabId, loadFunction) {
    const tab = document.getElementById(tabId);
    if (tab && tab.style.display !== 'none' && typeof window[loadFunction] === 'function') {
        window[loadFunction]();
    }
}

// ==================== Delete ====================
async function deletePost(postId) {
    showDeleteModal(window.translations.delete_post_question, async function () {
        try {
            const r = await fetch('/post/delete?id=' + postId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });
            const res = await r.json();
            if (res.success) {
                showNotification(window.translations.post_deleted, 'success');
                document.querySelectorAll(`[data-post-id="${postId}"]`).forEach(p => p.remove());
                const c = document.querySelector('.profile-stats .stat:first-child .stat-value');
                if (c) {
                    const v = parseInt(c.textContent) || 0;
                    c.textContent = Math.max(0, v - 1);
                }
            } else {
                showNotification(res.error || window.translations.error, 'error');
            }
        } catch (e) { }
    });
}

// ==================== Comments Loading ====================
async function loadComments(postId, listId = 'modal-comments-list') {
    try {
        const response = await fetch(`/post/comments?id=${postId}`);
        const html = await response.text();
        
        const commentsList = document.getElementById(listId);
        if (!commentsList) return;
        
        commentsList.innerHTML = html;
        
        // Инициализируем обработчики для комментариев
        initCommentHandlers(postId);
        
    } catch (error) {
        console.error('Error loading comments:', error);
        const commentsList = document.getElementById(listId);
        if (commentsList) commentsList.innerHTML = `<p class="error-message">${window.translations.error_loading_comments}</p>`;
    }
}

function initCommentForm(postId) {
    const commentForm = document.querySelector('#post-modal .comment-form');
    if (!commentForm) return;
    
    const textarea = commentForm.querySelector('.modal-comment-input');
    const submitBtn = commentForm.querySelector('.modal-comment-submit');
    const counter = commentForm.querySelector('.comment-form__counter');
    
    if (!textarea || !submitBtn) return;
    
    // Обновление счётчика
    const updateCounter = () => {
        const len = textarea.value.length;
        if (counter) {
            counter.textContent = len;
            counter.style.color = len > 900 ? '#ef4444' : len > 800 ? '#f59e0b' : 'inherit';
        }
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    };
    
    // Отправка комментария
    const sendComment = async () => {
        const content = textarea.value.trim();
        if (!content) {
            showNotification(window.translations.write_comment, 'error');
            return;
        }
        
        if (!window.currentUserId) {
            showNotification(window.translations.login_to_comment, 'error');
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.textContent = window.translations.loading;
        
        try {
            const response = await postWithCsrf('/api/comment/create', {
                post_id: postId,
                content: content
            });
            const result = await response.json();
            
            if (result.success && result.comment) {
                textarea.value = '';
                updateCounter();
                
                // Добавляем комментарий в список
                if (typeof window.addCommentToModal === 'function') {
                    window.addCommentToModal(result.comment);
                }
                
                // Обновляем счётчик комментариев в заголовке модалки
                const headerCount = document.querySelector('#post-modal .comments-header__count');
                if (headerCount) {
                    const current = parseInt(headerCount.textContent) || 0;
                    headerCount.textContent = current + 1;
                }
                
                // Обновляем счётчик в карточке поста в ленте
                const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                if (postCard) {
                    const commentCountEl = postCard.querySelector('.btn-comment-toggle .action-count');
                    if (commentCountEl) {
                        const current = parseInt(commentCountEl.textContent) || 0;
                        commentCountEl.textContent = current + 1;
                    }
                }
                
                showNotification(window.translations.comment_added, 'success');
            } else {
                showNotification(result.error || window.translations.error, 'error');
            }
        } catch (error) {
            console.error('Comment error:', error);
            showNotification(window.translations.error_sending, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = window.translations.send;
        }
    };
    
    // Навешиваем обработчики
    textarea.removeEventListener('input', updateCounter);
    textarea.addEventListener('input', updateCounter);
    
    textarea.removeEventListener('keydown', (e) => {});
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendComment();
        }
    });
    
    submitBtn.removeEventListener('click', sendComment);
    submitBtn.addEventListener('click', sendComment);
    
    updateCounter();
}

function initCommentHandlers(postId) {
    // Кнопки удаления комментариев
    document.querySelectorAll('#modal-comments-list .btn-delete-comment, .comments-list .btn-delete-comment').forEach(btn => {
        if (btn.hasAttribute('data-handler-inited')) return;
        btn.setAttribute('data-handler-inited', 'true');
        
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const commentId = btn.dataset.commentId;
            if (commentId && typeof window.deleteComment === 'function') {
                window.deleteComment(commentId, postId);
            }
        });
    });
    
    // Кнопки редактирования комментариев
    document.querySelectorAll('#modal-comments-list .btn-edit-comment, .comments-list .btn-edit-comment').forEach(btn => {
        if (btn.hasAttribute('data-handler-inited')) return;
        btn.setAttribute('data-handler-inited', 'true');
        
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const commentId = btn.dataset.commentId;
            if (commentId && typeof window.editComment === 'function') {
                window.editComment(commentId, postId);
            }
        });
    });
}

async function submitModalComment(postId) {
    // Находим textarea в текущей открытой модалке
    const textarea = document.querySelector('#post-modal .comment-form__input, #modal-comment-input');
    if (!textarea) {
        console.error('Comment textarea not found');
        showNotification(window.translations.input_field_not_found, 'error');
        return;
    }
    
    const content = textarea.value.trim();
    if (!content) {
        showNotification(window.translations.write_comment, 'error');
        return;
    }
    
    if (!window.currentUserId) {
        showNotification(window.translations.login_to_comment, 'error');
        return;
    }
    
    // Блокируем кнопку отправки
    const submitBtn = textarea.closest('.comment-form')?.querySelector('.comment-form__btn, .btn-send');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = window.translations.sending;
    }
    
    try {
        const response = await postWithCsrf('/api/comment/create', {
            post_id: postId,
            content: content
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Очищаем поле
            textarea.value = '';
            
            // Обновляем счётчик
            const counter = document.querySelector('#post-modal .comment-form__counter, #comment-char-count');
            if (counter) {
                counter.textContent = '0';
                counter.style.color = 'inherit';
            }
            
            // Сбрасываем высоту textarea
            textarea.style.height = 'auto';
            
            // Перезагружаем комментарии
            await loadComments(postId, 'modal-comments-list');
            
            // Обновляем счётчик комментариев в заголовке
            const commentsHeaderCount = document.querySelector('#post-modal .comments-header__count');
            if (commentsHeaderCount) {
                const currentCount = parseInt(commentsHeaderCount.textContent) || 0;
                commentsHeaderCount.textContent = currentCount + 1;
            }
            
            showNotification(window.translations.comment_added, 'success');
        } else {
            showNotification(result.error || window.translations.error_sending_comment, 'error');
        }
    } catch (error) {
        console.error('Comment error:', error);
        showNotification(window.translations.error_sending_comment, 'error');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = window.translations.send;
        }
    }
}

async function deleteComment(commentId, postId) {
    showDeleteModal(window.translations.delete_comment_question, async () => {
        try {
            const response = await postWithCsrf('/api/comment/delete', { comment_id: commentId });
            const result = await response.json();
            
            if (result.success) {
                showNotification(window.translations.comment_deleted, 'success');
                
                // Удаляем комментарий из DOM
                const commentEl = document.querySelector(`.comment-item[data-comment-id="${commentId}"]`);
                if (commentEl) commentEl.remove();
                
                // Обновляем счётчик комментариев в заголовке модалки
                const commentsHeaderCount = document.querySelector('#post-modal .comments-header__count');
                if (commentsHeaderCount) {
                    const currentCount = parseInt(commentsHeaderCount.textContent) || 0;
                    commentsHeaderCount.textContent = Math.max(0, currentCount - 1);
                }
                
                // Обновляем счётчик в карточке поста в ленте
                const postCard = document.querySelector(`.post-card[data-post-id="${postId}"]`);
                if (postCard) {
                    const commentCountEl = postCard.querySelector('.btn-comment-toggle .action-count');
                    if (commentCountEl) {
                        const current = parseInt(commentCountEl.textContent) || 0;
                        commentCountEl.textContent = Math.max(0, current - 1);
                    }
                }
                
                // Если комментариев не осталось, показываем пустое состояние
                const commentsList = document.getElementById('modal-comments-list');
                if (commentsList && commentsList.querySelectorAll('.comment-item').length === 0) {
                    commentsList.innerHTML = `
                        <div class="empty-comments">
                            <div class="empty-comments-icon">💬</div>
                            <p>${window.translations.no_comments}</p>
                            <p class="empty-hint">${window.translations.be_first_to_comment}</p>
                        </div>
                    `;
                }
            } else {
                showNotification(result.error || window.translations.error_deleting, 'error');
            }
        } catch (error) {
            console.error('Delete comment error:', error);
            showNotification(window.translations.error_deleting, 'error');
        }
    });
}

// ==================== Edit Comment ====================
async function editComment(commentId, postId) {
    const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
    if (!commentEl) return;
    
    // Если уже есть форма редактирования — удаляем её
    const existingForm = commentEl.querySelector('.comment-edit-form');
    if (existingForm) {
        existingForm.remove();
        const textEl = commentEl.querySelector('.comment-text');
        if (textEl) textEl.style.display = 'block';
        return;
    }
    
    const textEl = commentEl.querySelector('.comment-text');
    if (!textEl) return;
    
    const currentText = textEl.textContent.trim();
    
    const editForm = document.createElement('div');
    editForm.className = 'comment-edit-form';
    editForm.innerHTML = `
        <textarea class="comment-edit-textarea" rows="2">${escapeHtml(currentText)}</textarea>
        <div class="comment-edit-actions">
            <button class="btn-save-edit">${window.translations.save}</button>
            <button class="btn-cancel-edit">${window.translations.cancel}</button>
        </div>
    `;
    
    textEl.style.display = 'none';
    textEl.parentNode.insertBefore(editForm, textEl.nextSibling);
    
    const textarea = editForm.querySelector('.comment-edit-textarea');
    textarea.focus();
    
    const saveBtn = editForm.querySelector('.btn-save-edit');
    const cancelBtn = editForm.querySelector('.btn-cancel-edit');
    
    // Убираем старые обработчики, чтобы не дублировать
    saveBtn.replaceWith(saveBtn.cloneNode(true));
    cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    
    const newSaveBtn = editForm.querySelector('.btn-save-edit');
    const newCancelBtn = editForm.querySelector('.btn-cancel-edit');
    
    newSaveBtn.onclick = async () => {
        const newText = textarea.value.trim();
        if (!newText || newText === currentText) {
            newCancelBtn.click();
            return;
        }
        
        try {
            const response = await postWithCsrf('/api/comment/update', {
                comment_id: commentId,
                content: newText
            });
            const data = await response.json();
            
            if (data.success) {
                textEl.textContent = newText;
                const header = commentEl.querySelector('.comment-header');
                if (header && !header.querySelector('.edited-mark')) {
                    const mark = document.createElement('span');
                    mark.className = 'edited-mark';
                    mark.textContent = window.translations.edited_mark;
                    header.appendChild(mark);
                }
                showNotification(window.translations.comment_updated, 'success');
                newCancelBtn.click();
            } else {
                showNotification(data.error || window.translations.error, 'error');
            }
        } catch (error) {
            showNotification(window.translations.error, 'error');
        }
    };
    
    newCancelBtn.onclick = () => {
        textEl.style.display = 'block';
        editForm.remove();
    };
    
    textarea.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.ctrlKey) {
            newSaveBtn.click();
        } else if (e.key === 'Escape') {
            newCancelBtn.click();
        }
    });
}

// ==================== Poll ====================
function submitPollVote(pollId, postId) {
  console.log("submitPollVote called with pollId:", pollId, "postId:", postId);

  let pollContainer = document.querySelector(
    `#post-modal .poll-widget[data-poll-id="${pollId}"]`,
  );
  if (!pollContainer) {
    pollContainer = document.querySelector(
      `.poll-widget[data-poll-id="${pollId}"]`,
    );
  }

  if (!pollContainer) {
    console.error("Poll container not found for pollId:", pollId);
    showNotification(window.translations.poll_container_not_found, "error");
    return;
  }

  const selected = pollContainer.querySelectorAll("input:checked");

  if (!selected.length) {
    showNotification(window.translations.select_answer_option, "error");
    return;
  }

  const optionIds = Array.from(selected).map((input) => input.value);

  const voteBtn = pollContainer.querySelector(".poll-widget-vote-btn");
  if (voteBtn) {
    voteBtn.disabled = true;
    voteBtn.textContent = window.translations.loading;
  }

  postWithCsrf("/poll/vote", { poll_id: pollId, option_ids: optionIds })
    .then((r) => r.json())
    .then((data) => {
      if (data.success && data.poll) {
        // Обновляем HTML опроса в модалке
        const newHtml = renderPoll(data.poll);
        pollContainer.outerHTML = newHtml;

        // ========== ОБНОВЛЯЕМ КАРТОЧКУ ПОСТА В ЛЕНТЕ ==========
        if (postId) {
          // Ищем карточку поста в ленте профиля
          const postCard = document.querySelector(
            `.post-card[data-post-id="${postId}"]`,
          );
          if (postCard) {
            // Обновляем счётчики голосов в опросе внутри карточки
            const pollInCard = postCard.querySelector(
              `.poll-widget[data-poll-id="${pollId}"]`,
            );
            if (pollInCard && data.poll.options) {
              data.poll.options.forEach((option) => {
                const optionEl = pollInCard.querySelector(
                  `.poll-option[data-option-id="${option.id}"]`,
                );
                if (optionEl) {
                  const percentageSpan =
                    optionEl.querySelector(".poll-percentage");
                  const votesSpan = optionEl.querySelector(".poll-votes");
                  const bar = optionEl.querySelector(".poll-bar");
                  if (percentageSpan)
                    percentageSpan.textContent = option.percentage + "%";
                  if (votesSpan)
                    votesSpan.textContent = option.votes_count + " " + window.translations.votes;
                  if (bar) bar.style.width = option.percentage + "%";
                }
              });
              // Обновляем общее количество голосов
              const totalSpan = pollInCard.querySelector(".poll-widget-total");
              if (totalSpan) {
                totalSpan.textContent = `📊 ${data.poll.total_votes} ` + window.translations.votes_abbr;
              }
            }

            // Обновляем кнопку лайка в карточке (если нужно)
            const likeBtn = postCard.querySelector(".btn-like");
            if (likeBtn && data.post?.likes_count !== undefined) {
              const countSpan = likeBtn.querySelector(".action-count");
              if (countSpan) countSpan.textContent = data.post.likes_count;
            }
          }
        }

        showNotification(window.translations.vote_counted, "success");
      } else {
        showNotification(data.error || window.translations.voting_error, "error");
        const newPollContainer = document.querySelector(
          `.poll-widget[data-poll-id="${pollId}"]`,
        );
        const newVoteBtn = newPollContainer?.querySelector(
          ".poll-widget-vote-btn",
        );
        if (newVoteBtn) {
          newVoteBtn.disabled = false;
          newVoteBtn.textContent = window.translations.vote_button;
        }
      }
    })
    .catch((error) => {
      console.error("Vote error:", error);
      showNotification(window.translations.network_error, "error");
      const newPollContainer = document.querySelector(
        `.poll-widget[data-poll-id="${pollId}"]`,
      );
      const newVoteBtn = newPollContainer?.querySelector(
        ".poll-widget-vote-btn",
      );
      if (newVoteBtn) {
        newVoteBtn.disabled = false;
        newVoteBtn.textContent = window.translations.vote_button;
      }
    });
}

function cancelPollVote(pollId, postId) {
  let pollContainer = document.querySelector(
    `.poll-widget[data-poll-id="${pollId}"]`,
  );
  if (!pollContainer) return;

  postWithCsrf("/poll/cancel-vote", { poll_id: pollId })
    .then((r) => r.json())
    .then((data) => {
      if (data.success && data.poll) {
        const newHtml = renderPoll(data.poll);
        // Обновляем ВСЕ контейнеры опроса на странице
        const allPollContainers = document.querySelectorAll(
          `.poll-widget[data-poll-id="${pollId}"]`,
        );
        allPollContainers.forEach((container) => {
          container.outerHTML = newHtml;
        });
        showNotification(window.translations.vote_cancelled, "success");
      } else {
        showNotification(data.error || window.translations.error, "error");
      }
    })
    .catch(() => showNotification(window.translations.network_error, "error"));
}

function renderPoll(poll) {
  const isMultiple = poll.multiple_votes === true || poll.multiple_votes === 1;
  const inputType = isMultiple ? "checkbox" : "radio";
  const name = `poll_${poll.id}`;
  const totalVotes = poll.total_votes || 0;
  const hasUserVoted =
    poll.has_user_voted === true || poll.has_user_voted === 1;

  let html = `<div class="poll-widget ${hasUserVoted ? "voted" : ""}" data-poll-id="${poll.id}">
        <div class="poll-widget-question">${escapeHtml(poll.question)}</div>
        <div class="poll-widget-options">`;

  poll.options.forEach((option) => {
    const isChecked = poll.user_votes?.includes(option.id);
    const percentage = option.percentage || 0;
    const votesCount = option.votes_count || 0;

    html += `<div class="poll-widget-option ${isChecked ? "is-selected" : ""}" data-option-id="${option.id}">
            <label class="poll-widget-label">
                <input type="${inputType}" 
                       name="${name}${isMultiple ? "[]" : ""}" 
                       value="${option.id}" 
                       ${isChecked ? "checked" : ""}
                       ${hasUserVoted ? "disabled" : ""}>
                <span class="poll-widget-text">${escapeHtml(option.text)}</span>
            </label>`;

    if (hasUserVoted) {
      html += `<div class="poll-widget-results">
                        <div class="poll-widget-bar" style="width: ${percentage}%"></div>
                        <span class="poll-widget-percentage">${percentage}%</span>
                        <span class="poll-widget-votes">${votesCount} ` + window.translations.votes_abbr + `</span>
                        ${isChecked ? '<span class="poll-widget-checked">' + window.translations.your_vote + '</span>' : ""}
                    </div>`;
    }

    html += `</div>`;
  });

  html += `</div><div class="poll-widget-footer">
        <span class="poll-widget-total">📊 ${totalVotes} ` + window.translations.votes_abbr + `</span>`;

  if (!hasUserVoted) {
    html += `<button class="poll-widget-vote-btn" onclick="submitPollVote(${poll.id})">${window.translations.vote_button}</button>`;
  } else {
    html += `<button class="poll-widget-cancel-btn" onclick="cancelPollVote(${poll.id})">${window.translations.cancel}</button>`;
  }

  html += `</div></div>`;
  return html;
}

// ==================== Fullscreen Image Viewer ====================
let currentFullscreenImages = [];
let currentFullscreenIndex = 0;

function openImageFullscreen(imageUrl, totalCount = 1, index = 0) {
    // Ищем модалку
    let modal = document.getElementById('fullscreen-image-modal');
    
    // Если модалки нет — создаём
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'fullscreen-image-modal';
        modal.className = 'fullscreen-image-modal';
        modal.innerHTML = `
            <div class="fullscreen-image-overlay"></div>
            <div class="fullscreen-image-container">
                <button class="fullscreen-image-close">&times;</button>
                <div class="fullscreen-image-navigation">
                    <button class="fullscreen-image-prev">‹</button>
                    <button class="fullscreen-image-next">›</button>
                </div>
                <div class="fullscreen-image-content">
                    <img id="fullscreen-image" src="" alt="Fullscreen image">
                    <div class="fullscreen-image-info">
                        <span id="fullscreen-image-counter">1 / 1</span>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Навешиваем обработчики через addEventListener для надёжности
        const overlay = modal.querySelector('.fullscreen-image-overlay');
        const closeBtn = modal.querySelector('.fullscreen-image-close');
        const prevBtn = modal.querySelector('.fullscreen-image-prev');
        const nextBtn = modal.querySelector('.fullscreen-image-next');
        
        if (overlay) overlay.addEventListener('click', closeFullscreenImage);
        if (closeBtn) closeBtn.addEventListener('click', closeFullscreenImage);
        if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); prevFullscreenImage(); });
        if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); nextFullscreenImage(); });
    }
    
    // Собираем все изображения из поста
    const postCard = document.querySelector(`img[src="${imageUrl}"]`)?.closest('.post-card, .post-modal');
    if (postCard) {
        const imageItems = postCard.querySelectorAll('.post-image-item, .gallery__item, .post-modal-image-item');
        if (imageItems.length > 0) {
            currentFullscreenImages = Array.from(imageItems).map(item => {
                const img = item.querySelector('img');
                return { url: img?.src || item.dataset.imageUrl, index: parseInt(item.dataset.imageIndex) || 0 };
            });
        } else {
            currentFullscreenImages = [{ url: imageUrl, index: 0 }];
        }
    } else {
        currentFullscreenImages = [{ url: imageUrl, index: 0 }];
    }
    
    // Убираем дубликаты по URL
    currentFullscreenImages = currentFullscreenImages.filter((item, idx, self) => 
        idx === self.findIndex(i => i.url === item.url)
    );
    
    currentFullscreenIndex = Math.min(index, currentFullscreenImages.length - 1);
    
    const imageElement = document.getElementById('fullscreen-image');
    const counterElement = document.getElementById('fullscreen-image-counter');
    
    if (imageElement && counterElement) {
        imageElement.src = currentFullscreenImages[currentFullscreenIndex]?.url || imageUrl;
        counterElement.textContent = `${currentFullscreenIndex + 1} / ${currentFullscreenImages.length}`;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', handleFullscreenKeydown);
    }
}

function closeFullscreenImage() {
    const modal = document.getElementById('fullscreen-image-modal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.removeEventListener('keydown', handleFullscreenKeydown);
    }
}

function prevFullscreenImage() {
    if (currentFullscreenImages.length <= 1) return;
    currentFullscreenIndex = (currentFullscreenIndex - 1 + currentFullscreenImages.length) % currentFullscreenImages.length;
    updateFullscreenImage();
}

function nextFullscreenImage() {
    if (currentFullscreenImages.length <= 1) return;
    currentFullscreenIndex = (currentFullscreenIndex + 1) % currentFullscreenImages.length;
    updateFullscreenImage();
}

function updateFullscreenImage() {
    const imageElement = document.getElementById('fullscreen-image');
    const counterElement = document.getElementById('fullscreen-image-counter');
    if (imageElement && counterElement && currentFullscreenImages[currentFullscreenIndex]) {
        imageElement.src = currentFullscreenImages[currentFullscreenIndex].url;
        counterElement.textContent = `${currentFullscreenIndex + 1} / ${currentFullscreenImages.length}`;
    }
}

function handleFullscreenKeydown(e) {
    switch (e.key) {
        case 'Escape': closeFullscreenImage(); break;
        case 'ArrowLeft': prevFullscreenImage(); break;
        case 'ArrowRight': nextFullscreenImage(); break;
    }
}

// Принудительная инициализация обработчиков в модалке
function initModalPollHandlers() {
    const modal = document.getElementById('post-modal');
    if (!modal || !modal.classList.contains('show')) return;
    
    // Переназначаем onclick для кнопок голосования в модалке
    const voteBtns = modal.querySelectorAll('.poll-widget-vote-btn');
    voteBtns.forEach(btn => {
        const pollId = btn.closest('.poll-widget')?.dataset.pollId;
        const postId = modal.querySelector('.post-modal__body')?.dataset.postId || window.currentModalPostId;
        if (pollId && !btn.hasAttribute('data-modal-handler')) {
            btn.setAttribute('data-modal-handler', 'true');
            btn.onclick = (e) => {
                e.preventDefault();
                submitPollVote(pollId, postId);
            };
        }
    });
    
    const cancelBtns = modal.querySelectorAll('.poll-widget-cancel-btn');
    cancelBtns.forEach(btn => {
        const pollId = btn.closest('.poll-widget')?.dataset.pollId;
        if (pollId && !btn.hasAttribute('data-modal-handler')) {
            btn.setAttribute('data-modal-handler', 'true');
            btn.onclick = (e) => {
                e.preventDefault();
                cancelPollVote(pollId);
            };
        }
    });
}

// Наблюдатель за появлением модалки
const modalObserver = new MutationObserver(() => {
    const modal = document.getElementById('post-modal');
    if (modal && modal.classList.contains('show')) {
        setTimeout(initModalPollHandlers, 100);
    }
});
modalObserver.observe(document.body, { childList: true, subtree: true });

function closePostModal() {
  const modal = document.getElementById("post-modal");
  if (modal) {
    modal.classList.remove("show");
    modal.classList.add("hidden");
    if (typeof currentModalPostId !== "undefined") {
      window.currentModalPostId = null;
    }
  }
}

// ==================== Global Event Delegation ====================
document.addEventListener('click', function (e) {
    const modalSelectors = [
        { modalId: 'delete-modal', condition: m => m.classList.contains('show') && (e.target === m || e.target.closest('.modal-overlay')), handler: hideDeleteModal },
        { modalId: 'post-modal', condition: m => m.classList.contains('show') && (e.target === m || e.target.classList.contains('modal-overlay')), handler: () => typeof closePostModal === 'function' && closePostModal() },
        { modalId: 'profile-post-modal', condition: m => m.classList.contains('show') && (e.target === m || e.target.classList.contains('modal-overlay')), handler: () => typeof closeProfilePostModal === 'function' && closeProfilePostModal() },
        { modalId: 'story-view-modal', condition: m => m.classList.contains('show') && e.target === m && !m.classList.contains('fullscreen'), handler: () => typeof hideStoryView === 'function' && hideStoryView() },
        { modalId: 'block-modal', condition: m => m.classList.contains('show') && (e.target === m || e.target.classList.contains('modal-overlay')), handler: () => typeof hideBlockModal === 'function' && hideBlockModal() },
        { modalId: 'fullscreen-image-modal', condition: m => m.style.display === 'flex' && e.target.classList.contains('fullscreen-image-overlay'), handler: closeFullscreenImage }
    ];

    for (const { modalId, condition, handler } of modalSelectors) {
        const modal = document.getElementById(modalId);
        if (modal && condition(modal)) {
            handler();
            break;
        }
    }
});

// ==================== Exports ====================
window.handleLike = handleLike;
window.handleSave = handleSave;
window.toggleRepost = toggleRepost;
window.deletePost = deletePost;
window.deleteComment = deleteComment;
window.editComment = editComment;
window.loadComments = loadComments;
window.initCommentForm = initCommentForm;
window.showDeleteModal = showDeleteModal;
window.hideDeleteModal = hideDeleteModal;
window.showNotification = showNotification;
window.closePostModal = closePostModal;
window.postWithCsrf = postWithCsrf;
window.escapeHtml = escapeHtml;
window.submitPollVote = submitPollVote;
window.cancelPollVote = cancelPollVote;
window.renderPoll = renderPoll;
window.initCommentHandlers = initCommentHandlers;
window.openImageFullscreen = openImageFullscreen;
window.closeFullscreenImage = closeFullscreenImage;
window.prevFullscreenImage = prevFullscreenImage;
window.nextFullscreenImage = nextFullscreenImage;