<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionApproval extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'transaction_id',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
