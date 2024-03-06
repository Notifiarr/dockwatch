# syntax=docker/dockerfile:1

FROM ghcr.io/linuxserver/baseimage-alpine-nginx:3.18

# set version label
ARG BUILD_DATE
ARG VERSION
ARG NGINX_VERSION
LABEL build_version="Linuxserver.io version:- ${VERSION} Build-date:- ${BUILD_DATE}"
LABEL maintainer="aptalca"

# install packages
RUN \
  if [ -z ${NGINX_VERSION+x} ]; then \
    NGINX_VERSION=$(curl -sL "http://dl-cdn.alpinelinux.org/alpine/v3.18/main/x86_64/APKINDEX.tar.gz" | tar -xz -C /tmp \
    && awk '/^P:nginx$/,/V:/' /tmp/APKINDEX | sed -n 2p | sed 's/^V://'); \
  fi && \

  apk add --no-cache --repository=http://dl-cdn.alpinelinux.org/alpine/edge/community \
    php82-pecl-mcrypt && \
  echo "**** configure php-fpm to pass env vars ****" && \
  sed -E -i 's/^;?clear_env ?=.*$/clear_env = no/g' /etc/php82/php-fpm.d/www.conf && \
  grep -qxF 'clear_env = no' /etc/php82/php-fpm.d/www.conf || echo 'clear_env = no' >> /etc/php82/php-fpm.d/www.conf && \
  echo "env[PATH] = /usr/local/bin:/usr/bin:/bin" >> /etc/php82/php-fpm.conf

# install sockets
RUN apk add --no-cache \
  php82-sockets

# add regctl for container digest checks
ARG TARGETARCH
ARG REGCTL_VERSION=v0.5.6
RUN curl -sSf -L -o /usr/local/bin/regctl "https://github.com/regclient/regclient/releases/download/${REGCTL_VERSION}/regctl-linux-${TARGETARCH}" \
  && chmod +x /usr/local/bin/regctl

# permissions
ARG INSTALL_PACKAGES=docker gzip
RUN apk add --update ${INSTALL_PACKAGES} && \
  addgroup -g 281 unraiddocker && \
  usermod -aG unraiddocker abc

# healthchecks
HEALTHCHECK --interval=60s --timeout=30s --start-period=180s --start-interval=10s --retries=5 \
  CMD curl -f http://localhost/health.html > /dev/null || exit 1

# add local files
COPY root/ /

ARG COMMIT=unknown
ARG COMMITS=0
ARG BRANCH=unknown
ARG COMMIT_MSG=unknown
RUN echo -e "\n//-- DOCKERFILE DEFINES"                        >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_BUILD_DATE', '${BUILD_DATE}');" >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_COMMIT', '${COMMIT}');"         >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_COMMITS', '${COMMITS}');"       >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_BRANCH', '${BRANCH}');"         >> /app/www/public/includes/constants.php \
    && echo "define('DOCKWATCH_COMMIT_MSG', <<< END_COMMITMSG" >> /app/www/public/includes/constants.php \
    && echo -e "${COMMIT_MSG}\nEND_COMMITMSG\n);"              >> /app/www/public/includes/constants.php \
    && echo "//-- END DOCKERFILE DEFINES"                      >> /app/www/public/includes/constants.php

# ports and volumes
EXPOSE 80 443

VOLUME /config