#!/bin/sh

# Create a directory for cron jobs
mkdir -p /var/www/dj-connect-back/cron.d

# Add your cron job here
echo "* * * * * cd /var/www/dj-connect-back && php artisan schedule:run --no-ansi >> /var/www/dj-connect-back/storage/logs/cron.log 2>&1" > /var/www/dj-connect-back/cron.d/laravel-schedule

# Temporary cron job for testing
echo "* * * * * echo \"Cron is working at \$(date)\" >> /var/www/dj-connect-back/storage/logs/cron_test.log 2>&1" >> /var/www/dj-connect-back/cron.d/laravel-schedule

# Give execute permission to the cron job file
chmod 0644 /var/www/dj-connect-back/cron.d/laravel-schedule

# Ensure correct permissions for logs directory and files
chown -R www-data:www-data /var/www/dj-connect-back/storage/logs
chmod -R 775 /var/www/dj-connect-back/storage/logs

echo "Starting Supercronic..."

# Start supercronic in the foreground (for Docker)
supercronic /var/www/dj-connect-back/cron.d/laravel-schedule