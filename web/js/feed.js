// ==================== Feed State ====================
let posts = [];
let currentFeedType = "following";
let pollInterval = null;
let feedOffset = 0;
let feedLimit = 10;
let isLoadingFeed = false;
let hasMorePosts = true;
let currentModalPostId = null;
let lastCheck = 0;
let selectedImages = [];
let processedCommentIds = new Set(); // Для предотвращения дублирования комментариев

// === Настройки сжатия изображений ===
const MAX_IMAGE_WIDTH = 1200;
const MAX_IMAGE_HEIGHT = 1200;
const WEBP_QUALITY = 0.8;
const JPEG_QUALITY = 0.85;

// ==================== Lazy Loading ====================
function setupLazyLoading() {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          const src = img.dataset.src;
          if (src && !img.src) {
            img.src = src;
            img.classList.add("loaded");
            observer.unobserve(img);
          }
        }
      });
    },
    { rootMargin: "50px 0px", threshold: 0.01 },
  );
  document
    .querySelectorAll("img[data-src]")
    .forEach((img) => observer.observe(img));
}

// ==================== Polling ====================
function startPolling() {
  if (pollInterval || !window.currentUserId) return;
  const isMobile = window.innerWidth < 768;
  const interval = isMobile ? 10000 : 3000;
  pollInterval = setInterval(async () => {
    if (document.visibilityState === "hidden") return;
    try {
      const response = await fetch(`/api/poll?last_check=${lastCheck}`);
      const data = await response.json();
      lastCheck = data.timestamp;
      if (data.success && data.posts?.length) {
        data.posts.forEach((post) => {
          if (!posts.find((p) => p.id === post.id)) {
            addPostToFeed(post, true);
            posts.unshift(post);
          }
        });
      }
      if (data.likes) data.likes.forEach((like) => updatePostLikes(like));
      if (data.comments?.length)
        data.comments.forEach((comment) => addCommentToPost(comment));
    } catch (e) {}
  }, interval);
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") lastCheck = 0;
  });
}

// ==================== Feed Switching ====================
function switchFeed(type) {
  currentFeedType = type;
  feedOffset = 0;
  hasMorePosts = true;

  document.querySelectorAll(".feed-filter").forEach((btn) => {
    if (btn.dataset.type === type) {
      btn.classList.add("active");
    } else {
      btn.classList.remove("active");
    }
  });

  posts = [];
  const container = document.getElementById("posts-container");
  if (container) {
    container.innerHTML = "";
  }
  loadPosts();
}

// ==================== Load Posts ====================
async function loadPosts(append = false) {
  if (isLoadingFeed || !hasMorePosts) return;
  try {
    isLoadingFeed = true;
    const spinner = document.getElementById("feed-spinner");
    if (spinner) spinner.classList.remove("hidden");

    if (!window.currentUserId) {
      showEmptyStateForGuest();
      return;
    }

    const response = await fetch(
      `/feed/get-posts?type=${currentFeedType}&offset=${feedOffset}&limit=${feedLimit}`,
    );
    if (!response.ok) throw new Error("Network error");

    const result = await response.json();

    const container = document.getElementById("posts-container");
    if (!container) {
      console.warn("Posts container not found");
      return;
    }

    if (result.html !== undefined) {
      if (result.count === 0) {
        hasMorePosts = false;
        const hasPosts = container.querySelectorAll(".post-card").length > 0;
        if (!hasPosts && !append) showEmptyState();
      } else {
        if (append) {
          container.insertAdjacentHTML("beforeend", result.html);
          feedOffset += result.count;
        } else {
          container.innerHTML = result.html;
          feedOffset = result.count;
        }
        initializePosts();
      }
    }
  } catch (error) {
    console.error("Load posts error:", error);
    if (!append) showEmptyState();
  } finally {
    isLoadingFeed = false;
    const spinner = document.getElementById("feed-spinner");
    if (spinner) spinner.classList.add("hidden");
  }
}

