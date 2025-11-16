<?php

namespace App\Http\Controllers\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Database\QueryException;

class AdminService
{
    /**
     * Handle admin login with validation
     */
    public function login(array $credentials)
    {
        // Validation
        $validator = Validator::make($credentials, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $admin = Admin::where('username', $credentials['username'])->first();

            if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
                throw ValidationException::withMessages([
                    'username' => ['Invalid credentials.'],
                ]);
            }

            // Optional: revoke old tokens
            $admin->tokens()->delete();

            // Create token
            $token = $admin->createToken('admin_token', ['admin'])->plainTextToken;

            return [
                'admin' => $admin,
                'token' => $token,
            ];
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'error' => ['An error occurred during login.'],
            ]);
        }
    }

    /**
     * Register admin with validation
     */
    public function register(array $data)
    {
        // Validation
        $validator = Validator::make($data, [
            'name'     => 'required|string|min:3|max:255',
            'username' => 'required|string|min:3|max:255|unique:admins,username',
            'password' => 'required|string', //'required|string|min:8'
            'role'     => 'required|in:admin,official',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            return Admin::create([
                'name'     => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'role'     => $data['role'],
            ]);
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'error' => ['Failed to register admin. Possibly duplicate username or invalid data.'],
            ]);
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'error' => ['An unexpected error occurred during registration.'],
            ]);
        }
    }

    public function update(Admin $admin, array $data)
    {
        // Validation rules
        $rules = [
            'name'     => 'sometimes|required|string|min:3|max:255',
            'username' => 'sometimes|required|string|min:3|max:255|unique:admins,username,' . $admin->id,
            'password' => 'sometimes|required|string',
            'role'     => 'sometimes|required|in:admin,official',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            if (isset($data['name'])) {
                $admin->name = $data['name'];
            }
            if (isset($data['username'])) {
                $admin->username = $data['username'];
            }
            if (isset($data['password'])) {
                $admin->password = Hash::make($data['password']);
            }
            if (isset($data['role'])) {
                $admin->role = $data['role'];
            }
            $admin->save();

            return $admin;
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'error' => ['Failed to update admin. Possibly duplicate username or invalid data.'],
            ]);
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'error' => ['An unexpected error occurred during update.'],
            ]);
        }
    }

    /**
     * Logout admin (revoke all tokens)
     */
    public function logout($admin)
    {
        try {
            $admin->tokens()->delete();
            return true;
        } catch (Exception $e) {
            throw ValidationException::withMessages([
                'error' => ['An error occurred during logout.'],
            ]);
        }
    }

    
}
