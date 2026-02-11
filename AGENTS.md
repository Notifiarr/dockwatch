# Project Overview

This project, Dockwatch, is a PHP-based web application that provides a user interface for managing Docker containers. It allows users to monitor container status, manage updates, and receive notifications. The application runs in a Docker container and is designed to be self-hosted.

## Architecture

The application follows a traditional web server architecture:

- **Web Server:** Nginx is used as the web server to serve the PHP application.
- **Application Logic:** The core application logic is written in PHP. It uses the `cboden/ratchet` library for WebSocket communication and `phiki/phiki` for templating.
- **Backend Services:** The application relies on several backend services and tools:
    - **Docker:** The application interacts with the Docker daemon to manage containers.
    - **`regctl`:** This tool is used for container digest checks.
    - **`yq`:** This tool is used for YAML processing.
    - **Memcached:** This is used for caching.
- **Cron Jobs:** A series of cron jobs are used to automate tasks such as pulling images, checking container health, and collecting stats.

## Building and Running

The project is designed to be built and run using Docker.

### Building the Image

To build the Docker image, run the following command from the project root:

```bash
sh docker/build.sh
```

This will build the image and tag it as `ghcr.io/notifiarr/dockwatch:local`.

### Running the Application

To run the application, you can use the provided `docker-compose.yml` file:

```bash
docker-compose -f docker/compose.yml up -d
```

This will start the Dockwatch application on port 10000.

## Development Conventions

- **Dependency Management:** PHP dependencies are managed with Composer.
- **Docker:** The application is containerized using Docker. The `Dockerfile` is located in the `docker` directory.
- **Development Environment:** The `docker-compose.yml` file in the `docker` directory is configured for development, with the local application code mounted into the container.
- **Cron Jobs:** Scheduled tasks are managed with cron jobs, defined in `root/etc/crontabs/abc`.
- **Database:** The application uses a SQLite database, and migrations are handled by the application itself.
