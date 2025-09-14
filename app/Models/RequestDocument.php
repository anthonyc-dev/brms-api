<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RequestDocument extends Model
{
    use HasFactory;
    protected $table = 'document_requests';
    protected $fillable = [
        'user_id',
        'document_type',
        'full_name',
        'address',
        'contact_number',
        'email',
        'purpose',
        'reference_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
