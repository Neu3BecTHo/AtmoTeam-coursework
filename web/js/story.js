// ==================== Story System with Modal & Image Compression ====================
let selectedImageFile = null;
let currentStoryIndex = 0;
let currentUserStories = [];
let storyModal = null;
let storyContextMenu = null;

// === Настройки сжатия для историй ===
const STORY_MAX_WIDTH = 1200;
const STORY_MAX_HEIGHT = 1200;
const STORY_WEBP_QUALITY = 0.8;
const STORY_JPEG_QUALITY = 0.85;

// ==================== Helper Functions ====================
function getStoriesWord(count) {
    const last = count % 10;
    const lastTwo = count % 100;
    if (lastTwo >= 11 && lastTwo <= 19) return "историй";
    if (last === 1) return "история";
    if (last >= 2 && last <= 4) return "истории";
    return "историй";
}

function escapeHtml(str) {
    if (!str) return "";
    return str.replace(/[&<>]/g, function (m) {
        if (m === "&") return "&amp;";
        if (m === "<") return "&lt;";
        if (m === ">") return "&gt;";
        return m;
    });
}

function resetImageInput() {
    const input = document.getElementById("story-image-input");
    if (input) input.value = "";
}

// ==================== View Story - используем fullscreen image viewer как в постах ====================
function viewStory(storyId) {
    const storyEl = document.querySelector(`.story-item[data-story-id="${storyId}"]`);
    if (!storyEl) {
        showNotification("Ошибка: не удалось найти историю", "error");
        return;
    }

    const imageUrl = storyEl.querySelector('.story-image')?.src;
    const author = storyEl.querySelector('.username')?.textContent || 'Пользователь';
    
    if (imageUrl) {
        // Сохраняем текущие истории для навигации
        const userStoriesBlock = storyEl.closest('.user-stories');
        if (userStoriesBlock) {
            const userId = userStoriesBlock.dataset.userId;
            loadUserStoriesForFullscreen(userId, storyId);
        } else {
            // Если нет блока, просто открываем изображение
            openImageFullscreen(imageUrl, 1, 0);
        }
    }
}

