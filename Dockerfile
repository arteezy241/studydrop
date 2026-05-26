FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    libapache2-mod-php8.1 \
    && rm -rf /var/lib/apt/lists/*

# Completely remove default site
RUN rm -f /etc/apache2/sites-enabled/000-default.conf
RUN rm -f /var/www/html/index.html

RUN a2enmod rewrite

# Copy files
COPY . /var/www/html/

# Set up virtual host
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex index.php\n\
    <Directory /var/www/html>\n\
        Options -Indexes\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-enabled/000-default.conf

RUN mkdir -p /var/www/html/uploads \
    && chmod 777 /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]