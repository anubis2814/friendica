ARG VARIANT="8.0-apache-bullseye"
FROM mcr.microsoft.com/vscode/devcontainers/php:${VARIANT}

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -y;

# Install MariaDB client
RUN apt-get install -y mariadb-client

# Base packages
RUN apt install -y vim software-properties-common sudo nano gnupg2

RUN apt-get install -y \
        libpng-dev \
        libjpeg62-turbo-dev \
        libtool \
        libmagick++-dev \
        libmemcached-dev \
        libgraphicsmagick1-dev \
        libfreetype6-dev \
        libwebp-dev \
        librsvg2-2 \
        libzip-dev \
        libldap2-dev \
        libgmp-dev \
        libmagickcore-6.q16-6-extra \
	&& docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
	&& docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        exif \
        zip \
        opcache \
        ctype \
        pcntl \
        ldap \
        gmp \
        intl

RUN apt-get clean -y && rm -rf /var/lib/apt/lists/*