// Загрузка историй пользователя для навигации в fullscreen viewer
function loadUserStoriesForFullscreen(userId, currentStoryId) {
    fetch(`/api/story/get?user_id=${userId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.stories && data.stories.length > 0) {
                currentUserStories = data.stories;
                const index = currentUserStories.findIndex(s => s.id == currentStoryId);
                currentStoryIndex = index !== -1 ? index : 0;
                
                // Открываем fullscreen viewer
                const story = currentUserStories[currentStoryIndex];
                if (story) {
                    openImageFullscreen(story.image_url, currentUserStories.length, currentStoryIndex);
                    // Обновляем counter с именем автора
                    const counter = document.getElementById('fullscreen-image-counter');
                    if (counter) {
                        counter.textContent = `${story.author?.username || 'История'} ${currentStoryIndex + 1}/${currentUserStories.length}`;
                    }
                    
                    // Навешиваем обработчики для кнопок навигации
                    setTimeout(() => {
                        const prevBtn = document.querySelector('.fullscreen-image-prev');
                        const nextBtn = document.querySelector('.fullscreen-image-next');
                        if (prevBtn) {
                            prevBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                prevStoryImage();
                            });
                        }
                        if (nextBtn) {
                            nextBtn.addEventListener('click', (e) => {
                                e.preventDefault();
                                nextStoryImage();
                            });
                        }
                    }, 100);
                }
            }
        })
        .catch(e => console.error('Ошибка загрузки историй:', e));
}

// ==================== Сжатие изображения для истории ====================
async function compressStoryImage(file) {
    if (file.type === "image/gif" || file.size < 100 * 1024) {
        return file;
    }

    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e) => {
            const img = new Image();
            img.src = e.target.result;
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                if (width > STORY_MAX_WIDTH || height > STORY_MAX_HEIGHT) {
                    const ratio = Math.min(STORY_MAX_WIDTH / width, STORY_MAX_HEIGHT / height);
                    width = Math.floor(width * ratio);
                    height = Math.floor(height * ratio);
                }

                const canvas = document.createElement("canvas");
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, width, height);

                let mime = "image/webp";
                let quality = STORY_WEBP_QUALITY;
                if (!canvas.toBlob || !canvas.toBlob.bind(canvas)) {
                    mime = "image/jpeg";
                    quality = STORY_JPEG_QUALITY;
                }

                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            reject(new Error("Не удалось создать Blob"));
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
                        resolve(compressed);
                    },
                    mime,
                    quality
                );
            };
            img.onerror = () => reject(new Error("Ошибка загрузки изображения (возможно, файл повреждён или слишком велик)"));
        };
        reader.onerror = () => reject(new Error("Ошибка чтения файла"));
    });
}

// ==================== Upload & Delete ====================
function showStoryUpload() {
    const modal = document.getElementById("story-upload-modal");
    if (modal) {
        modal.style.display = "flex";
        modal.classList.add("show");
        resetUploadForm();
    } else {
        showNotification("Ошибка: модальное окно не найдено", "error");
    }
}

function hideStoryUpload() {
    const modal = document.getElementById("story-upload-modal");
    if (modal) {
        modal.style.display = "none";
        modal.classList.remove("show");
        resetUploadForm();
    }
}

function resetUploadForm() {
    const input = document.getElementById("story-image-input");
    if (input) input.value = "";
    const previewImage = document.getElementById("preview-image");
    if (previewImage) {
        previewImage.style.display = "none";
        previewImage.src = "";
    }
    const uploadArea = document.getElementById("upload-area");
    const storyForm = document.getElementById("story-form");
    if (uploadArea && storyForm) {
        uploadArea.style.display = "block";
        storyForm.style.display = "none";
    }
    const caption = document.getElementById("story-caption");
    if (caption) caption.value = "";
    selectedImageFile = null;
}

function chooseAnotherImage() {
    const input = document.getElementById("story-image-input");
    const previewImage = document.getElementById("preview-image");
    const uploadArea = document.getElementById("upload-area");
    const storyForm = document.getElementById("story-form");
    if (input && previewImage && uploadArea && storyForm) {
        input.value = "";
        storyForm.style.display = "none";
        uploadArea.style.display = "block";
        previewImage.style.display = "none";
        previewImage.src = "";
        const placeholder = uploadArea.querySelector(".upload-placeholder");
        if (placeholder) {
            placeholder.innerHTML = `
                <div class="upload-icon">📸</div>
                <p>Нажмите для выбора изображения</p>
                <p class="upload-hint">или перетащите файл сюда</p>
            `;
        }
        selectedImageFile = null;
    }
}

async function handleImageFile(file) {
    if (!file) {
        showNotification("Файл не выбран", "error");
        return;
    }

    const validImageTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
    if (!validImageTypes.includes(file.type)) {
        showNotification("Неподдерживаемый формат. Допустимы: JPG, PNG, GIF, WebP", "error");
        resetImageInput();
        return;
    }


    try {
        const compressed = await compressStoryImage(file);
        selectedImageFile = compressed;

        const reader = new FileReader();
        reader.onload = (e) => {
            const previewImage = document.getElementById("preview-image");
            previewImage.src = e.target.result;
            previewImage.style.display = "block";

            const uploadArea = document.getElementById("upload-area");
            const storyForm = document.getElementById("story-form");
            if (uploadArea && storyForm) {
                uploadArea.style.display = "none";
                storyForm.style.display = "block";
            }

            const fileInfo = uploadArea?.querySelector(".upload-placeholder");
            if (fileInfo) {
                const compressedSizeMB = (compressed.size / 1024 / 1024).toFixed(2);
                fileInfo.innerHTML = `
                    ✅ ${compressed.name}<br>
                    <small>${compressedSizeMB}MB (сжато)</small><br>
                    <button onclick="chooseAnotherImage()">🔄 Выбрать другое изображение</button>
                `;
            }
        };
        reader.readAsDataURL(compressed);
    } catch (err) {
        console.error("Ошибка сжатия:", err);
        showNotification("Не удалось сжать изображение. Попробуйте другое фото или меньшего размера.", "error");
        resetImageInput();
        selectedImageFile = null;
    }
}

async function uploadStory() {
    if (!selectedImageFile) {
        showNotification("Выберите изображение", "error");
        return;
    }

    const caption = document.getElementById("story-caption").value.trim();
    const formData = new FormData();
    formData.append("image", selectedImageFile);
    formData.append("caption", caption);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) formData.append("_csrf", csrfToken);

    const btnUpload = document.querySelector(".btn-upload");
    if (btnUpload) {
        btnUpload.disabled = true;
        btnUpload.textContent = "⏳ Публикация...";
    }

    try {
        const response = await fetch("/api/story/upload", {
            method: "POST",
            body: formData,
        });
        const result = await response.json();
        if (result.success) {
            showNotification("История опубликована!", "success");
            hideStoryUpload();
            addStoryToFeedGrid(result.story);
            addStoryToGrid(result.story);
            resetUploadForm();
        } else {
            showNotification(result.error || "Ошибка загрузки", "error");
        }
    } catch (error) {
        console.error(error);
        showNotification("Ошибка загрузки истории", "error");
    } finally {
        if (btnUpload) {
            btnUpload.disabled = false;
            btnUpload.textContent = "Опубликовать";
        }
    }
}

// ==================== Dynamic UI Updates ====================
function addStoryToGrid(story) {
    const container = document.querySelector(".story-content");
    if (!container) return;

    // Удаляем пустое состояние
    const emptyState = container.querySelector(".empty-state");
    if (emptyState) emptyState.remove();

    // Проверяем, есть ли обёртка со скроллом
    let scrollWrapper = container.querySelector(".stories-scroll-wrapper");
    let storiesContainer;

    if (!scrollWrapper) {
        // Создаём обёртку с кнопками
        scrollWrapper = document.createElement("div");
        scrollWrapper.className = "stories-scroll-wrapper";
        scrollWrapper.innerHTML = `
            <button class="scroll-btn scroll-btn-left" onclick="scrollStories(-1)">‹</button>
            <div class="stories-grid" id="stories-grid"></div>
            <button class="scroll-btn scroll-btn-right" onclick="scrollStories(1)">›</button>
        `;
        container.appendChild(scrollWrapper);
        storiesContainer = scrollWrapper.querySelector(".stories-grid");
    } else {
        storiesContainer = scrollWrapper.querySelector(".stories-grid, #stories-grid");
    }

    if (!storiesContainer) {
        storiesContainer = document.createElement("div");
        storiesContainer.id = "stories-grid";
        storiesContainer.className = "stories-grid";
        scrollWrapper.appendChild(storiesContainer);
    }

    // Убираем инлайн-стили и атрибуты, чтобы скролл инициализировался заново
    storiesContainer.removeAttribute('style');
    storiesContainer.removeAttribute('data-scroll-initialized');

    const userId = story.user_id;
    let userBlock = storiesContainer.querySelector(`.user-stories[data-user-id="${userId}"]`);

    if (!userBlock) {
        userBlock = document.createElement("div");
        userBlock.className = "user-stories";
        userBlock.dataset.userId = userId;
        userBlock.innerHTML = `
            <div class="user-header">
                <a href="/profile?id=${userId}" class="user-avatar-link" title="${escapeHtml(story.author?.username || '')}">
                    <img class="user-avatar" src="${story.author?.avatar || ''}" alt="${escapeHtml(story.author?.username || '')}" loading="lazy">
                </a>
                <div class="user-info">
                    <div class="username">${escapeHtml(story.author?.username || '')}</div>
                    <div class="stories-count">1 история</div>
                </div>
            </div>
            <div class="stories-list"></div>
        `;
        storiesContainer.appendChild(userBlock);
    } else {
        const countEl = userBlock.querySelector(".stories-count");
        if (countEl) {
            const currentCount = userBlock.querySelectorAll(".story-item").length + 1;
            countEl.textContent = `📸 ${currentCount} ${getStoriesWord(currentCount)}`;
        }
    }

    const storiesList = userBlock.querySelector(".stories-list");
    if (storiesList) {
        storiesList.removeAttribute('style');
        storiesList.removeAttribute('data-scroll-initialized');

        const storyEl = createStoryElement(story);
        storiesList.appendChild(storyEl);
        initAllStoryScrolling();
        attachStoryContextMenuHandlers();
    }
}

function addStoryToFeedGrid(story) {
    console.log("Adding story to feed:", story);
    let storiesSection = document.querySelector(".feed-stories-section");
    if (window.location.pathname.includes("/story")) return;
    if (!storiesSection) return;

    const emptyState = storiesSection.querySelector(".stories-empty, .empty-state, .guest-state");
    if (emptyState) emptyState.remove();

    let scrollWrapper = storiesSection.querySelector(".stories-scroll-wrapper");
    let storiesGrid;

    if (!scrollWrapper) {
        scrollWrapper = document.createElement("div");
        scrollWrapper.className = "stories-scroll-wrapper";
        scrollWrapper.innerHTML = `
            <button class="scroll-btn scroll-btn-left" onclick="scrollStories(-1)">‹</button>
            <div class="stories-grid" id="feed-stories-grid"></div>
            <button class="scroll-btn scroll-btn-right" onclick="scrollStories(1)">›</button>
        `;
        storiesSection.appendChild(scrollWrapper);
        storiesGrid = scrollWrapper.querySelector("#feed-stories-grid");
    } else {
        storiesGrid = scrollWrapper.querySelector(".stories-grid, #feed-stories-grid");
    }

    if (!storiesGrid) {
        storiesGrid = document.createElement("div");
        storiesGrid.id = "feed-stories-grid";
        storiesGrid.className = "stories-grid";
        scrollWrapper.appendChild(storiesGrid);
    }

    storiesSection.style.display = "block";

    const userId = story.user_id;
    let userBlock = storiesGrid.querySelector(`.user-stories[data-user-id="${userId}"]`);

    if (!userBlock) {
        userBlock = document.createElement("div");
        userBlock.className = "user-stories";
        userBlock.dataset.userId = userId;
        userBlock.innerHTML = `
            <div class="user-header">
                <a href="/profile?id=${userId}" class="user-avatar-link" title="${escapeHtml(story.author?.username || '')}">
                    <img class="user-avatar" src="${story.author?.avatar || ''}" alt="${escapeHtml(story.author?.username || '')}" loading="lazy">
                </a>
                <div class="user-info">
                    <div class="username">${escapeHtml(story.author?.username || '')}</div>
                    <div class="stories-count">📸 1 история</div>
                </div>
            </div>
            <div class="stories-list"></div>
        `;
        storiesGrid.appendChild(userBlock);
    } else {
        const countEl = userBlock.querySelector(".stories-count");
        if (countEl) {
            const currentCount = userBlock.querySelectorAll(".story-item").length + 1;
            countEl.textContent = `📸 ${currentCount} ${getStoriesWord(currentCount)}`;
        }
    }

    const storiesList = userBlock.querySelector(".stories-list");
    if (storiesList) {
        const storyElement = createStoryElement(story);
        storiesList.insertBefore(storyElement, storiesList.firstChild);
    }
    
    if (storiesGrid) {
        storiesGrid.removeAttribute('style');
        storiesGrid.removeAttribute('data-scroll-initialized');
    }
    if (storiesList) {
        storiesList.removeAttribute('style');
        storiesList.removeAttribute('data-scroll-initialized');
    }
    initAllStoryScrolling();
    attachStoryContextMenuHandlers();
}

function createStoryElement(story) {
    const storyEl = document.createElement("div");
    storyEl.className = "story-item";
    storyEl.dataset.storyId = story.id;
    storyEl.dataset.userId = story.user_id;
    const isOwner = window.currentUserId && story.user_id == window.currentUserId;
    const timeLeft = story.time_left || "24ч";
    storyEl.innerHTML = `
        <div class="story-image-container">
            <img class="story-image" src="${story.image_url}" alt="История" onclick="event.stopPropagation(); viewStory(${story.id})">
            <div class="story-time-left">⏱️ ${escapeHtml(timeLeft)}</div>
            ${story.caption ? `<div class="story-caption">${escapeHtml(story.caption)}</div>` : ""}
            <div class="story-overlay-buttons">
                <button class="story-view-btn" onclick="event.stopPropagation(); viewStory(${story.id})" title="Просмотр">👁️</button>
            </div>
        </div>
    `;
    return storyEl;
}

async function deleteStory(storyId) {
    if (typeof window.showDeleteModal === "function") {
        window.showDeleteModal("Удалить эту историю?", () => performStoryDeletion(storyId));
    } else if (confirm("Удалить эту историю?")) {
        await performStoryDeletion(storyId);
    }
}

async function performStoryDeletion(storyId) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { "Content-Type": "application/json" };
        if (csrfToken) headers["X-CSRF-Token"] = csrfToken;
        const response = await fetch("/api/story/delete", {
            method: "POST",
            headers,
            body: JSON.stringify({ story_id: storyId }),
        });
        const result = await response.json();
        if (result.success) {
            showNotification("История удалена", "success");
            const storyItem = document.querySelector(`.story-item[data-story-id="${storyId}"]`);
            const userStoriesList = storyItem?.closest(".stories-list");
            const userBlock = userStoriesList?.closest(".user-stories");
            const storiesGrid = userBlock?.closest(".stories-grid, #feed-stories-grid");
            
            storyItem?.remove();
            
            if (userStoriesList && userStoriesList.querySelectorAll(".story-item").length === 0) {
                userBlock?.remove();
            }
            
            if (storiesGrid && storiesGrid.querySelectorAll(".user-stories").length === 0) {
                const storiesSection = document.querySelector(".feed-stories-section, .story-content");
                if (storiesSection) {
                    storiesSection.innerHTML = '';
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <div class="empty-icon">📖</div>
                        <h3>Нет активных историй</h3>
                        <p>Истории появляются здесь, когда ваши подписки делятся моментами из жизни</p>
                        <button class="btn-create-story" onclick="showStoryUpload()">✨ Создать первую историю</button>
                    `;
                    storiesSection.appendChild(emptyState);
                }
            }
            
            // Закрываем fullscreen viewer если открыт
            closeFullscreenImage();
        } else {
            showNotification(result.error || "Ошибка удаления", "error");
        }
    } catch (error) {
        showNotification("Ошибка сети", "error");
    }
}

