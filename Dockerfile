# syntax=docker/dockerfile:1

FROM php:8.4-apache

# Enable Apache rewrite (needed for Laravel routes)
RUN a2enmod rewrite

# Install common PHP extensions Laravel often uses (kept lean)
# - pdo_mysql is included in case you later point to an external DB
# - intl/zip/mbstring are common for many Laravel packages
RUN apt-get update && apt-get install -y --no-install-recommends \
        libicu-dev zlib1g-dev libzip-dev unzip git \
    && docker-php-ext-install \
        intl zip mbstring pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Set the working directory
WORKDIR /var/www/html

# Use a vhost that points DocumentRoot to /public and enables rewrites
COPY docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# (Optional) If you prefer building the image with your code baked in, uncomment:
# COPY . /var/www/html
# RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Apache listens on 80 inside the container
EXPOSE 80
