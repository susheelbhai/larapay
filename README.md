# Larapay | Multiple Payment Gateway for Laravel Application


## Installation

### Laravel
Require this package in your composer.json and update composer. This will download the package and install Larapay package.


    composer require susheelbhai/larapay

## Configuration


### Vendor Publish

Publish all the required files using the following command 

```
php artisan vendor:publish --tag="larapay" --force 

```  

### Add credientials in .env file  
```
php artisan larapay:initial_settings

```

### Migrate database

Migrate  databse tables and seed with the following commands

```
php artisan migrate

```

## Complete step in single action
```
composer require susheelbhai/larapay
php artisan vendor:publish --tag="larapay" --force
php artisan larapay:initial_settings
php artisan migrate

```

## Uses

### Add a form inside your view file
```
<form action="{{ route('pay') }}" method="post">
@csrf
<input name="action_url" type="hidden" value="{{ route('dashboard') }}" />
<input name="redirect_url" type="hidden" value="" />
<input name="gateway" type="hidden" value="{{ config('payment.gateway_id') }}" />
<input name="name" type="hidden" value="{{ Auth::guard('web')->user()?->name }}" />
<input name="email" type="hidden" value="{{ Auth::guard('web')->user()?->email }}" />
<input name="phone" type="hidden" value="{{ Auth::guard('web')->user()?->phone }}" />
<input name="amount" type="hidden" value="{{ 100*1.18 }}"/>
<input name="gst_percentage" type="hidden" value="{{ $gst_percentage ?? 18}}"/>
<input name="button" type="submit" title="Pay Now" />
</form>
```

### License

This Multi Auth Package is developed by susheelbhai for personal use software licensed under the [MIT license](http://opensource.org/licenses/MIT)
