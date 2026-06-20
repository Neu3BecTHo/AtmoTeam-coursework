// profile.js - все функции для профиля + сжатие изображений

// ==================== Сжатие изображений (общая функция) ====================
const PROFILE_IMAGE_MAX_WIDTH = 1200;
const PROFILE_IMAGE_MAX_HEIGHT = 1200;
const PROFILE_IMAGE_QUALITY = 0.85;
const AVATAR_SIZE = 400; // итоговый размер аватара

/**
 * Сжимает изображение (для постов)
 */
async function compressProfileImage(file) {
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
        if (
          width > PROFILE_IMAGE_MAX_WIDTH ||
          height > PROFILE_IMAGE_MAX_HEIGHT
        ) {
          const ratio = Math.min(
            PROFILE_IMAGE_MAX_WIDTH / width,
            PROFILE_IMAGE_MAX_HEIGHT / height,
          );
          width = Math.floor(width * ratio);
          height = Math.floor(height * ratio);
        }
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, width, height);
        let mime = "image/webp";
        let quality = PROFILE_IMAGE_QUALITY;
        if (!canvas.toBlob) {
          mime = "image/jpeg";
          quality = 0.9;
        }
        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error(window.t('blob_creation_error')));
              return;
            }
            let newName = file.name;
            if (mime === "image/webp" && !newName.endsWith(".webp")) {
              newName = newName.replace(/\.(jpe?g|png)$/i, ".webp");
            }
            const compressed = new File([blob], newName, {
              type: mime,
              lastModified: Date.now(),
            });
            console.log(
              `Сжатие для профиля: ${(file.size / 1024).toFixed(1)} KB → ${(compressed.size / 1024).toFixed(1)} KB`,
            );
            resolve(compressed);
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
 * Сжимает изображение для аватара (до 400x400, WebP/JPEG)
 */
async function compressAvatar(file) {
  if (file.type === "image/gif" || file.size < 50 * 1024) return file;

  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (e) => {
      const img = new Image();
      img.src = e.target.result;
      img.onload = () => {
        let width = img.width;
        let height = img.height;
        if (width > AVATAR_SIZE || height > AVATAR_SIZE) {
          const ratio = Math.min(AVATAR_SIZE / width, AVATAR_SIZE / height);
          width = Math.floor(width * ratio);
          height = Math.floor(height * ratio);
        }
        const canvas = document.createElement("canvas");
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0, width, height);
        let mime = "image/webp";
        let quality = 0.9;
        if (!canvas.toBlob) {
          mime = "image/jpeg";
          quality = 0.92;
        }
        canvas.toBlob(
          (blob) => {
            if (!blob) {
              reject(new Error(window.t('blob_creation_error')));
              return;
            }
            let newName = file.name;
            if (mime === "image/webp" && !newName.endsWith(".webp")) {
              newName = newName.replace(/\.(jpe?g|png)$/i, ".webp");
            }
            const compressed = new File([blob], newName, {
              type: mime,
              lastModified: Date.now(),
            });
            console.log(
              `Сжатие аватара: ${(file.size / 1024).toFixed(1)} KB → ${(compressed.size / 1024).toFixed(1)} KB`,
            );
            resolve(compressed);
          },
          mime,
          quality,
        );
      };
      img.onerror = () =>
        reject(new Error(window.t('image_loading_error')));
    };
    reader.onerror = () => reject(new Error(window.t('file_reading_error')));
  });
}

// ==================== Create Post Form Handler (с сжатием) ====================
function initProfilePostForm() {
  const form = document.getElementById("create-post-form");
  if (!form) return;
  form.removeEventListener("submit", handleProfilePostSubmit);
  form.addEventListener("submit", handleProfilePostSubmit);
}

