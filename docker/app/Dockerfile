FROM php:8.3-fpm-alpine

# OS & build deps（★ linux-headers を追加）
RUN apk add --no-cache \
    bash git vim wget curl tzdata \
    icu-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libzip-dev libxml2-dev postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" gd pdo pdo_mysql mysqli intl zip bcmath opcache
cd .\frontend\
$env:LK_API_KEY="devkey"
$env:LK_API_SECRET="6d1680de114c4ac0908936c8de20c4905896dbb2e2634087a87bb5ed6df986cb4"

# 参加者ごとに異なる identity を使う（alice / bob 例）
$tokAlice = (node .\scripts\mint-token.mjs demo alice).Trim()
$tokBob   = (node .\scripts\mint-token.mjs demo bob).Trim()

# サーバーで有効性チェック（両方とも "success" ならOK）
curl.exe -G --http1.1 --data-urlencode ("access_token="+$tokAlice) http://127.0.0.1:7880/rtc/validate
curl.exe -G --http1.1 --data-urlencode ("access_token="+$tokBob)   http://127.0.0.1:7880/rtc/validate

# 2つのウィンドウで開く（クエリはそれぞれのトークン）
Start-Process ("http://localhost:5176/?token=" + [uri]::EscapeDataString($tokAlice))
Start-Process ("http://localhost:5176/?token=" + [uri]::EscapeDataString($tokBob))

# xdebug（必要なら）
RUN pecl install xdebug \
 && docker-php-ext-enable xdebug

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
