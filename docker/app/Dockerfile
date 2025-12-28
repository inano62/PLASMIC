#FROM php:8.3-fpm-alpine
#
#RUN apk add --no-cache \
#    bash git vim wget curl tzdata \
#    icu-dev oniguruma-dev \
#    libpng-dev libjpeg-turbo-dev freetype-dev \
#    libzip-dev libxml2-dev postgresql-dev \
#    linux-headers \
#    $PHPIZE_DEPS
#
## Laravel 必須拡張をインストール
#RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
# && docker-php-ext-install -j"$(nproc)" \
#    gd pdo pdo_mysql mysqli intl zip bcmath opcache dom
#
## 👇 composer を公式イメージからコピー
#COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
#
## 👇 apk 経由で入った PHP83 を削除（もし残っていれば）
#RUN apk del composer php83 php83-common || true
#
## 👇 PHPの実行ファイルを composer が確実に使えるように
#RUN ln -sf /usr/local/bin/php /usr/bin/php
#
#WORKDIR /var/www
#FROM php:8.3-fpm-alpine AS php
#
#RUN apk add --no-cache \
#    bash git curl tzdata \
#    icu-dev oniguruma-dev \
#    libpng-dev libjpeg-turbo-dev freetype-dev \
#    libzip-dev libxml2-dev postgresql-dev \
#    linux-headers \
#    $PHPIZE_DEPS
#
#RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
# && docker-php-ext-install -j"$(nproc)" \
#    gd pdo pdo_mysql mysqli intl zip bcmath opcache dom
#
#COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
#
#WORKDIR /var/www
FROM php:8.3-fpm

RUN set -eux; \
  if command -v apk >/dev/null 2>&1; then \
    echo "### alpine detected"; \
    apk add --no-cache \
      bash git curl tzdata \
      icu-dev oniguruma-dev \
      libpng-dev libjpeg-turbo-dev freetype-dev \
      libzip-dev libxml2-dev postgresql-dev \
      linux-headers \
      $PHPIZE_DEPS; \
  elif command -v apt-get >/dev/null 2>&1; then \
    echo "### debian detected"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      bash git curl tzdata \
      libicu-dev libonig-dev \
      libpng-dev libjpeg-dev libfreetype6-dev \
      libzip-dev libxml2-dev libpq-dev \
      $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/*; \
  else \
    echo "No supported package manager found"; exit 1; \
  fi; \
  docker-php-ext-configure gd --with-freetype --with-jpeg; \
  docker-php-ext-install -j"$(nproc)" \
    gd pdo pdo_mysql mysqli intl zip bcmath opcache dom

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www
