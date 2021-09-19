git fetch --all && git reset --hard origin/master && git pull origin master
rm -rf composer.lock composer.phar
wget https://getcomposer.org/download/latest-stable/composer.phar
php composer.phar update -vvv
php artisan v2board:update
php artisan horizon:publish
php artisan config:cache
php composer.phar dump
