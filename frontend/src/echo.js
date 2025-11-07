// frontend/src/echo.js
import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: "reverb",
  key: "2uzrl6iaj0k9iedjfuwx",           // must match REVERB_APP_KEY in your .env
  wsHost: "127.0.0.1",        // or 0.0.0.0 if you prefer, but use 127.0.0.1 in browser config
  wsPort: 8080,               // the Reverb port you saw running
  wssPort: 8080,
  forceTLS: false,            // Reverb uses ws (not wss) locally
  encrypted: false,
  disableStats: true,
  enabledTransports: ["ws", "wss"],
  // If your Reverb uses a path or prefix, you can add: path: '/app', etc.
});

export default echo;
