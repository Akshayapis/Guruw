# Cashpe — PHP 8.2 + Apache, no MySQL required.
FROM php:8.2-apache-bookworm

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    PORT=80

RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libcurl4-openssl-dev libzip-dev libpng-dev libonig-dev libxml2-dev unzip \
 && docker-php-ext-install curl mbstring zip gd \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY start.sh /usr/local/bin/cashpe-start
RUN chmod +x /usr/local/bin/cashpe-start

WORKDIR /var/www/html
COPY . /var/www/html/
RUN mkdir -p /var/www/html/storage \
 && chown -R www-data:www-data /var/www/html/storage \
 && chmod -R 0775 /var/www/html/storage

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=8s --start-period=20s --retries=3 \
  CMD PORT="${PORT:-80}" php -r '$p=getenv("PORT")?:"80"; $c=@file_get_contents("http://127.0.0.1:".$p."/health.php"); exit($c!==false?0:1);'

CMD ["/usr/local/bin/cashpe-start"]
