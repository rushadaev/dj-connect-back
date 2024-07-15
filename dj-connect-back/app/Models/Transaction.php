<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    
    protected $fillable = [
        'order_id', 'amount', 'status', 'payment_url'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function cancel()
    {
        if ($this->status === self::STATUS_PENDING) {
            $this->status = self::STATUS_CANCELLED;
            $this->save();
        } else {
            throw new \Exception('Only pending transactions can be cancelled');
        }
    }
}