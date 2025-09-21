/**
 * MentorConnect Service Worker - Optimized Caching Strategy
 * Implements efficient caching for performance optimization
 */

const CACHE_VERSION = 'v2.0.0';
const STATIC_CACHE = `mentorconnect-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `mentorconnect-dynamic-${CACHE_VERSION}`;
const API_CACHE = `mentorconnect-api-${CACHE_VERSION}`;

// Static resources to cache immediately
const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/assets/css/critical-optimized.css',
    '/assets/js/optimized-core.js',
    '/assets/images/logo.png',
    '/assets/images/default-avatar.png',
    '/manifest.json'
];

// Dynamic content patterns
const DYNAMIC_PATTERNS = [
    /\/dashboard\//,
    /\/auth\//,
    /\/profile\//
];

// API patterns
const API_PATTERNS = [
    /\/api\//
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('Caching static assets...');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                return self.skipWaiting();
            })
    );
});

// Activate event - cleanup old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (cacheName !== STATIC_CACHE && 
                            cacheName !== DYNAMIC_CACHE && 
                            cacheName !== API_CACHE) {
                            console.log('Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                return self.clients.claim();
            })
    );
});

// Fetch event - handle all network requests
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Handle different types of requests
    if (isStaticAsset(url.pathname)) {
        event.respondWith(handleStaticAsset(request));
    } else if (isAPIRequest(url.pathname)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isDynamicContent(url.pathname)) {
        event.respondWith(handleDynamicContent(request));
    }
});

// Check if request is for static asset
function isStaticAsset(pathname) {
    return /\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i.test(pathname) ||
           STATIC_ASSETS.includes(pathname);
}

// Check if request is for API
function isAPIRequest(pathname) {
    return API_PATTERNS.some(pattern => pattern.test(pathname));
}

// Check if request is for dynamic content
function isDynamicContent(pathname) {
    return DYNAMIC_PATTERNS.some(pattern => pattern.test(pathname));
}

// Handle static assets - Cache First strategy
async function handleStaticAsset(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        const networkResponse = await fetch(request);
        
        // Cache successful responses
        if (networkResponse.status === 200) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('Static asset fetch failed:', error);
        return new Response('Asset not available', { status: 404 });
    }
}

// Handle API requests - Network First with timeout
async function handleAPIRequest(request) {
    try {
        // Try network first with timeout
        const networkResponse = await Promise.race([
            fetch(request),
            new Promise((_, reject) => 
                setTimeout(() => reject(new Error('Timeout')), 3000)
            )
        ]);
        
        // Cache successful API responses briefly
        if (networkResponse.status === 200) {
            const cache = await caches.open(API_CACHE);
            const response = networkResponse.clone();
            
            // Set short expiry for API cache
            const headers = new Headers(response.headers);
            headers.set('sw-cache-timestamp', Date.now().toString());
            
            const cachedResponse = new Response(response.body, {
                status: response.status,
                statusText: response.statusText,
                headers: headers
            });
            
            cache.put(request, cachedResponse);
        }
        
        return networkResponse;
    } catch (error) {
        console.log('Network failed, trying cache:', error);
        
        // Try cache as fallback
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Check if cached response is too old (5 minutes)
            const timestamp = cachedResponse.headers.get('sw-cache-timestamp');
            if (timestamp && Date.now() - parseInt(timestamp) < 300000) {
                return cachedResponse;
            }
        }
        
        return new Response(JSON.stringify({ 
            error: 'Network unavailable',
            cached: !!cachedResponse 
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Handle dynamic content - Stale While Revalidate
async function handleDynamicContent(request) {
    try {
        const cache = await caches.open(DYNAMIC_CACHE);
        const cachedResponse = await cache.match(request);
        
        // Start network request
        const networkPromise = fetch(request)
            .then((response) => {
                if (response.status === 200) {
                    cache.put(request, response.clone());
                }
                return response;
            })
            .catch(() => null);
        
        // Return cached version immediately if available
        if (cachedResponse) {
            // Update cache in background
            networkPromise;
            return cachedResponse;
        }
        
        // Wait for network if no cache
        const networkResponse = await networkPromise;
        if (networkResponse) {
            return networkResponse;
        }
        
        // Fallback to offline page
        return new Response(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>MentorConnect - Offline</title>
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        text-align: center; 
                        padding: 50px; 
                        background: #f5f5f5; 
                    }
                    .offline-message {
                        max-width: 400px;
                        margin: 0 auto;
                        background: white;
                        padding: 30px;
                        border-radius: 10px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    h1 { color: #333; }
                    p { color: #666; }
                    .retry-btn {
                        background: #6366f1;
                        color: white;
                        border: none;
                        padding: 10px 20px;
                        border-radius: 5px;
                        cursor: pointer;
                        margin-top: 20px;
                    }
                </style>
            </head>
            <body>
                <div class="offline-message">
                    <h1>You're Offline</h1>
                    <p>This page isn't available right now. Check your connection and try again.</p>
                    <button class="retry-btn" onclick="window.location.reload()">Try Again</button>
                </div>
            </body>
            </html>
        `, {
            status: 200,
            headers: { 'Content-Type': 'text/html' }
        });
    } catch (error) {
        console.error('Dynamic content fetch failed:', error);
        return new Response('Page not available', { status: 404 });
    }
}

// Background sync for offline actions
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(syncOfflineActions());
    }
});

async function syncOfflineActions() {
    // Handle any offline actions stored in IndexedDB
    console.log('Syncing offline actions...');
}

// Push notifications
self.addEventListener('push', (event) => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/assets/images/logo.png',
            badge: '/assets/images/badge.png',
            data: data.url,
            actions: [
                {
                    action: 'open',
                    title: 'Open'
                },
                {
                    action: 'close',
                    title: 'Close'
                }
            ]
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.action === 'open' || !event.action) {
        event.waitUntil(
            clients.openWindow(event.notification.data || '/')
        );
    }
});

// Performance monitoring
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'PERFORMANCE_REPORT') {
        console.log('Performance metrics:', event.data.metrics);
        // Could send to analytics endpoint
    }
});

console.log('Service Worker registered successfully');