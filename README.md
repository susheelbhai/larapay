# Larapay | Multiple Payment Gateway for Laravel Application


## Installation

### Laravel
Require this package in your composer.json and update composer. This will download the package and install Larapay package.


    composer require susheelbhai/larapay

## Configuration


### Add credientials in .env file  
```
php artisan larapay:initial_settings

```

### Vendor Publish

Publish all the required files using the following command 

  ```
  php artisan vendor:publish --tag="larapay" --force 
  ```  


### Migrate database

Migrate  databse tables and seed with the following commands

  ```
  php artisan migrate
  ```

### License

This Multi Auth Package is developed by susheelbhai for personal use software licensed under the [MIT license](http://opensource.org/licenses/MIT)
