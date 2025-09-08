// MentorConnect Service Worker - Optimized for 100/100 Performance
const CACHE_VERSION = '2.2.0';
const STATIC_CACHE = `mentorconnect-static-v${CACHE_VERSION}`;
const DYNAMIC_CACHE = `mentorconnect-dynamic-v${CACHE_VERSION}`;
const API_CACHE = `mentorconnect-api-v${CACHE_VERSION}`;

// Critical assets to cache immediately
const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/auth/login.php',
    '/auth/signup.php',
    '/assets/css/critical.css',
    '/assets/css/style.css',
    '/assets/css/landing.css',
    '/assets/js/optimized-app.js',
    '/assets/js/app.js',
    '/assets/js/image-optimizer.js',
    '/assets/js/landing.js',
    '/manifest.json',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
];

// Runtime caching patterns
const RUNTIME_CACHING = [
    {
        urlPattern: /\/assets\//,
        handler: 'CacheFirst',
        options: {
            cacheName: 'assets-cache',
            expiration: {
                maxEntries: 60,
                maxAgeSeconds: 30 * 24 * 60 * 60 // 30 days
            }
        }
    },
    {
        urlPattern: /\/api\//,
        handler: 'NetworkFirst',
        options: {
            cacheName: 'api-cache',
            expiration: {
                maxEntries: 50,
                maxAgeSeconds: 5 * 60 // 5 minutes
            },
            networkTimeoutSeconds: 3
        }
    }
];

// Install event - cache critical resources
self.addEventListener('install', function(event) {
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE).then(cache => {
                return cache.addAll(STATIC_ASSETS.map(url => new Request(url, {
                    credentials: 'same-origin',
                    cache: 'reload'
                })));
            }),
            self.skipWaiting()
        ])
    );
});

// Activate event - clean up old caches and claim clients
self.addEventListener('activate', function(event) {
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(cacheName => {
                            return cacheName.startsWith('mentorconnect-') && 
                                   !cacheName.includes(CACHE_VERSION);
                        })
                        .map(cacheName => caches.delete(cacheName))
                );
            }),
            // Claim all clients immediately
            self.clients.claim()
        ])
    );
});

// Fetch event - advanced caching strategies
self.addEventListener('fetch', function(event) {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests and extensions
    if (request.method !== 'GET' || 
        url.protocol !== 'http:' && url.protocol !== 'https:') {
        return;
    }

    // Handle different types of requests
    if (request.destination === 'document') {
        event.respondWith(handleNavigationRequest(request));
    } else if (url.pathname.startsWith('/api/')) {
        event.respondWith(handleApiRequest(request));
    } else if (url.pathname.startsWith('/assets/') || 
               request.destination === 'style' || 
               request.destination === 'script' || 
               request.destination === 'font') {
        event.respondWith(handleAssetRequest(request));
    } else {
        event.respondWith(handleOtherRequest(request));
    }
});

// Navigation request handler (Stale While Revalidate)
async function handleNavigationRequest(request) {
    try {
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedResponse = await cache.match(request);
        
        const fetchPromise = fetch(request).then(response => {
            if (response.status === 200) {
                cache.put(request, response.clone());
            }
            return response;
        });

        return cachedResponse || await fetchPromise;
    } catch (error) {
        const cache = await caches.open(STATIC_CACHE);
        return await cache.match('/') || new Response('Offline', { status: 503 });
    }
}

// API request handler (Network First with timeout)
async function handleApiRequest(request) {
    const cache = await caches.open(API_CACHE);
    
    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);
        
        const response = await fetch(request, { 
            signal: controller.signal 
        });
        
        clearTimeout(timeoutId);
        
        if (response.status === 200) {
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        const cachedResponse = await cache.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

// Asset request handler (Cache First)
async function handleAssetRequest(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const response = await fetch(request);
        if (response.status === 200) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        throw error;
    }
}

// Other request handler (Network First)
async function handleOtherRequest(request) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(DYNAMIC_CACHE);
        
        if (response.status === 200) {
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedResponse = await cache.match(request);
        return cachedResponse || new Response('Network Error', { status: 503 });
    }
}

// Background sync for offline actions
self.addEventListener('sync', function(event) {
    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }
});

async function doBackgroundSync() {
    // Handle offline form submissions, etc.
    console.log('Background sync triggered');
}

// Push notification handling
self.addEventListener('push', function(event) {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/assets/images/icon-192x192.png',
            badge: '/assets/images/badge-72x72.png',
            data: data.data,
            actions: data.actions || [],
            tag: data.tag || 'default',
            renotify: true,
            requireInteraction: false,
            silent: false
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handling
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url || '/')
        );
    }
});

// Performance monitoring
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'GET_CACHE_INFO') {
        caches.keys().then(cacheNames => {
            event.ports[0].postMessage({
                caches: cacheNames,
                version: CACHE_VERSION
            });
        });
    }
});