function showEmptyStateForGuest() {
  const container = document.getElementById("posts-container");
  if (!container) return;
  const existingGuestNotice = document.querySelector(".guest-notice");
  if (existingGuestNotice) {
    container.innerHTML = "";
    return;
  }
  container.innerHTML = `
        <div class="guest-notice" style="text-align: center; padding: var(--space-10);">
            <div class="guest-notice-icon" style="font-size: 48px; margin-bottom: var(--space-4);">🔒</div>
            <p style="font-size: var(--text-lg); color: var(--text-primary); margin-bottom: var(--space-4);">${window.t('login_to_see_feed')}</p>
            <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">${window.t('publish_likes_friends')}</p>
            <div class="guest-notice-actions" style="display: flex; gap: var(--space-3); justify-content: center;">
                <a href="/login" class="btn btn-primary">${window.t('login')}</a>
                <a href="/register" class="btn btn-secondary">${window.t('register')}</a>
            </div>
        </div>
    `;
  const spinner = document.getElementById("feed-spinner");
  if (spinner) spinner.classList.add("hidden");
  const sentinel = document.getElementById("feed-sentinel");
  if (sentinel) sentinel.style.display = "none";
}

function showEmptyState() {
  const container = document.getElementById("posts-container");
  if (!container) return;
  if (container.querySelectorAll(".post-card").length > 0) return;
  container.innerHTML =
    '<div class="empty-state"><div class="empty-icon">📝</div><p>' + window.t('no_posts') + '</p></div>';
}

// ==================== Add Post to Feed ====================
async function addPostToFeed(post, prepend = false) {
  try {
    const response = await fetch(`/post/get-html?id=${post.id}`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const html = await response.text();
    const container = document.getElementById("posts-container");
    const emptyState = container.querySelector(".empty-state");
    if (emptyState) emptyState.remove();
    if (prepend) {
      container.insertAdjacentHTML("afterbegin", html);
    } else {
      container.insertAdjacentHTML("beforeend", html);
    }
    const newCards = container.querySelectorAll(
      ".post-card:not([data-handlers-initialized])",
    );
    newCards.forEach((card) => initializeSinglePost(card));
  } catch (error) {
    location.reload();
  }
}

// ==================== Single Post Initialization ====================
function initializeSinglePost(card) {
  if (card.dataset.handlersInitialized === "true") return;
  card.dataset.handlersInitialized = "true";
  const postId = card.dataset.postId;
  if (!postId) return;
  
  // Помечаем все существующие комментарии как обработанные
  card.querySelectorAll(".comment-item, .comment").forEach(comment => {
    const commentId = comment.dataset?.commentId;
    if (commentId) processedCommentIds.add(parseInt(commentId));
  });
  
  const likeBtn = card.querySelector(".btn-like");
  if (likeBtn) {
    likeBtn.removeEventListener("click", () => handleLike(postId));
    likeBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      handleLike(postId);
    });
  }
  const saveBtn = card.querySelector(".btn-save");
  if (saveBtn) {
    saveBtn.removeEventListener("click", () => handleSave(postId));
    saveBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      handleSave(postId);
    });
  }
  const repostBtn = card.querySelector(".btn-repost");
  if (repostBtn) {
    repostBtn.removeEventListener("click", () => toggleRepost(postId));
    repostBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleRepost(postId);
    });
  }
  const deleteBtn = card.querySelector(".btn-delete-post");
  if (deleteBtn) {
    deleteBtn.removeEventListener("click", () => window.deletePost?.(postId));
    deleteBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      window.deletePost?.(postId);
    });
  }
  const voteBtn = card.querySelector(".btn-vote");
  if (voteBtn) {
    const pollId = card.querySelector(".poll-container")?.dataset.pollId;
    if (pollId) {
      voteBtn.removeEventListener("click", () =>
        submitPollVote(pollId, postId),
      );
      voteBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        submitPollVote(pollId, postId);
      });
    }
  }
  const cancelBtn = card.querySelector(".btn-cancel-vote");
  if (cancelBtn) {
    const pollId = card.querySelector(".poll-container")?.dataset.pollId;
    if (pollId) {
      cancelBtn.removeEventListener("click", () => cancelPollVote(pollId));
      cancelBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        cancelPollVote(pollId);
      });
    }
  }
}

