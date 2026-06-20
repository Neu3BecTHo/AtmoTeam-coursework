// ==================== Инициализация ====================
let currentUserId = window.currentUserId;
let receiverId = window.receiverId;
let selectedImageFiles = [];
let contextMenuTimeout = null;
let contextMenuElement = null;

// === Настройки сжатия для сообщений ===
const MSG_MAX_WIDTH = 1200;
const MSG_MAX_HEIGHT = 1200;
const MSG_WEBP_QUALITY = 0.8;
const MSG_JPEG_QUALITY = 0.85;

// ==================== Helper Functions ====================
function getJsonHeaders() {
    const headers = { "Content-Type": "application/json" };
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrf) headers["X-CSRF-Token"] = csrf;
    return headers;
}
async function postJson(url, payload) {
    const res = await fetch(url, { method: "POST", headers: getJsonHeaders(), body: JSON.stringify(payload) });
    return res.json();
}
function formatTime(timestamp, withDate = true) {
    const date = new Date(timestamp * 1000);
    if (withDate) return date.toLocaleString(document.documentElement.lang || undefined, { day:"2-digit", month:"2-digit", hour:"2-digit", minute:"2-digit" });
    return date.toLocaleTimeString(document.documentElement.lang || undefined, { hour:"2-digit", minute:"2-digit" });
}
function scrollToBottom() {
    const container = document.getElementById("messages-container");
    if (container) container.scrollTop = container.scrollHeight;
}
function parseImageUrls(urls) {
    if (!urls) return [];
    if (Array.isArray(urls)) return urls;
    try { const parsed = JSON.parse(urls); return Array.isArray(parsed) ? parsed : []; } catch { return []; }
}
function escapeHtml(str) {
    if (!str) return "";
    return str.replace(/[&<>]/g, (m) => {
        if (m === "&") return "&amp;";
        if (m === "<") return "&lt;";
        if (m === ">") return "&gt;";
        return m;
    });
}
function showNotification(msg, type) {
    if (window.showNotification) window.showNotification(msg, type);
    else alert(msg);
}

// ==================== Сжатие изображений ====================
async function compressImage(file) {
    if (file.type === "image/gif" || file.size < 100 * 1024) return file;
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e) => {
            const img = new Image();
            img.src = e.target.result;
            img.onload = () => {
                let width = img.width, height = img.height;
                if (width > MSG_MAX_WIDTH || height > MSG_MAX_HEIGHT) {
                    const ratio = Math.min(MSG_MAX_WIDTH / width, MSG_MAX_HEIGHT / height);
                    width = Math.floor(width * ratio);
                    height = Math.floor(height * ratio);
                }
                const canvas = document.createElement("canvas");
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, width, height);
                let mime = "image/webp", quality = MSG_WEBP_QUALITY;
                if (!canvas.toBlob) { mime = "image/jpeg"; quality = MSG_JPEG_QUALITY; }
                canvas.toBlob((blob) => {
                    if (!blob) reject(new Error(window.t('blob_creation_error')));
                    else {
                        let newName = file.name;
                        if (mime === "image/webp" && !newName.endsWith(".webp")) newName = newName.replace(/\.(jpe?g|png)$/i, ".webp");
                        const compressed = new File([blob], newName, { type: mime, lastModified: Date.now() });
                        resolve(compressed);
                    }
                }, mime, quality);
            };
            img.onerror = () => reject(new Error(window.t('image_loading_error')));
        };
        reader.onerror = () => reject(new Error(window.t('file_reading_error')));
    });
}
async function handleImageSelection() {
    const input = document.getElementById("message-image-input");
    const preview = document.getElementById("message-image-preview");
    if (!input?.files?.length) return;
    const files = Array.from(input.files);
    try {
        const compressed = await Promise.all(files.map(compressImage));
        selectedImageFiles = compressed;
        if (preview) {
            preview.innerHTML = selectedImageFiles.map((_, i) => `
                <div class="message-image-preview-item">
                    <img data-index="${i}" src="" alt="Preview">
                    <button class="btn-remove-image" onclick="removeImageMessage(${i})" title="${window.t('delete')}">×</button>
                </div>
            `).join('');
            selectedImageFiles.forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = preview.querySelector(`img[data-index="${i}"]`);
                    if (img) img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }
    } catch(err) {
        selectedImageFiles = files;
        input.value = "";
        if (preview) preview.innerHTML = "";
    }
}
function removeImageMessage(index) {
    selectedImageFiles.splice(index, 1);
    const input = document.getElementById("message-image-input");
    const preview = document.getElementById("message-image-preview");
    if (selectedImageFiles.length === 0) {
        if (input) input.value = "";
        if (preview) preview.innerHTML = "";
    } else {
        if (preview) {
            preview.innerHTML = selectedImageFiles.map((_, i) => `
                <div class="message-image-preview-item">
                    <img data-index="${i}" src="" alt="Preview">
                    <button class="btn-remove-image" onclick="removeImageMessage(${i})" title="${window.t('delete')}">×</button>
                </div>
            `).join('');
            selectedImageFiles.forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = preview.querySelector(`img[data-index="${i}"]`);
                    if (img) img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }
    }
}