// ==================== Scrolling ====================
function initAllStoryScrolling() {
    document.querySelectorAll(".stories-grid").forEach((grid) => {
        if (grid.dataset.scrollInitialized === "true") return;
        grid.dataset.scrollInitialized = "true";
        grid.style.overflowX = "auto";
        grid.style.display = "flex";
        grid.style.flexWrap = "nowrap";
        grid.style.gap = "var(--space-4)";
        grid.style.padding = "var(--space-2)";
        grid.style.scrollSnapType = "x mandatory";
        const wrapper = grid.closest(".stories-scroll-wrapper");
        if (wrapper) {
            const leftBtn = wrapper.querySelector(".scroll-btn-left");
            const rightBtn = wrapper.querySelector(".scroll-btn-right");
            if (leftBtn) leftBtn.onclick = () => grid.scrollBy({ left: -300, behavior: "smooth" });
            if (rightBtn) rightBtn.onclick = () => grid.scrollBy({ left: 300, behavior: "smooth" });
        }
    });
    document.querySelectorAll(".stories-list").forEach((list) => {
        if (list.dataset.scrollInitialized === "true") return;
        list.dataset.scrollInitialized = "true";
        list.style.overflowX = "auto";
        list.style.display = "flex";
        list.style.flexWrap = "nowrap";
        list.style.gap = "var(--space-3)";
        list.style.padding = "var(--space-2)";
        list.style.scrollSnapType = "x mandatory";
        list.style.scrollbarWidth = "thin";
        list.addEventListener(
            "wheel",
            (e) => {
                if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) {
                    e.preventDefault();
                    list.scrollBy({ left: e.deltaX, behavior: "smooth" });
                } else if (Math.abs(e.deltaY) > 5) {
                    e.preventDefault();
                    list.scrollBy({ left: e.deltaY, behavior: "smooth" });
                }
            },
            { passive: false }
        );
    });
}

