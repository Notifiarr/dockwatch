# syntax=docker/dockerfile:1
FROM ghcr.io/linuxserver/baseimage-alpine-nginx:3.22

# Version labels
ARG BUILD_DATE
ARG VERSION
ARG COMMIT=unknown
ARG COMMITS=0
ARG BRANCH=unknown
ARG COMMIT_MSG=unknown

# https://github.com/Notifiarr/dockwatch/pull/59 - for future reference
ENV ATTACHED_DEVICES_PERMS="/var/run/docker.sock"

# Pass docker env vars to php-fpm
RUN \
  echo "**** configure php-fpm to pass env vars ****" && \
  sed -E -i 's/^;?clear_env ?=.*$/clear_env = no/g' /etc/php84/php-fpm.d/www.conf && \
  grep -qxF 'clear_env = no' /etc/php84/php-fpm.d/www.conf || echo 'clear_env = no' >> /etc/php84/php-fpm.d/www.conf && \
  echo "env[PATH] = /usr/local/bin:/usr/bin:/bin" >> /etc/php84/php-fpm.conf

# Add composer for PHP package management
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

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

# Install packages and add user to unraiddocker group
ARG INSTALL_PACKAGES="docker docker-cli-compose memcached expect parallel php84-sockets php84-sqlite3 php84-pecl-memcached php84-pecl-mcrypt php84-tokenizer php84-dom php84-mbstring"
RUN apk add --repository=http://dl-cdn.alpinelinux.org/alpine/edge/testing --no-cache --update ${INSTALL_PACKAGES} && \
  addgroup -g 281 unraiddocker && \
  usermod -aG unraiddocker abc

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