// ==================== Отправка сообщений ====================
async function sendMessage() {
    const input = document.getElementById("message-input");
    const content = input?.value.trim() || "";
    if (!content && selectedImageFiles.length === 0) return;
    if ([...content].length > 1000) {
        showNotification(window.t('message_too_long'), "error");
        return;
    }
    if (selectedImageFiles.length > 0) await sendImageMessage(content);
    else await sendTextMessage(content);
}

async function sendTextMessage(plainText) {
    try {
        const result = await postJson("/api/message/send", {
            receiver_id: receiverId,
            content: plainText
        });
        if (result.success) {
            const sentMessages = JSON.parse(localStorage.getItem('sent_messages') || '{}');
            sentMessages[result.message.id] = plainText;
            localStorage.setItem('sent_messages', JSON.stringify(sentMessages));
            await addMessageToChat(result.message, true, plainText);
            document.getElementById("message-input").value = "";
            scrollToBottom();
        } else {
            showNotification(result.error || window.t('send_error'), "error");
        }
    } catch (error) {
        showNotification(window.t('send_error'), "error");
    }
}

async function sendImageMessage(content) {
    const formData = new FormData();
    formData.append("receiver_id", receiverId);
    formData.append("content", content || '');
    selectedImageFiles.forEach(file => formData.append("images[]", file));
    try {
        const res = await fetch("/api/message/upload-images", { method: "POST", body: formData });
        const result = await res.json();
        if (result.success && result.message) {
            if (content) {
                const sentMessages = JSON.parse(localStorage.getItem('sent_messages') || '{}');
                sentMessages[result.message.id] = content;
                localStorage.setItem('sent_messages', JSON.stringify(sentMessages));
            }
            await addMessageToChat(result.message, true, content || '');
            showNotification(window.t('message_sent'), "success");
        } else {
            showNotification(result.error || window.t('send_error'), "error");
        }
    } catch (error) { showNotification(window.t('send_error'), "error"); }
    selectedImageFiles = [];
    const input = document.getElementById("message-input");
    const imageInput = document.getElementById("message-image-input");
    const preview = document.getElementById("message-image-preview");
    if (input) input.value = "";
    if (imageInput) imageInput.value = "";
    if (preview) preview.innerHTML = "";
    scrollToBottom();
}

// ==================== Отображение сообщений ====================
function buildMessageBubble(message) {
    const images = parseImageUrls(message.image_urls);
    const hasText = message.content && message.content.trim().length > 0;
    
    if (images.length === 0) {
        return `<div class="message-text" data-decrypted="false"></div>`;
    }
    
    // Если есть только изображения без текста - не создаём пустой блок
    if (!hasText) {
        if (images.length === 1) {
            return `<div class="message-image-bubble"><img class="message-image-content" src="${escapeHtml(images[0])}" alt="Image"></div>`;
        }
        // несколько изображений
        return `<div class="message-grouped-bubble">${images.map(url => `<img class="grouped-image" src="${escapeHtml(url)}" alt="Image">`).join('')}</div>`;
    }
    
    // Есть и текст, и изображения
    if (images.length === 1) {
        return `<div class="message-image-bubble"><img class="message-image-content" src="${escapeHtml(images[0])}" alt="Image"></div><div class="message-text" data-decrypted="false"></div>`;
    }
    // несколько изображений + текст
    return `<div class="message-grouped-bubble">${images.map(url => `<img class="grouped-image" src="${escapeHtml(url)}" alt="Image">`).join('')}</div><div class="message-text" data-decrypted="false"></div>`;
}

