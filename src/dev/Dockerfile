FROM php:8.4-fpm

RUN apt-get update -y && \
    apt-get install -y \
        nginx \
        supervisor \
        libsqlite3-dev

RUN docker-php-ext-install pdo_sqlite

COPY .mild/dev/supervisord.conf /etc/supervisor/docker.conf
COPY .mild/dev/nginx.conf /etc/nginx/sites-enabled/default
COPY vendor/ /var/www/vendor

EXPOSE 8888

CMD [ "/usr/bin/supervisord", "-c", "/etc/supervisor/docker.conf" ]
