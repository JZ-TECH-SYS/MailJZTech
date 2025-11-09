FROM php:8.2-apache

# Instala mysql-client (fornece mysqldump), gzip e extensões PHP necessárias
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
	   mariadb-client \
	   gzip \
	&& docker-php-ext-install pdo pdo_mysql \
	&& rm -rf /var/lib/apt/lists/*

# (Opcional) Composer para installs dentro do container quando necessário
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 80

# Apache já inicia via apache2-foreground no docker-compose

