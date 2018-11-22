<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

# Your APP

## Feature



## Requirements

1. php72
   extension:
   * bcmath
   * redis
   * swoole
   * xdebug
   * mysqlnd
   * mysqlnd_mysqli
2. composer
3. supervisor
4. node v10 + yarn
5. mysql 5.7
6. redis
7. nginx 
8. crond

## Installation

### precondition

Start `MySQL`,`redis`,`crond`,`supervisord` and `nginx` services first 

```bash
# Create database and grant user's Privilege. Reminder: change name as you like
mysql -uroot -p
mysql> CREATE DATABASE IF NOT EXISTS user default charset utf8mb4 COLLATE utf8mb4_unicode_ci;
mysql> grant all on user.* to tester@localhost identified by 'password';
mysql> flush privileges;
```

### initialize project

```bash
# cd to the root dir
cd /path/to/project

# install dependency packages 
composer install --prefer-dist --no-scripts -o

# cp env file and change some configurations according to the actual situation. Config db & redis at least
cp .env.example .env
./artisan largen:env

# use database for queue
./artisan largen:env QUEUE_CONNECTION database

# use redis for cache
./artisan largen:env CACHE_DRIVER redis

# user redis for session
./artisan largen:env SESSION_DRIVER redis

# make a secret key for crypt
./artisan key:generate 

# before executing next line, please make sure you had created the database first 
./artisan migrate

# install passport
./artisan passport:install

# make sure following dirs have RW permissions
chmod -R 777 storage bootstrap/cache
```



### Deploy at Server

#### schedule tasks

start schedule tasks via `crond` service（Don't forget to replace `/path/to/project/artisan`）

```bash
* * * * * php /path/to/project/artisan schedule:run >> /dev/null 2>&1
```

#### queue deamon

start queue via `supervisor`service（Don't forget to replace`/path/to/project/artisan`）

create a file use following lines and save to `/etc/supervisord.d/`

```bash
[program:user-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/artisan queue:work --queue=emails,sms,default --sleep=10 --tries=3
autostart=true
autorestart=true
numprocs=8
user=root

stderr_logfile=/var/log/supervisor/user-worker-error.log
stdout_logfile=/var/log/supervisor/user-worker.log
```

> Because queue:work is a STR Programs, don't forget to restart those
>
> ```bash
> ./artisan queue:restart
> ```
>
>

#### nginx

config for `nginx` （Don't forget to replace `root /path/to/project/public` and `server_name user.test`）

```bash
server {
        listen 80; 
        listen [::]:80;
        root /path/to/project/public;
        server_name user.test;
        index index.html index.php;


        add_header X-Frame-Options "SAMEORIGIN";
        add_header X-XSS-Protection "1; mode=block";
        add_header X-Content-Type-Options "nosniff";


        charset utf-8;

        location / { 
                try_files $uri/ $uri /index.php$is_args$query_string;
        }   


        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        error_page 404 /index.php;

        location ~ \.php$ {
                try_files $uri =404;
                fastcgi_pass 127.0.0.1:9000;
                fastcgi_index index.php;
                fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
                include fastcgi_params;
        }   

        location ~ .*\.(gif|jpg|jpeg|png|bmp|swf|ico|svg)$
        {
            expires      max;
        }

        location ~ .*\.(js|css)?$
        {
            expires      max;
        }

        location ~ .*\.(ttf|woff|woff2|eot)?$                                                                                                                                                        
        {
            expires      max;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }   
}

```

change env 

```bash
# change APP_URL
./artisan largen:env APP_URL "http://user.test"
```



You need only do those steps above one time. From now on, you need only execute the following lines after pushing code.   

```bash
composer install
./artisan queue:restart
./artisan migrate
```



### Develop in your local machine

Please do those steps above first, and fresh your local database as you need, If you do that, you should rebuild your data

```bash
# fresh all tables 
./artisan migrate:fresh --seed

# install passport and make a client
./artisan passport:install
./artisan passport:client --client --name="Server Client"

# one line command
./artisan migrate:fresh --seed && \
./artisan passport:install && \
./artisan passport:client --client --name="Server Client" && \
./artisan my:service_setting:mail 3
```



## Getting Started