async function handleProfilePostSubmit(e) {
  e.preventDefault();

  const content = document.getElementById("post-content")?.value.trim() || "";
  const imageInput = document.getElementById("post-image");
  const rawImages = imageInput ? Array.from(imageInput.files) : [];

  const pollQuestion = document.getElementById("poll-question")?.value;
  const pollMultiple = document.getElementById("poll-multiple")?.checked
    ? 1
    : 0;
  const pollOptions = [];
  document.querySelectorAll(".option-input").forEach((opt) => {
    const val = opt.value.trim();
    if (val) pollOptions.push(val);
  });

  if (!content && rawImages.length === 0 && !pollQuestion) {
    showNotification(window.t('fill_at_least_one_field_short'), "error");
    return;
  }

  const btnPublish = document.getElementById("btn-publish");
  if (btnPublish) {
    btnPublish.disabled = true;
    btnPublish.textContent = window.t('publishing');
  }

  // Сжатие изображений
  let compressedImages = [];
  if (rawImages.length) {
    showNotification(window.t('compressing_images'), "info");
    try {
      compressedImages = await Promise.all(rawImages.map(compressProfileImage));
    } catch (err) {
      console.error("Ошибка сжатия:", err);
      showNotification(window.t('compression_failed_originals'), "error");
      compressedImages = rawImages;
    }
  }

  const formData = new FormData();
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrfToken) formData.append("_csrf", csrfToken);
  formData.append("content", content);
  compressedImages.forEach((img) => formData.append("images[]", img));

  if (pollQuestion && pollOptions.length >= 2) {
    formData.append("poll_question", pollQuestion);
    formData.append("poll_multiple", pollMultiple);
    pollOptions.forEach((opt) => formData.append("poll_options[]", opt));
  }

  try {
    const response = await fetch("/api/post/create", {
      method: "POST",
      body: formData,
    });
    const data = await response.json();

    if (data.success) {
      document.getElementById("post-content").value = "";
      const charCount = document.getElementById("char-count");
      if (charCount) charCount.textContent = "0/2000";

      if (imageInput) imageInput.value = "";
      if (typeof window.removeSelectedImages === "function")
        window.removeSelectedImages();
      if (typeof window.removePoll === "function") window.removePoll();

      showNotification(window.t('post_published'), "success");

      if (typeof window.loadMoreProfilePosts === "function") {
        window.profilePostsOffset = 0;
        window.profileHasMorePosts = true;
        await window.loadMoreProfilePosts();
      } else if (typeof window.loadInitialProfilePosts === "function") {
        await window.loadInitialProfilePosts();
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

// ==================== Comments ====================
async function submitModalComment(postId) {
  const input = document.getElementById("modal-comment-input");
  const content = input?.value.trim();
  if (!content) {
    showNotification(window.t('write_comment'), "error");
    return;
  }
  if (!window.currentUserId) {
    showNotification(window.t('login_to_comment'), "error");
    return;
  }
  try {
    const response = await postWithCsrf("/api/comment/create", {
      post_id: postId,
      content,
    });
    const result = await response.json();
    if (result.success) {
      input.value = "";
      await loadComments(postId, "modal-comments-list");
      const postEl = document.querySelector(`[data-post-id="${postId}"]`);
      if (postEl) {
        const commentsCount = postEl.querySelector(".comments-count");
        if (commentsCount) {
          const match = commentsCount.textContent.match(/(\d+)/);
          if (match)
            commentsCount.textContent = window.t('comments_count', { n: parseInt(match[1]) + 1 });
        }
      }
    } else {
      showNotification(result.error || window.t('comment_send_error'), "error");
    }
  } catch (error) {
    showNotification(window.t('comment_send_error'), "error");
  }
}

// ==================== Avatar Cropper (с сжатием) ====================
let cropImage = null;
let cropScale = 1;
let cropOffsetX = 0;
let cropOffsetY = 0;
let isDragging = false;
let dragStartX = 0,
  dragStartY = 0;

async function openAvatarCropper(input) {
  console.log("openAvatarCropper called", input);
  if (!input.files || !input.files[0]) {
    console.log("No file selected");
    return;
  }

  const rawFile = input.files[0];
  console.log("Raw file:", rawFile.name, rawFile.size);

  // Сжимаем выбранный файл перед кропом
  let compressedFile;
  try {
    showNotification(window.t('compressing_avatar'), "info");
    compressedFile = await compressAvatar(rawFile);
  } catch (err) {
    console.error("Avatar compression failed:", err);
    showNotification(window.t('compression_failed_original'), "error");
    compressedFile = rawFile;
  }

  const reader = new FileReader();
  reader.onload = function (e) {
    cropImage = new Image();
    cropImage.onload = function () {
      console.log("Image loaded for crop:", cropImage.width, cropImage.height);
      cropScale = 1;
      cropOffsetX = 0;
      cropOffsetY = 0;
      renderCropCanvas();
      const modal = document.getElementById("avatar-crop-modal");
      if (modal) {
        modal.classList.add("show");
      } else {
        console.error("Crop modal not found");
      }
      setupCropEvents();
    };
    cropImage.onerror = function () {
      console.error("Image failed to load");
      showNotification(window.t('image_load_error'), "error");
    };
    cropImage.src = e.target.result;
  };
  reader.onerror = function () {
    console.error("FileReader error");
    showNotification(window.t('file_reading_error'), "error");
  };
  reader.readAsDataURL(compressedFile);
}

function closeAvatarCropper() {
  const modal = document.getElementById("avatar-crop-modal");
  if (modal) modal.classList.remove("show");
  cropImage = null;
  const input = document.getElementById("avatar-input");
  if (input) input.value = "";
}

function renderCropCanvas() {
  const canvas = document.getElementById("crop-canvas");
  if (!canvas || !cropImage) return;
  const ctx = canvas.getContext("2d");
  const size = 320;
  ctx.clearRect(0, 0, size, size);
  ctx.fillStyle = "#1a1a2e";
  ctx.fillRect(0, 0, size, size);
  const imgAspect = cropImage.width / cropImage.height;
  let drawWidth, drawHeight;
  if (imgAspect > 1) {
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
    drawHeight,
  );
  ctx.beginPath();
  ctx.arc(centerX, centerY, size / 2 - 2, 0, Math.PI * 2);
  ctx.strokeStyle = "rgba(59, 130, 246, 0.8)";
  ctx.lineWidth = 3;
  ctx.stroke();
  ctx.beginPath();
  ctx.rect(0, 0, size, size);
  ctx.arc(centerX, centerY, size / 2 - 2, 0, Math.PI * 2, true);
  ctx.fillStyle = "rgba(0, 0, 0, 0.5)";
  ctx.fill();
}

function setupCropEvents() {
  const canvas = document.getElementById("crop-canvas");
  const scaleSlider = document.getElementById("crop-scale");
  if (!canvas || !scaleSlider) return;
  scaleSlider.addEventListener("input", function () {
    cropScale = parseFloat(this.value);
    renderCropCanvas();
  });
  const startDrag = (clientX, clientY) => {
    isDragging = true;
    dragStartX = clientX - cropOffsetX;
    dragStartY = clientY - cropOffsetY;
  };
  const onDrag = (clientX, clientY) => {
    if (!isDragging) return;
    cropOffsetX = clientX - dragStartX;
    cropOffsetY = clientY - dragStartY;
    renderCropCanvas();
  };
  canvas.addEventListener("mousedown", (e) => startDrag(e.clientX, e.clientY));
  canvas.addEventListener("touchstart", (e) => {
    if (e.touches.length === 1)
      startDrag(e.touches[0].clientX, e.touches[0].clientY);
  });
  window.addEventListener("mousemove", (e) => onDrag(e.clientX, e.clientY));
  window.addEventListener("touchmove", (e) => {
    if (e.touches.length === 1)
      onDrag(e.touches[0].clientX, e.touches[0].clientY);
  });
  window.addEventListener("mouseup", () => (isDragging = false));
  window.addEventListener("touchend", () => (isDragging = false));
}

function applyAvatarCrop() {
  const canvas = document.getElementById("crop-canvas");
  if (!canvas || !cropImage) return;
  const finalCanvas = document.createElement("canvas");
  finalCanvas.width = AVATAR_SIZE;
  finalCanvas.height = AVATAR_SIZE;
  const ctx = finalCanvas.getContext("2d");
  const size = AVATAR_SIZE;
  const imgAspect = cropImage.width / cropImage.height;
  let drawWidth, drawHeight;
  if (imgAspect > 1) {
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
    drawHeight,
  );
  ctx.globalCompositeOperation = "destination-in";
  ctx.beginPath();
  ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
  ctx.fill();
  ctx.globalCompositeOperation = "source-over";

  // Итоговый аватар – сжатый PNG (или WebP)
  const finalDataURL = finalCanvas.toDataURL("image/png");
  const preview = document.getElementById("avatar-preview");
  if (preview) {
    preview.src = finalDataURL;
    preview.style.border = "2px solid var(--primary-500)";
  }
  const hiddenInput = document.getElementById("cropped-avatar-input");
  if (hiddenInput) hiddenInput.value = finalDataURL;
  closeAvatarCropper();
}

// ==================== Profile Posts Loading with Infinite Scroll ====================
let profilePostsOffset = 0;
let profileHasMorePosts = true;
let profileIsLoading = false;
let profileObserver = null;

async function loadInitialProfilePosts() {
  const userId = window.profileUserId;
  const grid = document.getElementById("user-posts");
  if (!userId || !grid) {
    console.error("loadInitialProfilePosts: missing userId or grid");
    return;
  }
  profilePostsOffset = 0;
  profileHasMorePosts = true;
  profileIsLoading = false;
  await loadMoreProfilePosts();
}

async function loadMoreProfilePosts() {
  if (profileIsLoading || !profileHasMorePosts) return;
  profileIsLoading = true;
  const spinner = document.getElementById("load-more-spinner");
  const sentinel = document.getElementById("load-more-sentinel");
  if (spinner) spinner.classList.remove("hidden");

  const userId = window.profileUserId;
  const offset = profilePostsOffset;

  try {
    const url = `/profile/${userId}/posts?offset=${offset}`;
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const result = await response.json();

    const container = document.getElementById("user-posts");
    if (!container) return;

    if (result.html && result.count > 0) {
      if (offset === 0) {
        container.innerHTML = result.html;
      } else {
        container.insertAdjacentHTML("beforeend", result.html);
      }
      profilePostsOffset = offset + result.count;
      profileHasMorePosts = result.count === 3;

      initProfilePostHandlers();
    } else if (offset === 0) {
      container.innerHTML =
        '<div class="empty-profile"><div class="empty-icon">📝</div><p>' + window.t('no_posts_yet') + '</p></div>';
      profileHasMorePosts = false;
    } else {
      profileHasMorePosts = false;
    }

    if (!profileHasMorePosts && sentinel) sentinel.style.display = "none";
  } catch (error) {
    console.error("Error loading profile posts:", error);
    if (offset === 0) {
      const container = document.getElementById("user-posts");
      if (container) {
        container.innerHTML =
          '<div class="empty-profile"><div class="empty-icon">⚠️</div><p>' + window.t('posts_load_error') + '</p></div>';
      }
    }
  } finally {
    profileIsLoading = false;
    if (spinner) spinner.classList.add("hidden");
  }
}

function initProfilePostHandlers() {
  const containers = ["#user-posts", "#user-reposts", "#user-saved"];
  containers.forEach((selector) => {
    document.querySelectorAll(`${selector} .post-card`).forEach((card) => {
      if (card.dataset.handlersInitialized === "true") return;
      card.dataset.handlersInitialized = "true";

      const postId = card.dataset.postId;
      if (!postId) return;

      const likeBtn = card.querySelector(".btn-like, .post-action.btn-like");
      if (likeBtn) {
        const newLikeBtn = likeBtn.cloneNode(true);
        likeBtn.parentNode.replaceChild(newLikeBtn, likeBtn);
        newLikeBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (typeof window.handleLike === "function")
            window.handleLike(postId, newLikeBtn);
        });
      }

      const saveBtn = card.querySelector(".btn-save, .post-action.btn-save");
      if (saveBtn) {
        const newSaveBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newSaveBtn, saveBtn);
        newSaveBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (typeof window.handleSave === "function")
            window.handleSave(postId);
        });
      }

      const repostBtn = card.querySelector(
        ".btn-repost, .post-action.btn-repost",
      );
      if (repostBtn) {
        const newRepostBtn = repostBtn.cloneNode(true);
        repostBtn.parentNode.replaceChild(newRepostBtn, repostBtn);
        newRepostBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          if (typeof window.toggleRepost === "function")
            window.toggleRepost(postId);
        });
      }

      const voteBtn = card.querySelector(".poll-widget-vote-btn, .btn-vote");
      if (voteBtn && !voteBtn.dataset.handlerAdded) {
        voteBtn.dataset.handlerAdded = "true";
        const pollId = voteBtn.closest(".poll-widget")?.dataset.pollId;
        if (pollId) {
          const newVoteBtn = voteBtn.cloneNode(true);
          voteBtn.parentNode.replaceChild(newVoteBtn, voteBtn);
          newVoteBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.submitPollVote === "function")
              window.submitPollVote(pollId, postId);
          });
        }
      }

      const cancelBtn = card.querySelector(
        ".poll-widget-cancel-btn, .btn-cancel-vote",
      );
      if (cancelBtn && !cancelBtn.dataset.handlerAdded) {
        cancelBtn.dataset.handlerAdded = "true";
        const pollId = cancelBtn.closest(".poll-widget")?.dataset.pollId;
        if (pollId) {
          const newCancelBtn = cancelBtn.cloneNode(true);
          cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
          newCancelBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.cancelPollVote === "function")
              window.cancelPollVote(pollId);
          });
        }
      }
    });
  });
}

