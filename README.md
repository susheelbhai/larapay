# Larapay | Multiple Payment Gateway for Laravel Application


## Installation

### Laravel
Require this package in your composer.json and update composer. This will download the package and install Larapay package.


    composer require susheelbhai/larapay

## Configuration


### Vendor Publish

Publish all the required files using the following command 

```
php artisan vendor:publish --tag="larapay" 

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
php artisan vendor:publish --tag="larapay"
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
<input name="amount" type="hidden" value="{{ 100*1.18 }}"/>
<input name="gst_percentage" type="hidden" value="{{ $gst_percentage ?? 18}}"/>
<input name="button" type="submit" title="Pay Now" />
</form>
```

## .env variables
UNABLE_PAYMENT_RESPONSE -> 0 or 1
    0 it will wait for webhook to respond
    1 it will directly respond if callback responce received

PAYMENT_TEMPLATE -> blade or react
    bassed on the starter kit you are using

## Setting up webhook
#### Razorpay 
Rooturl/webhook/2
#### Phonepe 
webhook url will be sent with payment request autometically, no action needed.
#### Cashfree 
Rooturl/webhook/7
#### PayU 
Rooturl/webhook/8

## Allow callback url work without csrf token

Add the callback_url in validateCsrfTokens except so that it can work even if this page is redirected by payment gateway

bootstrap/app.php
```
->withMiddleware(function (Middleware $middleware) {
        //------
        $middleware->validateCsrfTokens(except: [
            'callback_url',
        ]);
        //------
    })
```

## Refund Payment

Add the callback_url in validateCsrfTokens except so that it can work even if this page is redirected by payment gateway

```
use Susheelbhai\Larapay\Http\Controllers\PaymentController;

$Larapay = new PaymentController();
$response = $Larapay->refundPayment($payment_id, $amount, $speed);
```

Refend data is stored in $response and you can use it according to your need.
| variable      | value     | description   |
|------------   |--------   |---------|
| $payment_id   | int       | this is the primary key of payment table  |
| $amount       | ```full```  or custom amount   | 'full' for full refund and the custom value for partial refund |
| $speed        | ```normal``` or ```instant```   | 'normal' for standard refund and 'instant' for instant refund |



### License

This Multi Auth Package is developed by susheelbhai for personal use software licensed under the [MIT license](http://opensource.org/licenses/MIT)
