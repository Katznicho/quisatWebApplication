# Quisat API Documentation

## Base URL
```
https://yourdomain.com/api/v1
```

## Authentication
The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header:
```
Authorization: Bearer {token}
```

## Business Context
All authenticated users are automatically associated with their business. The API ensures that:
- Users can only access data within their business scope
- Business information is included in login responses
- All API requests are automatically scoped to the user's business
- Business features and permissions are enforced

## API Endpoints

### 1. User Authentication

#### Login
**POST** `/auth/login`

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "iPhone 12" // Optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "phone": "+256700000000",
            "status": "active",
            "business_id": 1,
            "business": {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "name": "Example School",
                "email": "school@example.com",
                "phone": "+256700000001",
                "address": "123 School Street",
                "city": "Kampala",
                "country": "Uganda",
                "logo": "https://example.com/logo.png",
                "type": "school",
                "mode": "production",
                "enabled_features": [1, 2, 3, 4]
            },
            "role": {
                "id": 1,
                "name": "Admin",
                "permissions": ["users.manage", "transactions.view"]
            },
            "branch": {
                "id": 1,
                "name": "Main Branch",
                "code": "MB-1"
            },
            "user_type": "business_admin"
        },
        "token": "1|abcdef123456...",
        "token_type": "Bearer"
    }
}
```

#### Parent/Guardian Login
**POST** `/auth/parent-login`

**Request Body:**
```json
{
    "email": "parent@example.com",
    "password": "password123",
    "device_name": "Android Phone" // Optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "parent": {
            "id": 1,
            "first_name": "Jane",
            "last_name": "Doe",
            "full_name": "Jane Doe",
            "email": "parent@example.com",
            "phone": "+256700000000",
            "relationship": "mother",
            "status": "active",
            "business_id": 1,
            "business": {
                "id": 1,
                "uuid": "550e8400-e29b-41d4-a716-446655440000",
                "name": "Example School",
                "email": "school@example.com",
                "phone": "+256700000001",
                "address": "123 School Street",
                "city": "Kampala",
                "country": "Uganda",
                "logo": "https://example.com/logo.png",
                "type": "school",
                "mode": "production",
                "enabled_features": [1, 2, 3, 4]
            },
            "students": [
                {
                    "id": 1,
                    "first_name": "John",
                    "last_name": "Doe",
                    "full_name": "John Doe",
                    "student_id": "STU001",
                    "class": "P5",
                    "status": "active"
                }
            ],
            "user_type": "parent_guardian"
        },
        "token": "1|abcdef123456...",
        "token_type": "Bearer"
    }
}
```

#### Logout
**POST** `/auth/logout`
**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "success": true,
    "message": "Logout successful"
}
```

### 2. User Profile Management

#### Get Profile
**GET** `/auth/profile`
**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "phone": "+256700000000",
            "status": "active",
            "business": { ... },
            "role": { ... },
            "branch": { ... }
        }
    }
}
```

#### Update Profile
**PUT** `/auth/profile`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "name": "John Smith",
    "phone": "+256700000001",
    "email": "johnsmith@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Smith",
            "email": "johnsmith@example.com",
            "phone": "+256700000001",
            "status": "active"
        }
    }
}
```

### 3. Password Management

#### Change Password
**POST** `/auth/change-password`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

#### Forgot Password (Email Code)
**POST** `/auth/forgot-password`

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset code sent to your email",
    "data": {
        "user_id": 1,
        "email": "user@example.com",
        "code": "123456",
        "expires_in": 600
    }
}
```

#### Reset Password (Email Code)
**POST** `/auth/reset-password`

**Request Body:**
```json
{
    "user_id": 1,
    "code": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset successfully"
}
```

#### Parent Forgot Password (Email Code)
**POST** `/auth/parent-forgot-password`

**Request Body:**
```json
{
    "email": "parent@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset code sent to your email",
    "data": {
        "parent_id": 1,
        "email": "parent@example.com",
        "code": "123456",
        "expires_in": 600
    }
}
```

#### Parent Reset Password (Email Code)
**POST** `/auth/parent-reset-password`

**Request Body:**
```json
{
    "parent_id": 1,
    "code": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset successfully"
}
```

#### Refresh Token
**POST** `/auth/refresh`
**Headers:** `Authorization: Bearer {token}`

**Response:**
```json
{
    "success": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "2|newtoken123456...",
        "token_type": "Bearer"
    }
}
```

## Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 6 characters."]
    }
}
```

### Authentication Error (401)
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

### Authorization Error (403)
```json
{
    "success": false,
    "message": "Account is not active. Please contact support."
}
```

### Not Found Error (404)
```json
{
    "success": false,
    "message": "Parent/Guardian not found"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "An error occurred during login"
}
```

## Status Codes

- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Rate Limiting

The API implements rate limiting to prevent abuse:
- **Login attempts:** 5 attempts per minute per IP
- **Password reset:** 3 attempts per hour per email
- **General API:** 1000 requests per hour per user

## Security Features

