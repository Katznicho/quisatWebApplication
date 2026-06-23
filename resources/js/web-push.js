const STORAGE_KEY = 'quisat_web_push_device_id';

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }

  return outputArray;
}

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function getOrCreateDeviceId() {
  let deviceId = localStorage.getItem(STORAGE_KEY);

  if (!deviceId) {
    deviceId = crypto.randomUUID();
    localStorage.setItem(STORAGE_KEY, deviceId);
  }

  return deviceId;
}

async function registerWebPush() {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    return;
  }

  const keyResponse = await fetch('/web-push/vapid-public-key', {
    headers: { Accept: 'application/json' },
    credentials: 'same-origin',
  });

  if (!keyResponse.ok) {
    return;
  }

  const keyJson = await keyResponse.json();
  const publicKey = keyJson?.data?.public_key;

  if (!publicKey) {
    return;
  }

  const permission = await Notification.requestPermission();

  if (permission !== 'granted') {
    return;
  }

  const registration = await navigator.serviceWorker.register('/sw.js');
  const subscription = await registration.pushManager.subscribe({
    userVisibleOnly: true,
    applicationServerKey: urlBase64ToUint8Array(publicKey),
  });

  await fetch('/web-push/subscribe', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    credentials: 'same-origin',
    body: JSON.stringify({
      subscription: subscription.toJSON(),
      device_id: getOrCreateDeviceId(),
      device_name: navigator.userAgent,
    }),
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const enableButton = document.getElementById('enable-web-push');

  if (enableButton) {
    enableButton.addEventListener('click', async () => {
      try {
        await registerWebPush();
        enableButton.textContent = 'Browser notifications enabled';
        enableButton.disabled = true;
      } catch (error) {
        console.error('Web push registration failed', error);
      }
    });
  }
});

export { registerWebPush };
