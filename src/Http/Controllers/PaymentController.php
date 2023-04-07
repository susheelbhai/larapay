<?php

namespace Susheelbhai\Larapay\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;

class PaymentController extends Controller{
    public function index()
    {
        return view('larapay::payment.index');
    }
}