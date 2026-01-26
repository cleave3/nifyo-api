<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Auth extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids;

    protected $table = 'auth';
    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'uid',
        'business_id',
        'email',
        'phone',
        'name',
        'user_type',
        'is_email_verified',
        'status',
        'is_phone_verified',
        'two_factor_enabled',
        'two_factor_option',
        'has_password',
        'password_hash',
        'last_login',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'uid' => $this->uid,
            'bid' => $this->business_id,
        ];
    }
    
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
