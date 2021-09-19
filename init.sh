rm -rf composer.phar
wget https://getcomposer.org/download/latest-stable/composer.phar
php composer.phar install -vvv
php artisan v2board:install
php artisan horizon:publish