function initializePosts() {
  document
    .querySelectorAll(".post-card")
    .forEach((card) => initializeSinglePost(card));
  setupLazyLoading();
}

// ==================== Infinite Scroll ====================
function setupInfiniteScroll() {
  const sentinel = document.getElementById("feed-sentinel");
  if (sentinel && "IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && !isLoadingFeed && hasMorePosts)
          loadPosts(true);
      },
      { rootMargin: "200px" },
    );
    observer.observe(sentinel);
  }
}

// ==================== Post Modal ====================
async function openPostModal(postId) {
  const modal = document.getElementById("post-modal");
  const body = document.getElementById("post-modal-body");
  if (!modal || !body) return;
  modal.classList.remove("hidden");
  modal.classList.add("show");
  body.innerHTML = '<div class="loading-spinner">' + window.t('loading') + '</div>';
  try {
    const response = await fetch(`/post/modal-content?id=${postId}`);
    body.innerHTML = await response.text();
    initModalHandlers(postId);
    await loadComments(postId, "modal-comments-list");
    initCommentHandlers(postId);
    initCommentForm(postId);
  } catch (error) {
    console.error("Error loading post:", error);
    body.innerHTML = '<p class="error-message">' + window.t('post_load_error') + '</p>';
  }
}

function initCommentCounter() {
  const textarea = document.querySelector(
    "#post-modal .comment-form__input, #modal-comment-input",
  );
  const counter = document.querySelector(
    "#post-modal .comment-form__counter, #comment-char-count",
  );
  if (!textarea || !counter) return;
  const updateCounter = () => {
    const len = textarea.value.length;
    counter.textContent = len;
    counter.style.color =
      len > 900 ? "#ef4444" : len > 800 ? "#f59e0b" : "inherit";
    textarea.style.height = "auto";
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + "px";
  };
  textarea.removeEventListener("input", updateCounter);
  textarea.addEventListener("input", updateCounter);
  updateCounter();
}

function closePostModal() {
  const modal = document.getElementById("post-modal");
  if (modal) {
    modal.classList.remove("show");
    modal.classList.add("hidden");
    currentModalPostId = null;
  }
}

function toggleComments(postId) {
  openPostModal(postId);
}

