# largen

Laravel framework is too flexible, while has too simple file generators.

Since that, it leaves so many things/configs to developers to use the file generated by laravel, and our poor developers can not remember all of the points.

Now, I make more further: Generate files with templates and enable it automatically BTW

> You should use this package in new project. Because it will change some config/env/script files. **Strongly recommend that using git for checking what files will be changed.**

## Feature


## Requirements

1. Linux / MacOS 
2. php 7.1.3+
3. ruby
4. laravel 5.7.*

## Installation


Create an empty laravel project:

```bash
composer create-project --prefer-dist laravel/laravel blog 5.7.*
cd blog
composer require maimake/largen
./artisan largen:install
```

Now, you can setup some environments with following command

```bash
# Setup DB configurations first please
>  ./artisan largen:env

# Will create Admin User(admin@admin.com, password)
>  ./artisan migrate --seed

# Run server
>  ./artisan serve            <== Run server use default url http://localhost:8000
Or
>  ./artisan largen:vhost user.test     <== Generate a ngnix config file, and add item to /etc/hosts, If you have nginx already local machine
Or
>  ./artisan largen:env APP_URL http://user.test   <== Only set 'APP_URL',if you have already configured webserver manually

# Start HMR
>  yarn start

# Setup Directories for IDEA IDE (Please open project in IDE first.)
>  ./artisan largen:idea
```

One line install and setup

```bash
name=blog && \
composer create-project --prefer-dist laravel/laravel $name 5.7.* && \
cd $name && \
composer require maimake/largen && \
./artisan largen:install -q && \
./artisan largen:env && \
./artisan migrate --seed && \
./artisan largen:vhost && \
yarn start
```



# Memo

## Optimize For Production

optimize autoloader
```bash
composer install --optimize-autoloader --no-dev
```

optimize bootstrap 

```bash
./artisan optimize
```

use redis,  change your .env file

```bash
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

use cache 

```bash
# merge all config file to one , and env
php artisan config:cache 

# merge all routes to one function. Except closure-route
php artisan route:cache

# Compile all of the application's Blade templates
php artisan view:cache

```

clear optimize

```bash
./artisan optimize:clear
./artisan clear-compiled
./artisan config:clear
./artisan route:clear
./artisan view:clear
```

clear cache data

```bash
./artisan cache:clear
./artisan auth:clear-resets
./artisan queue:flush
```



## Maintain Mode

```bash
# maintain mode and will show with 503.blade.php
php artisan down --allow=127.0.0.1 --allow=192.168.0.0/16
```



# How to contribute?

1. fork from this [repository](https://github.com/maimake/largen)

2. clone your repository to a local path (ie. pakcages/largen)

3. create an empty laravel project

4. add following config to the composer.json，change the url where your repository locates. 

   ```bash
       "repositories": [
           { "type": "path", "url": "../packages/*" }
       ]
   ```

5. require the package. And then it will automatically create a  Symlinking from your repository path.

   ```bash
   composer require "maimake/largen":"dev-master"
   ```

6. change some code in path `vendor/maimake/largen`, and finally make a PR to me.



Here is one line command. Replace the path of  `packages` as you like: 

```bash
git clone git@github.com:maimake/largen.git packages/largen && \
name=Demo && \
composer create-project --prefer-dist laravel/laravel $name 5.7.* && \
cd $name && \
sed -i '' -e '/"type": "project"/s/$/\' -e '"repositories": [{ "type": "path", "url": "..\/packages\/*" }],/' composer.json && \
composer require "maimake/largen":"dev-master" 
```

and then you can do something with many commands in this package

```bash
./artisan largen:install -q && \
./artisan largen:env && \
./artisan migrate --seed && \
./artisan largen:vhost && \
yarn start
```





## License

[MIT](http://opensource.org/licenses/MIT)
