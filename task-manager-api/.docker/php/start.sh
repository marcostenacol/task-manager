#!/bin/bash
php artisan storage:link
php-fpm &
sudo /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
