<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'business_id',
        'amount',
        'charges',
        'transaction_type',
        'status',
        'description',
        'meta_data',
        'requires_approval',
        'number_of_required_approval',
        'initiator_id',
        'updated_by_id',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
