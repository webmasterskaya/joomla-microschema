FROM joomla:5.4.0-php8.2-apache

RUN apt-get update && \
    apt-get install -y --no-install-recommends $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apt-get purge -y --auto-remove $PHPIZE_DEPS && \
    rm -rf /var/lib/apt/lists/*

RUN echo "xdebug.mode=develop,debug" > /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.max_nesting_level=8000" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.log=/var/log/xdebug.log" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.log_level=7" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.extended_info=0" >> /usr/local/etc/php/conf.d/99-xdebug.ini && \
    echo "xdebug.discover_client_host=0" >> /usr/local/etc/php/conf.d/99-xdebug.ini

COPY docker/joomla-entrypoint.sh /usr/local/bin/microschema-joomla-entrypoint

RUN chmod +x /usr/local/bin/microschema-joomla-entrypoint

ENTRYPOINT ["microschema-joomla-entrypoint"]

CMD ["apache2-foreground"]
