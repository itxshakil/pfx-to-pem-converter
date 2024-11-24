const DEBUG = true;

const APP_CACHE = 'v-0.2.0';
const STATIC_ASSETS = [
    '/css/app.css',
    '/manifest.json',
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

self.addEventListener('fetch', event => {
    const { request } = event;

    if (STATIC_ASSETS.includes(request.url)) {
        event.respondWith(handleAssetRequest(request));
    } else if (request.url.match(/^\/blogs\//)) {
        event.respondWith(cacheRequest(APP_CACHE, request, 50, BLOG_CACHE_MAX_AGE));
    } else {
        event.respondWith(fetch(request));
    }
});
