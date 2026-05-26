FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Install Apache + PHP
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-mysql \
    php8.1-pdo \
    libapache2-mod-php8.1 \
    && rm -rf /var/lib/apt/lists/*

# Enable rewrite
RUN a2enmod rewrite

# Remove ALL default Apache pages
RUN rm -rf /var/www/html/* && \
    rm -f /etc/apache2/sites-enabled/000-default.conf

# Copy project
COPY . /var/www/html/

# Create virtual host config
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-enabled/studydrop.conf

# Uploads + permissions
RUN mkdir -p /var/www/html/uploads && \
    chmod 755 /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html

# Suppress warning
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]