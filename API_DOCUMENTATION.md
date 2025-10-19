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
            "business": {
                "id": 1,
                "name": "Example School",
                "email": "school@example.com",
                "phone": "+256700000001",
                "address": "123 School Street",
                "city": "Kampala",
                "country": "Uganda"
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
            }
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
            "business": {
                "id": 1,
                "name": "Example School",
                "email": "school@example.com",
                "phone": "+256700000001",
                "address": "123 School Street",
                "city": "Kampala",
                "country": "Uganda"
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
            ]
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

#### Forgot Password
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
    "message": "Password reset link sent to your email"
}
```

#### Reset Password
**POST** `/auth/reset-password`

**Request Body:**
```json
{
    "email": "user@example.com",
    "token": "reset_token_from_email",
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
