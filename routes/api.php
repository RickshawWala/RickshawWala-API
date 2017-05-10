<?php

use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    $user = $request->user();

    if ($user->isUser() && !$user->isDriver()) {
        return $user::with(['userLocation'])->get();
    } elseif ($user->isDriver() && !$user->isUser()) {
        return $user::with(['userLocation','driverDetails'])->get();
    } elseif ($user->isUser() && $user->isDriver()) {
        return $user::with(['userLocation','driverDetails'])->get();
    } else {
        return $user;
    }
});
