<?php

namespace Susheelbhai\Larapay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutBeneficiary extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'payout_beneficiaries';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'payout_beneficiary_id');
    }

    public function user()
    {
        return $this->morphTo('user', 'user_type', 'user_id');
    }
}
