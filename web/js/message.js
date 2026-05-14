

let currentUserId = window.currentUserId;
let receiverId = window.receiverId;

if (!receiverId || !currentUserId) {
    document.addEventListener('DOMContentLoaded', () => {
        if (!receiverId) {
            const input = document.getElementById('message-input');
            if (input) {
                receiverId = input.dataset.receiverId;
            }
        }
        
        if (!currentUserId) {
            const meta = document.querySelector('meta[name="current-user-id"]');
            if (meta) {
                currentUserId = meta.content;
            }
        }

        initMessageHandlers();
    });
}

async function sendMessage() {
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    
    if (!content) {
        return;
    }

    const messageLength = [...content].length;
    if (messageLength > 1000) {
        showNotification('Сообщение слишком длинное (максимум 1000 символов)', 'error');
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'Content-Type': 'application/json' };
        if (csrfToken) {
            headers['X-CSRF-Token'] = csrfToken;
        }
        
        const response = await fetch('/api/message/send', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({
                receiver_id: receiverId,
                content: content
            })
        });
        
        const result = await response.json();
        if (result.success) {

            addMessageToChat(result.message, true);

            input.value = '';

            scrollToBottom();
        } else {
            showNotification(result.error || 'Ошибка отправки', 'error');
        }
    } catch (error) {
        
        showNotification('Ошибка отправки', 'error');
    }
}

function addMessageToChat(message, isSent = false) {
    const container = document.getElementById('messages-container');

    const emptyState = container.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }
    
    const timestamp = message.created_at || Math.floor(Date.now() / 1000);
    const messageEl = document.createElement('div');
    messageEl.className = `message ${isSent ? 'sent' : 'received'}`;
    messageEl.innerHTML = `
        <a href="/profile/${message.sender.id}" class="message-avatar-link" onclick="event.stopPropagation()">
            <img class="message-avatar" 
                 src="${message.sender.avatar}" 
                 alt="${message.sender.username}">
        </a>
        <div class="message-bubble">
            <div class="message-text">${escapeHtml(message.content)}</div>
            <div class="message-time" data-timestamp="${timestamp}">${formatMessageTime(timestamp)}</div>
        </div>
    `;
    
    container.appendChild(messageEl);
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}

async function markAsRead(senderId) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const headers = { 'Content-Type': 'application/json' };
        if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
        
        await fetch('/api/message/mark-read', {
            method: 'POST',
            headers: headers,
            body: JSON.stringify({ sender_id: senderId })
        });
    } catch (error) {
        
    }
}

async function pollMessages() {
    if (!receiverId || document.visibilityState === 'hidden') return;
    
    try {

        await markAsRead(receiverId);

        const response = await fetch(`/api/message/get-dialogue/${receiverId}`);
        if (response.ok) {
            const data = await response.json();
            if (data.success && data.messages && data.messages.length > 0) {
                const container = document.getElementById('messages-container');
                if (container) {

                    const existingMessages = container.querySelectorAll('.message');
                    const existingIds = new Set();
                    
                    existingMessages.forEach(msgEl => {

                        const timestamp = msgEl.querySelector('.message-time')?.dataset.timestamp;
                        if (timestamp) {
                            existingIds.add(timestamp);
                        }
                    });

                    let hasNewMessages = false;
                    data.messages.forEach(msg => {
                        const timestamp = msg.created_at || Math.floor(Date.now() / 1000);

                        if (!existingIds.has(timestamp.toString())) {
                            const messageEl = document.createElement('div');
                            messageEl.className = `message ${msg.sender_id === currentUserId ? 'sent' : 'received'} message-new`;
                            messageEl.innerHTML = `
                                <a href="/profile/${msg.sender_id}" class="message-avatar-link" onclick="event.stopPropagation()">
                                    <img class="message-avatar" src="${msg.sender.avatar}" alt="${msg.sender.username}">
                                </a>
                                <div class="message-bubble">
                                    <div class="message-text">${escapeHtml(msg.content)}</div>
                                    <div class="message-time" data-timestamp="${timestamp}">${formatMessageTime(timestamp)}</div>
                                </div>
                            `;
                            container.appendChild(messageEl);
                            hasNewMessages = true;
                        }
                    });

                    if (hasNewMessages) {
                        scrollToBottom();

                        setTimeout(() => {
                            container.querySelectorAll('.message-new').forEach(el => {
                                el.classList.remove('message-new');
                            });
                        }, 1000);
                    }
                }
            }
        }
    } catch (error) {
        
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatMessageTime(timestamp) {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString('ru-RU', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit'
    });
}

function updateMessageTimes() {
    document.querySelectorAll('.message-time[data-timestamp]').forEach(el => {
        const timestamp = parseInt(el.dataset.timestamp);
        if (!isNaN(timestamp)) {
            el.textContent = formatMessageTime(timestamp);
        }
    });
}

function initMessageHandlers() {
    const input = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-message-btn');

    updateMessageTimes();
    
    if (input && sendBtn) {

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        sendBtn.addEventListener('click', () => {
            sendMessage();
        });

        input.focus();

        scrollToBottom();

        if (receiverId) {

            const isMobile = window.innerWidth < 768;
            const pollInterval = isMobile ? 20000 : 10000; // 20с на мобильных, 10с на десктопе
            
            setInterval(pollMessages, pollInterval);

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    pollMessages();
                }
            });
        }
    }
}

