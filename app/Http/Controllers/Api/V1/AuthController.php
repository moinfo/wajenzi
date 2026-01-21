<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user and return API token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if ($user->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'email' => ['Your account is not active. Please contact administrator.'],
            ]);
        }

        // Revoke existing tokens for this device (optional - allows single session per device)
        if ($request->device_id) {
            $user->tokens()->where('name', 'mobile_' . $request->device_id)->delete();
        }

        // Create new token with abilities based on user roles
        $abilities = $this->getTokenAbilities($user);
        $tokenName = $request->device_id ? 'mobile_' . $request->device_id : $request->device_name;
        $token = $user->createToken($tokenName, $abilities);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
                'token' => $token->plainTextToken,
                'abilities' => $abilities,
            ],
            'message' => 'Login successful',
        ]);
    }

    /**
     * Logout user and revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Optionally remove device token for push notifications
        if ($request->device_id) {
            DeviceToken::where('user_id', $request->user()->id)
                ->where('device_id', $request->device_id)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only load relationships that exist
        $relations = ['department'];

        // Check if projects relationship exists and table is available
        try {
            if (method_exists($user, 'projects')) {
                $user->load(['projects' => function ($query) {
                    $query->wherePivot('status', 'active');
                }]);
            }
        } catch (\Exception $e) {
            // Projects table may not exist yet - skip loading
        }

        $user->load($relations);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|nullable|string|max:500',
            'profile' => 'sometimes|nullable|image|max:2048', // Profile picture
        ]);

        // Handle profile picture upload
        if ($request->hasFile('profile')) {
            // Delete old profile picture if exists
            if ($user->profile && Storage::disk('public')->exists($user->profile)) {
                Storage::disk('public')->delete($user->profile);
            }

            $path = $request->file('profile')->store('profiles', 'public');
            $validated['profile'] = $path;
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->fresh()),
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Register device token for push notifications.
     */
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
            'platform' => 'required|in:ios,android',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $request->device_id,
            ],
            [
                'fcm_token' => $request->fcm_token,
                'platform' => $request->platform,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully',
        ]);
    }

    /**
     * Get token abilities based on user roles and permissions.
     */
    private function getTokenAbilities(User $user): array
    {
        $abilities = ['*']; // Default: full access

        // Add role-based abilities if needed
        // Example:
        // if ($user->hasRole('supervisor')) {
        //     $abilities[] = 'approve:site-reports';
        // }

        return $abilities;
    }
}
