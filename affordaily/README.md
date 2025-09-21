# Affordaily - Property Management POS System

A minimal, reliable property-management POS + Dashboard system for affordable short-stay rooms. Focus on fast check-in/out from an Android POS (APK) and a lightweight web dashboard for front-desk managers.

## Features

### POS APK (Mobile)
- Quick check-in flow with automatic room assignment
- Guest information capture (name, phone, ID photo)
- Payment processing (cash or bank transfer)
- Check-out with damage notes and key return tracking
- Booking extension functionality
- Visitor pass issuance and management
- Search and view bookings

### Dashboard (Web)
- Real-time room occupancy overview
- Payment ledger with filtering
- Roll call (current occupants + visitor counts)
- Administrative settings for room rates and configuration
- Automated checkout processing

### Automated Operations
- **Midnight (00:00)**: Mark bookings as pending checkout
- **Noon (12:00)**: Automatic checkout for overdue bookings

## Installation

### Prerequisites
- PHP 8.1 or higher
- Composer
- SQLite (or MySQL/PostgreSQL)

### Setup
1. Clone the repository:
```bash
git clone <repository-url>
cd affordaily
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations and seeders:
```bash
php artisan migrate
php artisan db:seed
```

6. Start the development server:
```bash
php artisan serve
```

The application will be available at `http://localhost:8000`

## Default Users

### Admin User
- **Email**: admin@affordaily.com
- **Password**: admin123
- **Role**: Admin (full access)

### Receptionist User
- **Email**: receptionist@affordaily.com
- **Password**: receptionist123
- **Role**: Receptionist (POS operations)

## API Usage

### Authentication
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"receptionist@affordaily.com","password":"receptionist123"}'
```

### Create Booking (Check-in)
```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "guest_name": "John Doe",
    "guest_phone": "+1234567890",
    "number_of_nights": 2,
    "preferred_bed_type": "A",
    "payment_method": "cash",
    "payer_name": "John Doe"
  }'
```

See `API_DOCUMENTATION.md` for complete API reference.

## Database Schema

### Rooms
- Room number and bed type (A/B)
- Availability status
- Current occupancy

### Bookings
- Guest information
- Check-in/out times
- Payment details
- Status tracking

### Payments
- Payment method (cash/transfer)
- Amount and confirmation status
- Reference numbers

### Visitor Passes
- Visitor information
- Check-in/out times
- Active status

## Automated Tasks

The system includes automated tasks that run via Laravel's scheduler:

### Setup Cron Job
Add this to your crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Manual Task Execution
```bash
# Process checkouts for current time
php artisan affordaily:process-checkouts

# Process checkouts for specific time
php artisan affordaily:process-checkouts --time="2024-01-01 12:00:00"
```

## Room Assignment Logic

The system automatically assigns rooms based on:
1. Preferred bed type (if specified)
2. Same-type grouping preference
3. Availability
4. Sequential room numbering

## Configuration

### Room Rates
Default rates are seeded:
- Type A: $50/night
- Type B: $75/night

Update via API or admin dashboard.

### Checkout Times
- Scheduled checkout: 12:00 PM
- Auto-checkout: 12:00 PM (noon)
- Pending status: 12:00 AM (midnight)

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

### Database Reset
```bash
php artisan migrate:fresh --seed
```

## Production Deployment

1. Set up proper database (MySQL/PostgreSQL)
2. Configure environment variables
3. Set up cron job for scheduler
4. Configure web server (Apache/Nginx)
5. Set up SSL certificates
6. Configure backup procedures

## Mobile App Integration

The API is designed to work with Android POS applications. Key endpoints for mobile:

- `/api/bookings` - Check-in/out operations
- `/api/visitor-passes` - Visitor management
- `/api/rooms/available` - Room availability
- `/api/payments` - Payment processing

## Support

For issues and questions:
1. Check the API documentation
2. Review the logs in `storage/logs/`
3. Check database migrations
4. Verify environment configuration

## License

This project is proprietary software. All rights reserved.