# WatchList

A Laravel-based application for tracking TV shows and episodes with reminder notifications via email or SMS.

## Description

WatchList is a reminder application built with Laravel 12 and Livewire 3 that allows users to:
- Track TV shows they're watching
- Monitor episode schedules
- Receive notifications for upcoming episodes
- Configure personalized notification settings (SMTP email or SMS)
- Add personal notes to watchlist items

## Requirements

- PHP ^8.3
- Composer
- Node.js & NPM
- SQLite, MySQL, or PostgreSQL database

## Installation

### Clone the repository
```bash
git clone <repository-url>
cd watchlist
```

### Install PHP dependencies
```bash
composer install
```

### Install JavaScript dependencies
```bash
npm install
```

### Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

### Configure your database
Update the `.env` file with your database credentials:
```
DB_CONNECTION=sqlite
# or configure MySQL/PostgreSQL connection
```

### Run migrations
```bash
php artisan migrate
```

### Build assets
```bash
npm run build
# or for development with hot-reload:
npm run dev
```

## Development

Start the development server with all services (web server, queue worker, logs, and Vite):

```bash
composer dev
```

Or run individual services:

```bash
# Start the web server
php artisan serve

# Start the queue worker
php artisan queue:listen --tries=1

# Start the log viewer
php artisan pail --timeout=0

# Start Vite dev server
npm run dev
```

## Features

- **Show Tracking**: Add and manage TV shows in your watchlist
- **Episode Management**: Track episodes and their air dates
- **Reminders**: Get notified before episodes air
- **Notification Logs**: View history of sent notifications
- **Customizable Settings**: 
  - SMTP configuration for email notifications
  - SMS settings for text message notifications
- **Personal Notes**: Add notes to your watchlist items

## Technologies Used

### Backend
- Laravel 12
- Livewire 3
- Laravel Breeze (authentication)

### Frontend
- Vite
- Tailwind CSS
- Alpine.js

### Testing
- PHPUnit
- Faker

## Code Quality

Format code using Laravel Pint:
```bash
./vendor/bin/pint
```

Run tests:
```bash
./vendor/bin/phpunit
```

## License

MIT License
