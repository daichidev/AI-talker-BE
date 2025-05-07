*** Environment *** 

Ubuntu Server 24.04

1. Install Nginx

$ sudo apt update
$ sudo apt install nginx

2. Install php

$ sudo apt install php8.3-fpm php-mysql

3. Clone Project to /var/www/html

4. Install Composer

5. Modify nginx conf

$ sudo nano /etc/nginx/sites-available/default

server {
    listen 80;
    server_name 13.218.53.64;

    root /var/www/html/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # or your PHP version
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}

6. Restart nginx server

$ sudo nginx -t && sudo systemctl reload nginx

7. permission

$ sudo chown -R www-data:www-data /var/www/html/storage
$ sudo chown -R www-data:www-data /var/www/html/bootstrap/cache
$ sudo chmod -R 775 /var/www/html/storage
$ sudo chmod -R 775 /var/www/html/bootstrap/cache

8. Install Sql

$ sudo apt update
$ sudo apt install php8.3-mysql

$ sudo systemctl restart php8.3-fpm
$ sudo systemctl restart nginx

$ sudo mysql
CREATE DATABASE my_ai;
CREATE USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'My@italker';
GRANT ALL ON my_ai.* TO 'root'@'localhost';
php artisan config:clear
php artisan migrate