function scrollStories(direction) {
    const grid = document.getElementById("stories-grid") || document.getElementById("feed-stories-grid");
    if (grid) grid.scrollBy({ left: direction * 340, behavior: "smooth" });
}

// ==================== Event Listeners ====================
document.addEventListener("DOMContentLoaded", () => {
    const uploadArea = document.getElementById("upload-area");
    const imageInput = document.getElementById("story-image-input");
    if (uploadArea && imageInput) {
        uploadArea.addEventListener("click", () => imageInput.click());
        imageInput.addEventListener("change", (e) => {
            if (e.target.files[0]) handleImageFile(e.target.files[0]);
        });
        uploadArea.addEventListener("dragover", (e) => e.preventDefault());
        uploadArea.addEventListener("drop", (e) => {
            e.preventDefault();
            if (e.dataTransfer.files[0]) handleImageFile(e.dataTransfer.files[0]);
        });
    }
    setTimeout(initAllStoryScrolling, 100);
    setTimeout(attachStoryContextMenuHandlers, 200);
});

window.addEventListener("load", () => {
    setTimeout(initAllStoryScrolling, 200);
    setTimeout(attachStoryContextMenuHandlers, 300);
});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        closeFullscreenImage();
        hideStoryUpload();
    } else if (e.key === "ArrowLeft") {
        // Для историй используем prevStoryImage
        const modal = document.getElementById('fullscreen-image-modal');
        if (modal && modal.style.display === 'flex' && currentUserStories.length > 0) {
            prevStoryImage();
        }
    } else if (e.key === "ArrowRight") {
        // Для историй используем nextStoryImage
        const modal = document.getElementById('fullscreen-image-modal');
        if (modal && modal.style.display === 'flex' && currentUserStories.length > 0) {
            nextStoryImage();
        }
    }
});

