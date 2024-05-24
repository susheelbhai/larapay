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

## Usees

### Add a form inside your view file
<form action="{{ route('pay') }}" method="post">
@csrf
<x-form.element.input1 name="action_url" type="hidden" value="{{ route('dashboard') }}" />
<x-form.element.input1 name="redirect_url" type="hidden" value="" />
<x-form.element.input1 name="gateway" type="hidden" value="{{ config('payment.gateway_id') }}" />
<x-form.element.input1 name="name" type="hidden" value="{{ Auth::guard('web')->user()->name }}" />
<x-form.element.input1 name="email" type="hidden" value="{{ Auth::guard('web')->user()->email }}" />
<x-form.element.input1 name="phone" type="hidden" value="{{ Auth::guard('web')->user()->phone }}" />
<x-form.element.input1 name="amount" type="hidden" value="{{ 100*1.18 }}"/>
<x-form.element.input1 name="gst_percentage" type="hidden" value="{{ $gst_percentage }}"/>
<x-form.element.button1 name="button" type="submit" title="Pay Now" />
</form>

### License

This Multi Auth Package is developed by susheelbhai for personal use software licensed under the [MIT license](http://opensource.org/licenses/MIT)
