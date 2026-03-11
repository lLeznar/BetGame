import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

export function getEcho() {
  if (echoInstance) return echoInstance;
  
  window.Pusher = Pusher;
  
  try {
    const token = localStorage.getItem('auth_token');
    
    const config = {
      broadcaster: 'reverb',
      key: import.meta.env.VITE_REVERB_APP_KEY ?? 'simfd0hzcr3cwtvbbbsu',
      wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
      wsPort: import.meta.env.VITE_REVERB_PORT ?? 8081,
      wssPort: import.meta.env.VITE_REVERB_PORT ?? 8081,
      forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: '/broadcasting/auth',
      auth: {
        headers: {
          Authorization: token ? `Bearer ${token}` : '',
          Accept: 'application/json',
        },
      },
    };
    
    console.log('Initializing Echo with config:', config);
    echoInstance = new Echo(config);
  } catch (err) {
    console.warn('Laravel Echo could not connect to Reverb:', err.message);
    echoInstance = null;
  }
  
  return echoInstance;
}

// Call this after login/register to rebuild Echo with the fresh auth token
export function resetEcho() {
  if (echoInstance) {
    try { echoInstance.disconnect(); } catch (e) { /* ignore */ }
    echoInstance = null;
  }
}

// Robust proxy to prevent crashes if echo is null
const echoProxy = {
  private: (channel) => {
    const instance = getEcho();
    if (instance) return instance.private(channel);
    console.warn(`Echo unavailable: cannot subscribe to private channel ${channel}`);
    return { listen: () => echoProxy.private(channel), stopListening: () => {} }; // chainable no-op
  },
  join: (channel) => {
    const instance = getEcho();
    if (instance) return instance.join(channel);
    return { here: () => {}, joining: () => {}, leaving: () => {}, listen: () => {} };
  },
  leave: (channel) => {
    getEcho()?.leave(channel);
  }
};

export default echoProxy;
