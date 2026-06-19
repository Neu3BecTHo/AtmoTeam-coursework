// ==================== Notifications ====================
let notificationsOpen = false;

function getNotificationIcon(type) {
  const icons = {
    success: "✅",
    error: "❌",
    warning: "⚠️",
    info: "ℹ️",
    like: "❤️",
    comment: "💬",
    follow: "👤",
    mention: "@",
    system: "📢",
  };
  return icons[type] || "ℹ️";
}

function showAnimatedNotification(message, type = "info") {
  let container = document.getElementById("notification-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "notification-container";
    container.className = "notification-container";
    document.body.appendChild(container);
  }

  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
        <span class="notification-icon">${getNotificationIcon(type)}</span>
        <span class="notification-text">${escapeHtml(message)}</span>
        <button class="notification-close" aria-label="Закрыть уведомление">×</button>
    `;
  container.appendChild(notification);

  setTimeout(() => notification.classList.add("notification-enter"), 10);
  const timeout = setTimeout(() => closeNotification(notification), 5000);

  const closeBtn = notification.querySelector(".notification-close");
  closeBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    clearTimeout(timeout);
    closeNotification(notification);
  });
  notification.addEventListener("click", () => {
    clearTimeout(timeout);
    closeNotification(notification);
  });
}

function closeNotification(notification) {
  if (!notification?.parentNode) return;
  notification.classList.add("notification-exit");
  setTimeout(() => notification.remove(), 300);
}

// ==================== Loading Overlay ====================
let loadingCount = 0;

function showLoading(message = "Загрузка...") {
  loadingCount++;
  let container = document.getElementById("loading-container");
  if (!container) {
    container = document.createElement("div");
    container.id = "loading-container";
    container.innerHTML = `
            <div class="loading-indicator">
                <div class="global-loading-spinner"><div class="spinner"><div class="spinner-ring"></div><div class="spinner-ring"></div><div class="spinner-ring"></div></div></div>
                <span class="loading-text">${message}</span>
            </div>
        `;
    document.body.appendChild(container);
  }
  const textSpan = container.querySelector(".loading-text");
  if (textSpan) textSpan.textContent = message;
  container.style.display = "flex";
}

function hideLoading() {
  loadingCount = Math.max(0, loadingCount - 1);
  if (loadingCount === 0) {
    const container = document.getElementById("loading-container");
    if (container) container.style.display = "none";
  }
}

async function fetchWithLoading(url, options = {}) {
  const showLoader = options.showLoader !== false;
  if (showLoader) showLoading(options.loadingMessage);
  try {
    const response = await fetch(url, {
      ...options,
      headers: { "X-Requested-With": "XMLHttpRequest", ...options.headers },
    });
    if (!response.ok) throw new Error(await getErrorMessage(response));
    return response;
  } finally {
    if (showLoader) hideLoading();
  }
}

async function getErrorMessage(response) {
  try {
    const data = await response.clone().json();
    return data.message || data.error || `Ошибка HTTP ${response.status}`;
  } catch {
    const messages = {
      401: "Требуется авторизация",
      403: "Доступ запрещен",
      404: "Ресурс не найден",
      429: "Слишком много запросов",
      500: "Ошибка сервера",
    };
    return messages[response.status] || `Ошибка HTTP ${response.status}`;
  }
}

// ==================== Theme ====================
function initTheme() {
  const saved =
    localStorage.getItem("theme") ||
    (window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light");
  setTheme(saved);
  createThemeToggle();
}

function setTheme(theme) {
  document.documentElement.setAttribute("data-theme", theme);
  localStorage.setItem("theme", theme);
  const btn = document.getElementById("theme-toggle");
  if (btn) btn.textContent = theme === "dark" ? "☀️" : "🌙";
}

function toggleTheme() {
  const current =
    document.documentElement.getAttribute("data-theme") || "light";
  setTheme(current === "light" ? "dark" : "light");
}

function createThemeToggle() {
  if (document.getElementById("theme-toggle")) return;
  const btn = document.createElement("button");
  btn.id = "theme-toggle";
  btn.className = "theme-toggle";
  btn.onclick = toggleTheme;
  document.body.appendChild(btn);
  setTheme(localStorage.getItem("theme") || "light");
}

// ==================== User Menu ====================
function toggleUserMenu(e) {
  e?.preventDefault();
  const menu = document.getElementById("user-menu");
  if (!menu) return;
  const isOpen = menu.classList.contains("show");
  document
    .querySelectorAll(".nav-dropdown-menu, .notification-dropdown")
    .forEach((m) => m.classList.remove("show"));
  if (!isOpen) menu.classList.add("show");
}

function closeMenus(e) {
  if (!e.target.closest?.(".nav-dropdown, .nav-notification-link")) {
    document
      .querySelectorAll(".nav-dropdown-menu, .notification-dropdown")
      .forEach((m) => m.classList.remove("show"));
  }
}

// ==================== Notifications Dropdown (исправлен) ====================
function toggleNotifications(e) {
  e?.preventDefault();
  const dropdown = document.getElementById("notification-dropdown");
  if (!dropdown) return;
  dropdown.classList.toggle("show");
  // Закрываем другие открытые меню
  const userMenu = document.getElementById("user-menu");
  if (userMenu) userMenu.classList.remove("show");
  // Загружаем уведомления если ещё не загружены
  if (!dropdown.dataset.loaded) {
    loadNotifications();
  }
}

function toggleMobileNotifications() {
  const dropdown = document.getElementById("notification-dropdown");
  if (!dropdown) return;
  dropdown.classList.toggle("show");
  // Закрываем мобильное меню
  closeMobileMenu();
  // Загружаем уведомления если ещё не загружены
  if (!dropdown.dataset.loaded) {
    loadNotifications();
  }
}

async function loadNotifications() {
  const list = document.getElementById("notification-list");
  if (!list) return;
  try {
    const response = await fetch("/api/notifications");
    const data = await response.json();
    if (data.success && data.notifications?.length) {
      list.innerHTML = data.notifications
        .map(
          (n) => `
                <div class="notification-item ${n.is_read ? "" : "unread"}" data-id="${n.id}" onclick="markNotificationRead(${n.id})">
                    <div class="notification-avatar">
                        <img src="${n.from_user?.avatar || ""}" alt="">
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${escapeHtml(n.from_user?.username || "Пользователь")}</div>
                        <div class="notification-message">${escapeHtml(n.text)}</div>
                        <div class="notification-time">${n.timeAgo}</div>
                    </div>
                </div>
            `,
        )
        .join("");
      updateNotificationBadge(data.unread_count || 0);
    } else {
      list.innerHTML = '<div class="notification-empty">Нет уведомлений</div>';
      updateNotificationBadge(0);
    }
  } catch (e) {
    console.error(e);
    list.innerHTML = '<div class="notification-empty">Ошибка загрузки</div>';
  }
}

async function markNotificationRead(id) {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  try {
    await fetch("/api/notifications/read", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": token,
      },
      body: JSON.stringify({ id: id }),
    });
    loadNotifications();
  } catch (e) {
    console.error(e);
  }
}

async function markAllNotificationsRead() {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;
  try {
    await fetch("/api/notifications/read-all", {
      method: "POST",
      headers: token ? { "X-CSRF-Token": token } : {},
    });
    loadNotifications();
  } catch (e) {
    console.error(e);
  }
}

function updateNotificationBadge(count) {
  const badge = document.getElementById("notification-badge");
  if (!badge) return;
  if (count > 0) {
    badge.textContent = count > 99 ? "99+" : count;
    badge.style.display = "inline-block";
  } else {
    badge.style.display = "none";
  }
}

// Периодическое обновление счётчика непрочитанных уведомлений
function startNotificationPolling() {
  if (!window.currentUserId) return;
  setInterval(() => {
    if (document.visibilityState !== "hidden") {
      fetch("/api/notifications/unread-count")
        .then((r) => r.json())
        .then((data) => {
          if (data.success && data.count !== undefined) {
            updateNotificationBadge(data.count);
          }
        })
        .catch(console.error);
    }
  }, 30000);
}

// ==================== Image Optimization ====================
function supportsWebP() {
  const canvas = document.createElement("canvas");
  canvas.width = canvas.height = 1;
  return canvas.toDataURL("image/webp").startsWith("data:image/webp");
}

function getOptimizedImageUrl(url) {
  if (!url || url.includes(".webp") || url.startsWith("http")) return url;
  return supportsWebP() ? url.replace(/\.(jpg|jpeg|png)$/i, ".webp") : url;
}

// ==================== Search ====================
let linkPreviewTimeout;
let searchResults = [];

function initSearchFeatures() {
  const input = document.getElementById("global-search");
  if (!input) return;
  input.addEventListener("input", (e) => {
    const q = e.target.value.trim();
    clearTimeout(linkPreviewTimeout);
    if (q.length < 2) return hideSearchResults();
    linkPreviewTimeout = setTimeout(() => performSearch(q), 300);
  });
  createSearchResultsContainer();
}

function createSearchResultsContainer() {
  if (document.getElementById("search-results")) return;
  const container = document.createElement("div");
  container.id = "search-results";
  container.className = "search-results";
  container.style.cssText =
    "position:absolute;top:100%;left:0;right:0;background:var(--surface-color);border:1px solid var(--border-color);border-radius:8px;max-height:400px;overflow-y:auto;z-index:1000;display:none";
  const parent = document.getElementById("global-search")?.parentElement;
  if (parent) {
    parent.style.position = "relative";
    parent.appendChild(container);
  }
}

async function performSearch(query) {
  const container = document.getElementById("search-results");
  if (!container) return;
  try {
    const res = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
    const data = await res.json();
    if (data.success && data.results?.length) {
      container.innerHTML = data.results
        .map(
          (item) => `
                <div class="search-result-item" data-type="${item.type}" data-id="${item.id}" data-url="${item.url}">
                    <div class="search-result-title">${escapeHtml(item.title)}</div>
                    <div class="search-result-text">${escapeHtml(item.text || "")}</div>
                </div>
            `,
        )
        .join("");
      container.querySelectorAll(".search-result-item").forEach((el) => {
        el.addEventListener(
          "click",
          () => (window.location.href = el.dataset.url),
        );
      });
      container.style.display = "block";
    } else {
      container.innerHTML =
        '<div class="search-result-empty">Ничего не найдено</div>';
      container.style.display = "block";
    }
  } catch (e) {
    console.error(e);
  }
}

function hideSearchResults() {
  const container = document.getElementById("search-results");
  if (container) container.style.display = "none";
}

// ==================== Animations ====================
function animateElement(el, animation, duration = null) {
  if (!el) return;
  el.classList.remove(
    "fade-in",
    "fade-out",
    "slide-up",
    "slide-down",
    "bounce-in",
    "scale-in",
  );
  el.classList.add(animation);
  if (duration) el.style.animationDuration = duration;
  el.addEventListener(
    "animationend",
    () => {
      el.classList.remove(animation);
      el.style.animationDuration = "";
    },
    { once: true },
  );
}

const fadeIn = (el, d) => animateElement(el, "fade-in", d);
const fadeOut = (el, d) => animateElement(el, "fade-out", d);

// ==================== Form Validation ====================
class FormValidator {
  constructor(form, rules = {}) {
    this.form = form;
    this.rules = rules;
    this.errors = {};
    this.form.addEventListener("submit", (e) => {
      if (!this.validate()) e.preventDefault();
    });
    this.form.addEventListener("input", (e) =>
      this.clearFieldError(e.target.name),
    );
  }

  validate() {
    this.errors = {};
    for (const [name, rules] of Object.entries(this.rules)) {
      const field = this.form.querySelector(`[name="${name}"]`);
      if (!field) continue;
      const value = field.value.trim();
      for (const rule of rules) {
        let error = null;
        if (rule.type === "required" && !value) error = "Это поле обязательно";
        else if (
          rule.type === "email" &&
          value &&
          !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
        )
          error = "Введите корректный email";
        else if (rule.type === "minLength" && value.length < rule.params.length)
          error = `Минимум ${rule.params.length} символов`;
        else if (rule.type === "maxLength" && value.length > rule.params.length)
          error = `Максимум ${rule.params.length} символов`;
        if (error) {
          this.errors[name] = error;
          this.showFieldError(name, error);
          break;
        }
      }
    }
    return Object.keys(this.errors).length === 0;
  }

  showFieldError(name, message) {
    const field = this.form.querySelector(`[name="${name}"]`);
    if (!field) return;
    field.classList.add("field-error");
    let msg = field.parentNode.querySelector(".field-error-message");
    if (!msg) {
      msg = document.createElement("div");
      msg.className = "field-error-message";
      field.parentNode.appendChild(msg);
    }
    msg.textContent = message;
  }

  clearFieldError(name) {
    const field = this.form.querySelector(`[name="${name}"]`);
    if (field) {
      field.classList.remove("field-error");
      field.parentNode.querySelector(".field-error-message")?.remove();
    }
  }
}

function initFormValidation() {
  const login = document.querySelector("#login-form");
  if (login)
    new FormValidator(login, {
      email: [{ type: "required" }],
      password: [{ type: "required" }],
    });
  const register = document.querySelector("#register-form");
  if (register)
    new FormValidator(register, {
      username: [
        { type: "required" },
        { type: "minLength", params: { length: 3 } },
      ],
      email: [{ type: "required" }, { type: "email" }],
      password: [
        { type: "required" },
        { type: "minLength", params: { length: 6 } },
      ],
      passwordConfirm: [{ type: "required" }],
    });
}

// ==================== Service Worker ====================
function initServiceWorker() {
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("/js/service-worker.js")
      .catch((e) => console.error("SW registration failed:", e));
  }
}

// ==================== Online Count ====================
function updateOnlineCount() {
  fetch("/api/poll")
    .then((r) => r.json())
    .then((data) => {
      const el = document.getElementById("online-count");
      if (el && data.online_count !== undefined)
        el.textContent = data.online_count;
    })
    .catch((e) => console.error(e));
}

// ==================== Mobile Menu ====================
function toggleMobileMenu() {
  const overlay = document.getElementById('mobile-menu-overlay');
  if (!overlay) return;
  const isOpen = overlay.classList.contains('show');
  if (isOpen) {
    closeMobileMenu();
  } else {
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
}

function closeMobileMenu() {
  const overlay = document.getElementById('mobile-menu-overlay');
  if (overlay) overlay.classList.remove('show');
  document.body.style.overflow = '';
}

// Закрытие всех модалок
function closeAllModals() {
  // Закрываем пост-модалку
  const postModal = document.getElementById('post-modal');
  if (postModal) {
    postModal.classList.remove('show');
    postModal.classList.add('hidden');
  }
  
  // Закрываем все остальные модалки с классом modal-overlay
  document.querySelectorAll('.modal-overlay.show').forEach(modal => {
    modal.classList.remove('show');
    modal.classList.add('hidden');
  });
  
  // Закрываем fullscreen image modal
  const fullscreenModal = document.getElementById('fullscreen-image-modal');
  if (fullscreenModal) {
    fullscreenModal.style.display = 'none';
    const img = document.getElementById('fullscreen-image');
    if (img) img.src = '';
  }
  
  // Восстанавливаем скролл
  document.body.style.overflow = '';
}

// ==================== Gestures ====================
function initSwipeGestures() {
  let startX = 0;
  let startY = 0;
  let isSwiping = false;

  document.addEventListener("touchstart", (e) => {
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
    isSwiping = true;
  }, { passive: true });

  document.addEventListener("touchend", (e) => {
    if (!isSwiping) return;
    isSwiping = false;

    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    const deltaX = endX - startX;
    const deltaY = Math.abs(endY - startY);

    // Если был вертикальный скролл — игнорируем
    if (deltaY > 50) return;
    
    // Свайп вправо только если начался с левого края экрана (до 30px)
    if (deltaX > 50 && startX < 30) {
      toggleMobileMenu();
    }
  }, { passive: true });
}

// ==================== Cache ====================
class RequestCache {
  constructor(maxSize = 100, defaultTTL = 5 * 60 * 1000) {
    this.cache = new Map();
    this.maxSize = maxSize;
    this.defaultTTL = defaultTTL;
  }
  set(key, data, ttl = this.defaultTTL) {
    if (this.cache.size >= this.maxSize) this.cleanup();
    this.cache.set(key, { data, expires: Date.now() + ttl });
  }
  get(key) {
    const item = this.cache.get(key);
    if (!item) return null;
    if (Date.now() > item.expires) {
      this.cache.delete(key);
      return null;
    }
    return item.data;
  }
  cleanup() {
    const now = Date.now();
    for (const [k, v] of this.cache) {
      if (now > v.expires) this.cache.delete(k);
    }
  }
}

const apiCache = new RequestCache(200, 10 * 60 * 1000);

// ==================== Drag & Drop Upload ====================
function initDragDropUploaders() {
  const dropZone = document.querySelector(
    "#post-drop-zone, #comment-drop-zone, #avatar-drop-zone",
  );
  const input = document.querySelector(
    "#post-file-input, #comment-file-input, #avatar-file-input",
  );
  if (dropZone && input) {
    dropZone.addEventListener("dragover", (e) => e.preventDefault());
    dropZone.addEventListener("drop", (e) => {
      e.preventDefault();
      const file = e.dataTransfer.files[0];
      if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event("change"));
      }
    });
    dropZone.addEventListener("click", () => input.click());
  }
}

// ==================== Link Previews ====================
function initLinkPreviews() {
  document.querySelectorAll(".post-content a").forEach((link) => {
    if (link.href && !link.hostname.includes(window.location.hostname)) {
      link.style.position = "relative";
      link.insertAdjacentHTML(
        "afterend",
        `<div class="link-preview-loading" style="display:none">🔗</div>`,
      );
    }
  });
}

// ==================== DOM Ready ====================
document.addEventListener("DOMContentLoaded", () => {
  initServiceWorker();
  initSwipeGestures();
  initTheme();
  initSearchFeatures();
  initFormValidation();
  initDragDropUploaders();
  updateOnlineCount();
  document.addEventListener("click", closeMenus);
  setInterval(updateOnlineCount, 30000);
  
  // Закрытие мобильного меню и модалок по Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeMobileMenu();
      closePostModal();
      closeAllModals();
    }
  });
  
  // Закрытие модалок по клику вне их
  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
      closeAllModals();
    }
  });
  
  if (window.currentUserId) {
    fetch(`/api/user/public-key?id=${window.currentUserId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.public_key) {
                if (typeof window.ensureUserHasKeys === 'function') {
                    window.ensureUserHasKeys();
                } else {
                    generateAndStoreKeyPairGlobal();
                }
            }
        })
        .catch(e => console.error('Ошибка проверки ключа', e));
    }

  if (window.currentUserId) {
    startNotificationPolling();
  }
});

