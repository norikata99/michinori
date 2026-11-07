# syntax=docker/dockerfile:1
# php-fpm のイメージ。ステージ：base（共通）→ dev（開発用）
# 本番用ステージは別のPRで追加する。バージョンの固定方針は docs/adr/0002-versions.md

# ---- base：開発と本番で共通 ----
FROM php:8.5-fpm-trixie AS base

# Laravel と MySQL に必要な拡張だけを入れる
RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- dev：ソースは bind mount、Xdebug 入り ----
FROM base AS dev

RUN apt-get update \
    && apt-get install -y --no-install-recommends git \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY docker/php/conf.d/xdebug.ini "$PHP_INI_DIR/conf.d/zz-xdebug.ini"
