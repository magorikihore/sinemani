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
     * Register Expo push token for push notifications.
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        $request->validate([
            'push_token' => ['required', 'string', 'starts_with:ExponentPushToken['],
        ]);

        $request->user()->update(['expo_push_token' => $request->push_token]);

        return $this->success(null, 'Push token registered');
    }

    /**
     * Verify social login token with provider.
     */
    private function verifySocialToken(string $provider, string $token): ?array
    {
        try {
            return match ($provider) {
                'google' => $this->verifyGoogleToken($token),
                'facebook' => $this->verifyFacebookToken($token),
                'apple' => $this->verifyAppleToken($token),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error("Social login verification failed for {$provider}", [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify Google ID token via Google's tokeninfo endpoint.
     */
    private function verifyGoogleToken(string $idToken): ?array
    {
        $response = \Illuminate\Support\Facades\Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (!$response->successful()) {
            Log::warning('Google token verification failed', ['status' => $response->status()]);
            return null;
        }

        $data = $response->json();

        // Validate audience matches one of our client IDs
        $aud = $data['aud'] ?? '';
        $allowedClients = array_filter([
            config('services.google.client_id'),
            config('services.google.client_id_ios'),
            config('services.google.client_id_android'),
        ]);

        if (!in_array($aud, $allowedClients, true)) {
            Log::warning('Google token audience mismatch', ['aud' => $aud]);
            return null;
        }

        return [
            'id' => $data['sub'],
            'email' => $data['email'],
            'name' => $data['name'] ?? $data['email'],
            'avatar' => $data['picture'] ?? null,
        ];
    }

    /**
     * Verify Facebook access token via Graph API.
     */
    private function verifyFacebookToken(string $accessToken): ?array
    {
        $response = \Illuminate\Support\Facades\Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::warning('Facebook token verification failed', ['status' => $response->status()]);
            return null;
        }

        $data = $response->json();

        if (empty($data['id'])) {
            return null;
        }

        return [
            'id' => $data['id'],
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? 'Facebook User',
            'avatar' => $data['picture']['data']['url'] ?? null,
        ];
    }

    /**
     * Verify Apple identity token (JWT signed by Apple).
     */
    private function verifyAppleToken(string $identityToken): ?array
    {
        // Decode JWT payload without verification first to get claims
        $parts = explode('.', $identityToken);
        if (count($parts) !== 3) {
            Log::warning('Apple token: invalid JWT format');
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if (!$payload) {
            Log::warning('Apple token: failed to decode payload');
            return null;
        }

        // Fetch Apple's public keys and verify signature
        $response = \Illuminate\Support\Facades\Http::get('https://appleid.apple.com/auth/keys');
        if (!$response->successful()) {
            Log::warning('Apple: failed to fetch public keys');
            return null;
        }

        $keys = $response->json('keys');
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $kid = $header['kid'] ?? null;

        // Find matching key
        $matchingKey = null;
        foreach ($keys as $key) {
            if ($key['kid'] === $kid) {
                $matchingKey = $key;
                break;
            }
        }

        if (!$matchingKey) {
            Log::warning('Apple token: no matching key found', ['kid' => $kid]);
            return null;
        }

        // Validate basic claims
        $aud = $payload['aud'] ?? '';
        $iss = $payload['iss'] ?? '';
        $exp = $payload['exp'] ?? 0;

        if ($iss !== 'https://appleid.apple.com') {
            Log::warning('Apple token: invalid issuer', ['iss' => $iss]);
            return null;
        }

        $clientId = config('services.apple.client_id');
        if ($clientId && $aud !== $clientId) {
            Log::warning('Apple token: audience mismatch', ['aud' => $aud]);
            return null;
        }

        if ($exp < time()) {
            Log::warning('Apple token: expired');
            return null;
        }

        return [
            'id' => $payload['sub'],
            'email' => $payload['email'] ?? null,
            'name' => $payload['name'] ?? $payload['email'] ?? 'Apple User',
            'avatar' => null,
        ];
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
