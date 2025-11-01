<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
use App\Models\Resident;
use App\Models\RequestDocument;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile',
    ];
    

    public function resident()
    {
        return $this->hasOne(Resident::class);
    }

        // One user can have many request documents
    public function requestDocuments()
    {
        return $this->hasMany(RequestDocument::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the full URL for the profile image.
     *
     * @return string|null
     */
    public function getProfileUrlAttribute()
    {
        if (!$this->profile) {
            return null;
        }

        // If the profile path already starts with http/https, return as is
        if (str_starts_with($this->profile, 'http://') || str_starts_with($this->profile, 'https://')) {
            return $this->profile;
        }

        // Generate the URL using asset helper
        return asset('storage/' . $this->profile);
    }
}