// ==================== Modal Handlers ====================
function initModalHandlers(postId) {
  const likeBtn = document.querySelector("#post-modal .action-btn--like");
  if (likeBtn && !likeBtn.hasAttribute("data-handler-inited")) {
    likeBtn.setAttribute("data-handler-inited", "true");
    likeBtn.onclick = (e) => {
      e.preventDefault();
      handleLike(postId, likeBtn);
    };
  }
  const saveBtn = document.querySelector("#post-modal .action-btn--save");
  if (saveBtn && !saveBtn.hasAttribute("data-handler-inited")) {
    saveBtn.setAttribute("data-handler-inited", "true");
    saveBtn.onclick = (e) => {
      e.preventDefault();
      handleSave(postId);
    };
  }
  const repostBtn = document.querySelector("#post-modal .action-btn--repost");
  if (repostBtn && !repostBtn.hasAttribute("data-handler-inited")) {
    repostBtn.setAttribute("data-handler-inited", "true");
    repostBtn.onclick = (e) => {
      e.preventDefault();
      toggleRepost(postId);
    };
  }
  const deleteBtn = document.querySelector("#post-modal .btn-delete-post");
  if (deleteBtn && !deleteBtn.hasAttribute("data-handler-inited")) {
    deleteBtn.setAttribute("data-handler-inited", "true");
    deleteBtn.onclick = (e) => {
      e.preventDefault();
      if (typeof window.deletePost === "function") window.deletePost(postId);
    };
  }
  const voteBtn = document.querySelector(
    "#post-modal .poll-widget-vote-btn, #post-modal .btn-vote",
  );
  if (voteBtn && !voteBtn.hasAttribute("data-handler-inited")) {
    voteBtn.setAttribute("data-handler-inited", "true");
    const pollId = voteBtn.closest(".poll-widget")?.dataset.pollId;
    if (pollId) {
      voteBtn.onclick = (e) => {
        e.preventDefault();
        submitPollVote(pollId, postId);
      };
    }
  }
  const cancelBtn = document.querySelector(
    "#post-modal .poll-widget-cancel-btn, #post-modal .btn-cancel-vote",
  );
  if (cancelBtn && !cancelBtn.hasAttribute("data-handler-inited")) {
    cancelBtn.setAttribute("data-handler-inited", "true");
    const pollId = cancelBtn.closest(".poll-widget")?.dataset.pollId;
    if (pollId) {
      cancelBtn.onclick = (e) => {
        e.preventDefault();
        cancelPollVote(pollId);
      };
    }
  }
}

// ==================== Update Functions ====================
function updatePostLikes(data) {
  const postEl = document.querySelector(`[data-post-id="${data.post_id}"]`);
  if (!postEl) return;
  const likeBtn = postEl.querySelector(".btn-like");
  const countSpan = likeBtn?.querySelector(".action-count");
  const iconSpan = likeBtn?.querySelector(".action-icon");
  if (data.user_id === window.currentUserId) {
    if (data.action === "like" && !likeBtn?.classList.contains("liked")) {
      likeBtn?.classList.add("liked");
      if (iconSpan) iconSpan.textContent = "❤️";
    } else if (
      data.action === "unlike" &&
      likeBtn?.classList.contains("liked")
    ) {
      likeBtn?.classList.remove("liked");
      if (iconSpan) iconSpan.textContent = "🤍";
    }
  }
  if (countSpan && data.likes_count !== undefined)
    countSpan.textContent = data.likes_count;
}

function addCommentToPost(data) {
  // Проверяем, не обрабатывали ли уже этот комментарий
  if (processedCommentIds.has(data.id)) return;
  
  const postEl = document.querySelector(`[data-post-id="${data.post_id}"]`);
  if (!postEl) return;
  
  // Помечаем комментарий как обработанный
  processedCommentIds.add(data.id);
  
  // Обновляем счётчик в карточке поста (в ленте)
  const countEl = postEl.querySelector(".btn-comment-toggle .action-count");
  if (countEl) {
    const current = parseInt(countEl.textContent) || 0;
    countEl.textContent = current + 1;
  }
}
  
function addCommentToModal(data) {
  const modalCommentList = document.getElementById("modal-comments-list");
  if (!modalCommentList) return;
  
  // Проверяем, не был ли уже добавлен этот комментарий
  const existingComment = modalCommentList.querySelector(`[data-comment-id="${data.id}"]`);
  if (existingComment) return;
  
  // Удаляем пустое состояние если есть
  const emptyState = modalCommentList.querySelector('.empty-comments');
  if (emptyState) emptyState.remove();
  
  const commentHtml = `
    <div class="comment-item" data-comment-id="${data.id}">
      <div class="comment-author">
        <img src="${data.author?.avatar || ''}" class="comment-avatar" alt="">
        <div class="comment-author-info">
          <a href="/profile/view?id=${data.author?.id}" class="comment-author-name">${escapeHtml(data.author?.username || window.t('user'))}</a>
          <span class="comment-time">${data.timeAgo || window.t('just_now')}</span>
        </div>
      </div>
      <div class="comment-content">${escapeHtml(data.content)}</div>
      ${data.canDelete ? `<button class="btn-delete-comment" data-comment-id="${data.id}">🗑️</button>` : ''}
    </div>
  `;
  
  modalCommentList.insertAdjacentHTML('beforeend', commentHtml);
  
  // Инициализируем обработчики для нового комментария
  const deleteBtn = modalCommentList.querySelector(`.btn-delete-comment[data-comment-id="${data.id}"]`);
  if (deleteBtn) {
    deleteBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      if (typeof window.deleteComment === 'function') {
        window.deleteComment(data.id, data.post_id);
      }
    });
  }
}
  
