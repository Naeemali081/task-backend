<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ClientLoginRequest;
use App\Http\Responses\HttpResponse;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator; // ✅ Corrected import
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    use HttpResponse;

    public function login(LoginRequest $request)
    {
        $user = User::query()->firstWhere('email', $request->input('email'));

        if (!$user) {
            return $this->error(null, 'No user found with this email.', 404);
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return $this->error(null, 'Password is incorrect. Please try again.', 401);
        }

        $user->update(['last_login_at' => now()]);

        return $this->success([
            'user' => UserResource::make($user),
            'access_token' => $user->createToken(config('app.name'))->plainTextToken,
            'refresh_token' => $user->createToken(config('app.name') . '_refresh')->plainTextToken,
            'additional_info' => null
        ], 'User logged in successfully');
    }

    public function client_login(ClientLoginRequest $request)
    {
        $user = User::query()
            ->where('user_type', UserRoles::CLIENT)
            ->firstWhere('phone', $request->input('phone'));

        if (!$user) {
            return $this->error(null, 'No user found with this phone.');
        }

        if ($request->input('phone') !== $user->phone) {
            return $this->error(null, 'Phone number is incorrect. Please try again.');
        }

        $user->update(['last_login_at' => now()]);

        return $this->success([
            'user' => UserResource::make($user),
            'access_token' => $user->createToken(config('app.name'))->plainTextToken,
            'refresh_token' => $user->createToken(config('app.name') . '_refresh')->plainTextToken,
            'additional_info' => null
        ], 'User logged in successfully');
    }

    public function register(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ])->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user)); 


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return $this->success(null, 'Logged out successfully.');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'A password reset link has been sent to your email.']);
        }

        return response()->json(['message' => 'Failed to send password reset link.'], 400);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = auth()->user();
            $user->name = $validated['name'];

            if ($request->hasFile('profile_picture')) {
                $image = $request->file('profile_picture');
                $imagePath = $image->store('profile_pictures', 'public');
                $user->avatar = $imagePath;
            }

            $user->save();

            return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }
}