async function addMessageToChat(message, isSent, originalText = null) {
    const container = document.getElementById("messages-container");
    if (!container) return;
    const emptyState = container.querySelector(".empty-state");
    if (emptyState) emptyState.remove();
    
    const images = parseImageUrls(message.image_urls);
    const timestamp = message.created_at || Math.floor(Date.now()/1000);
    const isGrouped = images.length > 1;
    const isSingleImage = images.length === 1;
    const msgDiv = document.createElement("div");
    msgDiv.className = `message ${isSent ? "sent" : "received"} ${isGrouped ? "message-grouped-images" : ""} ${isSingleImage ? "message-image-message" : ""}`;
    if (message.id) msgDiv.dataset.messageId = message.id;
    
    msgDiv.innerHTML = `
        <a href="/profile/${escapeHtml(message.sender.id)}" class="message-avatar-link" onclick="event.stopPropagation()">
            <img class="message-avatar" src="${escapeHtml(message.sender.avatar)}" alt="${escapeHtml(message.sender.username)}">
        </a>
        <div class="message-bubble">
            ${buildMessageBubble(message)}
            <div class="message-time" data-timestamp="${timestamp}">${formatTime(timestamp)}</div>
        </div>
    `;
    container.appendChild(msgDiv);
    
    // Заполняем текст сообщения
    const textDiv = msgDiv.querySelector('.message-text');
    if (textDiv) {
        if (isSent && originalText !== null) {
            textDiv.innerHTML = escapeHtml(originalText);
        } else if (message.content) {
            textDiv.innerHTML = escapeHtml(message.content);
        } else {
            textDiv.innerHTML = '';
        }
        textDiv.setAttribute('data-decrypted', 'true');
    }
    
    // Принудительно навешиваем обработчики клика на изображения в этом сообщении
    attachImageClickHandlers(msgDiv);
    
    // Инициализируем обработчики контекстного меню для удаления
    attachMessageContextHandlers();
    
    scrollToBottom();
}

// ==================== Поллинг и диалоги ====================
async function markAsRead(senderId) {
    try { await postJson("/api/message/mark-read", { sender_id: senderId }); } catch {}
}
async function pollMessages() {
    if (!receiverId || document.visibilityState === "hidden") return;
    try {
        await markAsRead(receiverId);
        const res = await fetch(`/api/message/get-dialogue/${receiverId}`);
        const data = await res.json();
        if (data.success && data.messages?.length) {
            const container = document.getElementById("messages-container");
            if (!container) return;
            const existingIds = new Set();
            container.querySelectorAll(".message").forEach(msg => {
                const id = msg.dataset.messageId;
                const time = msg.querySelector(".message-time")?.dataset.timestamp;
                if (id) existingIds.add(`id:${id}`);
                else if (time) existingIds.add(`ts:${time}`);
            });
            let hasNew = false;
            for (const msg of data.messages) {
                const key = msg.id ? `id:${msg.id}` : `ts:${msg.created_at || Math.floor(Date.now()/1000)}`;
                if (!existingIds.has(key)) {
                    await addMessageToChat(msg, msg.sender_id === currentUserId);
                    hasNew = true;
                }
            }
            if (hasNew) scrollToBottom();
        }
    } catch (error) { /* игнорируем */ }
}
function formatDialogueTime(timestamp) {
    const diff = Math.floor(Date.now()/1000) - timestamp;
    if (diff < 60) return window.t('just_now');
    if (diff < 3600) return `${Math.floor(diff/60)} ${window.t('minutes_ago')}`;
    if (diff < 86400) return `${Math.floor(diff/3600)} ${window.t('hours_ago')}`;
    if (diff < 2592000) return `${Math.floor(diff/86400)} ${window.t('days_ago')}`;
    return new Date(timestamp*1000).toLocaleDateString(document.documentElement.lang || undefined);
}
async function updateDialoguesList() {
    if (window.location.pathname !== "/message") return;
    try {
        const res = await fetch('/api/message/get-dialogues');
        const data = await res.json();
        const container = document.querySelector('.dialogues-container');
        if (!container) return;
        if (!data.success || !data.dialogues?.length) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">💬</div><p>' + window.t('no_messages') + '</p><a href="/search" class="btn-find-users">' + window.t('find_users') + '</a></div>';
            return;
        }
        let list = container.querySelector('.dialogues-list');
        if (!list) {
            list = document.createElement('div');
            list.className = 'dialogues-list';
            container.innerHTML = '';
            container.appendChild(list);
        }
        list.innerHTML = data.dialogues.map(d => `
            <div class="dialogue-item" data-last-message-time="${d.last_message_time}" onclick="window.location.href='/message/dialogue/${d.user.id}'">
                <a href="/profile/${d.user.id}" class="dialogue-avatar-link" onclick="event.stopPropagation()">
                    <img class="dialogue-avatar" src="${escapeHtml(d.user.avatar)}" alt="${escapeHtml(d.user.username)}">
                </a>
                <div class="dialogue-content">
                    <div class="dialogue-header-info">
                        <div class="dialogue-user">${escapeHtml(d.user.username)}</div>
                        <div class="dialogue-time">${formatDialogueTime(d.last_message_time)}</div>
                    </div>
                    <div class="dialogue-preview">${escapeHtml(d.last_message || window.t('start_dialogue'))}</div>
                    ${d.unread_count > 0 ? `<span class="unread-badge">${d.unread_count}</span>` : ''}
                </div>
            </div>
        `).join('');
        const items = Array.from(list.children);
        items.sort((a,b) => (b.dataset.lastMessageTime||0) - (a.dataset.lastMessageTime||0));
        items.forEach(item => list.appendChild(item));
    } catch {}
}
async function updateUnreadCount() {
    try {
        const res = await fetch('/api/message/unread-count');
        const data = await res.json();
        const badge = document.querySelector('.messages-badge');
        if (badge) {
            if (data.success && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline-block';
            } else badge.style.display = 'none';
        }
    } catch {}
}
// ==================== Полноэкранный просмотр изображений ====================
let currentMessageImages = [];
let currentMessageImageIndex = 0;

