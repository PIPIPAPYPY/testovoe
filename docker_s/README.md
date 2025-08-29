# docker-laravel-postgres-nginx
Simple docker-compose for Laravel, with postgresql, nginx and php-fpm

# Contents
+ [Pre-requisites](#pre-requisites)
+ [Installation](#installation)
+ [Usage](#usage)
+ [Images](#images)
+ [Laravel](#laravel)
+ [Source files](#source)
+ [Troubleshooting](#troubleshooting)
+ [Deployment](#deployment)


# Pre-requisites
* Docker running on the host machine.
* Docker compose running on the host machine.
* composer installed on the host machine.
* Basic knowledge of Docker.
 

# Installation
+ To get started, the following steps needs to be taken:
+ Clone the repo.
+ `cd application`
+ `cp .env.example .env` to use laravel env config file (configuration needed, be sure about DB_HOST as db container name)
+ `composer install`
+ `cd ..` to back the project directory.
+ `cp .env.example .env` to use docker env config file (configuration needed)
+ Run `docker-compose up -d` to start the containers.
+ Run `docker exec -it app-php-fpm php artisan key:generate`
+ Visit http://localhost to see your Laravel application.
+ Try to connect 127.0.0.1:5432 to access Postgres
+ After starting, note that one directory and one file will be created with name *postgres* and file *data*, this files are Database archives

# Usage
+ `docker-compose up -d` to start all containers
+ `docker-compose down` to stop all containers
+ If you need to restart after modifying *docker-compose.yml* restart with `docker-compose down` and `docker-compose up -d`
+ If you need to run laravel command on running app `docker exec -it app-php-fpm <command>`
+ or some connand in deattach `docker exec -itd app-php-fpm <command>`

# Images
+ postgres:12.1-alpine
+ nginx:alpine
+ php74-fpm:latest

# Laravel
+ 6.14.0 version

# Source

## Into **sourcefiles** directory, exists others directories: **php-fpm** and **nginx**:

### php-fpm: Extensions PHP and PHP.INI
+ Dockerfile: php7.4-pgsql php7.4-gd
+ php-ini-overrides.ini

### nginx: nginx.conf
+ file conf nginx

### volumes:
- nginx folder
- php-ini-overrides.ini
- data(postgres)

### multiple servers:
- create file conf of nginx in nginx directory you should use default.conf as exemple 
- restart containers: `docker-compose down` and `docker-composer up -d`


# Troubleshooting

## If you need to restart after modifying *Dockerfile* and have Troubleshooting:
+ Verify all containers running: `docker ps -a`
+ Stop all containers and remove: `docker stop $(docker ps -a -q)` and `docker rm $(docker ps -a -q)`
+ Try to start again `docker-compose up -d`

## Be sure about laravel access rights on multi user servers and run commands from user started container
+ `sudo chown -R $USER:www-data storage`
+ `sudo chown -R $USER:www-data bootstrap/cache`
+ `chmod -R 775 storage`
+ `chmod -R 775 bootstrap/cache`


# Deployment

## Server requirements
* Docker running on the host machine.
* Docker compose running on the host machine.
* Composer installed on the host machine.
* Nginx installsed on the host machine.
* Git installsed on the host machine.
* Basic knowledge of Docker and Nginx.

## Connecting to server
`ssh server_user@server_ip_address`

## Creating new user
+ `sudo adduser usermane` for creating new user (follow command instructions)
+ `sudo usermod -aG sudo username` to add user to sudo group
+ `sudo su - username` to switch account to added user

### Docker
+ `sudo groupadd docker` adding docker group
+ `sudo usermod -aG docker username` adding user to docker group
+ `sudo newgrp docker`
+ Log out and log back in so that your group membership is re-evaluated (or simply restart server).

## Project set up
+ `sudo su - username` to log as project user
+ `mkdir logs` make logs folder for nginx
+ `ssh-keygen` to generate keys for git deploying
+ `cat .ssh/id_rsa.pub` copy result and add to git project as deploy key
+ `git clone git@gitlab.techvice.org:iakhramovich/laravel-docker.git project_folder_name` to get project
+ `cd project_folder_name`
+ And follow [installaion](#installation) instruction to set up project.
+ `exit` to return to your root user session

## Nginx
+ `cd /etc/nginx/sites-available/`
+ `sudo nano config_file_name.conf` paste default nginx conf (edit by your project) and save
```
server {
        server_name dns_name;

        root /home/username/project_folder_name/application/public;
        index index.php;

        access_log /home/username/logs/access.log;
        error_log /home/username/logs/error.log;

        location / {
                proxy_pass http://127.0.0.1:DOCKER_NGINX_PORT;
                proxy_http_version 1.1;
                proxy_set_header Upgrade $http_upgrade;
                proxy_set_header Connection 'upgrade';
                proxy_set_header Host $host;
                proxy_cache_bypass $http_upgrade;
                gzip on;
                gzip_min_length 1000;
                gzip_types text/plain application/xml application/x-javascript text/css text/javascript application/javascript;
        }

        client_max_body_size 99m;
}
```
+ `sudo ln -s /etc/nginx/sites-available/config_file_name.conf /etc/nginx/sites-enabled/config_file_name.conf`
+ `sudo service nginx restart`
+ And go to your site DNS address to see 'Welcome' message
