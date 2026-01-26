<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Casts\PostgresArray;
class User extends Model
{
    use HasFactory, HasUuids;

    protected $primaryKey = 'uid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'business_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'permissions',
        'image_url',
        'is_active',
        'is_superuser',
        'is_staff',
        'date_joined',
        'last_login',
    ];

    protected $casts = [
        'permissions' => PostgresArray::class,
        'is_active' => 'boolean',
        'is_superuser' => 'boolean',
        'is_staff' => 'boolean',
        'date_joined' => 'datetime',
        'last_login' => 'datetime',
    ];
}
