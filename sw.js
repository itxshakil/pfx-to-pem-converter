const DEBUG = false;

const APP_CACHE = 'v-0.4.0';
const OFFLINE_URL = '/offline.html';
const STATIC_ASSETS = [
    '/css/app.css',
    '/css/custom.css',
    '/js/app.js',
    '/manifest.json',
    OFFLINE_URL,
];

const BLOG_CACHE_MAX_AGE = 7 * 24 * 60 * 60; // seconds in 1 week (7 days)

const log = (message, ...optionalParams) => {
    if (DEBUG) {
        console.debug(
            `%c Service Worker %c ${message}`,
            'background: #333; color: #fff; border-radius: 0.1em; padding: 0 0.3em; margin-right: 0.5em;',
            'background: #3498db; color: #fff; border-radius: 0.1em; padding: 0 0.3em;',
            ...optionalParams
        );
    }
};

const logError = (message, ...optionalParams) => {
    if (DEBUG) {
        console.error(
            `%c Service Worker %c ${message}`,
            'background: #f00; color: #fff; border-radius: 0.1em; padding: 0 0.3em; margin-right: 0.5em;',
            'background: #3498db; color: #fff; border-radius: 0.1em; padding: 0 0.3em;',
            ...optionalParams
        );
    }
};

// Cache request function with max entries and max age
const cacheRequest = async (cacheName, request, maxEntries = 50, maxAge = 60 * 60) => {
    if (!('caches' in self) || request.method !== 'GET') return fetch(request);

    try {
        const cache = await caches.open(cacheName);
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            const cachedTime = new Date(cachedResponse.headers.get('date')).getTime();
            const isCacheOld = Date.now() - cachedTime > maxAge * 1000;
            if (isCacheOld) {
                await cache.delete(request);
            } else {
                return cachedResponse;
            }
        }

        const response = await fetch(request);
        if (response.ok) {
            const clonedResponse = response.clone();
            log('Caching New Resource', request.url);
            await cache.put(request, clonedResponse);

            const updatedKeys = await cache.keys();
            if (updatedKeys.length >= maxEntries) {
                await cache.delete(updatedKeys[0]);
            }
        }

        return response;
    } catch (error) {
        logError('Error caching request', error);
        return fetch(request); // fall back to network
    }
};

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(APP_CACHE)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
            .catch(error => {
                logError('Error during installation', error);
                throw error;
            })
    );
});

self.addEventListener('activate', async () => {
    log('Activated');
    try {
        const keys = await caches.keys();
        const deleteOldCaches = keys.map((key) => {
            if (key !== APP_CACHE) {
                log('Removing Old Cache', key);
                return caches.delete(key);
            }
        });
        await Promise.all(deleteOldCaches);

        await self.clients.claim();
        log('Clients claimed');
    } catch (error) {
        logError('Error during activation', error);
    }
});

const handleAssetRequest = async (request) => {
    try {
        const cache = await caches.open(APP_CACHE);
        const cachedResponse = await cache.match(request);
        if (cachedResponse) return cachedResponse;

        const response = await fetch(request);
        if (response.ok && request.method === 'GET') {
            log('Caching New Resource', request.url);
            const clonedResponse = response.clone();
            await cache.put(request, clonedResponse);
        }
        return response;
    } catch (error) {
        logError('Error handling asset request', error);
        return fetch(request);
    }
};

// Navigation requests: network-first so users always get fresh server-rendered
// HTML, but fall back to the cached page and finally the offline page when the
// network is unavailable.
const handleNavigationRequest = async (request) => {
    try {
        return await fetch(request);
    } catch (error) {
        log('Navigation offline, serving fallback', request.url);
        const cache = await caches.open(APP_CACHE);
        return (await cache.match(request)) || (await cache.match(OFFLINE_URL));
    }
};

self.addEventListener('fetch', event => {
    const { request } = event;

    // Only handle same-origin GET requests; let everything else hit the network
    // untouched (POST conversions, cross-origin CDN/analytics, etc.).
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigationRequest(request));
    } else if (STATIC_ASSETS.includes(url.pathname)) {
        event.respondWith(handleAssetRequest(request));
    } else if (url.pathname.startsWith('/blogs/')) {
        event.respondWith(cacheRequest(APP_CACHE, request, 50, BLOG_CACHE_MAX_AGE));
    }
    // Other GETs fall through to the browser's default network handling.
});