1. **Token-based Authentication** using Laravel Sanctum
2. **Password Hashing** using bcrypt
3. **Rate Limiting** to prevent brute force attacks
4. **Input Validation** on all endpoints
5. **CORS Protection** for cross-origin requests
6. **HTTPS Only** in production

## Mobile App Integration

### Flutter Example
```dart
// Login function
Future<Map<String, dynamic>> login(String email, String password) async {
  final response = await http.post(
    Uri.parse('https://yourdomain.com/api/v1/auth/login'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'email': email,
      'password': password,
      'device_name': 'Flutter App',
    }),
  );
  
  return jsonDecode(response.body);
}
```

### React Native Example
```javascript
// Login function
const login = async (email, password) => {
  const response = await fetch('https://yourdomain.com/api/v1/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      email,
      password,
      device_name: 'React Native App',
    }),
  });
  
  return await response.json();
};
```

## Testing

Use tools like Postman or curl to test the API:

```bash
# Login example
curl -X POST https://yourdomain.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "Test Device"
  }'
```

## Support

For API support, contact: no-reply@quisat.com

### 8. Assignments & Classwork

#### List Assignments
**GET** `/assignments`

**Query Parameters (optional):**
```
type=assignment|classwork|homework|project
status=draft|published|completed
class_room_id=1
subject_id=2
due_before=2025-11-30
due_after=2025-11-01
per_page=50
```

**Response:**
```json
{
  "success": true,
  "message": "Assignments fetched successfully.",
  "data": {
    "assignments": [
      {
        "id": 12,
        "uuid": "4f9f8c23-0b1f-4f7f-9f81-1d52b03f0ea9",
        "title": "Mathematics Homework",
        "description": "Solve the attached problem set and submit by Friday.",
        "assignment_type": "homework",
        "status": "published",
        "assigned_date": "2025-11-06",
        "due_date": "2025-11-10",
        "due_time": "17:00",
        "total_marks": 50,
        "attachments": [],
        "class_room": {
          "id": 3,
          "name": "Primary 4",
          "code": "P4"
        },
        "subject": {
          "id": 2,
          "name": "Mathematics",
          "code": "MATH"
        },
        "teacher": {
          "id": 6,
          "name": "Mrs. Jennifer Wilson",
          "email": "jennifer.wilson@school.com"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 6,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

#### Assignment Detail
**GET** `/assignments/{id|uuid}`

**Response:**
```json
{
  "success": true,
  "message": "Assignment retrieved successfully.",
  "data": {
    "assignment": {
      "id": 12,
      "uuid": "4f9f8c23-0b1f-4f7f-9f81-1d52b03f0ea9",
      "title": "Mathematics Homework",
      "description": "Solve the attached problem set and submit by Friday.",
      "assignment_type": "homework",
      "status": "published",
      "assigned_date": "2025-11-06",
      "due_date": "2025-11-10",
      "due_time": "17:00",
      "total_marks": 50,
      "attachments": [],
      "published_at": "2025-11-06T09:15:00Z",
      "is_overdue": false,
      "class_room": { "id": 3, "name": "Primary 4", "code": "P4" },
      "subject": { "id": 2, "name": "Mathematics", "code": "MATH" },
      "branch": { "id": 1, "name": "Main Branch", "code": "MB-1" },
      "teacher": { "id": 6, "name": "Mrs. Jennifer Wilson", "email": "jennifer.wilson@school.com" }
    }
  }
}
```

### 9. Broadcast Announcements

#### List Announcements
**GET** `/announcements`

**Query Parameters (optional):**
```
type=general|academic|event|urgent
status=draft|sent
search=conference
per_page=50
```

**Response:**
```json
{
  "success": true,
  "message": "Announcements fetched successfully.",
  "data": {
    "announcements": [
      {
        "id": 5,
        "title": "Parent-Teacher Conference",
        "content": "We will host a parent-teacher conference next week. Please confirm your attendance.",
        "type": "general",
        "status": "sent",
        "channels": ["in_app"],
        "target_roles": [],
        "target_users": [],
        "scheduled_at": null,
        "sent_at": "2025-11-07T08:00:00Z",
        "created_at": "2025-11-05T11:24:40Z",
        "updated_at": "2025-11-05T11:24:40Z",
        "sender": {
          "id": 6,
          "name": "Mrs. Jennifer Wilson",
          "email": "jennifer.wilson@school.com"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 3,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

#### Announcement Detail
**GET** `/announcements/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Announcement retrieved successfully.",
  "data": {
    "announcement": {
      "id": 5,
      "title": "Parent-Teacher Conference",
      "content": "We will host a parent-teacher conference next week. Please confirm your attendance.",
      "type": "general",
      "status": "sent",
      "channels": ["in_app"],
      "target_roles": [],
      "target_users": [],
      "scheduled_at": null,
      "sent_at": "2025-11-07T08:00:00Z",
      "created_at": "2025-11-05T11:24:40Z",
      "updated_at": "2025-11-05T11:24:40Z",
      "sender": {
        "id": 6,
        "name": "Mrs. Jennifer Wilson",
        "email": "jennifer.wilson@school.com"
      },
      "can_edit": false
    }
  }
}
```
