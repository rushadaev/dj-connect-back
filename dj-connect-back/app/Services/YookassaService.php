<?php

namespace App\Services;

use YooKassa\Client;
use App\Modles\Order;
use Illuminate\Support\Facades\Cache;
class YooKassaService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuth(config('yookassa.shop_id'), config('yookassa.secret_key'));
    }

    public function createPaymentLink($amount, $orderId, $description)
    {
        $uniqueId = uniqid('', true);

        $payment = $this->client->createPayment(
            [
                'amount' => [
                    'value' => $amount,
                    'currency' => 'RUB',
                ],
                'confirmation' => [
                    'type' => 'redirect',
                    'return_url' => route('payment.return', ['orderId' => $orderId]),
                ],
                'capture' => true,
                'description' => $description,
                'metadata' => [
                    'order_id' => $orderId,
                ],
            ],
            $uniqueId
        );

        // Store payment ID in cache with the order ID as the key
        Cache::put("payment_id_{$orderId}", $payment->getId(), now()->addMinutes(60));

        return $payment->getConfirmation()->getConfirmationUrl();
    }

    public function listOrders($id)
    {
        return Order::where('user_id', $id)->get();
    }

    public function createPayout($amount, $accountId, $description)
    {
        $payout = $this->client->createPayout(
            [
                'amount' => [
                    'value' => $amount,
                    'currency' => 'RUB',
                ],
                'payout_destination_data' => [
                    'type' => 'yoo_money',
                    'account_number' => $accountId,
                ],
                'description' => $description,
            ]
        );

        return $payout;
    }

    public function listPayouts()
    {
        // You can filter and paginate payouts as needed
        return $this->client->getPayouts([]);
    }

    public function retrievePayment($paymentId)
    {
        return $this->client->getPaymentInfo($paymentId);
    }
}