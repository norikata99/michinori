# syntax=docker/dockerfile:1
# nginx と php-fpm のイメージを1つの Dockerfile で作る。ビルドは --target で選ぶ
#   php-dev / nginx-dev   ：ローカル開発用（ソースは bind mount）
#   php-prod / nginx-prod ：本番用（ソースとビルド済みの CSS・JS をイメージに入れる）
# ビルドコンテキストはリポジトリのルート。バージョンの固定方針は docs/adr/0002-versions.md
#
# 1つにまとめている理由：Laravel は @vite で public/build/manifest.json を PHP 側から読むため、
# ビルドした CSS・JS は nginx と php-fpm の両方のイメージに必要になる。
# Dockerfile を分けると Node のビルドを2か所に書くことになる。

# =========================================================
# php-base：開発と本番で共通（拡張だけ）
# =========================================================
FROM php:8.5-fpm-trixie AS php-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# =========================================================
# php-dev：ソースは bind mount、Xdebug・composer 入り
# =========================================================
FROM php-base AS php-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/conf.d/xdebug.ini "$PHP_INI_DIR/conf.d/zz-xdebug.ini"

# =========================================================
# vendor：本番用の PHP 依存（開発用パッケージは入れない）
# =========================================================
FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# composer.json と lock だけ先にコピーし、ソース変更でキャッシュが切れないようにする
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY src/ ./
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# =========================================================
# assets：CSS・JS のビルド（Node はこのステージだけで使い、本番イメージには入れない）
# =========================================================
FROM node:26-trixie-slim AS assets

WORKDIR /app

COPY src/package.json src/package-lock.json src/.npmrc ./
RUN npm ci --no-fund

# Tailwind は vendor/ 内の Blade（ページネーションなど）も読むため、
# vendor ステージのソースと vendor/ をまとめて渡す（node_modules は上で入れたものを使う）
COPY --from=vendor /var/www/html/ ./
RUN npm run build

# =========================================================
# php-prod：本番用 php-fpm
# =========================================================
FROM php-base AS php-prod

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/conf.d/opcache-prod.ini "$PHP_INI_DIR/conf.d/zz-opcache.ini"

COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

# 書き込みが必要なディレクトリだけ www-data に渡す
RUN chown -R www-data:www-data storage bootstrap/cache

# =========================================================
# nginx-dev：開発用（public/ は bind mount）
# =========================================================
FROM nginx:1.30-alpine3.24 AS nginx-dev

# 起動時に envsubst で ${PHP_FPM_HOST} を置き換えて /etc/nginx/conf.d/ に出力される
COPY docker/nginx/templates/ /etc/nginx/templates/

# =========================================================
# nginx-prod：本番用。public/ とビルド済みの CSS・JS をコピーする
# （忘れると CSS が当たらない。docs/design.md 6章）
# =========================================================
FROM nginx-dev AS nginx-prod

COPY src/public/ /var/www/html/public/
COPY --from=assets /app/public/build /var/www/html/public/build
