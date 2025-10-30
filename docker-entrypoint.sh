#!/bin/bash
set -e

# Wait for MariaDB
until nc -z mariadb 3306; do
  echo "Waiting for database..."
  sleep 2
done

# Run migrations and fixtures
php bin/console doctrine:database:drop --force || true
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction

exec "$@"