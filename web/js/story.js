

let selectedImageFile = null;

const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(notificationStyles);

function addStoryToGrid(story) {
    const container = document.querySelector('.story-content');
    if (!container) return;

    const emptyState = container.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }
    
    let grid = container.querySelector('.stories-grid');
    if (!grid) {
        grid = document.createElement('div');
        grid.className = 'stories-grid';
        container.appendChild(grid);
    }

    const userId = story.user_id;
    let userBlock = grid.querySelector(`.user-stories[data-user-id="${userId}"]`);
    
    if (!userBlock) {

        userBlock = document.createElement('div');
        userBlock.className = 'user-stories';
        userBlock.dataset.userId = userId;
        userBlock.innerHTML = `
            <div class="user-header">
                <img class="user-avatar" src="${story.author?.avatar || ''}" alt="${story.author?.username || ''}">
                <div class="user-info">
                    <div class="username">${escapeHtml(story.author?.username || '')}</div>
                    <div class="stories-count">1 история</div>
                </div>
            </div>
            <div class="stories-list"></div>
        `;

        grid.insertBefore(userBlock, grid.firstChild);
    } else {

        const countEl = userBlock.querySelector('.stories-count');
        if (countEl) {
            const currentCount = userBlock.querySelectorAll('.story-item').length + 1;
            countEl.textContent = `${currentCount} ${getStoriesWord(currentCount)}`;
        }
    }
    
    const storiesList = userBlock.querySelector('.stories-list');
    if (storiesList) {
        const storyEl = document.createElement('div');
        storyEl.className = 'story-item';
        storyEl.dataset.storyId = story.id;

        const currentUserId = window.currentUserId;
        const isOwnStory = currentUserId && story.user_id === currentUserId;

        let storyHtml = `
            <div class="story-image-container">
                <img class="story-image" src="${story.image_url}" alt="История" onclick="viewStory(${story.id})" ondblclick="toggleFullscreenStory(${story.id})">
                <div class="story-time-left">${story.time_left || '24ч 0м'}</div>
        `;

        if (isOwnStory) {
            storyHtml += `<button class="story-delete-btn" onclick="event.stopPropagation(); deleteStory(${story.id})" title="Удалить">🗑️</button>`;
        }

        if (story.caption) {
            storyHtml += `<div class="story-caption">${escapeHtml(story.caption)}</div>`;
        }
        
        storyHtml += `</div>`;
        storyEl.innerHTML = storyHtml;
        
        storiesList.insertBefore(storyEl, storiesList.firstChild);
    }
}

function getStoriesWord(count) {
    const last = count % 10;
    const lastTwo = count % 100;
    if (lastTwo >= 11 && lastTwo <= 19) return 'историй';
    if (last === 1) return 'история';
    if (last >= 2 && last <= 4) return 'истории';
    return 'историй';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showStoryUpload() {
    const modal = document.getElementById('story-upload-modal');
    
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
        resetUploadForm();
    } else {
        showNotification('Ошибка инициализации модального окна', 'error');
    }
}

function hideStoryUpload() {
    const modal = document.getElementById('story-upload-modal');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
        resetUploadForm();
    }
}

function resetUploadForm() {

    const input = document.getElementById('story-image-input');
    if (input) {
        input.value = '';
    }

    const previewImage = document.getElementById('preview-image');
    if (previewImage) {
        previewImage.style.display = 'none';
        previewImage.src = '';
    }

    const uploadArea = document.getElementById('upload-area');
    const storyForm = document.getElementById('story-form');

    if (uploadArea && storyForm) {
        uploadArea.style.display = 'block';
        storyForm.style.display = 'none';
    }
        
    const caption = document.getElementById('story-caption');
    if (caption) caption.value = '';
    selectedImageFile = null;
}