document.addEventListener("click", (e) => {
    const uploadModal = document.getElementById("story-upload-modal");
    if (uploadModal && e.target === uploadModal) hideStoryUpload();
});

// ==================== Context Menu для удаления историй ====================
function showStoryContextMenu(e, storyId, isOwn) {
    e.preventDefault();
    e.stopPropagation();
    
    if (!isOwn) return; // Можно удалять только свои истории
    
    hideStoryContextMenu();
    
    const menu = document.createElement('div');
    menu.className = 'message-context-menu';
    menu.innerHTML = `
        <button class="context-menu-item delete-message-btn" onclick="deleteStoryFromContext(${storyId})">
            🗑️ Удалить
        </button>
    `;
    
    // Получаем координаты элемента
    const target = e.target;
    const rect = target.getBoundingClientRect();
    
    // Позиционируем меню под элементом
    const menuWidth = 150;
    const menuHeight = 50;
    const x = Math.min(rect.left, window.innerWidth - menuWidth - 10);
    const y = Math.min(rect.bottom + 5, window.innerHeight - menuHeight - 10);
    
    menu.style.left = `${x}px`;
    menu.style.top = `${y}px`;
    
    document.body.appendChild(menu);
    storyContextMenu = menu;
    
    // Закрываем меню при клике вне его
    setTimeout(() => {
        document.addEventListener('click', hideStoryContextMenu);
    }, 100);
}

