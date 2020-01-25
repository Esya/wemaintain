#! /bin/bash
set -e

echo 'Build and start docker containers'
docker-compose build && docker-compose up -d

echo 'Install dependencies using composer in php-fpm container'
docker-compose exec php-fpm composer install

echo 'Create database schema and populate tables'
docker-compose exec php-fpm bin/console doctrine:migration:migrate --no-interaction

green=`tput setaf 2`
echo  "Application is ready on ${green}http://localhost:8000"