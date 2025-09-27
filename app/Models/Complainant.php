<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Complainant extends Model
{
    use HasFactory;

    protected $table = 'complainant_report';

    protected $fillable = [
        'user_id',
        'report_type',
        'title',
        'description',
        'location',
        'date_time',
        'complainant_name',
        'contact_number',
        'email',
        'is_anonymous',
        'urgency_level',
        'witnesses',
        'additional_info',
        'status',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
        'date_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}