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
        'status', 'client_user_id', 'driver_user_id', 'origin_latitude', 'origin_longitude', 'destination_latitude', 'destination_longitude', 'fare'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'client_user_id', 'driver_user_id'
    ];

    /**
     * Get the client user that created the ride.
     */
    public function client()
    {
        return $this->belongsTo('App\User', 'client_user_id');
    }

    /**
     * Get the driver user that accepted the ride.
     */
    public function driver()
    {
        return $this->belongsTo('App\User', 'driver_user_id');
    }

}
