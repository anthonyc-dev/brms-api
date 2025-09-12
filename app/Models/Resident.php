<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    protected $fillable = [
        // Personal Information
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',
        'gender',
        'place_of_birth',
        'civil_status',
        'nationality',
        'religion',
        'occupation',
        
        // Address Information
        'house_number',
        'street',
        'zone',
        'city',
        'province',
        
        // Contact Information
        'contact_number',
        'email',
        
        // Parents Information
        'father_first_name',
        'father_middle_name',
        'father_last_name',
        'mother_first_name',
        'mother_middle_name',
        'mother_maiden_name',
        
        // Valid ID Upload Information
        'valid_id_path',
        'upload_id',
        'upload_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'upload_date' => 'datetime',
    ];
}
