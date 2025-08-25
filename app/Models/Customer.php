<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'otp',
        'token',
        'password',
        'authorized',
        'activated',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'token',
        'remember_token',
        'otp',
    ];


    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'customer_products', 'customer_id', 'product_id')
            ->withTimestamps();
    }

    public function maintenance_requests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function getNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    /**
     * Automatically hash the password when set.
     *
     * @param string $password
     * @return void
     */
    // public function setPasswordAttribute($password)
    // {
    //     $this->attributes['password'] = bcrypt($password);
    // }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();

    }
}
