# syntax=docker/dockerfile:1

FROM ghcr.io/linuxserver/baseimage-alpine-nginx:3.19

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
  apk add --no-cache \
    memcached \
    nginx==${NGINX_VERSION} \
    nginx-mod-http-brotli==${NGINX_VERSION} \
    nginx-mod-http-dav-ext==${NGINX_VERSION} \
    nginx-mod-http-echo==${NGINX_VERSION} \
    nginx-mod-http-fancyindex==${NGINX_VERSION} \
    nginx-mod-http-geoip==${NGINX_VERSION} \
    nginx-mod-http-geoip2==${NGINX_VERSION} \
    nginx-mod-http-headers-more==${NGINX_VERSION} \
    nginx-mod-http-image-filter==${NGINX_VERSION} \
    nginx-mod-http-perl==${NGINX_VERSION} \
    nginx-mod-http-redis2==${NGINX_VERSION} \
    nginx-mod-http-set-misc==${NGINX_VERSION} \
    nginx-mod-http-upload-progress==${NGINX_VERSION} \
    nginx-mod-http-xslt-filter==${NGINX_VERSION} \
    nginx-mod-mail==${NGINX_VERSION} \
    nginx-mod-rtmp==${NGINX_VERSION} \
    nginx-mod-stream==${NGINX_VERSION} \
    nginx-mod-stream-geoip==${NGINX_VERSION} \
    nginx-mod-stream-geoip2==${NGINX_VERSION} \
    nginx-vim==${NGINX_VERSION} \
    php82-bcmath \
    php82-bz2 \
    php82-dom \
    php82-exif \
    php82-ftp \
    php82-gd \
    php82-gmp \
    php82-imap \
    php82-intl \
    php82-ldap \
    php82-mysqli \
    php82-mysqlnd \
    php82-opcache \
    php82-pdo_mysql \
    php82-pdo_odbc \
    php82-pdo_pgsql \
    php82-pdo_sqlite \
    php82-pear \
    php82-pecl-apcu \
    php82-pecl-memcached \
    php82-pecl-redis \
    php82-pgsql \
    php82-posix \
    php82-soap \
    php82-sockets \
    php82-sodium \
    php82-sqlite3 \
    php82-tokenizer \
    php82-xmlreader \
    php82-xsl && \
  apk add --no-cache --repository=http://dl-cdn.alpinelinux.org/alpine/edge/community \
    php82-pecl-mcrypt && \
  echo "**** configure php-fpm to pass env vars ****" && \
  sed -E -i 's/^;?clear_env ?=.*$/clear_env = no/g' /etc/php82/php-fpm.d/www.conf && \
  grep -qxF 'clear_env = no' /etc/php82/php-fpm.d/www.conf || echo 'clear_env = no' >> /etc/php82/php-fpm.d/www.conf && \
  echo "env[PATH] = /usr/local/bin:/usr/bin:/bin" >> /etc/php82/php-fpm.conf

# add regctl for container digest checks
ARG TARGETARCH
ARG REGCTL_VERSION=v0.5.6
RUN curl -sSf -L -o /usr/local/bin/regctl "https://github.com/regclient/regclient/releases/download/${REGCTL_VERSION}/regctl-linux-${TARGETARCH}" \
  && chmod +x /usr/local/bin/regctl

ARG INSTALL_PACKAGES=docker gzip
RUN apk add --update ${INSTALL_PACKAGES} && \
  addgroup -g 281 unraiddocker && \
  usermod -aG unraiddocker abc

HEALTHCHECK --interval=5m --timeout=3s \
  CMD curl -f http://localhost/ || exit 1

# add local files
COPY root/ /

# ports and volumes
EXPOSE 80 443

VOLUME /config