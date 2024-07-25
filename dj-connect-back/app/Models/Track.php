<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 *
 * @OA\Schema(
 *     schema="Track",
 *     type="object",
 *     title="Track",
 *     required={"name", "artist", "duration"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Track ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Track name",
 *         example="Track Name"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2024-07-01T00:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2024-07-01T00:00:00Z"
 *     )
 * )
 */
class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    /**
     * The djs that belong to the track.
     */
    public function djs()
    {
        return $this->belongsToMany(DJ::class, 'dj_track', 'track_id', 'dj_id')->withPivot('price');
    }

    /**
     * Get the orders for the track.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}