// ==================== Follow Functions ====================
async function handleFollowClick(e) {
  e.preventDefault();
  e.stopPropagation();
  const btn = e.currentTarget;
  const userId = btn.dataset.userId;
  const username = btn.dataset.username;
  const isFollowing = btn.classList.contains("following");

  btn.disabled = true;
  btn.style.opacity = "0.6";

  try {
    const response = await fetch(
      `/profile/${userId}/${isFollowing ? "unfollow" : "follow"}`,
    );
    const result = await response.json();

    if (result.success) {
      btn.classList.toggle("following");
      const iconSpan = btn.querySelector(".btn-icon");
      const textSpan = btn.querySelector(".btn-text");
      if (iconSpan) iconSpan.textContent = result.following ? "🔓" : "🔔";
      if (textSpan)
        textSpan.textContent = result.following ? window.t('unfollow') : window.t('follow');

      const followersCount = document.getElementById("followers-count");
      if (followersCount) followersCount.textContent = result.followers_count;

      showNotification(
        result.following
          ? window.t('followed_user', { username })
          : window.t('unfollowed_user', { username }),
        "success",
      );
    } else {
      showNotification(result.error || window.t('error'), "error");
    }
  } catch (error) {
    console.error("Follow error:", error);
    showNotification(window.t('network_error'), "error");
  } finally {
    btn.disabled = false;
    btn.style.opacity = "";
  }
}