function viewImageMessage(imageElement, messageElement = null) {
    if (!messageElement) {
        messageElement = imageElement.closest('.message');
    }
    
    const images = messageElement ? messageElement.querySelectorAll('.message-image-content, .grouped-image') : [];
    const imageUrl = imageElement.src;

    // Если в сообщении несколько изображений — открываем fullscreen с навигацией
    if (images.length > 1) {
        currentMessageImages = Array.from(images).map(img => img.src);
        currentMessageImageIndex = Array.from(images).findIndex(img => img.src === imageUrl);
        if (currentMessageImageIndex < 0) currentMessageImageIndex = 0;
        
        openMessageImageFullscreen(currentMessageImages[currentMessageImageIndex]);
        return;
    }
    
    // Одно изображение — простая модалка
    const existing = document.querySelector('.message-image-viewer-modal');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.className = 'message-image-viewer-modal';
    modal.innerHTML = `
        <div class="image-viewer-overlay" onclick="this.closest('.message-image-viewer-modal')?.remove()">
            <div class="image-viewer-content" onclick="event.stopPropagation()">
                <button class="image-viewer-close-btn" onclick="this.closest('.message-image-viewer-modal')?.remove()">✕</button>
                <img class="image-viewer-image" src="${escapeHtml(imageUrl)}" alt="Full size image">
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function openMessageImageFullscreen(imageUrl) {
    const existing = document.querySelector('.message-image-viewer-modal.fullscreen');
    if (existing) existing.remove();
    
    const modal = document.createElement('div');
    modal.className = 'message-image-viewer-modal fullscreen';
    const hasMultiple = currentMessageImages.length > 1;
    
    modal.innerHTML = `
        <div class="image-viewer-overlay" onclick="closeMessageImageFullscreen()"></div>
        <div class="image-viewer-content" onclick="event.stopPropagation()">
            <button class="image-viewer-close-btn" onclick="closeMessageImageFullscreen()">✕</button>
            <img class="image-viewer-image" src="${escapeHtml(imageUrl)}" alt="Full size image">
        </div>
        ${hasMultiple ? `
            <button class="image-viewer-prev" data-action="prev">‹</button>
            <button class="image-viewer-next" data-action="next">›</button>
            <div class="image-viewer-counter">${currentMessageImageIndex + 1} / ${currentMessageImages.length}</div>
        ` : ''}
    `;
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // Навешиваем обработчики на кнопки
    if (hasMultiple) {
        const prevBtn = modal.querySelector('.image-viewer-prev');
        const nextBtn = modal.querySelector('.image-viewer-next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                prevMessageImage();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                nextMessageImage();
            });
        }
    }
}
    
function closeMessageImageFullscreen() {
    const modal = document.querySelector('.message-image-viewer-modal.fullscreen');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

function prevMessageImage(e) {
    e?.preventDefault();
    e?.stopPropagation();
    if (currentMessageImages.length <= 1) return;
    currentMessageImageIndex = (currentMessageImageIndex - 1 + currentMessageImages.length) % currentMessageImages.length;
    updateMessageImage();
}

function nextMessageImage(e) {
    e?.preventDefault();
    e?.stopPropagation();
    if (currentMessageImages.length <= 1) return;
    currentMessageImageIndex = (currentMessageImageIndex + 1) % currentMessageImages.length;
    updateMessageImage();
}

function updateMessageImage() {
    const modal = document.querySelector('.message-image-viewer-modal.fullscreen');
    if (!modal) return;
    
    const img = modal.querySelector('.image-viewer-image');
    const counter = modal.querySelector('.image-viewer-counter');
    
    if (img) {
        img.style.opacity = '0';
        setTimeout(() => {
            img.src = currentMessageImages[currentMessageImageIndex];
            img.style.opacity = '1';
        }, 150);
    }
    
    if (counter) {
        counter.textContent = `${currentMessageImageIndex + 1} / ${currentMessageImages.length}`;
    }
}

// Обработчик клавиш для навигации
document.addEventListener('keydown', (e) => {
    const modal = document.querySelector('.message-image-viewer-modal.fullscreen');
    if (!modal) return;
    
    if (e.key === 'Escape') {
        closeMessageImageFullscreen();
    } else if (e.key === 'ArrowLeft') {
        prevMessageImage();
    } else if (e.key === 'ArrowRight') {
        nextMessageImage();
    }
});

function showContextMenu(e, messageId, isOwn) {
    e.preventDefault();
    e.stopPropagation();
    
    if (!isOwn) return; // Можно удалять только свои сообщения
    
    hideContextMenu();
    
    const menu = document.createElement('div');
    menu.className = 'message-context-menu';
    menu.innerHTML = `
        <button class="context-menu-item delete-message-btn" onclick="deleteMessage(${messageId})">
            🗑️ ${window.t('delete')}
        </button>
    `;
    
    // Позиционируем меню
    const x = e.type === 'contextmenu' ? e.clientX : e.touches[0].clientX;
    const y = e.type === 'contextmenu' ? e.clientY : e.touches[0].clientY;
    menu.style.left = `${Math.min(x, window.innerWidth - 150)}px`;
    menu.style.top = `${Math.min(y, window.innerHeight - 100)}px`;
    
    document.body.appendChild(menu);
    contextMenuElement = menu;
    
    // Закрываем меню при клике вне его
    setTimeout(() => {
        document.addEventListener('click', hideContextMenu);
    }, 100);
}

function hideContextMenu() {
    if (contextMenuElement) {
        contextMenuElement.remove();
        contextMenuElement = null;
    }
    document.removeEventListener('click', hideContextMenu);
}

async function deleteMessage(messageId) {
    hideContextMenu();
    
    // Используем кастомную модалку вместо browser confirm
    if (typeof window.showDeleteModal === 'function') {
        window.showDeleteModal(window.t('delete_message_question'), async () => {
            try {
                const response = await postJson('/api/message/delete', { message_id: messageId });
                
                if (response.success) {
                    showNotification(window.t('message_deleted'), 'success');
                    
                    // Удаляем сообщение из DOM
                    const messageEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
                    if (messageEl) {
                        messageEl.style.transition = 'opacity 0.3s ease';
                        messageEl.style.opacity = '0';
                        setTimeout(() => messageEl.remove(), 300);
                    }
                    
                    // Удаляем из localStorage если есть
                    const sentMessages = JSON.parse(localStorage.getItem('sent_messages') || '{}');
                    delete sentMessages[messageId];
                    localStorage.setItem('sent_messages', JSON.stringify(sentMessages));
                } else {
                    showNotification(response.error || window.t('error_deleting'), 'error');
                }
            } catch (error) {
                console.error('Delete message error:', error);
                showNotification(window.t('error_deleting'), 'error');
            }
        });
    } else {
        // Fallback если showDeleteModal недоступна
        if (!confirm(window.t('delete_message_question'))) return;
        
        try {
            const response = await postJson('/api/message/delete', { message_id: messageId });
            
            if (response.success) {
                showNotification(window.t('message_deleted'), 'success');
                
                const messageEl = document.querySelector(`.message[data-message-id="${messageId}"]`);
                if (messageEl) {
                    messageEl.style.transition = 'opacity 0.3s ease';
                    messageEl.style.opacity = '0';
                    setTimeout(() => messageEl.remove(), 300);
                }
                
                const sentMessages = JSON.parse(localStorage.getItem('sent_messages') || '{}');
                delete sentMessages[messageId];
                localStorage.setItem('sent_messages', JSON.stringify(sentMessages));
            } else {
                showNotification(response.error || window.t('error_deleting'), 'error');
            }
        } catch (error) {
            console.error('Delete message error:', error);
            showNotification(window.t('error_deleting'), 'error');
        }
    }
}

function attachMessageContextHandlers() {
    const container = document.getElementById('messages-container');
    if (!container) return;
    
    container.querySelectorAll('.message').forEach(msg => {
        const messageId = msg.dataset.messageId;
        const isSent = msg.classList.contains('sent');
        const isOwn = isSent && parseInt(messageId) > 0;
        
        if (isOwn && messageId && !msg.dataset.contextHandler) {
            msg.dataset.contextHandler = 'true';
            
            // Правая кнопка мыши (десктоп)
            msg.addEventListener('contextmenu', (e) => {
                showContextMenu(e, messageId, isOwn);
            });
            
            // Долгий тап (мобильные)
            let touchTimer = null;
            msg.addEventListener('touchstart', (e) => {
                if (!isOwn) return;
                touchTimer = setTimeout(() => {
                    showContextMenu(e, messageId, isOwn);
                    // Вибрация для обратной связи
                    if (navigator.vibrate) navigator.vibrate(50);
                }, 500);
            }, { passive: true });
            
            msg.addEventListener('touchend', () => {
                if (touchTimer) clearTimeout(touchTimer);
            });
            
            msg.addEventListener('touchmove', () => {
                if (touchTimer) clearTimeout(touchTimer);
            });
        }
    });
}

// ==================== Инициализация обработчиков ====================
function initMessageHandlers() {
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-message-btn');
    const uploadBtn = document.getElementById('btn-upload-image');
    const imageInput = document.getElementById('message-image-input');
    
    if (!sendBtn) return;
    
    if (uploadBtn && imageInput) {
        uploadBtn.addEventListener('click', (e) => { e.preventDefault(); imageInput.click(); });
        imageInput.addEventListener('change', handleImageSelection);
    }
    if (input && sendBtn) {
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });
        sendBtn.addEventListener('click', (e) => { e.preventDefault(); sendMessage(); });
        input.focus();
        scrollToBottom();
        if (receiverId) {
            const isMobile = window.innerWidth < 768;
            setInterval(pollMessages, isMobile ? 5000 : 3000);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') pollMessages();
            });
        }
    }
    
    // Инициализируем обработчики для клика по изображениям (делегирование)
    const container = document.getElementById('messages-container');
    if (container) {
        container.addEventListener('click', function(e) {
            const img = e.target.closest('.message-image-content, .grouped-image');
            if (img) {
                e.preventDefault();
                e.stopPropagation();
                viewImageMessage(img, img.closest('.message'));
            }
        });
    }
    
    // Инициализируем обработчики контекстного меню для удаления сообщений
    attachMessageContextHandlers();
}

// ==================== Точка входа ====================
document.addEventListener("DOMContentLoaded", () => {
    if (!receiverId) {
        const input = document.getElementById("message-input");
        if (input?.dataset.receiverId) receiverId = input.dataset.receiverId;
    }
    if (!currentUserId) {
        const meta = document.querySelector('meta[name="current-user-id"]');
        if (meta) currentUserId = meta.content;
    }
    initMessageHandlers();
});

// ==================== Глобальные экспорты ====================
window.sendMessage = sendMessage;
window.removeImageMessage = removeImageMessage;
window.viewImageMessage = viewImageMessage;
window.deleteMessage = deleteMessage;
window.showContextMenu = showContextMenu;
window.hideContextMenu = hideContextMenu;
window.closeMessageImageFullscreen = closeMessageImageFullscreen;
window.prevMessageImage = prevMessageImage;
window.nextMessageImage = nextMessageImage;
window.openMessageImageFullscreen = openMessageImageFullscreen;
