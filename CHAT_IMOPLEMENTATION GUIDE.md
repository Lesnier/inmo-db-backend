# Chat Module Implementation Guide

## Overview
This module provides a real-time messaging system integrated into the CRM. It allows users (agents) and potential contacts to communicate in "rooms" (Chats).

## 1. Database Schema
We created 3 new tables to handle the chat logic:

### `inmo_chats`
Represents a conversation room.
- `id`: Unique identifier.
- `uuid`: Public ID for socket channels (e.g., `chat.uuid` or `chat.id`).
- `type`: 'private' (1-on-1) or 'group'.
- `subject`: Name of the room (optional for private chats).
- `contact_id`: (Optional) Link to a CRM Contact object.

### `inmo_chat_participants`
Pivot table linking Users to Chats.
- `chat_id`: Reference to the Chat.
- `user_id`: Reference to the User.
- `last_read_at`: Timestamp for "Read Receipts" logic.
- `is_muted`: Boolean to mute notifications.

### `inmo_messages`
Stores the actual content.
- `chat_id`: The room.
- `user_id`: The sender (Nullable for system messages).
- `content`: Text content.
- `type`: 'text', 'image', 'file'.
- `read_at`: Global read status.

## 2. Backend Files Created

### Models
- **`App\Models\Chat`**: Main entity. Has `participants()` (User) and `messages()` (Message).
- **`App\Models\Message`**: Belongs to Chat and Sender.
- **`App\Models\User`**: Added `chats()` relationship.

### Events (Real-Time)
- **`App\Events\MessageSent`**:
    - Implements `ShouldBroadcast`.
    - Broadcasts on `PrivateChannel('chat.{id}')`.
    - Payload: The message object + sender info.

### Routes & Controller
- **`App\Http\Controllers\Api\Chat\ChatController`**:
    - `index()`: List my chats (with latest message).
    - `store()`: Start a new private chat (idempotent).
    - `messages($id)`: Get history for a chat.
    - `sendMessage($id)`: Save message + Dispatch Event.

- **`routes/api.php`**: Protected routes under `/api/chat/`.
- **`routes/channels.php`**: Authorization logic for `Broacast::channel('chat.{id}')`.

## 3. How Real-Time functionality works (Push)

1.  **Frontend** sends a POST request to `/api/chat/rooms/{id}/messages`.
2.  **Backend** saves the message to MySQL (`inmo_messages`).
3.  **Backend** dispatches `MessageSent` event.
4.  **Laravel Reverb** (Broadcaster) receives the event and "Pushes" it to the specific channel `chat.{id}`.
5.  **Frontend** (Laravel Echo) listening on that channel receives the JSON payload instantly and appends it to the UI.

## 4. Setup Instructions for Real-Time

To enable the "Push" aspect, you need to run the Reverb server and Queue worker.

### A. Environment Configuration (.env)
Ensure these values are set:
```env
BROADCAST_DRIVER=reverb
QUEUE_CONNECTION=database

REVERB_APP_ID=my-app-id
REVERB_APP_KEY=my-app-key
REVERB_APP_SECRET=my-app-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### B. Run Services (Terminal)
You need two separate terminal windows running:

**Terminal 1: WebSocket Server**
```bash
php artisan reverb:start
```

**Terminal 2: Queue Worker**
(Processes the broadcasting jobs asynchronously)
```bash
php artisan queue:work
```

## 5. Testing Guide (Postman + HTML Client)

Since you are not using Vite in this backend project, you can test the WebSocket connection using a standalone HTML file.

### Step 1: Create a Test Client
Create a file named `test_chat.html` anywhere on your computer (e.g., Desktop).

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat Test Client</title>
    <!-- 1. Load Pusher and Echo from CDN -->
    <script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.15.3/echo.iife.js"></script>
</head>
<body>
    <h1>Chat Monitor</h1>
    <div id="status" style="color: red;">Disconnected</div>
    <div id="messages" style="border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: scroll;"></div>

    <script>
        // CONFIGURATION
        const TOKEN = 'YOUR_SANCTUM_TOKEN_HERE'; // Login via Postman to get this
        const CHAT_ID = 1; // The ID of the chat you want to monitor
        const REVERB_HOST = 'localhost';
        const REVERB_PORT = 8080;
        const REVERB_KEY = 'my-app-key'; // From your .env

        // 2. Initialize Echo
        window.Pusher = Pusher;
        
        const echo = new Echo({
            broadcaster: 'reverb',
            key: REVERB_KEY,
            wsHost: REVERB_HOST,
            wsPort: REVERB_PORT,
            wssPort: REVERB_PORT,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            authEndpoint: 'http://localhost:8000/api/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: `Bearer ${TOKEN}`,
                    Accept: 'application/json'
                }
            }
        });

        // 3. Listen to Channel
        console.log(`Connecting to chat.${CHAT_ID}...`);
        
        echo.private(`chat.${CHAT_ID}`)
            .listen('.message.sent', (e) => {
                console.log('EVENT RECEIVED:', e);
                const msg = e.message;
                const div = document.createElement('div');
                div.innerHTML = `<strong>${msg.sender.name}:</strong> ${msg.content} <small>(${msg.created_at})</small>`;
                document.getElementById('messages').appendChild(div);
            })
            .error((err) => {
                console.error('Subscription Error:', err);
                document.getElementById('status').innerText = 'Error (See Console)';
            });

        // Monitor Connection State
        echo.connector.pusher.connection.bind('connected', () => {
             document.getElementById('status').innerText = 'Connected via Reverb';
             document.getElementById('status').style.color = 'green';
        });
    </script>
</body>
</html>
```

### Step 2: Get a Token
1.  Open **Postman**.
2.  POST `http://127.0.0.1:8000/api/auth/login` with email/password.
3.  Copy the `token` from the response.
4.  Paste it into the `test_chat.html` file (`const TOKEN = ...`).
5.  Also ensure a Chat with ID=1 exists (use Postman to create one if needed).

### Step 3: See it in Action
1.  Open `test_chat.html` in your browser. It should say "Connected via Reverb".
2.  In **Postman**, send a POST request to:  
    `http://127.0.0.1:8000/api/chat/rooms/1/messages`
    *   Headers: `Authorization: Bearer YOUR_TOKEN`
    *   Body (JSON): `{"content": "Hello from Postman!"}`
3.  Look at your **Browser**. The message "Hello from Postman!" should appear instantly without refreshing!

## 6. Frontend Implementation Examples (Reference)

### A. Next.js (Real Estate Portal)
... [Same as before] ...

### B. Ionic (Agent App)
... [Same as before] ...
