FROM php:8.2-fpm

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git unzip zip cron vim pkg-config \
        default-mysql-client \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libxml2-dev libxslt1-dev libicu-dev libzip-dev zlib1g-dev \
        libonig-dev procps\
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring gd intl soap xsl zip bcmath opcache sockets \
    ; \
    pecl install redis && docker-php-ext-enable redis; \
    rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP defaults (your request)
RUN printf "memory_limit = 2G\nmax_execution_time = 300\n" \
    > /usr/local/etc/php/conf.d/zz-dev.ini

# Init script to auto-provision Magento
COPY docker/php/startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

WORKDIR /var/www/html
