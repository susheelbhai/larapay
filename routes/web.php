<?php

use Illuminate\Support\Facades\Route;
use Susheelbhai\Larapay\Http\Controllers\PaymentController;

Route::get('pay', [PaymentController::class, 'index']);