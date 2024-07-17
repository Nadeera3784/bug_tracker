FROM php:7.4.33-fpm

WORKDIR /var/www

RUN apt-get update
RUN apt-get install -y libbz2-dev curl

                 
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD ./docker/php/conf.d/custom.ini /usr/local/etc/php/conf.d/php.ini

RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www

RUN chown -R www:www /var/www/

USER www

EXPOSE 9000


#CMD ["php-fpm"]