function chooseAnotherImage() {
    const input = document.getElementById('story-image-input');
    const previewImage = document.getElementById('preview-image');
    const uploadArea = document.getElementById('upload-area');
    const storyForm = document.getElementById('story-form');

    if (input && previewImage && uploadArea && storyForm) {

        input.value = '';

        storyForm.style.display = 'none';
        uploadArea.style.display = 'block';

        previewImage.style.display = 'none';
        previewImage.src = '';

        const placeholder = uploadArea.querySelector('.upload-placeholder');
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

function handleImageFile(file) {

    if (!file) {
        showNotification('Файл не выбран', 'error');
        return;
    }

    const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validImageTypes.includes(file.type)) {
        showNotification(`Неподдерживаемый формат файла. Допустимы: JPG, PNG, GIF, WebP (получено: ${file.type || 'неизвестно'})`, 'error');
        const input = document.getElementById('story-image-input');
        if (input) input.value = '';
        return;
    }

    const fileName = file.name.toLowerCase();
    const validExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
    const hasValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
    
    if (!hasValidExtension) {
        showNotification(`Неподдерживаемый формат файла: ${file.name}. Допустимы: JPG, PNG, GIF, WebP`, 'error');
        const input = document.getElementById('story-image-input');
        if (input) input.value = '';
        return;
    }

    const maxSize = 10 * 1024 * 1024; // 10MB
    const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
    
    if (file.size > maxSize) {
        showNotification(`Файл слишком большой: ${fileSizeMB}MB. Максимум: 10MB`, 'error');
        const input = document.getElementById('story-image-input');
        if (input) input.value = '';
        return;
    }

    const minSize = 1024; // 1KB
    if (file.size < minSize) {
        showNotification(`Файл слишком маленький: ${file.size} байт. Минимум: 1KB`, 'error');
        const input = document.getElementById('story-image-input');
        if (input) input.value = '';
        return;
    }

    const img = new Image();
    const objectURL = URL.createObjectURL(file);
    
    img.onload = () => {
        URL.revokeObjectURL(objectURL);

        const minWidth = 100;
        const minHeight = 100;
        
        if (img.width < minWidth || img.height < minHeight) {
            showNotification(`Изображение слишком маленькое: ${img.width}x${img.height}px. Минимум: ${minWidth}x${minHeight}px`, 'error');
            const input = document.getElementById('story-image-input');
            if (input) input.value = '';
            return;
        }

        const maxWidth = 4096;
        const maxHeight = 4096;
        
        if (img.width > maxWidth || img.height > maxHeight) {
            showNotification(`Изображение слишком большое: ${img.width}x${img.height}px. Максимум: ${maxWidth}x${maxHeight}px`, 'error');
            const input = document.getElementById('story-image-input');
            if (input) input.value = '';
            return;
        }

        const input = document.getElementById('story-image-input');
        if (input) input.value = '';

        selectedImageFile = file;

        const reader = new FileReader();
        reader.onload = (e) => {
            const previewImage = document.getElementById('preview-image');
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
            
            const uploadArea = document.getElementById('upload-area');
            const storyForm = document.getElementById('story-form');
            
            if (uploadArea && storyForm) {
                uploadArea.style.display = 'none';
                storyForm.style.display = 'block';
            }

            const fileInfo = document.querySelector('.upload-area .upload-placeholder');
            if (fileInfo) {
                fileInfo.innerHTML = `
                    ✅ ${file.name}<br>
                    <small style="color: var(--text-tertiary);">
                        ${fileSizeMB}MB • ${img.width}x${img.height}px • ${(file.type || '').split('/')[1]?.toUpperCase() || 'IMAGE'}
                    </small>
                    <br>
                    <button onclick="chooseAnotherImage()" style="
                        margin-top: 10px;
                        padding: 6px 12px;
                        background: var(--surface-100);
                        border: 1px solid var(--border-primary);
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 13px;
                        color: var(--text-primary);
                    ">🔄 Выбрать другое изображение</button>
                `;
            }
        }
        reader.readAsDataURL(file);
    };
    
    img.onerror = () => {
        URL.revokeObjectURL(objectURL);
        showNotification('Ошибка: файл поврежден или не является изображением', 'error');
        const input = document.getElementById('story-image-input');
        if (input) input.value = '';
    };
    
    img.src = objectURL;
}

document.addEventListener('DOMContentLoaded', () => {
    const uploadArea = document.getElementById('upload-area');
    const imageInput = document.getElementById('story-image-input');
    const previewImage = document.getElementById('preview-image');

    if (uploadArea && imageInput) {

        uploadArea.addEventListener('click', () => {
            imageInput.click();
        });

        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                handleImageFile(file);
            }
        });

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleImageFile(files[0]);
            }
        });
    }

    const storiesGrid = document.getElementById('stories-grid') || document.querySelector('.stories-grid');
    if (storiesGrid) {
        storiesGrid.addEventListener('click', (e) => {
            const storyItem = e.target.closest('.story-item');
            if (!storyItem) return;

            if (e.target.closest('.story-delete-btn')) return;
            
            const storyId = storyItem.dataset.storyId;
            if (storyId) {
                viewStory(parseInt(storyId));
            }
        });
    }
});

