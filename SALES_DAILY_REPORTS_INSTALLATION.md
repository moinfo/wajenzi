# Sales Daily Reports System - Installation Guide

## Prerequisites

- PHP >= 8.0
- MySQL >= 5.7 or MariaDB >= 10.3
- Composer
- Git
- Web server (Apache/Nginx)
- Existing Laravel application with RingleSoft Process Approval package

## Installation Steps

### 1. Pull Latest Changes

```bash
# Navigate to your project directory
cd /path/to/your/wajenzi

# Pull the latest changes from repository
git pull origin main
```

### 2. Install Dependencies

```bash
# Install/update PHP dependencies
composer install --optimize-autoloader --no-dev

# Clear composer cache if needed
composer clear-cache
```

### 3. Run Database Migrations

```bash
# Run all new migrations
php artisan migrate

# If you encounter issues, try:
php artisan migrate:fresh --seed  # WARNING: This will drop all tables!
```

### 4. Run Database Seeders

Run the seeders in this specific order:

```bash
# 1. Create menu items for Sales Daily Reports
php artisan db:seed --class=SalesDailyReportMenuSeeder

# 2. Create menu items for Lead Management
php artisan db:seed --class=LeadManagementMenuSeeder

# 3. Setup approval workflow for Sales Daily Reports
php artisan db:seed --class=SalesDailyReportApprovalSeeder
```

### 5. Clear All Caches

```bash
# Clear application cache
php artisan cache:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear compiled classes
php artisan clear-compiled

# Optimize the application
php artisan optimize
```

### 6. Set Proper Permissions

```bash
# Set proper ownership (adjust user:group as needed)
sudo chown -R www-data:www-data storage bootstrap/cache

# Set proper permissions
sudo chmod -R 775 storage bootstrap/cache
```

### 7. Configure Permissions (In Application)

1. Login as administrator
2. Navigate to **Settings > Roles**
3. Edit the appropriate roles and assign these permissions:
   - `View Sales`
   - `Add Sales`
   - `Edit Sales`
   - `Delete Sales`
   - `View Leads`
   - `Add Leads`
   - `Edit Leads`
   - `Delete Leads`

### 8. Configure Approval Workflow

1. Navigate to **Settings > Approval Document Types**
2. Verify "Sales Daily Report" document type exists (ID: 14)
3. Navigate to **Settings > Approval Levels**
4. Configure approval levels for Sales Daily Report:
   - Set appropriate user groups for each approval level
   - Define the order of approvals
   - Set actions (CHECK, APPROVE, etc.)

### 9. Verify Installation

#### Check Menu Items
1. Navigate to the main menu
2. Under **Projects** menu, verify you see:
   - Sales Daily Reports
   - Lead Management

#### Test Basic Functionality
```bash
# Test routes are accessible
curl -I https://yourdomain.com/sales_daily_reports
curl -I https://yourdomain.com/leads
```

#### Create Test Report
1. Navigate to **Projects > Sales Daily Reports**
2. Click "New Report"
3. Fill in the form and save
4. Verify all sections work properly

### 10. Production Optimizations

```bash
# Cache routes
php artisan route:cache

# Cache config
php artisan config:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

## Troubleshooting

### Migration Errors

If you get foreign key constraint errors:
```bash
# Disable foreign key checks temporarily
php artisan migrate --force
```

### Permission Errors

If users can't see menu items or access pages:
```bash
# Refresh permissions cache
php artisan permission:cache-reset
```

### Approval Workflow Issues

If approval notifications show wrong URLs:
1. Check that both routes exist in `routes/web.php`:
   - `/sales_daily_report/show/{id}`
   - `/sales_daily_report/{id}/{document_type_id}`

2. Clear route cache:
```bash
php artisan route:clear
php artisan route:cache
```

### Department Display Issues

If departments show as blank:
```bash
# Run the migration to fix department defaults
php artisan migrate --path=database/migrations/2025_07_29_100259_update_sales_daily_reports_remove_department_default.php
```

## Environment Configuration

Add these to your `.env` file if not already present:

```env
# Queue configuration for notifications
QUEUE_CONNECTION=database

# Cache configuration
CACHE_DRIVER=file
SESSION_DRIVER=file
```

## Nginx Configuration (if applicable)

Add to your server block:
```nginx
location ~ ^/(sales_daily_reports?|leads?) {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Apache Configuration (if applicable)

Ensure `.htaccess` file exists in public directory with:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## Post-Installation Checklist

- [ ] All migrations ran successfully
- [ ] Menu items appear under Projects menu
- [ ] Users with proper permissions can access the modules
- [ ] Can create a new Sales Daily Report
- [ ] Can create a new Lead
- [ ] Approval workflow triggers on report submission
- [ ] Email notifications are sent (if configured)
- [ ] Date pickers show dd/mm/yyyy format
- [ ] Department auto-selects for logged-in user
- [ ] All caches cleared and regenerated

## Security Considerations

1. Ensure only authorized users have access to Sales Daily Reports
2. Review and configure approval levels appropriately
3. Set up SSL/HTTPS for production
4. Configure proper CORS headers if needed
5. Enable Laravel's CSRF protection (already enabled by default)

## Support

For issues specific to:
- **Sales Daily Reports**: Check `/storage/logs/laravel.log`
- **Approval Workflow**: Check RingleSoft documentation
- **Database**: Check MySQL/MariaDB error logs

---

**Last Updated**: July 29, 2025
**Version**: 1.0.0