# Wajenzi - Construction Project Management System

A comprehensive enterprise resource planning (ERP) system designed specifically for construction companies in Tanzania. Wajenzi streamlines project management, financial operations, HR management, and client relations.

## Features

### Project Management
- Project creation and tracking
- Bill of Quantities (BOQ) management
- Material requisition and inventory tracking
- Daily progress reports
- Site visit scheduling and management
- Project team assignment
- Document management
- Construction phase tracking

### Financial Management
- Purchase orders and approvals
- Invoice generation and management
- Expense tracking
- Bank reconciliation
- Petty cash management
- VAT and tax management
- Financial reports

### Human Resources
- Employee profiles and management
- Payroll processing
- Leave management
- Loan management
- Attendance tracking
- Recruitment
- Training management

### Client Management
- Client profiles
- Project proposals
- Contract management
- Client document repository

### Inventory & Procurement
- Material inventory tracking
- Supplier management
- Purchase requisitions
- Stock management
- Auto-purchase functionality

### Reporting & Analytics
- Financial reports
- Project progress reports
- Employee performance reports
- Inventory reports
- Custom report generation


## Requirements

- PHP >= 7.4
- MySQL >= 5.7
- Node.js >= 14.x
- Composer
- XAMPP/LAMP/WAMP Stack

## Installation

1. Clone the repository
```bash
git clone https://github.com/moinfo/wajenzi.git
cd wajenzi
```

2. Install PHP dependencies
```bash
composer install
```

3. Install Node.js dependencies
```bash
npm install
```

4. Copy the environment file
```bash
cp .env.example .env
```

5. Generate application key
```bash
php artisan key:generate
```

6. Configure your database in `.env` file
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wajenzi
DB_USERNAME=root
DB_PASSWORD=
```

7. Run database migrations
```bash
php artisan migrate
```

8. Build frontend assets
```bash
npm run dev
```

9. Start the development server
```bash
php artisan serve
```

## Development

### Compile assets for development
```bash
npm run dev
```

### Watch files for changes
```bash
npm run watch
```

### Compile assets for production
```bash
npm run prod
```

## Technologies Used

- **Backend**: Laravel 8.x
- **Frontend**: Vue.js, jQuery
- **Database**: MySQL
- **CSS Framework**: Bootstrap 4
- **Build Tool**: Laravel Mix
- **Authentication**: Laravel Sanctum

## License

This project is proprietary software. All rights reserved.
