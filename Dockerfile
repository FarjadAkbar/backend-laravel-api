# Set the base image
FROM php:7.4-fpm-alpine

# Set the working directory
WORKDIR /var/www/html

# Install the dependencies
RUN apk update \
    && apk add --no-cache git curl openssh-client \
    && docker-php-ext-install pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the rest of the application to the container
COPY . .

# Install the dependencies
RUN composer install

# Expose the port
EXPOSE 8000

# Start the application
CMD ["php-fpm"]