function hideStoryContextMenu() {
    if (storyContextMenu) {
        storyContextMenu.remove();
        storyContextMenu = null;
    }
    document.removeEventListener('click', hideStoryContextMenu);
}

async function deleteStoryFromContext(storyId) {
    hideStoryContextMenu();
    
    if (typeof window.showDeleteModal === 'function') {
        window.showDeleteModal('Удалить эту историю?', () => performStoryDeletion(storyId));
    } else if (confirm('Удалить эту историю?')) {
        await performStoryDeletion(storyId);
    }
}

function attachStoryContextMenuHandlers() {
    const grid = document.getElementById('stories-grid') || document.getElementById('feed-stories-grid');
    if (!grid) return;
    
    grid.querySelectorAll('.story-item').forEach(item => {
        const storyId = item.dataset.storyId;
        const userId = item.dataset.userId;
        const isOwn = window.currentUserId && userId == window.currentUserId;
        
        if (isOwn && storyId && !item.dataset.contextHandler) {
            item.dataset.contextHandler = 'true';
            
            let touchTimer = null;
            let isLongPress = false;
            
            // Удержание (десктоп и мобильные)
            item.addEventListener('mousedown', (e) => {
                if (!isOwn || e.button !== 0) return;
                isLongPress = false;
                touchTimer = setTimeout(() => {
                    isLongPress = true;
                    showStoryContextMenu(e, storyId, isOwn);
                }, 300);
            });
            
            item.addEventListener('mouseup', (e) => {
                if (touchTimer) {
                    clearTimeout(touchTimer);
                    touchTimer = null;
                }
                if (isLongPress) {
                    e.preventDefault();
                    isLongPress = false;
                }
            });
            
            item.addEventListener('mouseleave', () => {
                if (touchTimer) {
                    clearTimeout(touchTimer);
                    touchTimer = null;
                }
            });
            
            // Правая кнопка мыши
            item.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                showStoryContextMenu(e, storyId, isOwn);
            });
            
            // Мобильные — долгое нажатие
            item.addEventListener('touchstart', (e) => {
                if (!isOwn) return;
                isLongPress = false;
                touchTimer = setTimeout(() => {
                    isLongPress = true;
                    if (navigator.vibrate) navigator.vibrate(50);
                    showStoryContextMenu(e, storyId, isOwn);
                }, 300);
            }, { passive: true });
            
            item.addEventListener('touchend', () => {
                if (touchTimer) {
                    clearTimeout(touchTimer);
                    touchTimer = null;
                }
            });
            
            item.addEventListener('touchmove', () => {
                if (touchTimer) {
                    clearTimeout(touchTimer);
                    touchTimer = null;
                }
            });
        }
    });
}

