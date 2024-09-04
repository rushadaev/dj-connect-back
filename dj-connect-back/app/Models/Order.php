<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Events\OrderUpdated;
use App\Events\OrderCreated;

class Order extends Model
{
    use CrudTrait;
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PRICE_CHANGED = 'price_changed';
    
    protected $dispatchesEvents = [
        'updated' => OrderUpdated::class,
        'created' => OrderCreated::class,
    ];
    

    protected $fillable = [
        'user_id', 'dj_id', 'track_id', 'price', 'message', 'status', 'timezone', 'time_slot', 'reminder_sent', 'notification_sent', 'track_played'
    ];

    protected $appends = [
        'is_paid',
    ];

    protected $with = ['transactions', 'track:id,name', 'dj'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dj()
    {
        return $this->belongsTo(DJ::class);
    }

    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Create a transaction for the order.
     *
     * @param float $amount
     * @return Transaction
     * @throws \Exception
     */
    public function createTransaction($amount, $yookassa)
    {
        // Check if there's already a pending transaction and cancel it
        $existingTransaction = $this->transactions()->where('status', Transaction::STATUS_PENDING)->first();
    
        if ($existingTransaction) {
            $existingTransaction->cancel();
        }
    
        // Set transaction data
        $orderId = $this->id;
    
        // Generate URL to the Laravel route that will generate the payment link
        $paymentUrl = route('generate.payment.link', ['orderId' => $orderId, 'amount' => $amount]);
    
        // Create a new transaction
        return Transaction::create([
            'order_id' => $orderId,
            'amount' => $amount,
            'payment_url' => $paymentUrl,
            'status' => Transaction::STATUS_PENDING,
        ]);
    }

    /**
     * Check if the order is marked as paid by paid transaction.
     *
     * @return bool
     */
    public function getIsPaidAttribute()
    {
        return $this->transactions()->where('status', Transaction::STATUS_PAID)->exists();
    }

    /**
     * Cancel the order and all pending transactions.
     */
    public function cancel()
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();

        // Cancel all pending transactions
        foreach ($this->transactions()->where('status', Transaction::STATUS_PENDING)->get() as $transaction) {
            $transaction->cancel();
        }
    }

    public function getDjTelegramIdAttAttribute()
    {
        return $this->dj->telegram_id;
    }
    public function getUserTelegramIdAttAttribute()
    {
        return $this->user->telegram_id;
    }

    public function getTelegramIds()
    {
        return [
            'dj' => $this->dj->telegram_id,
            'user' => $this->user->telegram_id
        ];
    }

    public function getDjPaymentDetails()
    {
        return [
            'amount' => $this->price,
            'payment_url' => $this->transactions()->where('status', Transaction::STATUS_PENDING)->first()->payment_url,
            'payment_details' => $this->dj->payment_details,
        ];
    }
}