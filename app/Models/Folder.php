<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_name',
        'zip_name',
        'original_files',
        'description',
        'date_created',
    ];

    /**
     * IMPORTANT: Cast original_files as array for JSON handling
     */
    protected $casts = [
        'original_files' => 'array',
        'date_created' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}