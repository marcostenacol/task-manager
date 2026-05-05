#!/usr/bin/env bash
source $(dirname "$0")/_vars.sh
options=(`docker container ls --filter "label=php" --format "{{.Names}}"`)

echo -e $RED_COLOR"Running database migrations..."$RESET_COLOR
docker compose -f ./docker-compose.yml exec php-fpm php artisan migrate --force
