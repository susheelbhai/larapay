<?php

namespace Susheelbhai\Larapay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;
    protected $table = 'payments';
}