// ==================== Сжатие изображений в WebP ====================
/**
 * Сжимает одно изображение, масштабирует и конвертирует в WebP (или JPEG)
 * @param {File} file
 * @returns {Promise<File>}
 */
async function compressImage(file) {
  // Для GIF и очень маленьких картинок пропускаем сжатие
  if (file.type === "image/gif" || file.size < 100 * 1024) return file;

  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (e) => {
      const img = new Image();
      img.src = e.target.result;
      img.onload = () => {
        let width = img.width;
        let height = img.height;

        // Масштабируем, если превышает лимиты
        if (width > MAX_IMAGE_WIDTH || height > MAX_IMAGE_HEIGHT) {
          const ratio = Math.min(
            MAX_IMAGE_WIDTH / width,
            MAX_IMAGE_HEIGHT / height,
          );
          width = Math.floor(width * ratio);
          height = Math.floor(height * ratio);
        }

        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, width, height);

        // Пробуем сохранить как WebP (если браузер поддерживает)
        let mime = "image/webp";
        let quality = WEBP_QUALITY;
        // Fallback для старых браузеров
        if (!canvas.toBlob || !canvas.toBlob.bind(canvas)) {
          mime = "image/jpeg";
          quality = JPEG_QUALITY;
        }

        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error(window.t('blob_creation_error')));
              return;
            }
            // Сохраняем исходное имя, но меняем расширение на .webp (если надо)
            let newName = file.name;
            if (mime === "image/webp" && !newName.endsWith(".webp")) {
              newName = newName.replace(/\.(jpe?g|png)$/i, ".webp");
            }
            const compressedFile = new File([blob], newName, {
              type: mime,
              lastModified: Date.now(),
            });
            resolve(compressedFile);
          },
          mime,
          quality,
        );
      };
      img.onerror = () => reject(new Error(window.t('image_loading_error')));
    };
    reader.onerror = () => reject(new Error(window.t('file_reading_error')));
  });
}

/**
 * Сжимает массив файлов, обновляет selectedImages и превью
 */
async function compressAndSetImages(files) {
  if (!files.length) return;

  try {
    const compressed = await Promise.all(files.map(compressImage));
    selectedImages = compressed;
    updateImagePreviews(selectedImages);
  } catch (err) {
    console.error("Ошибка сжатия:", err);
    selectedImages = files;
    updateImagePreviews(selectedImages);
  }
  updateTotalSizeWarning();
  updatePublishButton();
}

// ==================== Image Preview & Size Checking ====================
function updateTotalSizeWarning() {
  const totalSize = selectedImages.reduce((sum, file) => sum + file.size, 0);
  const totalSizeMB = (totalSize / (1024 * 1024)).toFixed(2);
  const MAX_TOTAL_SIZE = 7 * 1024 * 1024; // 7 MB – после сжатия обычно укладывается
  let warningEl = document.getElementById("total-size-warning");
  if (!warningEl) {
    const container = document.getElementById("image-preview-container");
    if (container) {
      warningEl = document.createElement("div");
      warningEl.id = "total-size-warning";
      warningEl.style.fontSize = "12px";
      warningEl.style.marginTop = "8px";
      container.parentNode.insertBefore(warningEl, container.nextSibling);
    } else {
      return;
    }
  }
  if (selectedImages.length > 0 && totalSize > MAX_TOTAL_SIZE) {
    warningEl.style.display = "block";
    warningEl.innerHTML = '⚠️ ' + window.t('image_size_warning', { size: totalSizeMB, limit: MAX_TOTAL_SIZE / (1024 * 1024) });
    warningEl.style.color = "#ef4444";
  } else {
    warningEl.style.display = "none";
  }
  updatePublishButton();
}

