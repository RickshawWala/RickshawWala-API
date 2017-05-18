<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class DefaultController extends Controller
{
    /**
     * @var object
     */
    private $client;

    /**
     * DefaultController constructor.
     */
    public function __construct()
    {
        $this->client = DB::table('oauth_clients')->where('id', 2)->first();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function authenticate(Request $request)
    {
        // for when a is_client or is_driver parameter exists in the request, to
        // prevent a client from logging into a driver's account and vice versa
        if(Auth::attempt(['email' => $request->username, 'password' => $request->password])) {
            if($request->is_driver == "true" && !Auth::user()->is_driver) {
                return response()->json(['error' => 'invalid_credentials', 'message' => 'The user credentials were incorrect.'], 401);
            } else if($request->is_client == "true" && !Auth::user()->is_client) {
                return response()->json(['error' => 'invalid_credentials', 'message' => 'The user credentials were incorrect.'], 401);
            }
        }

        $request->request->add([
            'username' => $request->username,
            'password' => $request->password,
            'grant_type' => 'password',
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
            'scope' => '*'
        ]);

        $proxy = Request::create(
            'oauth/token',
            'POST'
        );

        return Route::dispatch($proxy);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function refreshToken(Request $request)
    {
        $request->request->add([
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->refresh_token,
            'client_id' => $this->client->id,
            'client_secret' => $this->client->secret,
        ]);

        $proxy = Request::create(
            '/oauth/token',
            'POST'
        );

        return Route::dispatch($proxy);
    }
}