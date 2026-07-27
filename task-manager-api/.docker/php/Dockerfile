########################################
# Stage 1: build — toolchain completo, compila extensões e instala o vendor/
########################################
FROM php:8.3-fpm AS builder

WORKDIR /var/www/html

# Dependências de build (headers -dev, compilador, git/curl para composer)
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libmagickwand-dev \
    libgmp-dev \
    libxml2-dev \
    zlib1g-dev libicu-dev g++ \
    git \
    curl \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extensões PHP
RUN docker-php-ext-install exif pcntl zip mbstring bcmath sockets dom xml
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install gd pdo pdo_pgsql intl gmp

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala as dependências de produção em build-time (não mais no start.sh)
COPY . /var/www/html
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

########################################
# Stage 2: runtime — só o necessário para rodar PHP-FPM em produção
########################################
FROM php:8.3-fpm

WORKDIR /var/www/html

# Bibliotecas de runtime (sem os pacotes -dev/toolchain de build) + cron/supervisor/sudo
RUN apt-get update && apt-get install -y \
    libpng16-16 \
    libjpeg62-turbo \
    libfreetype6 \
    libzip5 \
    libpq5 \
    libonig5 \
    libxml2 \
    zlib1g \
    libicu76 \
    libgmp10 \
    locales \
    locales-all \
    cron \
    supervisor \
    sudo \
    procps \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extensões PHP compiladas + composer, copiados do stage de build
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer

# Add user for laravel application
ARG ID_USER_LINUX=1001
ARG ID_GROUP_LINUX=1001

RUN groupadd -g ${ID_GROUP_LINUX} www
RUN useradd -u ${ID_USER_LINUX} -ms /bin/bash -g www www
RUN chown -R www:www /var/www

RUN echo "www ALL=(ALL) NOPASSWD: /usr/bin/supervisord" >> /etc/sudoers

#88 Configuring locale to pt_BR.UTF-8
ENV LC_ALL pt_BR.UTF-8
ENV LANG pt_BR.UTF-8
ENV LANGUAGE pt_BR.UTF-8

# Create supervisor log file and change ownership
RUN touch /var/log/supervisor/supervisord.log
RUN chown www:www /var/log/supervisor/supervisord.log

# Create supervisor pid file and change ownership
RUN touch /var/run/supervisord.pid
RUN chown www:www /var/run/supervisord.pid

# Copy Supervisor configuration
COPY .docker/php/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
RUN chown www:www /etc/supervisor/supervisord.conf && chmod 644 /etc/supervisor/supervisord.conf

# Copy crontab file
COPY .docker/php/crontab /etc/cron.d/laravel-cron
RUN chmod 0644 /etc/cron.d/laravel-cron && crontab /etc/cron.d/laravel-cron && touch /var/log/cron.log

# Copy application (vendor/ já resolvido no stage de build; em produção o
# volume bind-mount do docker-compose sobrepõe este diretório com o código do
# host, que já contém vendor/ instalado a partir de uma execução anterior)
COPY --from=builder /var/www/html /var/www/html
RUN chown -R www:www /var/www/html

# Copy start script and make it executable
COPY .docker/php/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Change current user to www
USER www
RUN echo 'alias pa="php artisan "' >> ~/.bashrc

# Change current user to root
USER root
RUN echo 'alias pa="php artisan "' >> ~/.bashrc

# Change current user to www
USER www
# Expose port 9000 and start php-fpm server
EXPOSE 9000
# Start PHP-FPM, Cron, and Supervisor using the start script
CMD ["/usr/local/bin/start.sh"]
