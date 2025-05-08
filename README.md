*** Environment *** 

Ubuntu Server 24.04

1. Install Nginx

$ sudo apt update

$ sudo apt upgrade -y

$ sudo apt install nginx

2. Install php

$ sudo add-apt-repository ppa:ondrej/php

$ sudo apt update

$ sudo apt install php8.2 php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-curl php8.2-mysql php8.2-zip php8.2-bcmath php8.2-intl -y

3. Clone Project to /var/www/html

4. Install Composer

$ cd ~

$ curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

$ HASH=`curl -sS https://composer.github.io/installer.sig`

$ php -r "if (hash_file('SHA384', '/tmp/composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

$ sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

5. Modify nginx conf

$ sudo apt install php8.2-fpm -y

$ sudo systemctl enable php8.2-fpm

$ sudo systemctl start php8.2-fpm

$ sudo nano /etc/nginx/sites-available/default

server {
    listen 80;
    server_name 43.207.78.212;

    root /var/www/html/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # or your PHP version
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

$ sudo chown -R www-data:www-data /var/www/html
$ sudo chown -R www-data:www-data /var/www/html/storage
$ sudo chown -R www-data:www-data /var/www/html/bootstrap/cache
$ sudo chmod -R 775 /var/www/html/storage
$ sudo chmod -R 775 /var/www/html/bootstrap/cache

8. Install Sql

$ sudo apt install mysql-server -y

$ sudo mysql

CREATE DATABASE my_ai;
CREATE USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'My@italker';
GRANT ALL ON my_ai.* TO 'root'@'localhost';
FLUSH PRIVILEGES;

$ sudo composer update

$ sudo cp .env.example .env

$ sudo php artisan key:generate

$ sudo php artisan config:clear

$ sudo php artisan migrate

$ sudo php artisan db:seed
