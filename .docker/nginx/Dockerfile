FROM nginx:mainline-alpine AS nginx_base

RUN mkdir /etc/nginx/templates

FROM nginx_base AS nginx_dev
COPY default.conf.dev.template /etc/nginx/templates/default.conf.template

FROM nginx_base AS nginx_prod
COPY default.conf.template /etc/nginx/templates