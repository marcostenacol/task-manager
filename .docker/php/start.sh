#!/bin/bash
composer install
php-fpm &
sudo /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
