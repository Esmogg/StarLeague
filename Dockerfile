FROM php:8.2-apache

# Instala mysqli y pdo_mysql para asegurar compatibilidad total con tu código
RUN docker-php-ext-install mysqli pdo_mysql


# mod_rewrite (URLs limpias /api/...) y mod_headers (cabeceras de seguridad)
RUN a2enmod rewrite headers

# Permite que el .htaccess del proyecto aplique reglas
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
      > /etc/apache2/conf-available/starleague.conf \
 && a2enconf starleague

# Solo se copia lo que debe ser público. NO se copian database/, scripts/, apache/, .env ni .git
COPY .htaccess  /var/www/html/.htaccess
COPY index.html /var/www/html/
COPY html   /var/www/html/html
COPY css    /var/www/html/css
COPY js     /var/www/html/js
COPY images /var/www/html/images
COPY php    /var/www/html/php

# Ocultar versión de Apache y PHP en las respuestas
RUN printf "ServerTokens Prod\nServerSignature Off\n" > /etc/apache2/conf-available/seguridad.conf \
 && a2enconf seguridad \
 && echo "expose_php=Off" > /usr/local/etc/php/conf.d/seguridad.ini