// ==================== E2EE key initialization ====================
if (window.currentUserId) {
    // Проверяем наличие публичного ключа у текущего пользователя
    fetch(`/api/user/public-key?id=${window.currentUserId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.public_key) {
                console.log('У пользователя нет ключа шифрования, генерируем...');
                // Если функция из message.js недоступна, создаём ключи прямо здесь
                if (typeof window.ensureUserHasKeys === 'function') {
                    window.ensureUserHasKeys();
                } else {
                    generateAndStoreKeyPairGlobal();
                }
            }
        })
        .catch(e => console.error('Ошибка проверки ключа', e));
}

// Глобальная функция генерации ключей (без зависимостей от message.js)
async function generateAndStoreKeyPairGlobal() {
    try {
        const keyPair = await window.crypto.subtle.generateKey(
            { name: "RSA-OAEP", modulusLength: 2048, publicExponent: new Uint8Array([1,0,1]), hash: "SHA-256" },
            true,
            ["encrypt", "decrypt"]
        );
        const exportedPrivate = await window.crypto.subtle.exportKey("pkcs8", keyPair.privateKey);
        const exportedPublic  = await window.crypto.subtle.exportKey("spki", keyPair.publicKey);
        const privateBase64 = btoa(String.fromCharCode(...new Uint8Array(exportedPrivate)));
        const publicBase64  = btoa(String.fromCharCode(...new Uint8Array(exportedPublic)));
        localStorage.setItem('rsa_private_key', privateBase64);
        localStorage.setItem('rsa_public_key', publicBase64);
        // Отправляем публичный ключ на сервер
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('/api/user/save-public-key', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ public_key: publicBase64 })
        });
        const result = await response.json();
        if (result.success) {
            console.log('Публичный ключ сохранён');
        } else {
            console.error('Ошибка сохранения ключа', result.error);
        }
    } catch(e) {
        console.error('Ошибка генерации ключей', e);
    }
}

// Экспортируем функцию для возможного вызова из других скриптов
window.generateAndStoreKeyPairGlobal = generateAndStoreKeyPairGlobal;
window.ensureUserHasKeys = generateAndStoreKeyPairGlobal;

// ==================== Exports ====================
window.toggleUserMenu = toggleUserMenu;
window.toggleNotifications = toggleNotifications;
window.toggleMobileNotifications = toggleMobileNotifications;
window.toggleMobileMenu = toggleMobileMenu;
window.closeMobileMenu = closeMobileMenu;
window.getOptimizedImageUrl = getOptimizedImageUrl;
window.supportsWebP = supportsWebP;
window.showLoading = showLoading;
window.hideLoading = hideLoading;
window.fetchWithLoading = fetchWithLoading;
window.fadeIn = fadeIn;
window.fadeOut = fadeOut;
window.markNotificationRead = markNotificationRead;
window.markAllNotificationsRead = markAllNotificationsRead;
