<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Admin;

class Event extends Model
{
    protected $fillable = [
        "posted_id",
        'title',
        'description',
        'date',
        'posted_by',
    ];
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

}
