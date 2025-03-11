# Base image
FROM php:8.2-fpm AS base

# Installation des dépendances communes
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_mysql \
    intl \
    zip

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY SportGest/ /var/www/html/

WORKDIR /var/www/html

RUN composer install

# Configuration PHP pour le développement
FROM base AS dev
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Installation du Symfony CLI
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.deb.sh' | bash \
    && apt-get update \
    && apt-get install -y symfony-cli

RUN symfony server:ca:install

# Copie et configuration du script de démarrage
COPY start-symfony.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/start-symfony.sh

CMD ["/usr/local/bin/start-symfony.sh"]