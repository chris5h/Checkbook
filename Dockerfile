FROM php:8.2-fpm-alpine

# Install nginx and mysqli dependencies
RUN apk add --no-cache nginx

# Install mysqli PHP extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Copy nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Copy app files
COPY . /var/www/html/

# Remove files that shouldn't be in the image
RUN rm -f /var/www/html/docker-compose.yaml \
          /var/www/html/.env \
          /var/www/html/.env.example \
          /var/www/html/Dockerfile

# Correct ownership
RUN chown -R www-data:www-data /var/www/html

# Startup script: run php-fpm and nginx together
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
