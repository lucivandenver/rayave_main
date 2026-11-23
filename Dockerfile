FROM php:8.3-apache

# Install system dependencies and PHP extensions required by the app & Composer packages
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        mysqli \
        pdo \
        pdo_mysql \
        xml \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Configure Apache to serve both / and /flowershop-main
RUN printf '%s\n' \
    'ServerName localhost' \
    '<VirtualHost *:80>' \
    '    ServerAdmin webmaster@localhost' \
    '    DocumentRoot /var/www/html' \
    '    Alias /flowershop-main /var/www/html' \
    '    <Directory /var/www/html>' \
    '        Options Indexes FollowSymLinks' \
    '        AllowOverride All' \
    '        Require all granted' \
    '    </Directory>' \
    '    ErrorLog ${APACHE_LOG_DIR}/error.log' \
    '    CustomLog ${APACHE_LOG_DIR}/access.log combined' \
    '</VirtualHost>' \
    > /etc/apache2/sites-available/000-default.conf

# Configure PHP settings
RUN printf '%s\n' \
    'variables_order = "EGPCS"' \
    'upload_max_filesize = 20M' \
    'post_max_size = 25M' \
    'memory_limit = 256M' \
    'date.timezone = "Asia/Manila"' \
    > /usr/local/etc/php/conf.d/flowershop.ini

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Install Composer dependencies if vendor is missing
RUN if [ ! -f vendor/autoload.php ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction; \
    fi \
    && mkdir -p /var/www/html/storage/logs /var/www/html/storage/support_attachments /var/www/html/storage/custom_designs /var/www/html/assets/images/products \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/assets/images \
    && chmod -R 775 /var/www/html/storage /var/www/html/assets/images

EXPOSE 80
