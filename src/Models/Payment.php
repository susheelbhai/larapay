<?php

namespace Susheelbhai\Larapay\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $table = 'payments';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
