FROM nginx:1.27-alpine

# Remove the image's localhost virtual host so every API request reaches Laravel.
RUN rm /etc/nginx/conf.d/default.conf

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d /etc/nginx/conf.d
COPY backend/public /var/www/html/public

EXPOSE 80
