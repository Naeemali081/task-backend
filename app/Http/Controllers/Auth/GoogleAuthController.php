<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Google_Client;
use App\Models\User;

class GoogleAuthController extends Controller
{
    public function handleGoogleToken(Request $request)
    {
        $token = $request->input('token');

        $client = new \Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($token);

        if (!$payload) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $email = $payload['email'];
        $name = $payload['name'];

        // Only allow whitelisted doctor emails
        // $allowedEmails = ['doc1@example.com', 'doc2@example.com'];
        // if (!in_array($email, $allowedEmails)) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => bcrypt('password'), 'role' => 'doctor', 'google_id' => $payload['sub'], 'avatar' => $payload['picture']]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user' => $user,
        ]);
    }
}