// ==================== Block Functions ====================
async function handleBlockClick(e) {
  e.preventDefault();
  e.stopPropagation();

  const btn = e.currentTarget;
  const userId = btn.dataset.userId;
  const username = btn.dataset.username;
  const isBlocked = btn.classList.contains("unblock");

  const action = isBlocked ? "unblock" : "block";
  const confirmMessage = isBlocked
    ? window.t('unblock_user_question', { username })
    : window.t('block_user_question', { username });
  const successMessage = isBlocked
    ? window.t('user_unblocked', { username })
    : window.t('user_blocked', { username });

  showDeleteModal(confirmMessage, async () => {
    try {
      const csrfToken = document.querySelector(
        'meta[name="csrf-token"]',
      )?.content;
      const headers = csrfToken
        ? { "X-CSRF-Token": csrfToken, "Content-Type": "application/json" }
        : { "Content-Type": "application/json" };
      const response = await fetch(`/api/profile/${action}`, {
        method: "POST",
        headers: headers,
        body: JSON.stringify({ user_id: userId }),
      });
      const result = await response.json();

      if (result.success) {
        if (action === "block") {
          btn.textContent = window.t('unblock_button');
          btn.classList.add("unblock");
        } else {
          btn.textContent = window.t('block_button');
          btn.classList.remove("unblock");
        }
        showNotification(successMessage, "success");
      } else {
        showNotification(result.error || window.t('error'), "error");
      }
    } catch (error) {
      console.error("Block error:", error);
      showNotification(window.t('network_error'), "error");
    }
  });
}

