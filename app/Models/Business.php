<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    //
    protected $fillable = [
            'id',
            'business_name',
            'business_address',
            'business_phone',
            'business_email',
            'logo_url',
            'business_type',
            'business_nature',
            'business_website',
            'business_reg_no',
            'certificate_url',
            'modules',
            'business_kyc_status',
            'created_at',
            'updated_at',
    ];
    public function preferences()
    {
        return $this->hasOne(BusinessPreference::class);
    }
}

