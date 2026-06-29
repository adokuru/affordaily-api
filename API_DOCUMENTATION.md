# Affordaily API Documentation

## Overview
Affordaily is a property management POS system for affordable short-stay rooms. This API provides endpoints for mobile POS operations and web dashboard management.

## Authentication
All API endpoints (except login) require authentication using Laravel Sanctum tokens.

Role access:
- `admin`: full access, including room setup, rate updates, payment confirmation, and payment correction/deletion.
- `receptionist`: POS-safe access for check-ins, visitor passes, room/payment reads, and payment creation.

### Login
```http
POST /api/v1/login
Content-Type: application/json

{
    "email": "receptionist@affordaily.com",
    "password": "CHANGE_ME"
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

## Local Demo Users
Default users are disabled unless `SEED_DEFAULT_USERS=true` is set before running `php artisan db:seed`. Demo credentials are for local development only and must not be used in production.

## API Endpoints

### Authentication
- `POST /api/v1/login` - Login user
- `POST /api/v1/logout` - Logout user (requires auth)
- `GET /api/v1/user` - Get current user info (requires auth)

### Bookings (POS Operations)
- `GET /api/v1/bookings` - List all bookings
- `POST /api/v1/bookings` - Create new booking (check-in)
- `GET /api/v1/bookings/{id}` - Get booking details
- `POST /api/v1/bookings/{id}/checkout` - Check out booking
- `POST /api/v1/bookings/{id}/extend` - Extend booking
- `GET /api/v1/bookings/search` - Search bookings
- `GET /api/v1/bookings/active` - Get active bookings

### Check-in (Create Booking)
```http
POST /api/v1/bookings
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "guest_name": "John Doe",
    "guest_phone": "+1234567890",
    "id_photo": "<image file>",
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
POST /api/v1/bookings/{id}/checkout
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
POST /api/v1/bookings/{id}/extend
Authorization: Bearer {token}
Content-Type: application/json

{
    "additional_nights": 1
}
```

### Visitor Passes
- `GET /api/v1/visitor-passes` - List visitor passes
- `POST /api/v1/visitor-passes` - Issue visitor pass
- `GET /api/v1/visitor-passes/{id}` - Get visitor pass details
- `POST /api/v1/visitor-passes/{id}/checkout` - Check out visitor
- `GET /api/v1/visitor-passes/booking/{bookingId}/active` - Get active visitors for booking
- `GET /api/v1/visitor-passes/booking/{bookingId}/all` - Get all visitors for booking

### Issue Visitor Pass
```http
POST /api/v1/visitor-passes
Authorization: Bearer {token}
Content-Type: application/json

{
    "booking_id": 1,
    "visitor_phone": "+1234567891",
    "visitor_name": "Jane Doe"
}
```

**Response includes:**
- `visitor`: Complete guest information (name, phone, email, etc.)
- `booking`: Booking reference and room number
- `is_active`: Pass status
- `check_in_time` / `check_out_time`: Timestamps

### Rooms
- `GET /api/v1/rooms` - List all rooms
- `POST /api/v1/rooms` - Create new room (admin)
- `GET /api/v1/rooms/{id}` - Get room details
- `PUT /api/v1/rooms/{id}` - Update room (admin)
- `DELETE /api/v1/rooms/{id}` - Delete room (admin)
- `GET /api/v1/rooms/available` - Get available rooms by type
- `GET /api/v1/rooms/occupancy` - Get occupancy statistics
- `GET /api/v1/rooms/rates` - Get room rates
- `POST /api/v1/rooms/rates` - Update room rates (admin)

### Guests
- `GET /api/v1/guests` - List all guests
- `POST /api/v1/guests` - Create new guest
- `GET /api/v1/guests/{id}` - Get guest details with booking history
- `PUT /api/v1/guests/{id}` - Update guest information
- `GET /api/v1/guests/search/phone` - Search guest by phone number

### Guest Phone Lookup
```http
GET /api/v1/guests/search/phone?phone=+1234567890
Authorization: Bearer {token}
```

### Payments
- `GET /api/v1/payments` - List payments
- `POST /api/v1/payments` - Create payment
- `GET /api/v1/payments/{id}` - Get payment details
- `PUT /api/v1/payments/{id}` - Update payment (admin)
- `DELETE /api/v1/payments/{id}` - Delete payment (admin)
- `POST /api/v1/payments/{id}/confirm` - Confirm payment (admin)
- `GET /api/v1/payments/ledger` - Get payment ledger

### Create Payment
```http
POST /api/v1/payments
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
- `GET /api/v1/dashboard/stats` - Get dashboard statistics
- `GET /api/v1/dashboard/roll-call` - Get roll call data
- `GET /api/v1/dashboard/payments` - Get dashboard payments

## Automated Operations

### Midnight (00:00)
- Automatically marks bookings with scheduled checkout as "pending_checkout"

### Noon (12:00)
- Automatically checks out overdue bookings (status: "auto_checkout")
- Makes rooms available again
- Deactivates all visitor passes

## Room Types
- **Bed Space A**: 2000 naira/night (200 rooms available)
- **Bed Space B**: 2000 naira/night (200 rooms available)
- **Total**: 400 rooms with automatic bed space assignment

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
