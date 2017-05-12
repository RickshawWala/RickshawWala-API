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
        if($request['isClient'] == 'true') {
            App\User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'mobile_number' => $request['mobile_number'],
                'password' => bcrypt($request['password']),
                'is_client' => $request['is_client'],
            ]);
        }
        else if($request['isDriver'] == 'true') {
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
        $user = $request->user();

        if ($user->isClient() && !$user->isDriver()) {
            return $user::with(['userLocation'])->get();
        } elseif ($user->isDriver() && !$user->isClient()) {
            return $user::with(['userLocation','driverDetails'])->get();
        } elseif ($user->isClient() && $user->isDriver()) {
            return $user::with(['userLocation','driverDetails'])->get();
        } else {
            return $user;
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

});