<?php

namespace App\Http\Controllers\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data)
    {
        $validator = validator($data, [
            'name'     => 'required|string|min:4',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $user;
    }


    public function updatePassword($id, array $data)
    {
        try {
            $user = User::findOrFail($id);

            $validator = validator($data, [
                'current_password' => 'required|string',
                'new_password'     => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            // Check if current password matches
            if (!Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }

            $user->password = Hash::make($data['new_password']);
            $user->save();

            return $user;
        } catch (ValidationException $e) {
            // Rethrow validation exceptions for controller to handle
            throw $e;
        } catch (\Exception $e) {
            // Log the error or handle as needed
            // Optionally, you can use Laravel's Log facade here
            throw new \Exception('Failed to update password: ' . $e->getMessage(), 0, $e);
        }
    }
}