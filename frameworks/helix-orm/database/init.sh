#!/bin/bash

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysql -h${DB_HOST} -u${DB_USERNAME} -p${DB_PASSWORD} -e "SELECT 1" > /dev/null 2>&1; do
    sleep 1
done

echo "MySQL is ready. Initializing database..."

# Run schema
mysql -h${DB_HOST} -u${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} < /app/database/schema.sql

echo "Database initialized successfully!"