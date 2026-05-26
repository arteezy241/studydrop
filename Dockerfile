FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Create uploads directory
RUN mkdir -p /var/www/html/uploads && \
    chmod 755 /var/www/html/uploads

# Apache config — allow .htaccess
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/studydrop.conf && \
    a2enconf studydrop

EXPOSE 80