let blockTargetUserId = null;

function showBlockModal(userId, username) {
  blockTargetUserId = userId;
  const modal = document.getElementById("block-modal");
  const nameEl = document.getElementById("block-modal-username");
  if (nameEl) nameEl.textContent = username;
  if (modal) {
    modal.classList.remove("hidden");
    modal.classList.add("show");
  }
  const confirmBtn = document.getElementById("block-confirm-btn");
  if (confirmBtn)
    confirmBtn.onclick = () =>
      blockTargetUserId && doBlockUser(blockTargetUserId);
}

function hideBlockModal() {
  const modal = document.getElementById("block-modal");
  if (modal) {
    modal.classList.add("hidden");
    modal.classList.remove("show");
  }
  blockTargetUserId = null;
}

async function doBlockUser(userId) {
  hideBlockModal();
  try {
    const csrfToken = document.querySelector(
      'meta[name="csrf-token"]',
    )?.content;
    const headers = csrfToken ? { "X-CSRF-Token": csrfToken } : {};
    const formData = new FormData();
    formData.append("user_id", userId);
    const response = await fetch("/api/profile/block", {
      method: "POST",
      headers,
      body: formData,
    });
    const result = await response.json();
    if (result.success) {
      showNotification(window.t('block_user_success'), "success");
      updateBlockButton(userId, true);
    } else {
      showNotification(result.error || window.t('block_error'), "error");
    }
  } catch (error) {
    console.error("Error blocking user:", error);
    showNotification(window.t('block_error'), "error");
  }
}

