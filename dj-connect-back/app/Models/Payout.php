<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



/**
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="errors", type="object")
 * )
 */
class Payout extends Model
{
    use CrudTrait;
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSED = 'processed';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'dj_id',
        'amount',
        'status',
        'payout_type',
        'payout_details',
        'yookassa_payout_id',
        'processed_at',
    ];
}