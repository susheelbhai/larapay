<?php

namespace App\Http\Controllers;

// This controller is developed to publish in main controller
class LarapayController extends Controller
{
    public function paymentSuccessful($request) {
        return view('larapay::payment.response', compact('request', 'data'));
    }
    public function paymentFailed($request) {
        return view('larapay::payment.response', compact('request', 'data'));
    }
}
