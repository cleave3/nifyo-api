<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'business_id',
        'name',
        'email',
        'phone',
        'image_url',
        'address',
        'payment_frequency',
        'sms_alert',
        'status',
        'next_payment_date',
        'customer_type',
        'customer_code',
        'creator_id',
    ];

    protected $casts = [
        'next_payment_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
