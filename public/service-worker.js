importScripts("https://js.pusher.com/beams/service-worker.js");

const CACHE_NAME = "your-cache-name";
const OFFLINE_URL = "/offline"; // Make sure this path is correct

self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open("app-cache").then(async (cache) => {
            return fetch("/manifest.json")
                .then((response) => response.json())
                .then((manifest) => {
                    const fullStartUrl =
                        manifest.start_url_base +
                        (manifest.query_params ? manifest.query_params : "");
                    return cache.add(fullStartUrl);
                })
                .catch((error) =>
                    console.error("Manifest fetch error:", error)
                );
        })
    );
});

self.addEventListener('push', function(event) {
    try {
        let data = event.data.text(); // Get raw text

        data = JSON.parse(data); // Ensure it's parsed correctly

        const options = {
            body: data.body || 'You have a new notification',
            icon: data.icon || '/img/192x192.png',
            badge: data.badge || '/icons/badge-72x72.png',
            data: data.data || {} // 👈 url should be here
        };

        event.waitUntil(
            self.registration.showNotification(data.title || 'New Notification', options)
        );
    } catch (error) {
        console.error("Error parsing push notification:", error);
    }
});

self.addEventListener("fetch", (event) => {
    // Only cache GET requests from supported schemes
    if (event.request.method !== "GET") {
        return event.respondWith(fetch(event.request));
    }

    // Skip unsupported schemes (chrome-extension, moz-extension, etc.)
    const url = new URL(event.request.url);
    const supportedSchemes = ["http", "https"];
    if (!supportedSchemes.includes(url.protocol.replace(":", ""))) {
        return event.respondWith(fetch(event.request));
    }

    event.respondWith(
        fetch(event.request)
            .then((response) => {
                if (
                    !response ||
                    response.status !== 200 ||
                    response.type !== "basic"
                ) {
                    return response; // Skip caching if the response is not valid
                }

                let responseClone = response.clone();
                caches.open("app-cache").then((cache) => {
                    cache.put(event.request, responseClone).catch((err) => {
                        console.warn("Cache Add Failed:", err);
                    });
                });

                return response;
            })
            .catch(() => caches.match(event.request)) // Serve from cache if offline
    );
});

// Activate Event - Clean up old caches (optional)
self.addEventListener("activate", (event) => {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (!cacheWhitelist.includes(cacheName)) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(clients.openWindow(event.notification.data.url));
    }
});
