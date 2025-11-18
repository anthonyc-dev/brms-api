<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Resident extends Model
{
    protected $fillable = [
        // Personal Information
        'user_id',
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

        'status',
        
        // Valid ID Upload Information
        'valid_id_path',
        'upload_id',
        'upload_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'birth_date' => 'date',
        'upload_date' => 'datetime',
    ];
}
