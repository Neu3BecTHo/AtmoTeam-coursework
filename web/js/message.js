// ==================== Инициализация ====================
let currentUserId = window.currentUserId;
let receiverId = window.receiverId;
let selectedImageFiles = [];

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
    if (withDate) return date.toLocaleString("ru-RU", { day:"2-digit", month:"2-digit", hour:"2-digit", minute:"2-digit" });
    return date.toLocaleTimeString("ru-RU", { hour:"2-digit", minute:"2-digit" });
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
                    if (!blob) reject(new Error("Не удалось создать Blob"));
                    else {
                        let newName = file.name;
                        if (mime === "image/webp" && !newName.endsWith(".webp")) newName = newName.replace(/\.(jpe?g|png)$/i, ".webp");
                        const compressed = new File([blob], newName, { type: mime, lastModified: Date.now() });
                        resolve(compressed);
                    }
                }, mime, quality);
            };
            img.onerror = () => reject(new Error("Ошибка загрузки изображения"));
        };
        reader.onerror = () => reject(new Error("Ошибка чтения файла"));
    });
}
async function handleImageSelection() {
    const input = document.getElementById("message-image-input");
    const preview = document.getElementById("message-image-preview");
    if (!input?.files?.length) return;
    const files = Array.from(input.files);
    showNotification("Сжатие изображений...", "info");
    try {
        const compressed = await Promise.all(files.map(compressImage));
        selectedImageFiles = compressed;
        if (preview) {
            preview.innerHTML = selectedImageFiles.map((_, i) => `
                <div class="message-image-preview-item">
                    <img data-index="${i}" src="" alt="Preview">
                    <button class="btn-remove-image" onclick="removeImageMessage(${i})" title="Удалить">×</button>
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
        showNotification(`${selectedImageFiles.length} изображений готово`, "success");
    } catch(err) {
        showNotification("Ошибка сжатия, используются оригиналы", "error");
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
                    <button class="btn-remove-image" onclick="removeImageMessage(${i})" title="Удалить">×</button>
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
        showNotification("Сообщение слишком длинное (максимум 1000 символов)", "error");
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
            showNotification(result.error || "Ошибка отправки", "error");
        }
    } catch (error) {
        showNotification("Ошибка отправки", "error");
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
            showNotification("Сообщение отправлено", "success");
        } else {
            showNotification(result.error || "Ошибка отправки", "error");
        }
    } catch (error) { showNotification("Ошибка отправки", "error"); }
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
    if (images.length === 0) {
        return `<div class="message-text" data-decrypted="false"></div>`;
    }
    if (images.length === 1) {
        return `<div class="message-image-bubble"><img class="message-image-content" src="${escapeHtml(images[0])}" alt="Image"></div><div class="message-text" data-decrypted="false"></div>`;
    }
    // несколько изображений
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
    
    scrollToBottom();
}

// Привязка обработчиков клика к изображениям
function attachImageClickHandlers(container) {
    const images = container.querySelectorAll('.message-image-content, .grouped-image');
    images.forEach(img => {
        img.removeEventListener('click', imageClickHandler);
        img.addEventListener('click', imageClickHandler);
    });
}

function imageClickHandler(e) {
    e.preventDefault();
    e.stopPropagation();
    viewImageMessage(this.src);
}

// Глобальный делегат на случай, если обработчики не навесились
document.addEventListener("click", (e) => {
    const img = e.target.closest(".message-image-content, .grouped-image");
    if (img) {
        e.preventDefault();
        e.stopPropagation();
        viewImageMessage(img.src);
    }
});

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
    if (diff < 60) return 'только что';
    if (diff < 3600) return `${Math.floor(diff/60)} мин. назад`;
    if (diff < 86400) return `${Math.floor(diff/3600)} ч. назад`;
    if (diff < 2592000) return `${Math.floor(diff/86400)} дн. назад`;
    return new Date(timestamp*1000).toLocaleDateString('ru-RU');
}
async function updateDialoguesList() {
    if (window.location.pathname !== "/message") return;
    try {
        const res = await fetch('/api/message/get-dialogues');
        const data = await res.json();
        const container = document.querySelector('.dialogues-container');
        if (!container) return;
        if (!data.success || !data.dialogues?.length) {
            container.innerHTML = '<div class="empty-state"><div class="empty-icon">💬</div><p>У вас пока нет сообщений</p><a href="/search" class="btn-find-users">Найти пользователей</a></div>';
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
                    <div class="dialogue-preview">${escapeHtml(d.last_message || "Начните диалог...")}</div>
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
function viewImageMessage(imageUrl) {
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