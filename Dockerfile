FROM php:8.5.11-apache

# Mailpit ships a sendmail replacement that hands PHP mail() to the mailpit container
COPY --from=axllent/mailpit:v1.31.2 /mailpit /usr/local/bin/mailpit
RUN echo 'sendmail_path = "/usr/local/bin/mailpit sendmail -S mailpit:1025"' > /usr/local/etc/php/php.ini
RUN docker-php-ext-install calendar
RUN docker-php-ext-configure calendar
RUN docker-php-ext-install mysqli
RUN docker-php-ext-enable mysqli

# The app itself, so the image runs on its own. docker-compose.yaml still mounts the working copy over it for development.
COPY . /var/www/html/
