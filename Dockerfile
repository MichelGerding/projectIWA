FROM debian:bullseye-slim

RUN apt update

# install php
# install php 8.2 and extensions
RUN apt install wget lsb-release apt-transport-https ca-certificates software-properties-common -y
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
RUN sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'

RUN apt update
RUN apt install php8.2 php-mysql php-curl php-xml git zip unzip -y

ENV COMPOSER_ALLOW_SUPERUSER=1

# install composer
RUN wget -O composer-setup.php https://getcomposer.org/installer
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN composer self-update

# install node and npm
RUN apt install curl -y
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
RUN apt install nodejs -y

COPY . /app
WORKDIR /app

RUN npm install
RUN composer i

EXPOSE 8000

CMD "./start.sh"

