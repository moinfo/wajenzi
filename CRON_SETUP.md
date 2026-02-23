# Cron Job Setup - Laravel Scheduler

This guide explains how to set up the Laravel task scheduler on your production server.

## Scheduled Commands

| Command | Schedule | Description |
|---------|----------|-------------|
| `db-archive:archive` | Daily at 01:00 | Archive old database records |
| `billing:send-reminders` | Daily at 00:00 | Send billing reminders |
| `followup:send-reminders` | Daily at 08:00 | Send sales follow-up reminders |
| `invoices:remind-accountants` | Daily at 08:30 | Send invoice due reminders |
| `sms:send-birthdays` | Daily at 08:00 | Send birthday SMS to employees |

## Setup Steps

### 1. SSH into your server

```bash
ssh user@your-server-ip
```

### 2. Open the crontab editor

```bash
crontab -e
```

### 3. Add this single line

```
* * * * * cd /path/to/wajenzi && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/wajenzi` with your actual project path, for example:

```
* * * * * cd /var/www/wajenzi && php artisan schedule:run >> /dev/null 2>&1
```

If you use a specific PHP version, use the full path:

```
* * * * * cd /var/www/wajenzi && /usr/bin/php8.2 artisan schedule:run >> /dev/null 2>&1
```

Save and exit (press `Ctrl+X`, then `Y`, then `Enter` if using nano).

### 4. Verify the cron is saved

```bash
crontab -l
```

You should see your line listed in the output.

### 5. Verify scheduled commands

```bash
cd /var/www/wajenzi
php artisan schedule:list
```

This will display all commands with their next scheduled run time.

### 6. Test a command manually (optional)

```bash
php artisan sms:send-birthdays
```

## How It Works

- The cron runs `schedule:run` **every minute**
- Laravel checks which commands are due and executes only those
- You only need **one cron entry** for all scheduled commands
- Adding new commands to `app/Console/Kernel.php` requires no cron changes

## Troubleshooting

### Commands not running?

1. **Check timezone**: Ensure the server timezone matches your expected schedule
   ```bash
   php artisan tinker --execute="echo config('app.timezone');"
   ```

2. **Check cron is running**:
   ```bash
   sudo service cron status
   ```

3. **Check logs**: Enable logging to debug
   ```
   * * * * * cd /var/www/wajenzi && php artisan schedule:run >> /var/www/wajenzi/storage/logs/cron.log 2>&1
   ```

4. **Permissions**: Ensure the cron user has read/write access to the project directory and storage folder.
