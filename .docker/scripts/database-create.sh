#!/usr/bin/env bash
source $(dirname "$0")/_vars.sh

while getopts d:u: flag
do
    case "${flag}" in
        d) database=${OPTARG};;
        u) username=${OPTARG};;
    esac
done

echo -e $RED_COLOR"Creating Database $database..."$RESET_COLOR
docker compose -f ./docker-compose.yml exec pgsql createdb -U "$username" "$database"