async function unblockUser(userId) {
  try {
    const csrfToken = document.querySelector(
      'meta[name="csrf-token"]',
    )?.content;
    const headers = csrfToken ? { "X-CSRF-Token": csrfToken } : {};
    const formData = new FormData();
    formData.append("user_id", userId);
    const response = await fetch("/api/profile/unblock", {
      method: "POST",
      headers,
      body: formData,
    });
    const result = await response.json();
    if (result.success) {
      showNotification(window.t('unblock_user_success'), "success");
      updateBlockButton(userId, false);
    } else {
      showNotification(result.error || window.t('unblock_error'), "error");
    }
  } catch (error) {
    console.error("Error unblocking user:", error);
    showNotification(window.t('unblock_error'), "error");
  }
}

function updateBlockButton(userId, isBlocked) {
  const btn = document.getElementById("block-btn");
  if (!btn) return;
  const username = btn.dataset.username || "";
  if (isBlocked) {
    btn.textContent = window.t('unblock_button');
    btn.onclick = () => unblockUser(userId);
  } else {
    btn.textContent = window.t('block_button');
    btn.onclick = () => showBlockModal(userId, username);
  }
}

// ==================== Tab Content Loaders ====================
async function loadRepostsPosts() {
  const container = document.getElementById("user-reposts");
  const sentinel = document.getElementById("load-more-sentinel-reposts");
  if (!container) return;
  const offset = parseInt(sentinel?.dataset.offset || "0");
  try {
    const response = await fetch(`/profile/reposts?offset=${offset}`);
    const result = await response.json();
    if (result.html) {
      if (offset === 0) container.innerHTML = result.html;
      else container.insertAdjacentHTML("beforeend", result.html);
      if (sentinel) sentinel.dataset.offset = offset + result.count;
      if (typeof window.initProfilePostHandlers === "function")
        window.initProfilePostHandlers();
    } else if (offset === 0) {
      container.innerHTML =
        '<div class="empty-profile"><div class="empty-icon">🔄</div><p>' + window.t('no_reposts') + '</p></div>';
    }
  } catch (error) {
    console.error("Error loading reposts:", error);
  }
}

async function loadSavedPosts() {
  const container = document.getElementById("user-saved");
  const sentinel = document.getElementById("load-more-sentinel-saved");
  if (!container) return;
  const offset = parseInt(sentinel?.dataset.offset || "0");
  try {
    const response = await fetch(`/profile/saved?offset=${offset}`);
    const result = await response.json();
    if (result.html) {
      if (offset === 0) container.innerHTML = result.html;
      else container.insertAdjacentHTML("beforeend", result.html);
      if (sentinel) sentinel.dataset.offset = offset + result.count;
      if (typeof window.initProfilePostHandlers === "function")
        window.initProfilePostHandlers();
      else if (typeof initProfilePostHandlers === "function")
        initProfilePostHandlers();
    } else if (offset === 0) {
      container.innerHTML =
        '<div class="empty-profile"><div class="empty-icon">🔖</div><p>' + window.t('no_saved_posts') + '</p></div>';
    }
  } catch (error) {
    console.error("Error loading saved posts:", error);
  }
}

function initFollowSmallButtons() {
  document.querySelectorAll(".btn-follow-small").forEach((btn) => {
    if (btn.dataset.listenerAdded === "true") return;
    btn.dataset.listenerAdded = "true";
    btn.removeEventListener("click", handleFollowSmallClick);
    btn.addEventListener("click", handleFollowSmallClick);
  });
}

