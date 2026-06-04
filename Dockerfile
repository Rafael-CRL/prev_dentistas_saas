FROM php:8.1-apache

# Instala extensões necessárias
RUN apt-get update && apt-get install -y libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mysqli intl

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Altera o DocumentRoot para a pasta public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
