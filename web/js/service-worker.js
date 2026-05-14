

const CACHE_NAME = 'social-network-v1';
const STATIC_CACHE = 'static-v1';
const API_CACHE = 'api-v1';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 часа

const STATIC_FILES = [
    '/',
    '/css/main.css',
    '/css/feed.css',
    '/css/profile.css',
    '/css/story.css',
    '/css/search.css',
    '/css/auth.css',
    '/css/admin.css',
    '/css/message.css',
    '/css/post.css',
    '/js/main.js',
    '/js/feed.js',
    '/js/profile.js',
    '/js/story.js',
    '/js/search.js',
    '/js/theme.js'
];

const API_ENDPOINTS = [
    '/api/feed',
    '/api/posts',
    '/api/profile',
    '/api/search',
    '/api/notifications'
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(cache => {
            return cache.addAll(STATIC_FILES.map(url => new Request(url)));
        }).then(() => {
            self.skipWaiting();
        })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== STATIC_CACHE && cacheName !== API_CACHE) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            self.clients.claim();
        })
    );
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (!url.protocol.startsWith('http')) {
        return fetch(request);
    }

    if (request.method !== 'GET' || 
        url.pathname.startsWith('/api/post/create') ||
        url.pathname.startsWith('/api/post/save') ||
        url.pathname.startsWith('/api/post/delete') ||
        url.pathname.startsWith('/api/comment/create') ||
        url.pathname.startsWith('/api/notifications/read')) {
        return fetch(request);
    }

    const isApiRequest = API_ENDPOINTS.some(endpoint => 
        url.pathname.startsWith(endpoint)
    );
    
    if (isApiRequest) {
        event.respondWith(handleApiRequest(request));
    } else {
        event.respondWith(handleStaticRequest(request));
    }
});

async function handleStaticRequest(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse && cachedResponse.ok) {

        const cachedDate = cachedResponse.headers.get('date');
        const now = new Date();
        const cacheAge = now - new Date(cachedDate);
        
        if (cacheAge < CACHE_DURATION) {
            return cachedResponse;
        }
    }

    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {

            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {

        return new Response(`
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Офлайн режим</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        margin: 0;
                        padding: 20px;
                        background: #f8fafc;
                        color: #1f2937;
                        text-align: center;
                    }
                    .offline-container {
                        max-width: 400px;
                        margin: 0 auto;
                    }
                    .offline-icon {
                        font-size: 64px;
                        margin-bottom: 20px;
                    }
                    h1 {
                        color: #ef4444;
                        margin-bottom: 10px;
                    }
                    p {
                        color: #6b7280;
                        margin-bottom: 20px;
                    }
                    .btn {
                        background: #667eea;
                        color: white;
                        border: none;
                        padding: 12px 24px;
                        border-radius: 8px;
                        text-decoration: none;
                        display: inline-block;
                        cursor: pointer;
                    }
                    .btn:hover {
                        background: #5a67d8;
                    }
                </style>
            </head>
            <body>
                <div class="offline-container">
                    <div class="offline-icon">📱</div>
                    <h1>Офлайн режим</h1>
                    <p>Отсутствует подключение к интернету</p>
                    <p>Некоторые функции могут быть недоступны</p>
                    <button class="btn" onclick="window.location.reload()">Обновить страницу</button>
                </div>
            </body>
            </html>
        `, {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
                'Content-Type': 'text/html'
            }
        });
    }
}

async function handleApiRequest(request) {
    const cache = await caches.open(API_CACHE);
    const cachedResponse = await cache.match(request);

    if (cachedResponse && cachedResponse.ok) {

        updateCacheInBackground(request);
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {

            const cache = await caches.open(API_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {

        if (cachedResponse) {
            return cachedResponse;
        }
        
        return new Response(JSON.stringify({
            success: false,
            error: 'Отсутствует подключение к интернету',
            offline: true
        }), {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
                'Content-Type': 'application/json'
            }
        });
    }
}

async function updateCacheInBackground(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(API_CACHE);
            cache.put(request, networkResponse);
        }
    } catch (error) {
    }
}

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_UPDATE') {

        updateAllCaches();
    }
});

async function updateAllCaches() {
    try {
        const staticCache = await caches.open(STATIC_CACHE);
        const apiCache = await caches.open(API_CACHE);

        for (const file of STATIC_FILES) {
            try {
                const response = await fetch(file);
                if (response.ok) {
                    await staticCache.put(new Request(file), response);
                }
            } catch (error) {
            }
        }
        
    } catch (error) {
    }
}
