#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Wait for MySQL to be reachable before continuing.
# `depends_on` only guarantees the container is up, not the service inside.
if [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
  for i in {1..30}; do
    if php -r "exit(@fsockopen('${DB_HOST}', ${DB_PORT:-3306}) ? 0 : 1);"; then
      echo "MySQL is reachable."
      break
    fi
    echo "  ...still waiting ($i)"
    sleep 2
  done
fi

# Hand off to whatever command the container was told to run (php-fpm, supervisord, etc.)
exec "$@"