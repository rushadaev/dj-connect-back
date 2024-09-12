<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *      schema="DJ",
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="user_id", type="integer", example=1),
 *      @OA\Property(property="stage_name", type="string", example="DJ Example"),
 *      @OA\Property(property="city", type="string", example="New York"),
 *      @OA\Property(property="payment_details", type="string", example="Bank details or any payment information"),
 *      @OA\Property(property="created_at", type="string", format="date-time"),
 *      @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class DJ extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $table = 'djs';

    protected $fillable = [
        'user_id',
        'stage_name',
        'city',
        'payment_details',
        'price',
        'sex',
        'phone',
        'email',
        'website',
        'photo',
        'description',
        'views'
    ];

    protected $appends = ['photo_url'];

    /**
     * Get the user that owns the DJ.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The tracks that belong to the DJ.
     */
    public function tracks()
    {
        return $this->belongsToMany(Track::class, 'dj_track', 'dj_id', 'track_id')->withPivot('price');
    }
    /**
     * Get the orders for the DJ.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'dj_id', 'id');
    }

    public function getTelegramIdAttribute()
    {
        return $this->user->telegram_id;
    }

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo) {
            return null;
        }
        
        $path = Storage::url($this->photo);
        return url($path);  // This will prepend the full domain
    }
}
