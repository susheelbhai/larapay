<?php

namespace Susheelbhai\Larapay\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payout extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'payouts';

    protected $casts = [
        'amount' => 'decimal:2',
        'inline_created' => 'boolean',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(PayoutBeneficiary::class, 'payout_beneficiary_id');
    }
}
