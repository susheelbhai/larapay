<?php

namespace Susheelbhai\Larapay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentRefund extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded= [];
    protected $table = 'payment_refunds';
}
