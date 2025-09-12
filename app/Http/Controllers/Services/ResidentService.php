<?php

namespace App\Http\Controllers\Services;

use App\Models\Resident;
use App\Models\User ;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class ResidentService
{
    public function createUser(array $data)
    {
        try {
            // Validate input data
            $validated = validator($data, [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ])->validate();

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            return $user;
        } catch (ValidationException $e) {
            // You can handle validation errors here as needed
            throw $e;
        } catch (\Exception $e) {
            // Handle other exceptions
            throw $e;
        }
    }

    //create resident
    public function registerUser(array $data)
    {
        $validator = validator($data, [
            // Personal Information
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'birth_date' => 'required|date',
            'gender' => 'required|in:Male,Female',
            'place_of_birth' => 'required|string|max:255',
            'civil_status' => 'required|in:Single,Married,Widowed,Divorced,Separated',
            'nationality' => 'required|string|max:255',
            'religion' => 'required|string|max:255',
            'occupation' => 'required|string|max:255',
    
            // Address Information
            'house_number' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'zone' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
    
            // Contact Information
            'contact_number' => 'required|string|max:255',
            'email' => 'required|email|unique:residents,email',
    
            // Parents Information
            'father_first_name' => 'required|string|max:255',
            'father_middle_name' => 'nullable|string|max:255',
            'father_last_name' => 'required|string|max:255',
            'mother_first_name' => 'required|string|max:255',
            'mother_middle_name' => 'nullable|string|max:255',
            'mother_maiden_name' => 'required|string|max:255',
    
            // Valid ID Upload Information
            'valid_id_path' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    
        try {
            // Handle file upload if present
            if (isset($data['valid_id_path']) && $data['valid_id_path'] instanceof UploadedFile) {
                $path = $data['valid_id_path']->store('uploads/valid_ids', 'public');
                $data['valid_id_path'] = $path; // store file path in DB
            }
    
            // Create resident record
            $resident = Resident::create($data);
    
            return $resident;
    
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    //update resident
    public function updateUser(array $data, Resident $resident)
    {
        $validator = validator($data, [
            // Personal Information
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'birth_date' => 'sometimes|required|date',
            'gender' => 'sometimes|required|in:Male,Female',
            'place_of_birth' => 'sometimes|required|string|max:255',
            'civil_status' => 'sometimes|required|in:Single,Married,Widowed,Divorced,Separated',
            'nationality' => 'sometimes|required|string|max:255',
            'religion' => 'sometimes|required|string|max:255',
            'occupation' => 'sometimes|required|string|max:255',

            // Address Information
            'house_number' => 'sometimes|required|string|max:255',
            'street' => 'sometimes|required|string|max:255',
            'zone' => 'sometimes|required|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'province' => 'sometimes|required|string|max:255',

            // Contact Information
            'contact_number' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:residents,email,' . $resident->id,

            // Parents Information
            'father_first_name' => 'sometimes|required|string|max:255',
            'father_middle_name' => 'nullable|string|max:255',
            'father_last_name' => 'sometimes|required|string|max:255',
            'mother_first_name' => 'sometimes|required|string|max:255',
            'mother_middle_name' => 'nullable|string|max:255',
            'mother_maiden_name' => 'sometimes|required|string|max:255',

            // Valid ID Upload Information
            'valid_id_path' => 'nullable|string',
            'upload_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $resident->update($data);
            return $resident;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    //delete resident
    public function deleteUser(Resident $resident)
    {
        try {
            $resident->delete();
            return $resident;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    //get resident
    public function getUser(Resident $resident)
    {
        try {
            return $resident;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
    