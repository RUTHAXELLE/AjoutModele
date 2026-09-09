FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql \
    && a2enmod rewrite

COPY . /var/www/html/

# Render fournit le port via $PORT ; Apache doit écouter dessus.
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
    && sed -i 's/:80/:${PORT}/' /etc/apache2/sites-enabled/000-default.conf

ENV PORT=10000
EXPOSE 10000

CMD ["apache2-foreground"]
