FROM node:latest as builder

# Set the working directory
WORKDIR /usr/src

# Copy package.json & lock file
COPY resources/app/package*.json .
# COPY resources/app/package-lock.json .

# Install dependencies
RUN npm install

# Copy source to working directory (/usr/src/app)
COPY ./resources/app ./
COPY ./resources/surveys/coi.json /usr/surveys/coi.json

# build the app
ENV BUILD_ENV=docker
RUN npm run build

FROM jward3/php:8.0-apache

LABEL maintainer="TJ Ward" \
    io.openshift.tags="laravel-epam:v1" \
    io.k8s.description="A system to manage the Expert Panel Application process." \
    io.openshift.expose-services="8080:http,8443:https" \
    io.k8s.display-name="epam version 1" \
    io.openshift.tags="php,apache"

# ENV XDG_CONFIG_HOME=/srv/app

USER root

WORKDIR /srv/app

COPY ./composer.lock ./composer.json /srv/app/
COPY ./database/seeders ./database/seeders
COPY ./database/factories ./database/factories

RUN composer install \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --no-dev \
        --no-suggest \
        --prefer-dist

COPY .docker/php/conf.d/* $PHP_INI_DIR/conf.d/

COPY .docker/start.sh /usr/local/bin/start

COPY . /srv/app

COPY --from=builder /usr/src/dist ./public
COPY --from=builder /usr/src/dist/index.html ./resources/views/app.blade.php

RUN chgrp -R 0 /srv/app \
    && chmod -R g+w /srv/app \
    && chmod g+x /srv/app/.openshift/deploy.sh \
    && chmod g+x /usr/local/bin/start
    # && pecl install xdebug-2.9.5 \
    # && docker-php-ext-enable xdebug \

RUN php artisan storage:link

USER 1001

CMD ["/usr/local/bin/start"]