async function uploadStory() {
    if (!selectedImageFile) {
        showNotification('Выберите изображение', 'error');
        return;
    }

    const caption = document.getElementById('story-caption').value.trim();
    const formData = new FormData();
    formData.append('image', selectedImageFile);
    formData.append('caption', caption);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        formData.append('_csrf', csrfToken);
    }

    try {
        const response = await fetch('/api/story/upload', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            if (response.status === 401) {
                showNotification('Требуется авторизация. Войдите в аккаунт.', 'error');
                setTimeout(() => {
                    window.location.href = '/site/login';
                }, 2000);
                return;
            } else if (response.status === 403) {
                showNotification('Доступ запрещен', 'error');
                return;
            } else if (response.status === 429) {
                showNotification('Слишком много запросов. Попробуйте позже.', 'error');
                return;
            } else {
                showNotification(`Ошибка сервера: ${response.status}`, 'error');
                return;
            }
        }

        const responseText = await response.text();

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            showNotification('Ошибка сервера. Неверный формат ответа.', 'error');
            return;
        }
        
        if (result.success) {
            showNotification('История опубликована!', 'success');
            hideStoryUpload();

            addStoryToGrid(result.story);

            selectedImageFile = null;
            document.getElementById('story-caption').value = '';
            const preview = document.getElementById('preview-image');
            if (preview) preview.style.display = 'none';
            const form = document.getElementById('story-form');
            if (form) form.style.display = 'none';
        } else {
            showNotification(result.error || 'Ошибка загрузки', 'error');
        }
    } catch (error) {
        showNotification('Ошибка загрузки истории', 'error');
        if (error.message && error.message.includes('Failed to fetch')) {
            showNotification('Ошибка сети. Проверьте подключение.', 'error');
        } else {
            showNotification('Ошибка загрузки истории', 'error');
        }
    }
}

async function viewStory(storyId) {
    try {
        const response = await fetch(`/api/story/view?id=${storyId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const html = await response.text();
        
        const contentEl = document.getElementById('story-view-content');
        const modalEl = document.getElementById('story-view-modal');
        
        if (contentEl) {
            contentEl.innerHTML = html;
        }
        
        if (modalEl) {
            modalEl.style.display = 'flex';
            modalEl.classList.add('show');
        }
    } catch (error) {
        showNotification('Ошибка загрузки истории', 'error');
    }
}

function hideStoryView() {
    const modalEl = document.getElementById('story-view-modal');
    if (modalEl) {
        modalEl.style.display = 'none';
        modalEl.classList.remove('show');
        modalEl.classList.remove('fullscreen');
        modalEl.classList.remove('fullscreen-active');
    }
    const contentEl = document.getElementById('story-view-content');
    if (contentEl) {
        contentEl.innerHTML = '';
    }

    const exitBtn = document.querySelector('.fullscreen-exit-btn');
    if (exitBtn) {
        exitBtn.style.display = 'none';
    }
}

function toggleFullscreenStory(storyId) {
    const storyItem = document.querySelector(`.story-item[data-story-id="${storyId}"]`);
    if (!storyItem) return;
    
    const image = storyItem.querySelector('.story-image');
    if (!image) return;
    
    const modalEl = document.getElementById('story-view-modal');
    const contentEl = document.getElementById('story-view-content');
    const exitBtn = document.querySelector('.fullscreen-exit-btn');
    
    if (!modalEl || !contentEl) return;

    fetch(`/api/story/view?id=${storyId}`)
        .then(response => response.text())
        .then(html => {
            contentEl.innerHTML = html;
            modalEl.style.display = 'flex';
            modalEl.classList.add('show');
            modalEl.classList.add('fullscreen');

            if (exitBtn) {
                exitBtn.style.display = 'block';
            }

            setTimeout(() => {
                modalEl.classList.add('fullscreen-active');
            }, 10);
        })
        .catch(error => {
            showNotification('Ошибка загрузки истории', 'error');
        });
}

function exitFullscreenStory() {
    const modalEl = document.getElementById('story-view-modal');
    const exitBtn = document.querySelector('.fullscreen-exit-btn');
    
    if (exitBtn) {
        exitBtn.style.display = 'none';
    }
    
    if (modalEl) {
        modalEl.classList.remove('fullscreen-active');
        setTimeout(() => {
            modalEl.classList.remove('fullscreen');
            hideStoryView();
        }, 300);
    }
}

function scrollToStory(storyId) {
    const storyItem = document.querySelector(`.story-item[data-story-id="${storyId}"]`);
    if (!storyItem) return;
    
    const grid = document.getElementById('stories-grid') || document.querySelector('.stories-grid');
    if (!grid) return;

    const itemRect = storyItem.getBoundingClientRect();
    const gridRect = grid.getBoundingClientRect();

    const itemCenter = itemRect.left + itemRect.width / 2;
    const screenCenter = window.innerWidth / 2;

    const scrollPosition = grid.scrollLeft + (itemCenter - screenCenter);
    
    grid.scrollTo({
        left: scrollPosition,
        behavior: 'smooth'
    });
}

function showEmptyState() {
    const storyContent = document.querySelector('.story-content');
    if (storyContent) {
        storyContent.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">📖</div>
                <h3>Нет активных историй</h3>
                <p>Истории появляются здесь, когда ваши подписки делятся моментами из жизни</p>
            </div>
        `;
    }
}

async function deleteStory(storyId) {
    if (!confirm('Удалить эту историю?')) return;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'Content-Type': 'application/json' };
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
        
        const response = await fetch('/api/story/delete', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ story_id: storyId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('История удалена', 'success');

            const storyItem = document.querySelector(`.story-item[data-story-id="${storyId}"]`);

            let userStoriesList = null;
            if (storyItem) {
                userStoriesList = storyItem.closest('.stories-list');
                storyItem.remove();
            }

            if (userStoriesList) {
                const remainingStories = userStoriesList.querySelectorAll('.story-item').length;
                
                if (remainingStories === 0) {

                    const userBlock = userStoriesList.closest('.user-stories');
                    if (userBlock) {
                        userBlock.remove();
                    }

                    const storiesGrid = document.querySelector('.stories-grid');
                    if (storiesGrid) {
                        const allStoryItems = storiesGrid.querySelectorAll('.story-item');
                        
                        if (allStoryItems.length === 0) {
                            showEmptyState();
                        }
                    }
                } else {

                    const countEl = userStoriesList.closest('.user-stories')?.querySelector('.stories-count');
                    if (countEl) {
                        const newCount = remainingStories;
                        countEl.textContent = `${newCount} ${getStoriesWord(newCount)}`;
                    }
                }
            }

            const viewModal = document.getElementById('story-view-modal');
            if (viewModal && viewModal.style.display === 'flex') {
                hideStoryView();
            }
        } else {
            showNotification(result.error || 'Ошибка удаления', 'error');
        }
    } catch (error) {
        showNotification('Ошибка сети', 'error');
    }
}


