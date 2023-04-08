<?php

use Illuminate\Support\Facades\Route;
use Susheelbhai\Larapay\Http\Controllers\PaymentController;

Route::get('pay', [PaymentController::class, 'form'])->name('payment_form');
Route::post('pay', [PaymentController::class, 'index']);
Route::post('callback_url', [PaymentController::class, 'paymentResponce'])->name('callback_url');