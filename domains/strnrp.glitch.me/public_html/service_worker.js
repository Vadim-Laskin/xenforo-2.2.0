"use strict";

// ################################## CONSTANTS #################################

var CACHE_NAME = 'xf-offline';
var CACHE_ROUTE = 'index.php?sw/cache.json';
var OFFLINE_ROUTE = 'index.php?sw/offline';

// ############################### EVENT LISTENERS ##############################

self.addEventListener('install', function(event)
{
	self.skipWaiting();

	event.waitUntil(createCache());
});

self.addEventListener('activate', function(event)
{
	self.clients.claim();

	event.waitUntil(
		new Promise(function(resolve)
		{
			if (self.registration.navigationPreload)
			{
				self.registration.navigationPreload.enable();
			}

			resolve();
		})
	);
});

self.addEventListener('message', function(event)
{
	var clientId = event.source.id;
	var message = event.data;
	if (typeof message !== 'object' || message === null)
	{
		console.error('Invalid message:', message);
		return;
	}

	recieveMessage(clientId, message.type, message.payload);
});

self.addEventListener('fetch', function(event)
{
	var request = event.request;

	if (
		request.mode !== 'navigate' ||
		request.method !== 'GET' ||
		!request.headers.get('accept').includes('text/html')
	)
	{
		return;
	}

	var response = event.preloadResponse
		? event.preloadResponse
		: fetch(request);

	event.respondWith(
		response
			.catch(function(error)
			{
				if (navigator.onLine)
				{
					// If we're online, don't display the offline error since it might be misleading
					throw new Error(error);
				}

				return caches.open(CACHE_NAME)
					.then(function(cache)
					{
						return cache.match(OFFLINE_ROUTE);
					});
			})
	);
});

self.addEventListener('push', function(event)
{
	if (!(self.Notification && self.Notification.permission === 'granted'))
	{
		return;
	}

	try
	{
		var data = event.data.json();
	}
	catch (e)
	{
		console.warn('Received push notification but payload not in the expected format.', e);
		console.warn('Received data:', event.data.text());
		return;
	}

	if (!data || !data.title || !data.body)
	{
		console.warn('Received push notification but no payload data or required fields missing.', data);
		return;
	}

	data.last_count = 0;

	var options = {
		body: data.body,
		dir: data.dir || 'ltr',
		data: data
	};
	if (data.badge)
	{
		options.badge = data.badge;
	}
	if (data.icon)
	{
		options.icon = data.icon;
	}

	var notificationPromise;

	if (data.tag && data.tag_phrase)
	{
		options.tag = data.tag;
		options.renotify = true;

		notificationPromise = self.registration.getNotifications({ tag: data.tag })
			.then(function(notifications)
			{
				var lastKey = (notifications.length - 1),
					notification = notifications[lastKey],
					count = 0;

				if (notification)
				{
					count = parseInt(notification.data.last_count, 10) + 1;
					options.data.last_count = count;

					options.body = options.body +  ' ' + data.tag_phrase.replace('{count}', count.toString());
				}

				return self.registration.showNotification(data.title, options);
			});
	}
	else
	{
		notificationPromise = self.registration.showNotification(data.title, options);
	}

	event.waitUntil(notificationPromise);
});

self.addEventListener('notificationclick', function(event)
{
	var notification = event.notification;

	notification.close();

	if (notification.data.url)
	{
		event.waitUntil(clients.openWindow(notification.data.url));
	}
});

// ################################## MESSAGING #################################

function sendMessage(clientId, type, payload)
{
	if (typeof type !== 'string' || type === '')
	{
		console.error('Invalid message type:', type);
		return;
	}

	if (typeof payload === 'undefined')
	{
		payload = {};
	}
	else if (typeof payload !== 'object' || payload === null)
	{
		console.error('Invalid message payload:', payload);
		return;
	}

	clients.get(clientId)
		.then(function (client)
		{
			client.postMessage({
				type: type,
				payload: payload
			});
		})
		.catch(function(error)
		{
			console.error('An error occurred while sending a message:', error);
		});
}

var messageHandlers = {};

function recieveMessage(clientId, type, payload)
{
	if (typeof type !== 'string' || type === '')
	{
		console.error('Invalid message type:', type);
		return;
	}

	if (typeof payload !== 'object' || payload === null)
	{
		console.error('Invalid message payload:', payload);
		return;
	}

	var handler = messageHandlers[type];
	if (typeof handler === 'undefined')
	{
		console.error('No handler available for message type:', type);
		return;
	}

	handler(clientId, payload);
}

// ################################### CACHING ##################################

function createCache()
{
	return caches.delete(CACHE_NAME)
		.then(function()
		{
			return caches.open(CACHE_NAME);
		})
		.then(function(cache)
		{
			return fetch(CACHE_ROUTE)
				.then(function(response)
				{
					return response.json();
				})
				.then(function(response)
				{
					var key = response.key || null;
					var files = response.files || [];
					files.push(OFFLINE_ROUTE);

					return cache.addAll(files)
						.then(function()
						{
							return key;
						});
				});
		})
		.catch(function(error)
		{
			console.error('There was an error setting up the cache:', error);
		});
}

function updateCacheKey(clientId, key)
{
	sendMessage(clientId, 'updateCacheKey', { 'key': key });
}

messageHandlers.updateCache = function(clientId, payload)
{
	createCache();
};
