<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate client and return Sanctum token.
     * Accepts email or phone number as the login field.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $client = ProjectClient::where('email', $request->login)
            ->orWhere('phone_number', $request->login)
            ->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$client->portal_access_enabled) {
            throw ValidationException::withMessages([
                'login' => ['Portal access is not enabled for your account. Please contact the project team.'],
            ]);
        }

        $token = $client->createToken($request->device_name);

        return response()->json([
            'success' => true,
            'data' => [
                'client' => [
                    'id' => $client->id,
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'full_name' => $client->full_name,
                    'email' => $client->email,
                    'phone_number' => $client->phone_number,
                ],
                'token' => $token->plainTextToken,
            ],
            'message' => 'Login successful',
        ]);
    }

    /**
     * Revoke current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Return authenticated client profile.
     */
    public function me(Request $request): JsonResponse
    {
        $client = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone_number' => $client->phone_number,
                'address' => $client->address,
                'identification_number' => $client->identification_number,
                'projects_count' => $client->projects()->count(),
            ],
        ]);
    }

    /**
     * Update authenticated client profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:project_clients,email,' . $request->user()->id,
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $client = $request->user();
        $client->update($request->only(['first_name', 'last_name', 'email', 'phone_number', 'address']));

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $client->id,
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'full_name' => $client->full_name,
                'email' => $client->email,
                'phone_number' => $client->phone_number,
                'address' => $client->address,
                'identification_number' => $client->identification_number,
                'projects_count' => $client->projects()->count(),
            ],
        ]);
    }

    /**
     * Change authenticated client password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $client = $request->user();

        if (!Hash::check($request->current_password, $client->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $client->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }
}
