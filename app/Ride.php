<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status', 'client_user_id', 'driver_user_id', 'destination_latitude', 'destination_longitude', 'fare'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'client_user_id', 'driver_user_id'
    ];
}
