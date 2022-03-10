# FROM node:latest as builder

# # Set the working directory
# WORKDIR /usr/src

# # Copy package.json & lock file
# COPY resources/app/package*.json .
# # COPY resources/app/package-lock.json .

# # Install dependencies
# RUN npm install

# # Copy source to working directory (/usr/src/app)
# COPY ./resources/app ./
# COPY ./resources/surveys/coi.json /usr/surveys/coi.json

# # build the app
# ENV BUILD_ENV=docker
# ENV NODE_OPTIONS=--openssl-legacy-provider
# RUN npm run build

# Final stage
FROM jward3/php:8.0-apache

# Set a bunch of labels for k8s and Openshift.
LABEL maintainer="TJ Ward" \
    io.openshift.tags="laravel-epam:v1" \
    io.k8s.description="A system to manage the Expert Panel Application process." \
    io.openshift.expose-services="8080:http,8443:https" \
    io.k8s.display-name="epam version 1" \
    io.openshift.tags="php,apache"

# Use root user to set things up.
USER root

WORKDIR /srv/app

# Copy/Run the stuff that doesn't change that much first.
# This speeds up builds.

# Kafka client stuff
# RUN apt-get install -yqq librdkafka-dev \
#     && pecl install rdkafka-5.0.0 \
#     && apt-get install -y --no-install-recommends openssl \
#     && sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf \
#     && sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf\
#     && rm -rf /var/lib/apt/lists/*

# PHP configs (including loading phprdkafka)
COPY .docker/php/conf.d/* $PHP_INI_DIR/conf.d/

COPY .docker/start.sh /usr/local/bin/start

COPY ./composer.lock ./composer.json /srv/app/
COPY ./database/seeders ./database/seeders
COPY ./database/factories ./database/factories

RUN composer install \
        # --optimize-autoloader \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --no-dev \
        --no-suggest \
        --prefer-dist

# We need this directory because Tinker depends on psysh
# and psysh doesn't provide a good way to change the directory
# to the project directory.
RUN mkdir -p /.config/psysh \
    && chgrp -R 0 /.config/psysh \
    && chmod g+wx /.config/psysh

# Copy the source code.
COPY . /srv/app

# Copy over the build artifacts from the node build container.
# COPY --from=builder /usr/src/dist ./public
# COPY --from=builder /usr/src/dist/index.html ./resources/views/app.blade.php

# Change ownership of files so non-root user can use them.
RUN chgrp -R 0 /srv/app \
    && chmod -R g+w /srv/app \
    && chmod g+x /srv/app/.openshift/deploy.sh \
    && chmod g+x /usr/local/bin/start
    # && pecl install xdebug-2.9.5 \
    # && docker-php-ext-enable xdebug \

# Link the uploads storage directory to public for downloads.
RUN php artisan storage:link

# Switch to non-root user for security (and to make OpenShift happy).
USER 1001

# Run the start command.
CMD ["/usr/local/bin/start"]
