<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OAuthController extends Controller
{
    public function redirect()
    {
        $queries = http_build_query([
            'client_id' => 1,
            'redirect_uri' => 'http://localhost:8002/oauth/callback',
            'response_type' => 'code',
        ]);

        return redirect('http://localhost:8003/oauth/authorize?' . $queries);
    }

    public function callback(Request $request)
    {

        $response = Http::post('http://localhost:8003/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => 1,
            'client_secret' => 'A7MfVUxbnYfCHK7VFajzc2uhyGfq8JBUMgK7QA75',
            'redirect_uri' => 'http://localhost:8002/oauth/callback',
            'code' => $request->code
        ]);
        $users = User::first();
        echo $users->email;
         $response = $response->json();

      return  $request->user();
        $request->user()->token()->delete();

        $request->user()->token()->create([
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'],
            'refresh_token' => $response['refresh_token']
        ]);
//comment
        return redirect('/home');
    }

    public function refresh(Request $request)
    {
        $response = Http::post(config('services.oauth_server.uri') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->user()->token->refresh_token,
            'client_id' => config('services.oauth_server.client_id'),
            'client_secret' => config('services.oauth_server.client_secret'),
            'redirect_uri' => config('services.oauth_server.redirect'),
            'scope' => 'view-posts'
        ]);

        if ($response->status() !== 200) {
            $request->user()->token()->delete();

            return redirect('/home')
                ->withStatus('Authorization failed from OAuth server.');
        }

        $response = $response->json();
        $request->user()->token()->update([
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'],
            'refresh_token' => $response['refresh_token']
        ]);

        return redirect('/home');
    }
}