function scrollStories(direction) {
    const grid = document.getElementById('stories-grid') || document.getElementById('feed-stories-grid');
    if (!grid) {
        return;
    }
    
    const scrollAmount = 340;
    grid.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('stories-grid') || document.getElementById('feed-stories-grid');
    if (!grid) return;

    const leftBtn = document.querySelector('.scroll-btn-left');
    const rightBtn = document.querySelector('.scroll-btn-right');
    
    if (leftBtn) {
        leftBtn.addEventListener('click', (e) => {
            e.preventDefault();
            scrollStories(-1);
        });
    }
    if (rightBtn) {
        rightBtn.addEventListener('click', (e) => {
            e.preventDefault();
            scrollStories(1);
        });
    }

    grid.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            grid.scrollBy({ left: e.deltaY, behavior: 'smooth' });
        }
    }, { passive: false });

    let isDown = false;
    let startX;
    let scrollLeft;
    
    grid.addEventListener('mousedown', (e) => {
        if (e.target.closest('.story-image') || e.target.closest('button')) return;
        isDown = true;
        grid.classList.add('grabbing');
        startX = e.pageX - grid.offsetLeft;
        scrollLeft = grid.scrollLeft;
    });
    
    grid.addEventListener('mouseleave', () => {
        isDown = false;
        grid.classList.remove('grabbing');
    });
    
    grid.addEventListener('mouseup', () => {
        isDown = false;
        grid.classList.remove('grabbing');
    });
    
    grid.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - grid.offsetLeft;
        const walk = (x - startX) * 1.5;
        grid.scrollLeft = scrollLeft - walk;
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            scrollStories(-1);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            scrollStories(1);
        }
    });
});

setInterval(() => {
    if (window.location.pathname.includes('/story/index')) {

        updateStoryTimers();
    }
}, 60000); // Каждую минуту

function updateStoryTimers() {
    const timeElements = document.querySelectorAll('.story-time-left');
    timeElements.forEach(element => {


        location.reload();
    });
}

window.deleteStory = deleteStory;
window.scrollStories = scrollStories;
window.chooseAnotherImage = chooseAnotherImage;
window.viewStory = viewStory;
window.hideStoryView = hideStoryView;
window.toggleFullscreenStory = toggleFullscreenStory;
window.exitFullscreenStory = exitFullscreenStory;
window.scrollToStory = scrollToStory;
window.showEmptyState = showEmptyState;
window.showStoryUpload = showStoryUpload;
window.hideStoryUpload = hideStoryUpload;
window.uploadStory = uploadStory;

if (typeof showNotification === 'undefined') {
    window.showNotification = function(msg, type) {
        alert(msg);
    };
}

window.showNotification = showNotification;
window.hideDeleteModal = typeof hideDeleteModal !== 'undefined' ? hideDeleteModal : function() {};

document.addEventListener('DOMContentLoaded', () => {

    const viewModal = document.getElementById('story-view-modal');
    const uploadModal = document.getElementById('story-upload-modal');
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hideStoryUpload();
        hideStoryView();
    }
});

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('story-upload-modal')) {
        hideStoryUpload();
    }
    if (e.target.classList.contains('story-view-modal')) {
        hideStoryView();
    }
});