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
FROM ghcr.io/clingen-data-model/cgwi-php-11.7-8.2:v1.1.6

# Set a bunch of labels for k8s and Openshift.
LABEL maintainer="UNC ClinGen Infrastructure Team" \
    io.openshift.tags="laravel-gpm:v1" \
    io.k8s.description="A system to manage the ClinGen Personnel information" \
    io.openshift.expose-services="8080:http,8443:https" \
    io.k8s.display-name="gpm version 1" \
    io.openshift.tags="php"

# Use root user to set things up.
USER root

WORKDIR /srv/app

# Copy/Run the stuff that doesn't change that much first.
# This speeds up builds.

COPY ./composer.lock ./composer.json /srv/app/

RUN composer install \
        # --optimize-autoloader \
        --no-interaction \
        --no-plugins \
        --no-scripts \
        --no-dev \
        --prefer-dist

# Copy the source code.
COPY . /srv/app

# Copy over the build artifacts from the node build container.
# COPY --from=builder /usr/src/dist ./public
# COPY --from=builder /usr/src/dist/index.html ./resources/views/app.blade.php

# This is here mostly because Tinker depends on psysh, which
# wants config to be in $HOME/.config/psysh, so $HOME needs to be writeable
ENV HOME=/srv/app

# Change ownership of files so non-root user can use them.
RUN chgrp -R 0 /srv/app \
    && chmod -R g+w /srv/app \
    && chmod a+x /srv/app/.openshift/deploy.sh \
    && chmod a+x /srv/app/.docker/entrypoint.sh

# Link the uploads storage directory to public for downloads.
RUN php artisan storage:link

# Switch to non-root user for security (and to make OpenShift happy).
USER www-data:0

CMD ["/bin/bash"]