function updateImagePreviews(files) {
  const container = document.getElementById("image-preview-container");
  const previews = document.getElementById("image-previews");
  if (!container || !previews) return;
  previews.innerHTML = "";
  if (!files.length) {
    container.style.display = "none";
    updateTotalSizeWarning();
    return;
  }
  container.style.display = "block";
  files.forEach((file, idx) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      const div = document.createElement("div");
      div.className = "image-preview-item";
      div.innerHTML = `<img src="${e.target.result}" class="image-preview-thumb"><button type="button" class="btn-remove-preview" onclick="removeImagePreview(${idx})">✕</button>`;
      previews.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
  updateTotalSizeWarning();
}

function removeImagePreview(index) {
  selectedImages.splice(index, 1);
  updateImagePreviews(selectedImages);
  const imageInput = document.getElementById("post-image");
  if (imageInput) imageInput.value = "";
  updatePublishButton();
}

function removeSelectedImages() {
  selectedImages = [];
  const container = document.getElementById("image-preview-container");
  if (container) container.style.display = "none";
  const previews = document.getElementById("image-previews");
  if (previews) previews.innerHTML = "";
  const imageInput = document.getElementById("post-image");
  if (imageInput) imageInput.value = "";
  updateTotalSizeWarning();
  updatePublishButton();
}

// ==================== Poll Form Functions ====================
function addPoll() {
  const container = document.getElementById("poll-container");
  const btn = document.querySelector(".btn-add-poll");
  if (!container || !btn) return;
  if (container.style.display === "none") {
    container.style.display = "block";
    btn.classList.add("active");
    btn.textContent = "📊";
    // Добавляем обработчики на существующие инпуты
    document.querySelectorAll(".option-input").forEach(input => {
      input.addEventListener("input", updatePublishButton);
    });
  } else {
    removePoll();
  }
  updatePublishButton();
}

function removePoll() {
  const container = document.getElementById("poll-container");
  const btn = document.querySelector(".btn-add-poll");
  if (container) container.style.display = "none";
  if (btn) {
    btn.classList.remove("active");
    btn.textContent = "📊";
  }
  const question = document.getElementById("poll-question");
  if (question) {
    question.value = "";
    question.removeEventListener("input", updatePublishButton);
  }
  const multiple = document.getElementById("poll-multiple");
  if (multiple) multiple.checked = false;
  const options = document.getElementById("poll-options");
  if (options) {
    options.innerHTML = `<div class="poll-option-input"><input type="text" class="option-input" placeholder="${window.t('poll_option_1')}"><button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button></div>
                            <div class="poll-option-input"><input type="text" class="option-input" placeholder="${window.t('poll_option_2')}"><button type="button" class="btn-remove-option" onclick="removeOption(this)">✕</button></div>`;
    // Добавляем обработчики на новые инпуты
    options.querySelectorAll(".option-input").forEach(input => {
      input.addEventListener("input", updatePublishButton);
    });
  }
  updatePublishButton();
}

function addPollOption() {
  const options = document.getElementById("poll-options");
  if (!options) return;
  const current = options.querySelectorAll(".poll-option-input").length;
  if (current >= 10) {
    showNotification(window.t('max_poll_options'), "error");
    return;
  }
  const div = document.createElement("div");
  div.className = "poll-option-input";
  const input = document.createElement("input");
  input.type = "text";
  input.className = "option-input";
  input.placeholder = window.t('poll_option_n', { n: current + 1 });
  const removeBtn = document.createElement("button");
  removeBtn.type = "button";
  removeBtn.className = "btn-remove-option";
  removeBtn.textContent = "✕";
  removeBtn.onclick = function() { removeOption(this); };
  div.appendChild(input);
  div.appendChild(removeBtn);
  options.appendChild(div);
  input.addEventListener("input", updatePublishButton);
  updatePublishButton();
}

function removeOption(btn) {
  const options = document.getElementById("poll-options");
  if (!options) return;
  if (options.querySelectorAll(".poll-option-input").length <= 2) {
    showNotification(window.t('min_poll_options'), "error");
    return;
  }
  btn.closest(".poll-option-input").remove();
  updatePublishButton();
}

function getPollData() {
  const container = document.getElementById("poll-container");
  if (!container || container.style.display === "none") return null;
  const question = document.getElementById("poll-question")?.value.trim();
  if (!question) return null;
  const multiple = document.getElementById("poll-multiple")?.checked || false;
  const options = [];
  document.querySelectorAll(".option-input").forEach((input) => {
    const val = input.value.trim();
    if (val) options.push(val);
  });
  if (options.length < 2) return null;
  return { question, multiple_votes: multiple, options };
}

function updatePublishButton() {
  const ta = document.getElementById("post-content");
  const btn = document.getElementById("btn-publish");
  if (!ta || !btn) return;
  const hasText = ta.value.trim().length > 0;
  const hasImages = selectedImages.length > 0;
  const pollContainer = document.getElementById("poll-container");
  const hasPollUI = pollContainer && pollContainer.style.display !== "none";
  
  // Проверяем опрос: если UI показан, проверяем вопрос и варианты
  let hasValidPoll = false;
  if (hasPollUI) {
    const question = document.getElementById("poll-question")?.value.trim();
    const options = [];
    document.querySelectorAll(".option-input").forEach((input) => {
      const val = input.value.trim();
      if (val) options.push(val);
    });
    hasValidPoll = question && options.length >= 2;
  }
  
  // Кнопка активна если есть текст, изображения, или заполненный опрос
  btn.disabled = !(hasText || hasImages || hasValidPoll);
}

// ==================== Publish Post (единая функция) ====================
async function publishPost() {
  const textarea = document.getElementById("post-content");
  const content = textarea?.value.trim() || "";
  const images = selectedImages;
  const pollData = getPollData();

  if (!content && images.length === 0 && !pollData) {
    showNotification(window.t('fill_at_least_one_field'), "error");
    return;
  }

  // Проверка размера (после сжатия они уже должны быть маленькими, но на всякий случай)
  const MAX_TOTAL_SIZE = 7 * 1024 * 1024;
  const totalSize = images.reduce((sum, img) => sum + img.size, 0);
  if (images.length > 0 && totalSize > MAX_TOTAL_SIZE) {
    showNotification(
      window.t('image_total_size_exceeds', { size: (totalSize / 1024 / 1024).toFixed(2) }),
      "error",
    );
    return;
  }

  const btnPublish = document.getElementById("btn-publish");
  if (btnPublish) {
    btnPublish.disabled = true;
    btnPublish.textContent = window.t('publishing');
  }

  try {
    const formData = new FormData();
    const csrfToken = document.querySelector(
      'meta[name="csrf-token"]',
    )?.content;

    formData.append("content", content);
    images.forEach((img) => formData.append("images[]", img));
    if (pollData) {
      formData.append("poll_question", pollData.question);
      formData.append("poll_multiple", pollData.multiple_votes ? "1" : "0");
      pollData.options.forEach((opt) => formData.append("poll_options[]", opt));
    }

    const response = await fetch("/api/post/create", {
      method: "POST",
      headers: csrfToken ? { "X-CSRF-Token": csrfToken } : {},
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      textarea.value = "";
      const charCount = document.getElementById("char-count");
      if (charCount) charCount.textContent = "0/5000";
      removeSelectedImages();
      removePoll();
      showNotification(window.t('post_published'), "success");

      if (data.post && typeof addPostToFeed === "function") {
        addPostToFeed(data.post, true);
      } else if (typeof loadPosts === "function") {
        loadPosts(false);
      } else {
        location.reload();
      }
    } else {
      showNotification(data.error || window.t('publish_error'), "error");
    }
  } catch (error) {
    console.error("Publish error:", error);
    showNotification(window.t('publish_error'), "error");
  } finally {
    if (btnPublish) {
      btnPublish.disabled = false;
      btnPublish.textContent = window.t('publish_button');
    }
  }
}

// ==================== Image Select & Form Init ====================
async function handleImageSelect(e) {
  const files = Array.from(e.target.files);
  if (!files.length) return;
  await compressAndSetImages(files);
  e.target.value = ""; // чтобы можно было выбрать те же файлы повторно
}

function initPostForm() {
  const form = document.getElementById("create-post-form");
  if (form) {
    // Удаляем старый обработчик submit, если был
    form.removeEventListener("submit", publishPost);
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      publishPost();
    });
  }
  const textarea = document.getElementById("post-content");
  const imageInput = document.getElementById("post-image");
  const btnPublish = document.getElementById("btn-publish");
  const pollQuestion = document.getElementById("poll-question");
  if (textarea) {
    textarea.addEventListener("input", updatePublishButton);
    const charCount = document.getElementById("char-count");
      if (charCount) {
        const updateCharCount = () => {
          const len = textarea.value.length;
          charCount.textContent = `${len}/5000`;
          charCount.style.color =
            len > 4900 ? "#ef4444" : len > 4800 ? "#f59e0b" : "inherit";
        };
        textarea.addEventListener("input", updateCharCount);
        updateCharCount();
      }
  }
  if (imageInput) {
    imageInput.removeEventListener("change", handleImageSelect);
    imageInput.addEventListener("change", handleImageSelect);
  }
  if (pollQuestion) {
    pollQuestion.addEventListener("input", updatePublishButton);
  }
  if (btnPublish) btnPublish.disabled = true;
  setTimeout(updatePublishButton, 100);
}

