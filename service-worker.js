const CACHE_VERSION = 'v1';
const CACHE_NAME = `speedwayfast-${CACHE_VERSION}`;

// Resources to cache
const CACHED_RESOURCES = [
    '/',
    '/wp-includes/css/dist/block-library/style.min.css',
    '/wp-includes/js/jquery/jquery.min.js',
    // Add more resources to cache
];

// Install event - cache critical resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(CACHED_RESOURCES))
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => name.startsWith('speedwayfast-') && name !== CACHE_NAME)
                        .map(name => caches.delete(name))
                );
            })
            .then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) return;

    event.respondWith(
        caches.match(event.request)
            .then(response => {
                if (response) {
                    // Return cached response
                    return response;
                }

                // Clone the request - stream can only be used once
                const fetchRequest = event.request.clone();

                return fetch(fetchRequest)
                    .then(response => {
                        // Check if response is valid
                        if (!response || response.status !== 200 || response.type !== 'basic') {
                            return response;
                        }

                        // Clone the response - stream can only be used once
                        const responseToCache = response.clone();

                        // Cache the fetched response
                        caches.open(CACHE_NAME)
                            .then(cache => {
                                cache.put(event.request, responseToCache);
                            });

                        return response;
                    });
            })
    );
}); 