async function handleFollowSmallClick(e) {
  e.preventDefault();
  e.stopPropagation();
  const btn = e.currentTarget;
  const userId = btn.dataset.userId;
  const username = btn.dataset.username;
  const isFollowing = btn.classList.contains("following");

  btn.disabled = true;
  btn.style.opacity = "0.6";

  try {
    const response = await fetch(
      `/profile/${userId}/${isFollowing ? "unfollow" : "follow"}`,
    );
    const result = await response.json();
    if (result.success) {
      btn.classList.toggle("following");
      const iconSpan = btn.querySelector(".btn-icon");
      const textSpan = btn.querySelector(".btn-text");
      if (iconSpan) iconSpan.textContent = result.following ? "🔓" : "🔔";
      if (textSpan)
        textSpan.textContent = result.following ? window.t('unfollow') : window.t('follow');

      const followersCount = document.getElementById("followers-count");
      if (
        followersCount &&
        window.profileUserId &&
        userId == window.profileUserId
      ) {
        const countResponse = await fetch(`/profile/${userId}/followers-count`);
        const countResult = await countResponse.json();
        if (countResult.success) followersCount.textContent = countResult.count;
      }
      if (typeof showNotification === "function") {
        showNotification(
          result.following
            ? window.t('followed_user', { username })
            : window.t('unfollowed_user', { username }),
          "success",
        );
      }
    } else {
      if (typeof showNotification === "function")
        showNotification(result.error || window.t('error'), "error");
    }
  } catch (error) {
    console.error("Follow error:", error);
    if (typeof showNotification === "function")
      showNotification(window.t('network_error'), "error");
  } finally {
    btn.disabled = false;
    btn.style.opacity = "";
  }
}

async function loadFollowers() {
  const container = document.getElementById("user-followers");
  if (!container) return;
  const spinner = document.getElementById("load-more-spinner-followers");
  if (spinner) spinner.classList.remove("hidden");
  try {
    const response = await fetch(
      `/profile/followers?id=${window.profileUserId}`,
    );
    const result = await response.json();
    if (result.success && result.html) {
      container.innerHTML = result.html;
      initFollowSmallButtons();
    } else {
      container.innerHTML =
        '<div class="empty-profile"><div class="empty-icon">👥</div><p>' + window.t('no_followers') + '</p></div>';
    }
  } catch (error) {
    console.error("Error loading followers:", error);
    container.innerHTML =
      '<div class="empty-profile"><div class="empty-icon">⚠️</div><p>' + window.t('loading_error') + '</p></div>';
  } finally {
    if (spinner) spinner.classList.add("hidden");
  }
}

async function loadFollowing() {
  const container = document.getElementById("user-following");
  if (!container) return;
  const spinner = document.getElementById("load-more-spinner-following");
  if (spinner) spinner.classList.remove("hidden");
  try {
    const response = await fetch(
      `/profile/following?id=${window.profileUserId}`,
    );
    const result = await response.json();
    if (result.success && result.html) {
      container.innerHTML = result.html;
      initFollowSmallButtons();
    } else {
      container.innerHTML =
        '<div class="empty-profile"><div class="empty-icon">📋</div><p>' + window.t('no_following') + '</p></div>';
    }
  } catch (error) {
    console.error("Error loading following:", error);
    container.innerHTML =
      '<div class="empty-profile"><div class="empty-icon">⚠️</div><p>' + window.t('loading_error') + '</p></div>';
  } finally {
    if (spinner) spinner.classList.add("hidden");
  }
}

function initPostModalHandlers() {
  const modal = document.getElementById("post-modal");
  if (!modal) return;
  const voteBtn = modal.querySelector(".poll-widget-vote-btn");
  if (voteBtn && !voteBtn.dataset.modalHandlerAdded) {
    voteBtn.dataset.modalHandlerAdded = "true";
    const pollId = voteBtn.closest(".poll-widget")?.dataset.pollId;
    const postId =
      voteBtn.closest(".post-modal__content")?.dataset.postId ||
      window.currentModalPostId;
    if (pollId) {
      voteBtn.onclick = (e) => {
        e.preventDefault();
        if (typeof window.submitPollVote === "function")
          window.submitPollVote(pollId, postId);
      };
    }
  }
  const cancelBtn = modal.querySelector(".poll-widget-cancel-btn");
  if (cancelBtn && !cancelBtn.dataset.modalHandlerAdded) {
    cancelBtn.dataset.modalHandlerAdded = "true";
    const pollId = cancelBtn.closest(".poll-widget")?.dataset.pollId;
    if (pollId) {
      cancelBtn.onclick = (e) => {
        e.preventDefault();
        if (typeof window.cancelPollVote === "function")
          window.cancelPollVote(pollId);
      };
    }
  }
}

