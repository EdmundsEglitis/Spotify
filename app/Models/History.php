<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    // Explicitly specify the table name
    protected $table = 'mood_histories';

    // Define the fillable fields that match the table structure
    protected $fillable = ['user_id', 'mood', 'playlist_url'];

    // Define the relationship to the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}