#!/bin/bash
php-fpm &
sudo /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
