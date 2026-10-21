FROM php:8.3-apache

# Cai dat extension ket noi MySQL
RUN docker-php-ext-install mysqli pdo_mysql

# Thu muc lam viec cua website
WORKDIR /var/www/html

# Sao chep ma nguon PHP vao container
COPY app/ /var/www/html/

# Cong HTTP cua Apache
EXPOSE 80
