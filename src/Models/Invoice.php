<?php

namespace Susheelbhai\Larapay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Susheelbhai\Larapay\Models\Payment;

class Invoice extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function payment()
    {
        return $this->hasMany(Payment::class);
    }
}
