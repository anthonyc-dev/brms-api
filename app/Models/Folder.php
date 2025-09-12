<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $fillable = [
        'folder_name',
        'zip_name',
        'original_files',
        'description',
        'date_created',
    ];
    
    protected $casts = [
        'original_files' => 'array',
        'date_created' => 'date',
    ];
    
}
