# syntax=docker/dockerfile:1
FROM ghcr.io/linuxserver/baseimage-alpine-nginx:3.23

# Version labels
ARG BUILD_DATE
ARG VERSION
ARG COMMIT=unknown
ARG COMMITS=0
ARG BRANCH=unknown
ARG COMMIT_MSG=unknown

# https://github.com/Notifiarr/dockwatch/pull/59 - for future reference
ENV ATTACHED_DEVICES_PERMS="/var/run/docker.sock"

# Add regctl for container digest checks
ARG TARGETARCH
ARG REGCTL_VERSION=v0.5.6
RUN curl -sSf -L -o /usr/local/bin/regctl "https://github.com/regclient/regclient/releases/download/${REGCTL_VERSION}/regctl-linux-${TARGETARCH}" \
  && chmod +x /usr/local/bin/regctl

# Add yq for YAML processing
ARG TARGETARCH
ARG YQ_VERSION=v4.46.1
RUN curl -sSf -L -o /usr/local/bin/yq "https://github.com/mikefarah/yq/releases/download/${YQ_VERSION}/yq_linux_${TARGETARCH}" \
  && chmod +x /usr/local/bin/yq

# Add composer for PHP package management
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install system packages
ARG PHP_VERSION=85
ARG INSTALL_PACKAGES="docker docker-cli-compose memcached expect php${PHP_VERSION}-sockets php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-pecl-memcached php${PHP_VERSION}-tokenizer php${PHP_VERSION}-dom php${PHP_VERSION}-mbstring"
RUN apk add --repository=https://dl-cdn.alpinelinux.org/alpine/v3.23/main --no-cache --update ${INSTALL_PACKAGES}

# Add user for unraid docker
RUN addgroup -g 281 unraiddocker && \
  usermod -aG unraiddocker abc

# Image specific changes for Dockwatch
COPY /docker/branding /etc/s6-overlay/s6-rc.d/init-adduser/branding

RUN \
  echo "**** customize base lsio image ****" && \
  # Apply branding branch and hash
  sed -i "s@\${BRANCH}@${BRANCH}@g" /etc/s6-overlay/s6-rc.d/init-adduser/branding && \
  sed -i "s@\${COMMIT}@${COMMIT:0:7}@g" /etc/s6-overlay/s6-rc.d/init-adduser/branding && \
  # Migrations not needed because we ship with our own static nginx config
  rm -r /etc/s6-overlay/s6-rc.d/init-migrations && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-migrations && \
  rm /etc/s6-overlay/s6-rc.d/init-adduser/dependencies.d/init-migrations && \
  # Read above.
  rm -r /etc/s6-overlay/s6-rc.d/init-nginx && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-nginx && \
  rm -r /etc/s6-overlay/s6-rc.d/init-samples && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-samples && \
  rm -r /etc/s6-overlay/s6-rc.d/init-version-checks && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-version-checks && \
  rm /etc/s6-overlay/s6-rc.d/init-nginx-end/dependencies.d/init-version-checks && \
  # Overwritten by root/etc/php**/conf.d/99-dockwatch.ini
  rm -r /etc/s6-overlay/s6-rc.d/init-php && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-php && \
  # We are running on port 80 only
  rm -r /etc/s6-overlay/s6-rc.d/init-keygen && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-keygen && \
  rm /etc/s6-overlay/s6-rc.d/init-permissions/dependencies.d/init-keygen && \
  # Moved to /config/logs. Before: /config/log*
  rm -r /etc/s6-overlay/s6-rc.d/init-folders && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-folders && \
  # Disable custom files
  rm -r /etc/s6-overlay/s6-rc.d/init-custom-files && \
  rm /etc/s6-overlay/s6-rc.d/user/contents.d/init-custom-files && \
  rm /etc/s6-overlay/s6-rc.d/init-services/dependencies.d/init-custom-files

ARG PHP_FPM_CONF="/etc/php${PHP_VERSION}/php-fpm.conf"
RUN \
  echo "**** update config paths ****" && \
  # Don't allow include of additional php-fpm conf files
  sed -i '\|include=/config/php/\*.conf|d' "${PHP_FPM_CONF}" && \
  # Update php-fpm log paths to /config/logs
  sed -i 's|error_log = /config/log/php/error.log|error_log = /config/logs/php/error.log|g' "${PHP_FPM_CONF}" && \
  # Update logrotate paths to /config/logs
  sed -i 's|/config/log/php/\*.log|/config/logs/php/*.log|' /etc/logrotate.d/nginx && \
  sed -i 's|/config/log/nginx/\*.log|/config/logs/nginx/*.log|' /etc/logrotate.d/php-fpm && \
  sed -i 's|/config/log/logrotate.status|/config/logs/logrotate.status|g' /etc/periodic/daily/logrotate

# Pass docker env vars to php-fpm
ARG PHP_WWW_CONF="/etc/php${PHP_VERSION}/php-fpm.d/www.conf"
RUN \
  echo "**** configure php-fpm to pass env vars ****" && \
  sed -E -i 's/^;?clear_env ?=.*$/clear_env = no/g' "${PHP_WWW_CONF}" && \
  grep -qxF 'clear_env = no' "${PHP_WWW_CONF}" || echo 'clear_env = no' >> "${PHP_WWW_CONF}" && \
  echo "env[PATH] = /usr/local/bin:/usr/bin:/bin" >> "${PHP_WWW_CONF}"

# Healthcheck
HEALTHCHECK --interval=60s --timeout=30s --start-period=180s --start-interval=10s --retries=5 \
  CMD curl -f http://localhost/health > /dev/null || exit 1

# Copy root files
COPY root/ /

# Copy composer files and install dependencies
COPY composer.json composer.lock* /app/www/
WORKDIR /app/www
RUN composer install --no-dev --optimize-autoloader

RUN echo -e "\n//-- DOCKERFILE DEFINES"                        >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_BUILD_DATE', '${BUILD_DATE}');" >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_COMMIT', '${COMMIT}');"         >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_COMMITS', '${COMMITS}');"       >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_BRANCH', '${BRANCH}');"         >> /app/www/public/includes/constants.php \
    && echo "//-- END DOCKERFILE DEFINES"                      >> /app/www/public/includes/constants.php

# Set docker config path
ENV DOCKER_CONFIG=/config/.docker

# Expose only HTTP and set config volume
EXPOSE 80
VOLUME /config
