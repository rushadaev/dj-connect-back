<?php

namespace App\Services;

use YooKassa\Client;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Notification\NotificationCanceled;
use YooKassa\Model\Notification\NotificationRefundSucceeded;
use YooKassa\Model\Notification\NotificationEventType;
use App\Modles\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

    public function createPayout(array $amount, array $payoutDestination, string $description)
    {
        // Assume $this->client is your initialized YooKassa client
        $response = $this->client->createPayout([
            'amount' => $amount,
            'payout_destination_data' => $payoutDestination,
            'description' => $description,
            'metadata' => [
                'order_id' => $description,
            ],
        ]);

        return $response;
    }

    public function getSbpBanks()
    {
        // Mocked response data
        return (object) [
            'items' => [
                (object) [
                    'id' => '100000000111',
                    'name' => 'Сбербанк',
                    'bic' => '044525225',
                ],
            ],
        ];
        return $this->client->getSbpBanks();
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

    public function handleWebhook($requestBody)
    {
        try {
            switch ($requestBody['event']) {
                case NotificationEventType::PAYMENT_SUCCEEDED:
                    $notification = new NotificationSucceeded($requestBody);
                    break;
                case NotificationEventType::PAYMENT_WAITING_FOR_CAPTURE:
                    $notification = new NotificationWaitingForCapture($requestBody);
                    break;
                case NotificationEventType::PAYMENT_CANCELED:
                    $notification = new NotificationCanceled($requestBody);
                    break;
                case NotificationEventType::REFUND_SUCCEEDED:
                    $notification = new NotificationRefundSucceeded($requestBody);
                    break;
                default:
                    throw new \Exception('Unknown event type');
            }

            // Get the payment object
            $payment = $notification->getObject();

            return $payment;
        } catch (\Exception $e) {
            // Handle errors if data is invalid
            Log::error('Webhook processing error', ['error' => $e->getMessage()]);
            return null;
        }
    }
}