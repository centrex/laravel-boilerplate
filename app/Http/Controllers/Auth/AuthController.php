<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Log};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use \App\Traits\ApiResponser;

    /**
     * Handle user registration request
     *
     * @param RegistrationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegistrationRequest $request)
    {
        try {
            $validated = $request->validated();
            
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'fcm_tokens' => [
                    [
                        'device_id' => $validated['device_id'] ?? Str::uuid(),
                        'device_type' => $validated['device_type'] ?? 'unknown',
                        'fcm_token' => $validated['fcm_token'],
                        'logged_in' => true
                    ]
                ],
            ]);

            $token = $user->createToken($validated['device_id'])->plainTextToken;

            $this->logAuthActivity($user, 'Registration');

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'device_id' => $validated['device_id']
            ], 'User registered successfully', 201);

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            Log::error("Registration Error: " . $e->getMessage());
            return $this->serverErrorResponse('Registration failed. Please try again.');
        }
    }

    /**
     * Handle user login request
     *
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = User::where('phone', $validated['phone'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            $fcmTokens = $user->fcm_tokens ?? [];
            $deviceId = $validated['device_id'] ?? Str::uuid();
            $deviceExists = false;

            // Update existing device or add new device
            foreach ($fcmTokens as &$tokenData) {
                if ($tokenData['device_id'] === $deviceId) {
                    $tokenData['fcm_token'] = $validated['fcm_token'];
                    $tokenData['device_type'] = $validated['device_type'] ?? $tokenData['device_type'] ?? 'unknown';
                    $tokenData['logged_in'] = true;
                    $deviceExists = true;
                    break;
                }
            }

            if (!$deviceExists) {
                $fcmTokens[] = [
                    'device_id' => $deviceId,
                    'device_type' => $validated['device_type'] ?? 'unknown',
                    'fcm_token' => $validated['fcm_token'],
                    'logged_in' => true
                ];
            }

            // Clean old Sanctum tokens for this device
            $this->cleanExistingTokens($user, $deviceId);
            
            // Update user with new FCM tokens
            $user->update(['fcm_tokens' => $fcmTokens]);

            $token = $user->createToken($deviceId)->plainTextToken;

            $this->logAuthActivity($user, 'Login');

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'device_id' => $deviceId
            ], 'Login successful');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            Log::error("Login Error: " . $e->getMessage());
            return $this->serverErrorResponse('Login failed. Please try again.');
        }
    }

    /**
     * Handle user logout request
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $deviceId = $request->input('device_id');
            
            if ($user = Auth::user()) {
                $this->logoutOnThisDevice($user, $deviceId);
                return $this->successResponse([], 'Logged out successfully');
            }

            return $this->errorResponse('No authenticated user', 401);

        } catch (\Exception $e) {
            Log::error("Logout Error: " . $e->getMessage());
            return $this->serverErrorResponse('Logout failed. Please try again.');
        }
    }

    /**
     * Clean existing tokens for the device
     */
    private function cleanExistingTokens(User $user, string $deviceId): void
    {
        $user->tokens()
            ->where('name', $deviceId)
            ->delete();
    }

    /**
     * Logout from current device
     */
    private function logoutOnThisDevice(User $user, string $deviceId): void
    {
        // Update FCM tokens to mark device as logged out
        $fcmTokens = $user->fcm_tokens ?? [];
        
        foreach ($fcmTokens as &$tokenData) {
            if ($tokenData['device_id'] === $deviceId) {
                $tokenData['logged_in'] = false;
                break;
            }
        }
        
        $user->update(['fcm_tokens' => $fcmTokens]);
        
        // Delete Sanctum token
        $this->cleanExistingTokens($user, $deviceId);
    }

    /**
     * Log authentication activities
     */
    private function logAuthActivity(User $user, string $action): void
    {
        Log::info("$action Success Through API", [
            'user_id' => $user->id,
            'name' => $user->name,
            'ip' => request()->ip(),
            'host' => request()->getHost(),
            'user_agent' => request()->userAgent()
        ]);
    }
}