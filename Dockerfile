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

# Copy project
COPY . /var/www/html/

# Remove default index
RUN rm -f /var/www/html/index.html

# Uploads folder
RUN mkdir -p /var/www/html/uploads && \
    chmod 755 /var/www/html/uploads && \
    chown -R www-data:www-data /var/www/html

# Apache config
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]