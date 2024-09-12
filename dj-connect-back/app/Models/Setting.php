<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use CrudTrait;
    use HasFactory;

    // Table associated with the model
    protected $table = 'settings';
    protected $appends = ['photo_url'];

    // The attributes that are mass assignable
    protected $fillable = [
        'title',
        'description',
        'settings',
        'photo', // Include photo in the fillable attributes
    ];

    // The attributes that should be cast
    protected $casts = [
        'settings' => 'array', // Cast settings column as JSON array
    ];

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) {
            return null;
        }
        
        $path = Storage::url($this->photo);
        return url($path);  // This will prepend the full domain
    }
}