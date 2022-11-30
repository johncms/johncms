FROM ubuntu:22.04

ARG NODE_VERSION=18

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && apt-get install -y gnupg nano curl ca-certificates zip unzip git cron nginx supervisor sqlite3 libcap2-bin libpng-dev python2 \
    && mkdir -p ~/.gnupg \
    && chmod 600 ~/.gnupg \
    && echo "disable-ipv6" >> ~/.gnupg/dirmngr.conf \
    && echo "keyserver hkp://keyserver.ubuntu.com:80" >> ~/.gnupg/dirmngr.conf \
    && gpg --recv-key 0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c \
    && gpg --export 0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c > /usr/share/keyrings/ppa_ondrej_php.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.1-cli php8.1-fpm php8.1-dev \
       php8.1-sqlite3 php8.1-gd php8.1-imagick php8.1-curl \
       php8.1-imap php8.1-mysql php8.1-mbstring php8.1-redis \
       php8.1-xml php8.1-zip php8.1-bcmath php8.1-soap php8.1-intl \
    && php -r "readfile('https://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -sLS https://deb.nodesource.com/setup_$NODE_VERSION.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm \
    && apt-get update \
    && apt-get install -y mysql-client \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.1

COPY cron/crontab /var/spool/cron/crontabs/root
RUN chmod 0600 /var/spool/cron/crontabs/root

COPY start /usr/local/bin/start
COPY nginx /etc/nginx
COPY supervisor /etc/supervisor
COPY php/php.ini /etc/php/8.1/cli/conf.d/99-php.ini
COPY php/php.ini /etc/php/8.1/fpm/conf.d/99-php.ini

RUN chmod +x /usr/local/bin/start

# USER www-data
EXPOSE 8000

ENTRYPOINT ["start"]
