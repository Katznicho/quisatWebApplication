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

### 10. Conversations & Messaging

#### List Conversations
**GET** `/conversations`

**Query Parameters (optional):**
```
per_page=25
```

**Response:**
```json
{
  "success": true,
  "message": "Conversations fetched successfully.",
  "data": {
    "conversations": [
      {
        "id": 3,
        "uuid": "6a9d4c24-40e5-4d2c-9f66-1b0f23be7d0d",
        "title": "Mrs. Jennifer Wilson",
        "type": "direct",
        "last_message": {
          "id": 42,
          "content": "Hello! Just a quick update on the class — students are progressing very well!",
          "type": "text",
          "is_from_user": false,
          "is_read": false,
          "created_at": "2025-11-09T09:15:00Z",
          "sender": {
            "id": 6,
            "name": "Mrs. Jennifer Wilson",
            "email": "jennifer.wilson@school.com"
          }
        },
        "unread_count": 1,
        "last_message_at": "2025-11-09T09:15:00Z",
        "participants": [
          {
            "id": 6,
            "name": "Mrs. Jennifer Wilson",
            "email": "jennifer.wilson@school.com",
            "avatar_url": "https://example.com/avatar.jpg",
            "is_self": false
          },
          {
            "id": 1,
            "name": "Jane Doe",
            "email": "admin@example.com",
            "avatar_url": null,
            "is_self": true
          }
        ]
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 25,
      "total": 1,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

#### Conversation Messages
**GET** `/conversations/{id}/messages`

**Query Parameters (optional):**
```
per_page=50
page=1
```

**Response:**
```json
{
  "success": true,
  "message": "Messages fetched successfully.",
  "data": {
    "messages": [
      {
        "id": 42,
        "content": "Hello! Just a quick update on the class — students are progressing very well!",
        "type": "text",
        "is_from_user": false,
        "is_read": true,
        "read_at": "2025-11-09T12:00:00Z",
        "created_at": "2025-11-09T09:15:00Z",
        "sender": {
          "id": 6,
          "name": "Mrs. Jennifer Wilson",
          "email": "jennifer.wilson@school.com",
          "avatar_url": "https://example.com/avatar.jpg"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total": 3,
      "last_page": 1,
      "has_more": false
    }
  }
}
```

#### Send Message
**POST** `/conversations/{id}/messages`

**Request Body:**
```json
{
  "content": "Thanks for the update!",
  "type": "text"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Message sent successfully.",
  "data": {
    "message": {
      "id": 43,
      "content": "Thanks for the update!",
      "type": "text",
      "is_from_user": true,
      "is_read": true,
      "created_at": "2025-11-09T12:05:00Z",
      "sender": {
        "id": 1,
        "name": "Jane Doe",
        "email": "admin@example.com"
      }
    },
    "conversation": { /* same as list payload */ }
  }
}
```

#### Mark Conversation Read
**POST** `/conversations/{id}/read`

**Response:**
```json
{
  "success": true,
  "message": "Conversation marked as read."
}
```

### 11. Staff Dashboard

**GET** `/staff/dashboard`

Returns aggregated insights for the authenticated staff member’s business.

**Response:**
```json
{
  "success": true,
  "message": "Dashboard data loaded successfully.",
  "data": {
    "quick_stats": {
      "assignments_due": 4,
      "announcements_new": 2,
      "students_total": 120,
      "parents_total": 88
    },
    "today_schedule": [
      {
        "id": 15,
        "subject": "Mathematics",
        "class": "Grade 6B",
        "room": "Room 202",
        "start_time": "08:00",
        "end_time": "09:30",
        "teacher": "Mr. David Chen"
      }
    ],
    "upcoming_events": [
      {
        "id": 4,
        "title": "Science Fair",
        "start_date": "2025-11-20T06:00:00Z",
        "end_date": "2025-11-20T09:00:00Z",
        "location": "Main Hall",
        "event_type": "school"
      }
    ],
    "recent_announcements": [
      {
        "id": 9,
        "title": "Staff Meeting",
        "content": "All teaching staff are invited to…",
        "type": "staff",
        "sent_at": "2025-11-08T15:00:00Z"
      }
    ],
    "recent_assignments": [
      {
        "id": 21,
        "title": "Algebra Homework",
        "assignment_type": "homework",
        "subject": "Mathematics",
        "class_room": "Grade 6B",
        "due_date": "2025-11-11T15:00:00Z",
        "status": "published"
      }
    ]
  }
}
```

### 12. Parent Dashboard

**GET** `/parent/dashboard`

Provides home-screen data for the authenticated parent/guardian.

**Response:**
```json
{
  "success": true,
  "message": "Parent dashboard data loaded successfully.",
  "data": {
    "children": [
      {
        "id": 12,
        "uuid": "e3b8…",
        "full_name": "Ethan Johnson",
        "class": "Grade 3A",
        "class_room_id": 5,
        "student_id": "STU-00045",
        "avatar_url": "https://ui-avatars.com/api/…"
      }
    ],
    "announcements": [
      {
        "id": 27,
        "title": "Performance Night",
        "content": "Join us for the annual performance night…",
        "type": "community",
        "sent_at": "2025-11-07T17:30:00Z"
      }
    ],
    "upcoming_events": [
      {
        "id": 4,
        "title": "Science Fair",
        "description": "Showcase of student projects…",
        "start_date": "2025-11-20T06:00:00Z",
        "end_date": "2025-11-20T09:00:00Z",
        "location": "Main Hall"
      }
    ],
    "upcoming_assignments": [
      {
        "id": 21,
        "title": "Algebra Homework",
        "description": "Complete exercises 10-18",
        "due_date": "2025-11-11T15:00:00Z",
        "class_room": "Grade 6B",
        "subject": "Mathematics",
        "assignment_type": "homework"
      }
    ]
  }
}
```

### 13. Attendance

#### Student History
**GET** `/attendance/history?student_id={id}&limit=20`

**Response:**
```json
{
  "success": true,
  "message": "Attendance history loaded successfully.",
  "data": {
    "student": {
      "id": 12,
      "full_name": "Ethan Johnson",
      "class": "Grade 3A"
    },
    "attendance": [
      {
        "id": 88,
        "attendance_date": "2025-11-06",
        "status": "present",
        "class_room": "Grade 3A",
        "marked_by": "Class Teacher"
      }
    ]
  }
}
```

#### Check-In
**POST** `/attendance/check-in`

**Body:**
```json
{
  "student_id": 12,
  "parent_name": "Jane Doe",
  "parent_identifier": "ID-9042"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Check-in recorded successfully.",
  "data": {
    "attendance": {
      "id": 92,
      "attendance_date": "2025-11-09",
      "status": "present",
      "marked_by": "Jane Doe",
      "remarks": "Checked in by Jane Doe (ID-9042)"
    }
  }
}
```

#### Check-Out
**POST** `/attendance/check-out`

Body mirrors the check-in payload. Status will be saved as `excused`.

### 14. Student Progress

**GET** `/students/{student}/progress`

**Response:**
```json
{
  "success": true,
  "message": "Student progress loaded successfully.",
  "data": {
    "student": {
      "id": 12,
      "full_name": "Ethan Johnson",
      "class": "Grade 3A",
      "avatar_url": "https://ui-avatars.com/api/…"
    },
    "overview": {
      "overall_progress": "On Track",
      "academic_average": 89.4,
      "attendance": 96.5
    },
    "performance": {
      "monthly": [
        { "label": "Jun", "english": 88.5, "math": 92.4 },
        { "label": "Jul", "english": 90.1, "math": 93.8 }
      ],
      "quarterly": [
        { "label": "Q1", "english": 87.3, "math": 91.6 }
      ],
      "annually": [
        { "label": "2025", "english": 89.4, "math": 92.7 }
      ]
    }
  }
}
```

### 15. Documents

**GET** `/documents`

Returns a flattened list of assignment attachments available to the authenticated user.

**Response:**
```json
{
  "success": true,
  "message": "Documents retrieved successfully.",
  "data": {
    "documents": [
      {
        "assignment_id": 21,
        "assignment_title": "Algebra Homework",
        "class_room": "Grade 6B",
        "subject": "Mathematics",
        "due_date": "2025-11-11T15:00:00Z",
        "name": "Mathematics Homework Brief.pdf",
        "url": "https://example-files.online-convert.com/document/pdf/example.pdf",
        "type": "pdf",
        "size": "256KB"
      }
    ]
  }
}
```
