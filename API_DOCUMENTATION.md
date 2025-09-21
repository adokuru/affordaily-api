# Affordaily API Documentation

## Overview
Affordaily is a property management POS system for affordable short-stay rooms. This API provides endpoints for mobile POS operations and web dashboard management.

## Authentication
All API endpoints (except login) require authentication using Laravel Sanctum tokens.

### Login
```http
POST /api/login
Content-Type: application/json

{
    "email": "receptionist@affordaily.com",
    "password": "receptionist123"
}
```

### Response
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Receptionist User",
            "email": "receptionist@affordaily.com",
            "role": "receptionist"
        },
        "access_token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

## Default Users
- **Admin**: admin@affordaily.com / admin123
- **Receptionist**: receptionist@affordaily.com / receptionist123

## API Endpoints

### Authentication
- `POST /api/login` - Login user
- `POST /api/logout` - Logout user (requires auth)
- `GET /api/user` - Get current user info (requires auth)

### Bookings (POS Operations)
- `GET /api/bookings` - List all bookings
- `POST /api/bookings` - Create new booking (check-in)
- `GET /api/bookings/{id}` - Get booking details
- `POST /api/bookings/{id}/checkout` - Check out booking
- `POST /api/bookings/{id}/extend` - Extend booking
- `GET /api/bookings/search` - Search bookings
- `GET /api/bookings/active` - Get active bookings

### Check-in (Create Booking)
```http
POST /api/bookings
Authorization: Bearer {token}
Content-Type: application/json

{
    "guest_name": "John Doe",
    "guest_phone": "+1234567890",
    "id_photo_path": "/uploads/id_photos/john_doe.jpg",
    "number_of_nights": 2,
    "preferred_bed_type": "A",
    "payment_method": "cash",
    "payer_name": "John Doe",
    "reference": "TXN123456"
}
```

**Response includes:**
- `booking_reference`: Auto-generated unique reference (e.g., "REF123ABC456")
- `room_id`: Assigned room with bed space A or B
- `total_amount`: Calculated as number_of_nights × 2000 naira

### Check-out
```http
POST /api/bookings/{id}/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
    "damage_notes": "Minor scratch on wall",
    "key_returned": true,
    "early_checkout": false
}
```

### Extend Booking
```http
POST /api/bookings/{id}/extend
Authorization: Bearer {token}
Content-Type: application/json

{
    "additional_nights": 1
}
```

### Visitor Passes
- `GET /api/visitor-passes` - List visitor passes
- `POST /api/visitor-passes` - Issue visitor pass
- `GET /api/visitor-passes/{id}` - Get visitor pass details
- `POST /api/visitor-passes/{id}/checkout` - Check out visitor
- `GET /api/visitor-passes/booking/{bookingId}/active` - Get active visitors for booking
- `GET /api/visitor-passes/booking/{bookingId}/all` - Get all visitors for booking

### Issue Visitor Pass
```http
POST /api/visitor-passes
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_id": 1,
    "visitor_name": "Jane Doe",
    "visitor_phone": "+1234567891",
    "visitor_id_photo_path": "/uploads/visitor_photos/jane_doe.jpg"
}
```

### Rooms
- `GET /api/rooms` - List all rooms
- `POST /api/rooms` - Create new room
- `GET /api/rooms/{id}` - Get room details
- `PUT /api/rooms/{id}` - Update room
- `DELETE /api/rooms/{id}` - Delete room
- `GET /api/rooms/available` - Get available rooms by type
- `GET /api/rooms/occupancy` - Get occupancy statistics
- `GET /api/rooms/rates` - Get room rates
- `POST /api/rooms/rates` - Update room rates

### Payments
- `GET /api/payments` - List payments
- `POST /api/payments` - Create payment
- `GET /api/payments/{id}` - Get payment details
- `PUT /api/payments/{id}` - Update payment
- `DELETE /api/payments/{id}` - Delete payment
- `POST /api/payments/{id}/confirm` - Confirm payment
- `GET /api/payments/ledger` - Get payment ledger

### Create Payment
```http
POST /api/payments
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_id": 1,
    "payment_method": "transfer",
    "amount": 50.00,
    "payer_name": "John Doe",
    "reference": "TXN123456"
}
```

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics
- `GET /api/dashboard/roll-call` - Get roll call data
- `GET /api/dashboard/payments` - Get dashboard payments

## Automated Operations

### Midnight (00:00)
- Automatically marks bookings with scheduled checkout as "pending_checkout"

### Noon (12:00)
- Automatically checks out overdue bookings (status: "auto_checkout")
- Makes rooms available again
- Deactivates all visitor passes

## Room Types
- **Type A**: Lower rate (default: $50/night)
- **Type B**: Higher rate (default: $75/night)

## Booking Statuses
- `active` - Guest is currently checked in
- `pending_checkout` - Checkout time has passed, awaiting manual checkout
- `completed` - Normal checkout completed
- `auto_checkout` - Automatically checked out at noon
- `early_checkout` - Guest checked out before scheduled time

## Payment Methods
- `cash` - Cash payment
- `transfer` - Bank transfer or online payment

## Error Responses
All error responses follow this format:
```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Validation error message"]
    }
}
```

## Success Responses
All success responses follow this format:
```json
{
    "success": true,
    "data": {
        // Response data
    }
}
```

## Rate Limiting
API endpoints are rate-limited to prevent abuse. Default limits:
- 60 requests per minute for authenticated users
- 30 requests per minute for unauthenticated users

## CORS
API supports CORS for cross-origin requests from mobile applications.