function initModalPollHandlers() {
  const modal = document.getElementById("post-modal");
  if (!modal || !modal.classList.contains("show")) return;
  const voteBtn = modal.querySelector(".poll-widget-vote-btn");
  if (voteBtn && !voteBtn.dataset.modalHandler) {
    voteBtn.dataset.modalHandler = "true";
    const pollId = voteBtn.closest(".poll-widget")?.dataset.pollId;
    const postId =
      modal.querySelector(".post-modal__body")?.dataset.postId ||
      window.currentModalPostId;
    if (pollId) {
      voteBtn.onclick = (e) => {
        e.preventDefault();
        if (typeof window.submitPollVote === "function")
          window.submitPollVote(pollId, postId);
      };
    }
  }
  const cancelBtn = modal.querySelector(".poll-widget-cancel-btn");
  if (cancelBtn && !cancelBtn.dataset.modalHandler) {
    cancelBtn.dataset.modalHandler = "true";
    const pollId = cancelBtn.closest(".poll-widget")?.dataset.pollId;
    if (pollId) {
      cancelBtn.onclick = (e) => {
        e.preventDefault();
        if (typeof window.cancelPollVote === "function")
          window.cancelPollVote(pollId);
      };
    }
  }
}

setInterval(() => {
  const modal = document.getElementById("post-modal");
  if (modal && modal.classList.contains("show")) initModalPollHandlers();
}, 500);

// ==================== DOM Ready ====================
document.addEventListener("DOMContentLoaded", () => {
  initProfilePostForm();

  if (document.getElementById("user-posts") && window.profileUserId) {
    setTimeout(() => loadInitialProfilePosts(), 50);
  }

  const followBtn = document.getElementById("follow-btn");
  if (followBtn) {
    followBtn.removeEventListener("click", handleFollowClick);
    followBtn.addEventListener("click", handleFollowClick);
  }

  const blockBtn = document.getElementById("block-btn");
  if (blockBtn) {
    blockBtn.removeEventListener("click", handleBlockClick);
    blockBtn.addEventListener("click", handleBlockClick);
  }

  const modalObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      if (mutation.attributeName === "class") {
        const modal = document.getElementById("post-modal");
        if (modal && modal.classList.contains("show"))
          setTimeout(initPostModalHandlers, 100);
      }
    });
  });
  const modalEl = document.getElementById("post-modal");
  if (modalEl) modalObserver.observe(modalEl, { attributes: true });

  document
    .querySelectorAll(".profile-tabs .tab-btn[data-tab]")
    .forEach((btn) => {
      btn.addEventListener("click", () => {
        const tab = btn.dataset.tab;
        document
          .querySelectorAll(".profile-tabs .tab-btn")
          .forEach((b) => b.classList.remove("active"));
        btn.classList.add("active");
        document
          .querySelectorAll(".tab-content")
          .forEach((c) => (c.style.display = "none"));
        const tabContent = document.getElementById(`${tab}-tab`);
        if (tabContent) tabContent.style.display = "block";
        if (tab === "saved") loadSavedPosts();
        else if (tab === "reposts") loadRepostsPosts();
        else if (tab === "followers") loadFollowers();
        else if (tab === "following") loadFollowing();
      });
    });

  document.addEventListener("click", (e) => {
    const modal = document.getElementById("block-modal");
    if (modal && e.target === modal) hideBlockModal();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") hideBlockModal();
  });
});

// ==================== Exports ====================
window.submitModalComment = submitModalComment;
window.openAvatarCropper = openAvatarCropper;
window.closeAvatarCropper = closeAvatarCropper;
window.applyAvatarCrop = applyAvatarCrop;
window.loadInitialProfilePosts = loadInitialProfilePosts;
window.loadMoreProfilePosts = loadMoreProfilePosts;
window.handleFollowClick = handleFollowClick;
window.handleBlockClick = handleBlockClick;
window.showBlockModal = showBlockModal;
window.hideBlockModal = hideBlockModal;
window.doBlockUser = doBlockUser;
window.unblockUser = unblockUser;
window.initFollowSmallButtons = initFollowSmallButtons;
window.handleFollowSmallClick = handleFollowSmallClick;
window.initProfilePostHandlers = initProfilePostHandlers;
window.toggleFollow = handleFollowClick;
