FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    php8.1-mbstring \
    libapache2-mod-php8.1 \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN rm -f /etc/apache2/sites-enabled/000-default.conf
RUN rm -f /var/www/html/index.html

COPY . /var/www/html/

RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex index.php\n\
    <Directory /var/www/html>\n\
        Options -Indexes\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-enabled/000-default.conf

RUN mkdir -p /var/uploads \
    && chmod 755 /var/uploads \
    && chown -R www-data:www-data /var/uploads

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Block direct access to uploads via .htaccess
RUN echo 'Options -Indexes\n\
<FilesMatch ".*">\n\
    Order allow,deny\n\
    Deny from all\n\
</FilesMatch>' > /var/www/html/uploads/.htaccess

EXPOSE 80
CMD ["apache2ctl", "-D", "FOREGROUND"]