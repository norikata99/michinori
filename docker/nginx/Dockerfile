# syntax=docker/dockerfile:1
# nginx のイメージ。ステージ：dev（開発用）
# 本番用ステージ（public/ とビルド済みの CSS・JS をコピー）は別のPRで追加する

FROM nginx:1.30-alpine3.24 AS dev

# 起動時に envsubst で ${PHP_FPM_HOST} などを置き換えて /etc/nginx/conf.d/ に出力される
COPY docker/nginx/templates/ /etc/nginx/templates/
