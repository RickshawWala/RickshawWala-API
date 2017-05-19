<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// proxy /oauth/token & /oauth/refresh to prevent leaking client id & secret while requesting access tokens
Route::post('auth/token', 'Api\Auth\DefaultController@authenticate');
Route::post('auth/refresh', 'Api\Auth\DefaultController@refreshToken');
// from: https://web.archive.org/web/20170509132215/https://laracasts.com/discuss/channels/code-review/api-authentication-with-passport/replies/282168

Route::post('/register', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'mobile_number' => 'required|numeric|digits:10|unique:users',
        'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'error' => 'Registration Failed',
            'fields' => $validator->messages()
        ]);
    }

    try {
        if($request['is_client'] == 'true') {
            App\User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'mobile_number' => $request['mobile_number'],
                'password' => bcrypt($request['password']),
                'is_client' => $request['is_client'],
            ]);
        }
        else if($request['is_driver'] == 'true') {
            $user = App\User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'mobile_number' => $request['mobile_number'],
                'password' => bcrypt($request['password']),
                'is_driver' => $request['is_driver'],
            ]);
            $driver_details = new App\DriverDetails([
                'licence_number' => $request['licence_number'],
                'vehicle_registration_number' => $request['vehicle_registration_number']
            ]);
            $user->DriverDetails()->save($driver_details);
        } else {
            App\User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'mobile_number' => $request['mobile_number'],
                'password' => bcrypt($request['password']),
            ]);
        }
        return response()->json([
            'success' => 'Registration Successful',
        ]);
    } catch(Illuminate\Database\QueryException $e) {
        if(Config::get('app.debug')) {
            return response()->json([
                'error' => $e->getMessage(),
            ]);
        } else {
            return response()->json([
                'error' => 'Registration Failed',
            ]);
        }
    }
});

Route::group(['middleware' => 'auth:api'], function () {

    Route::get('/user', function (Request $request) {
        $user = Auth::user(); // can also be $request->user()

        if ($user->isClient() && !$user->isDriver()) {
            return App\User::with(['userLocation'])->find($user->id);
        } elseif ($user->isDriver() && !$user->isClient()) {
            return App\User::with(['userLocation','driverDetails'])->find($user->id);
        } elseif ($user->isClient() && $user->isDriver()) {
            return App\User::with(['userLocation','driverDetails'])->find($user->id);
        } else {
            return App\User::with(['userLocation'])->find($user->id);
        }
    });

    Route::post('/location-update', function (Request $request) {
        try {
            App\UserLocation::updateOrCreate(
                ['user_id' => $request->user()->id],
                ['latitude' => $request->latitude, 'longitude' => $request->longitude]
            );
            return response()->json([
                'success' => 'Location Updated',
            ]);
        } catch(Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Location Update Failed',
            ]);
        }
    });

    Route::post('/ride/create', function (Request $request) {
        try {
            App\Ride::create([
                'client_user_id' => Auth::id(),
                'origin_latitude' => $request->origin_latitude,
                'origin_longitude' => $request->origin_longitude,
                'destination_latitude' => $request->destination_latitude,
                'destination_longitude' => $request->destination_longitude,
            ]);
            return response()->json([
                'success' => 'Ride Created',
            ]);
        } catch(Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Ride Creation Failed',
            ]);
        }
    });

    Route::post('/ride/update', function (Request $request) {
        try {
            $ride = App\Ride::find($request->id);
            $ride->driver_user_id = Auth::id();
            $ride->status = $request->status;
            $ride->save();
            return response()->json([
                'success' => 'Ride Updated',
            ]);
        } catch(Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Ride Update Failed',
            ]);
        }
    });

    Route::get('/ride/status', function (Request $request) {
        return App\Ride::where('client_user_id', Auth::id())->with('driver')->orderBy('updated_at', 'desc')->first();
    });

    Route::get('/ride/named-locations', function (Request $request) {
        $ride = App\Ride::where('client_user_id', Auth::id())->orderBy('updated_at', 'desc')->first();

        $origLat = $ride->origin_latitude;
        $origLong = $ride->origin_longitude;
        $destLat = $ride->destination_latitude;
        $destLong = $ride->destination_longitude;

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origLat},{$origLong}&destinations={$destLat},{$destLong}";

        $json = json_decode(file_get_contents($url), true);

        $status = $json['rows'][0]['elements'][0]['status'];
        if($status == "ZERO_RESULTS") {
            return response()->json([
                'error' => 'Location names could not be found',
            ]);
        } else if ($status == "OK") {
            $originName = $json['origin_addresses'];
            $destinationName = $json['destination_addresses'];
            return response()->json([
                'origin' => $originName,
                'destination' => $destinationName
            ]);
        }
    });

    Route::get('/created-rides', function (Request $request) {
        return App\Ride::where('status', 'created')->with('client')->get();
    });

});