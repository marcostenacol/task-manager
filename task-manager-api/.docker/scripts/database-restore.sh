#!/usr/bin/env bash
source $(dirname "$0")/_vars.sh

while getopts f:d:u: flag
do
    case "${flag}" in
        f) filename=${OPTARG};;
        d) database=${OPTARG};;
        u) username=${OPTARG};;
    esac
done

echo -e $RED_COLOR"Creating Postgis Extension..."$RESET_COLOR
 docker compose -f ./docker-compose.yml exec pgsql psql -U "$username" -d "$database" -c "CREATE EXTENSION IF NOT EXISTS postgis"

echo -e $RED_COLOR"Restoring Database backup..."$RESET_COLOR
docker compose -f ./docker-compose.yml exec pgsql psql -U "$username" -d "$database" -f "/home/backups/$filename"
