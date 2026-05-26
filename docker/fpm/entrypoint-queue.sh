#!/bin/sh
set -eu

cd /var/www/html

attempt=1
max_attempts="${QUEUE_BOOTSTRAP_MAX_ATTEMPTS:-60}"
sleep_seconds="${QUEUE_BOOTSTRAP_SLEEP_SECONDS:-2}"

while [ "$attempt" -le "$max_attempts" ]; do
    if php /queue-bootstrap-check.php; then
        exec php artisan queue:work --sleep=3 --tries=3 --timeout=90
    fi

    echo "Waiting for database-backed Laravel tables before starting queue worker... ($attempt/$max_attempts)"
    attempt=$((attempt + 1))
    sleep "$sleep_seconds"
done

echo "Queue worker timed out waiting for Laravel database tables." >&2
exit 1
