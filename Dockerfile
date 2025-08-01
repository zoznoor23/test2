FROM php:8.1-apache


RUN apt-get update && apt-get install -y util-linux


COPY index.php /var/www/html/
COPY php.ini /usr/local/etc/php/


COPY flag.php /tmp/flag.php


RUN chmod 444 /tmp/flag.php 

EXPOSE 80
