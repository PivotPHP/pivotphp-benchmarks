#!/bin/bash
set -e

# Initialize database if needed
if [ ! -f /app/.db-initialized ]; then
    echo "First run - initializing database..."
    /app/database/init.sh && touch /app/.db-initialized
fi

# Execute the command
exec "$@"