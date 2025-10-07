// MentorConnect Service Worker
// Version 1.0.0

const CACHE_NAME = 'mentorconnect-v1.0.0';
const STATIC_CACHE_NAME = 'mentorconnect-static-v1.0.0';
const DYNAMIC_CACHE_NAME = 'mentorconnect-dynamic-v1.0.0';

// Files to cache immediately
const STATIC_FILES = [
    '/mentorconnect/',
    '/mentorconnect/index.php',
    '/mentorconnect/assets/css/critical.css',
    '/mentorconnect/assets/css/style.css',
    '/mentorconnect/assets/css/landing.css',
    '/mentorconnect/assets/css/theme.css',
    '/mentorconnect/assets/js/app.js',
    '/mentorconnect/assets/js/performance.js',
    '/mentorconnect/assets/images/default-profile.svg',
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// API endpoints to cache with network-first strategy
const API_ENDPOINTS = [
    '/mentorconnect/api/notifications.php',
    '/mentorconnect/api/user-preferences.php',
    '/mentorconnect/api/search.php'
];

// Install event - cache static files
self.addEventListener('install', event => {
    console.log('Service Worker: Installing...');
    
    event.waitUntil(
        Promise.all([
            caches.open(STATIC_CACHE_NAME)
                .then(cache => {
                    console.log('Service Worker: Caching static files');
                    return cache.addAll(STATIC_FILES);
                })
                .catch(err => {
                    console.log('Service Worker: Error caching static files', err);
                })
        ])
    );
    
    // Force activation of new service worker
    self.skipWaiting();
});

// Activate event - cleanup old caches
self.addEventListener('activate', event => {
    console.log('Service Worker: Activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== STATIC_CACHE_NAME && cache !== DYNAMIC_CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => {
            // Take control of all pages immediately
            return self.clients.claim();
        })
    );
});

// Fetch event - serve cached files or fetch from network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip cross-origin requests
    if (url.origin !== location.origin && !STATIC_FILES.some(file => request.url.includes(file))) {
        return;
    }
    
    // Handle different types of requests
    if (request.method === 'GET') {
        // API requests - Network first, then cache
        if (isApiRequest(request.url)) {
            event.respondWith(networkFirstStrategy(request));
        }
        // Static files - Cache first, then network
        else if (isStaticFile(request.url)) {
            event.respondWith(cacheFirstStrategy(request));
        }
        // HTML pages - Stale while revalidate
        else if (request.headers.get('accept')?.includes('text/html')) {
            event.respondWith(staleWhileRevalidateStrategy(request));
        }
        // Default - Network first with fallback
        else {
            event.respondWith(networkFirstStrategy(request));
        }
    }
});

// Cache strategies
async function cacheFirstStrategy(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('Cache first strategy failed:', error);
        return new Response('Offline - Content not available', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('Network first - trying cache:', error);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Return offline page for HTML requests
        if (request.headers.get('accept')?.includes('text/html')) {
            return caches.match('/mentorconnect/offline.html') || 
                   new Response('Offline - Please check your connection', {
                       status: 503,
                       statusText: 'Service Unavailable'
                   });
        }
        
        return new Response('Offline', {
            status: 503,
            statusText: 'Service Unavailable'
        });
    }
}

async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const cachedResponse = caches.match(request);
    
    const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(() => {
        // Network failed, return cached version if available
        return cachedResponse;
    });
    
    // Return cached version immediately if available, otherwise wait for network
    return cachedResponse || fetchPromise;
}

// Helper functions
function isApiRequest(url) {
    return url.includes('/api/') || API_ENDPOINTS.some(endpoint => url.includes(endpoint));
}

function isStaticFile(url) {
    return /\.(css|js|png|jpg|jpeg|gif|svg|webp|woff|woff2|ttf|eot|ico)$/.test(url) ||
           STATIC_FILES.some(file => url.includes(file));
}

// Background sync for failed requests
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync') {
        console.log('Service Worker: Background sync triggered');
        event.waitUntil(
            // Retry failed network requests
            retryFailedRequests()
        );
    }
});

async function retryFailedRequests() {
    // Implementation for retrying failed requests
    // This could be used for sending messages, form submissions, etc.
    console.log('Service Worker: Retrying failed requests...');
}

// Push notifications
self.addEventListener('push', event => {
    if (!event.data) return;
    
    const data = event.data.json();
    const options = {
        body: data.message,
        icon: '/mentorconnect/assets/images/icon-192.png',
        badge: '/mentorconnect/assets/images/badge-72.png',
        tag: data.tag || 'mentorconnect-notification',
        requireInteraction: false,
        actions: [
            {
                action: 'view',
                title: 'View'
            },
            {
                action: 'dismiss',
                title: 'Dismiss'
            }
        ]
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title || 'MentorConnect', options)
    );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow('/mentorconnect/dashboard/')
        );
    }
});

// Handle messages from main thread
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE_NAME).then(cache => {
                return cache.addAll(event.data.urls);
            })
        );
    }
});

// Periodic background sync (if supported)
self.addEventListener('periodicsync', event => {
    if (event.tag === 'update-notifications') {
        event.waitUntil(
            updateNotifications()
        );
    }
});

async function updateNotifications() {
    try {
        const response = await fetch('/mentorconnect/api/notifications.php?action=count');
        const data = await response.json();
        
        // Update cached notification count
        const cache = await caches.open(DYNAMIC_CACHE_NAME);
        cache.put('/mentorconnect/api/notifications.php?action=count', 
                   new Response(JSON.stringify(data)));
                   
    } catch (error) {
        console.log('Failed to update notifications in background:', error);
    }
}

console.log('Service Worker: Loaded successfully');