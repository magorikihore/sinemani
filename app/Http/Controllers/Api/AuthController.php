<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected CoinService $coinService
    ) {}

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'username' => $request->username,
            'phone' => $request->phone,
        ]);

        $user->assignRole('user');

        // Grant signup bonus
        $signupBonus = (int) config('dramabox.signup_bonus_coins', 50);
        if ($signupBonus > 0) {
            $this->coinService->credit(
                $user,
                $signupBonus,
                'signup_bonus',
                'Welcome bonus coins!'
            );
            $user->refresh();
        }

        $token = $user->createToken($request->input('device_name', 'default'))->plainTextToken;

        return $this->created([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Registration successful');
    }

    /**
     * Login with email and password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return $this->error('Your account has been suspended.', 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken($request->input('device_name', 'default'))->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Social login (Google, Facebook, Apple).
     * In production, validate the token with the provider's API.
     */
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        $provider = $request->provider;
        $providerToken = $request->token;

        // In production: validate token with provider API and get user data
        // This is a placeholder - implement actual provider verification
        $socialUser = $this->verifySocialToken($provider, $providerToken);

        if (!$socialUser) {
            return $this->error('Invalid social login token.', 401);
        }

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser['id'])
            ->first();

        if (!$user) {
            // Check if email already exists
            $user = User::where('email', $socialUser['email'])->first();

            if ($user) {
                // Link social account to existing user
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser['id'],
                    'provider_token' => $providerToken,
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'name' => $socialUser['name'],
                    'email' => $socialUser['email'],
                    'provider' => $provider,
                    'provider_id' => $socialUser['id'],
                    'provider_token' => $providerToken,
                    'avatar' => $socialUser['avatar'] ?? null,
                    'email_verified_at' => now(),
                ]);

                $user->assignRole('user');

                // Signup bonus
                $signupBonus = (int) config('dramabox.signup_bonus_coins', 50);
                if ($signupBonus > 0) {
                    $this->coinService->credit($user, $signupBonus, 'signup_bonus', 'Welcome bonus coins!');
                    $user->refresh();
                }
            }
        }

        if (!$user->is_active) {
            return $this->error('Your account has been suspended.', 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken($request->input('device_name', 'default'))->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Initialize or retrieve a guest account by device ID.
     */
    public function guestInit(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
        ]);

        $deviceId = $request->input('device_id');

        // Look for existing guest account with this device
        $user = User::where('device_id', $deviceId)
            ->where('email', 'like', 'guest_%@sinemani.app')
            ->first();

        if ($user) {
            if (!$user->is_active) {
                return $this->error('Your account has been suspended.', 403);
            }

            // Revoke old tokens and issue a fresh one
            $user->tokens()->delete();
            $token = $user->createToken('mobile')->plainTextToken;

            return $this->success([
                'user' => $this->formatUser($user),
                'token' => $token,
                'is_new' => false,
            ], 'Guest session restored');
        }

        // Create a new guest account tied to this device
        $guestId = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(8))), 0, 10);
        $guestEmail = "guest_{$guestId}@sinemani.app";

        $user = User::create([
            'name' => 'Viewer ' . substr($guestId, 0, 5),
            'email' => $guestEmail,
            'password' => bcrypt(bin2hex(random_bytes(16))),
            'device_id' => $deviceId,
        ]);

        $user->assignRole('user');

        // Grant signup bonus
        $signupBonus = (int) config('dramabox.signup_bonus_coins', 50);
        if ($signupBonus > 0) {
            $this->coinService->credit(
                $user,
                $signupBonus,
                'signup_bonus',
                'Welcome bonus coins!'
            );
            $user->refresh();
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->created([
            'user' => $this->formatUser($user),
            'token' => $token,
            'is_new' => true,
        ], 'Guest account created');
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get current authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success($this->formatUser($request->user()));
    }

    /**
     * Update FCM token for push notifications.
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
        ]);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return $this->success(null, 'FCM token updated');
    }

    /**
     * Verify social login token with provider.
     * PLACEHOLDER: Implement actual verification in production.
     */
    private function verifySocialToken(string $provider, string $token): ?array
    {
        // TODO: Implement actual provider verification
        // For Google: use Google_Client to verify ID token
        // For Facebook: call graph API
        // For Apple: verify JWT

        Log::warning("Social login token verification not implemented for {$provider}. Using placeholder.");

        return null; // Return null until implemented
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'gender' => $user->gender,
            'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
            'bio' => $user->bio,
            'coin_balance' => $user->coin_balance,
            'language' => $user->language,
            'country' => $user->country,
            'is_vip' => $user->isVipActive(),
            'vip_expires_at' => $user->vip_expires_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
        ];
    }
}
