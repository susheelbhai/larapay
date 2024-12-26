<?php

use Illuminate\Support\Facades\Route;
use Susheelbhai\Larapay\Http\Controllers\PaymentController;


Route::middleware('web')->group(function () {
        Route::get('pay', [PaymentController::class, 'form'])->name('pay');
        Route::post('pay', [PaymentController::class, 'index']);
        Route::post('callback_url', [PaymentController::class, 'paymentResponce'])->name('callback_url');
        Route::post('webhook/{gateway}', [PaymentController::class, 'webhook'])->name('webhook');
        Route::post('manualWebhook', [PaymentController::class, 'manualWebhook'])->name('manualWebhook');
        Route::post('checkPayment', [PaymentController::class, 'checkPayment'])->name('checkPayment');
        Route::post('paymentSuccess', [PaymentController::class, 'paymentSuccess'])->name('paymentSuccess');
        Route::post('paymentFailed', [PaymentController::class, 'paymentFailed'])->name('paymentFailed');
});
