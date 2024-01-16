<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    /**
     * Get the user that owns the achievement.
     */
    public function user()
    {
        return $this->belongsToMany(User::class);
    }
}
