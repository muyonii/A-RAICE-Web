<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Models\User;
use App\Services\GeoLocationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->formatUserData($user),
        ]);
    }

    public function register(RegisterRequest $request, GeoLocationService $geo): JsonResponse
    {
        $validated = $request->validated();

        if (! $geo->insideMunicipality(
            $validated['municipality'],
            (float) $validated['farm_lat'],
            (float) $validated['farm_long'],
        )) {
            throw ValidationException::withMessages([
                'farm_lat' => 'These coordinates are not inside the selected municipality.',
            ]);
        }

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->forceFill(['role' => UserRole::Farmer]);

            if (! config('auth.require_email_verification')) {
                $user->forceFill(['email_verified_at' => now()]);
            }
            $user->save();

            $user->farmerProfile()->create([
                'full_name' => $validated['name'],
                'barangay' => $validated['barangay'],
                'municipality' => $validated['municipality'],
                'contact_number' => $validated['contact_number'],
                'farm_lat' => $validated['farm_lat'],
                'farm_long' => $validated['farm_long'],
            ]);

            return $user;
        });

        if (config('auth.require_email_verification')) {
            event(new Registered($user));
        }

        $user->load('farmerProfile');

        return response()->json([
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $this->formatUserData($user),
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user()->fresh();
        if ($user->role === UserRole::Farmer) {
            $user->loadMissing('farmerProfile');
        }

        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];

        if ($user->role === UserRole::Farmer && $user->farmerProfile !== null) {
            $profile = $user->farmerProfile;
            $userData['farmer_profile'] = [
                'municipality' => $profile->municipality->value,
                'barangay' => $profile->barangay,
                'farm_lat' => (float) $profile->farm_lat,
                'farm_long' => (float) $profile->farm_long,
                'contact_number' => $profile->contact_number,
            ];
        }

        return response()->json($userData);
    }

    public function updateProfile(UpdateProfileRequest $request, GeoLocationService $geo): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($user->role === UserRole::Farmer && $user->farmerProfile !== null) {
            $profile = $user->farmerProfile;
            $muni = $validated['municipality'] ?? $profile->municipality->value;
            $lat = isset($validated['farm_lat']) ? (float) $validated['farm_lat'] : (float) $profile->farm_lat;
            $long = isset($validated['farm_long']) ? (float) $validated['farm_long'] : (float) $profile->farm_long;

            if (isset($validated['municipality']) || isset($validated['farm_lat']) || isset($validated['farm_long'])) {
                if (! $geo->insideMunicipality($muni, $lat, $long)) {
                    throw ValidationException::withMessages([
                        'farm_lat' => 'These coordinates are not inside the selected municipality.',
                    ]);
                }
            }
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($user->role === UserRole::Farmer && $user->farmerProfile !== null) {
            $profileData = array_intersect_key($validated, array_flip([
                'barangay', 'municipality', 'contact_number', 'farm_lat', 'farm_long',
            ]));

            if (! empty($validated['name'])) {
                $profileData['full_name'] = $validated['name'];
            }

            if (! empty($profileData)) {
                $user->farmerProfile()->update($profileData);
            }
        }

        return $this->user($request);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', PasswordRule::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => trans($status),
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => trans($status),
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatUserData(User $user): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];

        if ($user->role === UserRole::Farmer && $user->farmerProfile !== null) {
            $data['farmer_profile'] = [
                'municipality' => $user->farmerProfile->municipality->value,
                'barangay' => $user->farmerProfile->barangay,
                'farm_lat' => (float) $user->farmerProfile->farm_lat,
                'farm_long' => (float) $user->farmerProfile->farm_long,
                'contact_number' => $user->farmerProfile->contact_number,
            ];
        }

        return $data;
    }
}
