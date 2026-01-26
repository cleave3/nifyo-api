<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessPreference extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    //
    protected $fillable = [
            'id',
            'business_id',
            'sms_notification',
            'sms_id',
            'email_notification',
            'require_two_factor',
            'created_at',
            'updated_at',
    ];
}
