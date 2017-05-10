<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'mobile_number', 'is_user', 'is_driver'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function isUser() {
        return $this->is_user;
    }

    public function isDriver() {
        return $this->is_driver;
    }

    public function userLocation()
    {
        return $this->hasOne('App\UserLocation');
    }

    public function driverDetails()
    {
        return $this->hasOne('App\DriverDetails');
    }
}
