# ClinGen Expert Panel Application Management

A system for managing and streamlining the Application process for ClinGen Expert panels.
For lack of a better name we'll call it the EPAM.

## Installation
### Using DockerCompose
`docker-compose up -d` will bring up all containers with the app available at http://localhost:8080.

## Architecture

The EPAM is a Laravel application using MySQL for persistance and Redis as a cache and pub-sub for laravel's queue.

The app, scheduler, and queue containers all use the same image. The entry point script (found in `.docker/start.sh`) determines the behavior of each container based on the `CONTAINER_ROLE` environment variable.

