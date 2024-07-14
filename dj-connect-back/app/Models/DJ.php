<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *      schema="DJ",
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="user_id", type="integer", example=1),
 *      @OA\Property(property="stage_name", type="string", example="DJ Example"),
 *      @OA\Property(property="city", type="string", example="New York"),
 *      @OA\Property(property="base_prices", type="string", example="100"),
 *      @OA\Property(property="payment_details", type="string", example="Bank details or any payment information"),
 *      @OA\Property(property="created_at", type="string", format="date-time"),
 *      @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class DJ extends Model
{
    use HasFactory;
    protected $table = 'djs';

    protected $fillable = [
        'user_id',
        'stage_name',
        'city',
        'base_prices',
        'payment_details',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