// ==================== Exports ====================
window.switchFeed = switchFeed;
window.loadPosts = loadPosts;
window.openPostModal = openPostModal;
window.closePostModal = closePostModal;
window.toggleComments = toggleComments;
window.addPoll = addPoll;
window.removePoll = removePoll;
window.addPollOption = addPollOption;
window.removeOption = removeOption;
window.removeImagePreview = removeImagePreview;
window.removeSelectedImages = removeSelectedImages;
window.removeAllSelectedImages = removeSelectedImages;
window.getPollData = getPollData;
window.updatePublishButton = updatePublishButton;
window.publishPost = publishPost;
window.initPostForm = initPostForm;
window.initModalHandlers = initModalHandlers;
window.toggleComments = openPostModal;
window.selectedImages = selectedImages;
window.addCommentToPost = addCommentToPost;
window.addCommentToModal = addCommentToModal;

// ==================== DOM Ready ====================
document.addEventListener("DOMContentLoaded", () => {
  const postsContainer = document.getElementById("posts-container");
  if (!postsContainer) {
    console.log("Feed container not found, skipping feed initialization");
    return;
  }
  const urlParams = new URLSearchParams(window.location.search);
  currentFeedType = urlParams.get("type") || "following";
  document.querySelectorAll(".feed-filter").forEach((btn) => {
    btn.removeEventListener("click", handleFilterClick);
    btn.addEventListener("click", handleFilterClick);
    if (btn.dataset.type === currentFeedType) btn.classList.add("active");
  });
  loadPosts(false);
  setupInfiniteScroll();
  initializePosts();
  startPolling();
  initPostForm();
});

function handleFilterClick(e) {
  const btn = e.currentTarget;
  const type = btn.dataset.type;
  switchFeed(type);
}