FROM php:8.3-cli
RUN apt-get update && apt-get install -y libzip-dev libpng-dev libjpeg62-turbo-dev libonig-dev && docker-php-ext-configure gd --with-jpeg && docker-php-ext-install pdo_mysql zip gd mbstring
WORKDIR /app
CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app"]
