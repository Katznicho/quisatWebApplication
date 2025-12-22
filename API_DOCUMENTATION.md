# KidsMart API Documentation

## Base URL
`https://app.quisat.com/api/v1`

## Authentication
Most endpoints require authentication using Bearer token:
```
Authorization: Bearer {token}
```

---

## Products API (Public)

### 1. List Products
**GET** `/products`

**Query Parameters:**
- `category` (optional) - Filter by category
- `business_id` (optional) - Filter by business
- `search` (optional) - Search in name, description, SKU
- `in_stock_only` (optional) - Show only products in stock

**Response:**
```json
{
    "success": true,
  "message": "Products retrieved successfully.",
    "data": {
    "products": [
      {
        "id": 1,
        "uuid": "aca50b23-42e6-42b5-a8d6-b9db40a3e716",
        "name": "Educational Building Blocks Set",
        "description": "Colorful wooden building blocks...",
        "price": 45000.00,
        "category": "Toys",
        "image_url": "https://app.quisat.com/storage/products/...",
        "images": [
          {
            "id": 1,
            "url": "https://app.quisat.com/storage/products/...",
            "is_primary": true,
            "sort_order": 0
          }
        ],
        "stock_quantity": 25,
        "is_available": true,
            "status": "active",
        "sku": "PROD-ABC123",
            "business": {
          "id": 16,
          "name": "Plumblt",
          "email": "info@plumblt.com",
          "phone": "+256700000000"
        }
      }
    ],
    "total": 5,
    "categories": ["Toys", "Books", "Art Supplies"]
    }
}
```

### 2. Get Single Product
**GET** `/products/{id}`

**Response:**
```json
{
    "success": true,
  "message": "Product retrieved successfully.",
    "data": {
    "product": {
            "id": 1,
      "uuid": "aca50b23-42e6-42b5-a8d6-b9db40a3e716",
      "name": "Educational Building Blocks Set",
      "description": "...",
      "price": 45000.00,
      "category": "Toys",
      "image_url": "...",
      "images": [...],
      "stock_quantity": 25,
      "is_available": true,
            "status": "active",
      "sku": "PROD-ABC123",
      "business": {...},
      "created_at": "2025-12-21T10:00:00Z",
      "updated_at": "2025-12-21T10:00:00Z"
        }
    }
}
```

---

## Orders API (Protected - Requires Authentication)

### 1. Create Order
**POST** `/orders`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 2,
      "quantity": 1
    }
  ],
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "customer_phone": "+256700000000",
  "customer_address": "123 Main Street, Kampala",
  "notes": "Please deliver in the morning"
}
```

**Response:**
```json
{
    "success": true,
  "message": "Order placed successfully. The seller will contact you soon.",
  "data": {
    "order": {
      "id": 1,
      "uuid": "order-uuid",
      "order_number": "ORD-ABC123XYZ",
      "status": "pending",
      "subtotal": 125000.00,
      "total": 125000.00,
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "customer_phone": "+256700000000",
      "customer_address": "123 Main Street, Kampala",
      "notes": "Please deliver in the morning",
      "seller_notes": null,
      "business": {
        "id": 16,
        "name": "Plumblt",
        "email": "info@plumblt.com",
        "phone": "+256700000000"
      },
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "Educational Building Blocks Set",
          "price": 45000.00,
          "quantity": 2,
          "subtotal": 90000.00,
          "product": {
            "id": 1,
            "uuid": "product-uuid",
            "name": "Educational Building Blocks Set",
            "image_url": "..."
          }
        }
      ],
      "created_at": "2025-12-21T10:00:00Z"
    }
  }
}
```

### 2. List Orders
**GET** `/orders`

**Query Parameters:**
- `status` (optional) - Filter by status: pending, confirmed, processing, shipped, delivered, cancelled

**Response:**
```json
{
  "success": true,
  "message": "Orders retrieved successfully.",
  "data": {
    "orders": [...],
    "total": 5
  }
}
```

### 3. Get Single Order
**GET** `/orders/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Order retrieved successfully.",
  "data": {
    "order": {
            "id": 1,
      "uuid": "order-uuid",
      "order_number": "ORD-ABC123XYZ",
      "status": "pending",
      "subtotal": 125000.00,
      "total": 125000.00,
      "customer_name": "John Doe",
      "customer_email": "john@example.com",
      "customer_phone": "+256700000000",
      "customer_address": "123 Main Street, Kampala",
      "notes": "Please deliver in the morning",
      "seller_notes": null,
      "business": {...},
      "items": [...],
      "created_at": "2025-12-21T10:00:00Z",
      "updated_at": "2025-12-21T10:00:00Z"
    }
  }
}
```

### 4. Update Order Status (Seller Only)
**PATCH** `/orders/{id}/status`

**Request Body:**
```json
{
  "status": "confirmed",
  "seller_notes": "Order confirmed, will process tomorrow"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order status updated successfully.",
  "data": {
    "order": {...}
  }
}
```

---

## Order Statuses
- `pending` - Order just placed
- `confirmed` - Seller confirmed the order
- `processing` - Order being prepared
- `shipped` - Order has been shipped
- `delivered` - Order delivered to customer
- `cancelled` - Order cancelled

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "items": ["The items field is required."],
    "items.0.product_id": ["The selected product id is invalid."]
  }
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Product not found."
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "An error occurred while placing the order.",
  "error": "Error details..."
}
```
