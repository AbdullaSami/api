# MailBox API Documentation

## Overview
The MailBox API provides messaging functionality for members to send and receive messages within the system. All endpoints require authentication using Sanctum tokens.

## Base URL
```
/api/v1
```

## Authentication
All endpoints require `Authorization: Bearer {token}` header where `{token}` is the user's Sanctum token.

## Endpoints

### 1. Get Inbox Messages
**GET** `/messages/inbox`

Retrieve messages received by the authenticated user.

**Query Parameters:**
- `per_page` (optional, integer): Number of messages per page (default: 20)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "message_id": 1,
        "recipient_id": 123,
        "is_read": false,
        "read_at": null,
        "deleted_by_recipient": false,
        "created_at": "2026-05-12T15:30:00.000000Z",
        "updated_at": "2026-05-12T15:30:00.000000Z",
        "message": {
          "id": 1,
          "sender_id": 456,
          "subject": "Welcome Message",
          "body": "Hello and welcome!",
          "delivery_type": "direct",
          "tree_side": "both",
          "created_at": "2026-05-12T15:30:00.000000Z",
          "updated_at": "2026-05-12T15:30:00.000000Z",
          "sender": {
            "id": 456,
            "name": "John Doe",
            "email": "john@example.com"
          }
        }
      }
    ],
    "first_page_url": "http://example.com/api/v1/messages/inbox?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://example.com/api/v1/messages/inbox?page=1",
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

### 2. Get Sent Messages
**GET** `/messages/sent`

Retrieve messages sent by the authenticated user.

**Query Parameters:**
- `per_page` (optional, integer): Number of messages per page (default: 20)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "sender_id": 123,
        "subject": "Team Update",
        "body": "Here's the latest update...",
        "delivery_type": "downline",
        "tree_side": "both",
        "created_at": "2026-05-12T15:30:00.000000Z",
        "updated_at": "2026-05-12T15:30:00.000000Z",
        "recipients_count": 5,
        "recipients": [
          {
            "id": 1,
            "recipient_id": 456,
            "recipient": {
              "id": 456,
              "name": "Jane Smith",
              "email": "jane@example.com"
            }
          }
        ]
      }
    ],
    "first_page_url": "http://example.com/api/v1/messages/sent?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://example.com/api/v1/messages/sent?page=1",
    "per_page": 20,
    "to": 1,
    "total": 1
  }
}
```

### 3. Get Trash Messages
**GET** `/messages/trash`

Retrieve deleted messages from the authenticated user's trash.

**Query Parameters:**
- `per_page` (optional, integer): Number of messages per page (default: 20)

**Response:** Same structure as inbox endpoint

### 4. View Message Details
**GET** `/messages/{id}`

View a specific message and automatically mark it as read.

**Path Parameters:**
- `id` (integer): Message ID

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "message_id": 1,
    "recipient_id": 123,
    "is_read": true,
    "read_at": "2026-05-12T15:35:00.000000Z",
    "deleted_by_recipient": false,
    "created_at": "2026-05-12T15:30:00.000000Z",
    "updated_at": "2026-05-12T15:35:00.000000Z",
    "message": {
      "id": 1,
      "sender_id": 456,
      "subject": "Important Update",
      "body": "Please review the attached documents...",
      "delivery_type": "direct",
      "tree_side": "both",
      "created_at": "2026-05-12T15:30:00.000000Z",
      "updated_at": "2026-05-12T15:30:00.000000Z",
      "sender": {
        "id": 456,
        "name": "Admin User",
        "email": "admin@example.com"
      },
      "attachments": []
    }
  }
}
```

### 5. Compose and Send Message
**POST** `/messages/compose`

Send a new message to recipients.

**Request Body:**
```json
{
  "subject": "Team Announcement",
  "body": "This is an important announcement for the team...",
  "delivery_type": "downline",
  "tree_side": "both",
  "recipient_ids": [123, 456, 789]
}
```

**Fields:**
- `subject` (optional, string, max 255): Message subject
- `body` (required, string): Message content
- `delivery_type` (required, string): Delivery method
  - `direct`: Send to specific recipients (requires `recipient_ids`)
  - `upline`: Send to upline members
  - `downline`: Send to downline members
- `tree_side` (optional, string): Tree side for upline/downline delivery
  - `left`: Left side of the tree
  - `right`: Right side of the tree
  - `both`: Both sides (default)
- `recipient_ids` (optional, array): Array of user IDs (required when `delivery_type` is `direct`)

**Response:**
```json
{
  "success": true,
  "message": "Message sent successfully",
  "data": {
    "id": 1,
    "sender_id": 123,
    "subject": "Team Announcement",
    "body": "This is an important announcement for the team...",
    "delivery_type": "downline",
    "tree_side": "both",
    "created_at": "2026-05-12T15:40:00.000000Z",
    "updated_at": "2026-05-12T15:40:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "No recipients found"
}
```

### 6. Mark Message as Read
**POST** `/messages/{id}/read`

Manually mark a message as read.

**Path Parameters:**
- `id` (integer): Message ID

**Response:**
```json
{
  "success": true,
  "message": "Message marked as read"
}
```

### 7. Move Message to Trash
**POST** `/messages/{id}/trash`

Move a message to the trash.

**Path Parameters:**
- `id` (integer): Message ID

**Response:**
```json
{
  "success": true,
  "message": "Message moved to trash"
}
```

### 8. Restore Message from Trash
**POST** `/messages/{id}/restore`

Restore a message from trash back to inbox.

**Path Parameters:**
- `id` (integer): Message ID

**Response:**
```json
{
  "success": true,
  "message": "Message restored successfully"
}
```

## Usage Examples

### JavaScript/Fetch API
```javascript
// Get inbox messages
const getInbox = async (page = 1) => {
  const response = await fetch(`/api/v1/messages/inbox?page=${page}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });
  return await response.json();
};

// Send a message
const sendMessage = async (messageData) => {
  const response = await fetch('/api/v1/messages/compose', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(messageData)
  });
  return await response.json();
};

// Example usage
const newMessage = {
  subject: 'Welcome to the Team',
  body: 'We are excited to have you join our team!',
  delivery_type: 'direct',
  recipient_ids: [123, 456]
};

sendMessage(newMessage).then(result => {
  console.log('Message sent:', result);
});
```

### Axios Example
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Get sent messages
const getSentMessages = async (perPage = 20) => {
  const response = await api.get('/messages/sent', {
    params: { per_page: perPage }
  });
  return response.data;
};

// Mark message as read
const markAsRead = async (messageId) => {
  const response = await api.post(`/messages/${messageId}/read`);
  return response.data;
};
```

## Error Handling

All endpoints return standard HTTP status codes:
- `200`: Success
- `401`: Unauthorized (invalid/missing token)
- `404`: Message not found
- `422`: Validation error
- `500`: Server error

Error responses follow this format:
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## Notes

1. Messages are automatically marked as read when viewed via the `show` endpoint
2. Users cannot send messages to themselves
3. When using `upline` or `downline` delivery types, the system automatically determines recipients based on the MLM tree structure
4. Deleted messages are moved to trash and can be restored
5. All timestamps are in UTC format
6. Pagination follows Laravel's standard pagination format