if (currentUserId && receiverId) {
    document.addEventListener('DOMContentLoaded', () => {

        initMessageHandlers();
    });
}

async function updateUnreadCount() {
    try {
        const response = await fetch('/api/message/unread-count');
        const data = await response.json();
        
        if (data.success) {

            const badge = document.querySelector('.messages-badge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    } catch (error) {
        
    }
}

async function updateDialoguesList() {
    if (window.location.pathname !== '/message') {
        return; // Только на странице списка диалогов
    }
    
    try {
        const response = await fetch('/api/message/get-dialogues');
        const data = await response.json();
        
        if (data.success && data.dialogues) {
            const container = document.querySelector('.dialogues-list');
            const emptyState = document.querySelector('.empty-state');
            const dialoguesContainer = document.querySelector('.dialogues-container');
            
            if (!dialoguesContainer) return;
            
            if (data.dialogues.length === 0) {

                if (container) container.remove();
                if (!emptyState) {
                    dialoguesContainer.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon">💬</div>
                            <p>У вас пока нет сообщений</p>
                            <a href="/search" class="btn-find-users">
                                Найти пользователей для общения
                            </a>
                        </div>
                    `;
                }
            } else {

                if (emptyState) emptyState.remove();

                if (!container) {
                    const newContainer = document.createElement('div');
                    newContainer.className = 'dialogues-list';
                    dialoguesContainer.appendChild(newContainer);
                    renderDialogues(newContainer, data.dialogues);
                } else {
                    renderDialogues(container, data.dialogues);
                }
            }
        }
    } catch (error) {
        
    }
}

function renderDialogues(container, dialogues) {
    const existingDialogues = new Map();

    container.querySelectorAll('.dialogue-item').forEach(item => {
        const userId = item.href.match(/\/message\/dialogue\/(\d+)/)?.[1];
        if (userId) {
            existingDialogues.set(userId, item);
        }
    });

    const newDialogues = new Map();
    dialogues.forEach(dialogue => {
        newDialogues.set(dialogue.user.id.toString(), dialogue);
    });

    existingDialogues.forEach((item, userId) => {
        if (!newDialogues.has(userId)) {
            item.remove();
        }
    });

    dialogues.forEach(dialogue => {
        const userId = dialogue.user.id.toString();
        const existingItem = existingDialogues.get(userId);
        
        if (existingItem) {

            updateDialogueItem(existingItem, dialogue);
        } else {

            const newItem = createDialogueItem(dialogue);
            container.appendChild(newItem);
        }
    });

    const items = Array.from(container.querySelectorAll('.dialogue-item'));
    items.sort((a, b) => {
        const timeA = parseInt(a.dataset.lastMessageTime) || 0;
        const timeB = parseInt(b.dataset.lastMessageTime) || 0;
        return timeB - timeA;
    });

    items.forEach(item => container.appendChild(item));
}

function createDialogueItem(dialogue) {
    const item = document.createElement('div');
    item.className = 'dialogue-item dialogue-new';
    item.dataset.lastMessageTime = dialogue.last_message_time;
    
    item.innerHTML = `
        <a href="/profile/${dialogue.user.id}" class="dialogue-avatar-link" onclick="event.stopPropagation()">
            <img class="dialogue-avatar" 
                 src="${dialogue.user.avatar}" 
                 alt="${dialogue.user.username}">
        </a>
        <div class="dialogue-content" onclick="window.location.href='/message/dialogue/${dialogue.user.id}'">
            <div class="dialogue-info">
                <div class="dialogue-header-info">
                    <div class="dialogue-user">${escapeHtml(dialogue.user.username)}</div>
                    <div class="dialogue-time">${formatDialogueTime(dialogue.last_message_time)}</div>
                </div>
                <div class="dialogue-preview">${escapeHtml(dialogue.last_message || 'Начните диалог...')}</div>
                <div class="dialogue-status">
                    <div class="dialogue-online-indicator"></div>
                </div>
            </div>
            ${dialogue.unread_count > 0 ? `<span class="unread-badge">${dialogue.unread_count}</span>` : ''}
        </div>
    `;

    setTimeout(() => {
        item.classList.remove('dialogue-new');
    }, 1000);
    
    return item;
}

function updateDialogueItem(item, dialogue) {
    item.dataset.lastMessageTime = dialogue.last_message_time;
    
    const avatar = item.querySelector('.dialogue-avatar');
    const username = item.querySelector('.dialogue-user');
    const time = item.querySelector('.dialogue-time');
    const preview = item.querySelector('.dialogue-preview');
    const badge = item.querySelector('.unread-badge');
    const content = item.querySelector('.dialogue-content');

    if (avatar) {
        avatar.src = dialogue.user.avatar;
        avatar.alt = dialogue.user.username;

        if (!avatar.parentElement.classList.contains('dialogue-avatar-link')) {
            const avatarLink = document.createElement('a');
            avatarLink.href = `/profile/${dialogue.user.id}`;
            avatarLink.className = 'dialogue-avatar-link';
            avatarLink.onclick = (e) => e.stopPropagation();
            avatar.parentNode.insertBefore(avatarLink, avatar);
            avatarLink.appendChild(avatar);
        }
    }
    
    if (username) username.textContent = dialogue.user.username;
    if (time) time.textContent = formatDialogueTime(dialogue.last_message_time);
    if (preview) preview.textContent = dialogue.last_message || 'Начните диалог...';

    if (content) {
        content.onclick = () => {
            window.location.href = `/message/dialogue/${dialogue.user.id}`;
        };
    }
    
    if (dialogue.unread_count > 0) {
        if (!badge) {
            const newBadge = document.createElement('span');
            newBadge.className = 'unread-badge';
            newBadge.textContent = dialogue.unread_count;
            const info = item.querySelector('.dialogue-info');
            if (info) {
                info.appendChild(newBadge);
            }
        } else {
            badge.textContent = dialogue.unread_count;
        }
    } else if (badge) {
        badge.remove();
    }
}

function formatDialogueTime(timestamp) {
    const diff = Math.floor(Date.now() / 1000) - timestamp;
    
    if (diff < 60) return 'только что';
    if (diff < 3600) return Math.floor(diff / 60) + ' мин. назад';
    if (diff < 86400) return Math.floor(diff / 3600) + ' ч. назад';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' дн. назад';
    
    return new Date(timestamp * 1000).toLocaleDateString('ru-RU');
}

if (window.location.pathname.includes('/message')) {
    setInterval(updateUnreadCount, 10000); // Каждые 10 секунд

    if (window.location.pathname === '/message') {
        setInterval(updateDialoguesList, 15000); // Каждые 15 секунд

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                updateDialoguesList();
            }
        });
    }
}