// ==================== Fullscreen Navigation (для историй) ====================
function prevStoryImage() {
    if (currentUserStories.length > 0 && currentStoryIndex > 0) {
        currentStoryIndex--;
        updateFullscreenStory();
    }
}

function nextStoryImage() {
    if (currentUserStories.length > 0 && currentStoryIndex < currentUserStories.length - 1) {
        currentStoryIndex++;
        updateFullscreenStory();
    }
}

function updateFullscreenStory() {
    const story = currentUserStories[currentStoryIndex];
    if (!story) return;
    
    const img = document.getElementById('fullscreen-image');
    const counter = document.getElementById('fullscreen-image-counter');
    
    if (img) {
        img.src = story.image_url;
    }
    if (counter) {
        counter.textContent = `${story.author?.username || 'История'} ${currentStoryIndex + 1}/${currentUserStories.length}`;
    }
}

// ==================== Exports ====================
window.deleteStory = deleteStory;
window.scrollStories = scrollStories;
window.chooseAnotherImage = chooseAnotherImage;
window.viewStory = viewStory;
window.prevStoryImage = prevStoryImage;
window.nextStoryImage = nextStoryImage;
window.showStoryUpload = showStoryUpload;
window.hideStoryUpload = hideStoryUpload;
window.uploadStory = uploadStory;
window.initAllStoryScrolling = initAllStoryScrolling;
window.addStoryToFeedGrid = addStoryToFeedGrid;
window.createStoryElement = createStoryElement;
window.getStoriesWord = getStoriesWord;
window.attachStoryContextMenuHandlers = attachStoryContextMenuHandlers;
window.showStoryContextMenu = showStoryContextMenu;
window.hideStoryContextMenu = hideStoryContextMenu;
window.deleteStoryFromContext = deleteStoryFromContext;