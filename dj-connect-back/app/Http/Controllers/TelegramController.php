<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TelegramBot\Api\Client;
use TelegramBot\Api\Exception;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TransactionController;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class TelegramController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $bot = new Client(config('telegram.bot_token'));

        $this->handleCommands($bot);
        $this->handleMessages($bot);

        try {
            $bot->run();
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function handleCommands(Client $bot)
    {
        $bot->command('ping', function ($message) use ($bot) {
            $bot->sendMessage($message->getChat()->getId(), 'pong!');
        });
        
        $bot->command('tracks', function ($message) use ($bot) {
            $chatId = $message->getChat()->getId();
            $params = explode(' ', $message->getText(true));
        
            if (count($params) <= 1) {
                $bot->sendMessage($chatId, '❌ Добавьте к команде ID диджея. Например: /tracks 1');
                return;
            }
        
            $djId = intval($params[1]);
            if (!$djId) {
                $bot->sendMessage($chatId, '❌ Неверный ID диджея.');
                return;
            }
            Cache::put($chatId, ['dj_id' => $djId], now()->addMinutes(5));
        
            // Fetch tracks of the DJ
            $djController = new DJController();
            $response = $djController->getTracks($djId);
            $tracks = $response->getData();
        
            if ($response->status() == 404) {
                $bot->sendMessage($chatId, '❌ DJ не найден.');
                return;
            }
        
            if (empty($tracks)) {
                $bot->sendMessage($chatId, '❌ У данного DJ нет доступных треков.');
                return;
            }
        
            $keyboard = [];
            foreach ($tracks as $track) {
                $priceText = $track->price ? ' | ' . $track->price : '';
                $keyboard[] = [['text' => "{$track->name}{$priceText}", 'callback_data' => 'choose_track_' . $track->id]];
            }
        
            $inlineKeyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup($keyboard);
            $bot->sendMessage($chatId, '🎵 Выберите трек из доступных:', null, false, null, $inlineKeyboard);
        });
    }

    protected function handleMessages(Client $bot)
    {
        $bot->on(function ($update) use ($bot) {
            $message = $update->getMessage();
            $callbackQuery = $update->getCallbackQuery();
            
            if ($message) {
                $chatId = $message->getChat()->getId();
                $text = $message->getText();
            } elseif ($callbackQuery) {
                $chatId = $callbackQuery->getMessage()->getChat()->getId();
                $text = $callbackQuery->getData();
            } else {
                Log::error('Update does not contain a valid message or callback query', ['update' => $update]);
                return;
            }
    
            $this->processSession($chatId, $text, $bot, $update);
    
            $this->processCallbackData($chatId, $text, $bot, $callbackQuery);
        }, function () {
            return true;
        });
    }


    protected function processSession($chatId, $text, Client $bot, $update)
    {
        $session = Cache::get($chatId);
    
        Log::info('Received update', ['update' => $this->getUpdateDetails($update)]);
    
        if ($session) {
            if (isset($session['action'])) {
                switch ($session['action']) {
                    case 'collect_price':
                        $this->collectPrice($chatId, $text, $session, $bot);
                        break;
                    case 'collect_message':
                        $this->collectMessage($chatId, $text, $session, $bot);
                        break;
                    case 'collect_timeslot':
                        $this->collectTimeslot($chatId, $text, $session, $bot);
                        break;
                    case 'collect_decline_message':
                        $this->collectDeclineMessage($chatId, $text, $session, $bot);
                        break;
                    case 'collect_payment_screenshot':
                        Log::info('Processing collect_payment_screenshot action', [
                            'chatId' => $chatId,
                            'session' => $session,
                        ]);
    
                        $message = $update->getMessage();
                        if ($message) {
                            $this->logObjectProperties('Message found in update', $message);
    
                            $photos = $message->getPhoto();
                            if ($photos && count($photos) > 0) {
                                foreach ($photos as $photo) {
                                    $this->logObjectProperties('PhotoSize object', $photo);
                                }
                                $photo = end($photos);
                                Log::info('Photo received for collect_payment_screenshot', [
                                    'photo' => $photo
                                ]);
                                $this->collectPaymentScreenshot($chatId, $photo, $session, $bot);
                            } else {
                                Log::warning('No photo found in message for collect_payment_screenshot', [
                                    'message' => $message
                                ]);
                                $bot->sendMessage($chatId, '📸 Пожалуйста, отправьте скриншот подтверждения оплаты.');
                            }
                        } else {
                            Log::warning('No message found in update for collect_payment_screenshot', [
                                'update' => $this->getUpdateDetails($update)
                            ]);
                            $bot->sendMessage($chatId, '📸 Пожалуйста, отправьте скриншот подтверждения оплаты.');
                        }
                        break;
                    default:
                        Log::warning('Unknown action in session', ['action' => $session['action']]);
                        $bot->sendMessage($chatId, 'Неизвестное действие. Пожалуйста, начните заново.');
                        break;
                }
            } else {
                Log::warning('No action found in session', ['session' => $session]);
            }
        } else {
            Log::info('No active session found for chatId', [
                'chatId' => $chatId,
                'text' => $text
            ]);
        }
    }
    
    protected function getUpdateDetails($update)
    {
        return [
            'update_id' => $update->getUpdateId(),
            'message' => $update->getMessage() ? $this->getMessageDetails($update->getMessage()) : null,
            'callback_query' => $update->getCallbackQuery() ? $this->getCallbackQueryDetails($update->getCallbackQuery()) : null,
            'edited_message' => $update->getEditedMessage() ? $this->getMessageDetails($update->getEditedMessage()) : null,
        ];
    }
    
    protected function getMessageDetails($message)
    {
        return [
            'message_id' => $message->getMessageId(),
            'from' => $message->getFrom() ? $this->getUserDetails($message->getFrom()) : null,
            'chat' => $message->getChat() ? $this->getChatDetails($message->getChat()) : null,
            'date' => $message->getDate(),
            'text' => $message->getText(),
            'photo' => $message->getPhoto(),
            'entities' => $message->getEntities(),
            // Add other relevant fields as needed
        ];
    }
    
    protected function getUserDetails($user)
    {
        return [
            'id' => $user->getId(),
            'is_bot' => $user->isBot(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'username' => $user->getUsername(),
            'language_code' => $user->getLanguageCode(),
        ];
    }
    
    protected function getChatDetails($chat)
    {
        return [
            'id' => $chat->getId(),
            'type' => $chat->getType(),
            'title' => $chat->getTitle(),
            'username' => $chat->getUsername(),
            'first_name' => $chat->getFirstName(),
            'last_name' => $chat->getLastName(),
        ];
    }
    
    protected function getCallbackQueryDetails($callbackQuery)
    {
        return [
            'id' => $callbackQuery->getId(),
            'from' => $callbackQuery->getFrom() ? $this->getUserDetails($callbackQuery->getFrom()) : null,
            'message' => $callbackQuery->getMessage() ? $this->getMessageDetails($callbackQuery->getMessage()) : null,
            'inline_message_id' => $callbackQuery->getInlineMessageId(),
            'chat_instance' => $callbackQuery->getChatInstance(),
            'data' => $callbackQuery->getData(),
            'game_short_name' => $callbackQuery->getGameShortName(),
        ];
    }
    
    protected function logObjectProperties($title, $object)
    {
        if (is_object($object)) {
            $reflection = new \ReflectionClass($object);
            $properties = $reflection->getProperties();
            $propertyValues = [];
    
            foreach ($properties as $property) {
                $property->setAccessible(true);
                $propertyValues[$property->getName()] = $property->getValue($object);
            }
    
            Log::info($title, $propertyValues);
        } else {
            Log::info($title, ['object' => $object]);
        }
    }

    protected function collectPrice($chatId, $text, $session, Client $bot)
    {
        $price = floatval($text);
        Cache::put($chatId, ['action' => 'collect_message', 'order_id' => $session['order_id'], 'price' => $price, 'status' => Order::STATUS_PRICE_CHANGED], now()->addMinutes(5));
        $bot->sendMessage($chatId, '📨 Добавьте сообщение к заказу');
    }

    protected function collectMessage($chatId, $text, $session, Client $bot)
    {
        $orderId = $session['order_id'] ?? null;
        $price = $session['price'] ?? null;
        $status = $session['status'] ?? null;

        $this->acceptOrder($orderId, $price, $text, $status);
        Cache::forget($chatId);
    }

    protected function collectDeclineMessage($chatId, $text, $session, Client $bot)
    {
        $orderId = $session['order_id'];
        $this->declineOrder($orderId, $text);
        Cache::forget($chatId);
    }

    protected function collectTimeslot($chatId, $text, $session, Client $bot)
    {
        // Validate time format (e.g., HH:MM)
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $text)) {
            $orderId = $session['order_id'];

            // Save the time slot to the order
            Order::where('id', $orderId)->update(['time_slot' => now()->format('Y-m-d') . ' ' . $text]);

            $bot->sendMessage($chatId, "🕒 Время $text успешно сохранено для заказа #$orderId.");
            
            // Clear session
            Cache::forget($chatId);
        } else {
            $bot->sendMessage($chatId, '⛔️ Неверный формат времени. Пожалуйста, введите время в формате HH:MM (например, 21:00).');
        }
    }

    protected function processEnterTimeslot($chatId, $text, Client $bot)
    {
        $orderId = str_replace('enter_timeslot_', '', $text);
        // Cache order id to collect time slot
        Cache::put($chatId, ['action' => 'collect_timeslot', 'order_id' => $orderId], now()->addMinutes(5));
        $bot->sendMessage($chatId, '🕒 Пожалуйста, введите время для трека (например, 21:00)');
    }

    protected function processCallbackData($chatId, $text, Client $bot, $callbackQuery)
    {
        if (strpos($text, 'choose_track_') !== false) {
            $trackId = str_replace('choose_track_', '', $text);
            $this->createOrderFromTrackSelection($chatId, $trackId, $bot);
        } elseif (strpos($text, 'accept_') !== false) {
            $this->processAccept($chatId, $text, $bot);
        } elseif (strpos($text, 'decline_') !== false) {
            $this->processDecline($chatId, $text, $bot);
        } elseif (strpos($text, 'cancel_') !== false) {
            $this->processCancel($chatId, $text, $bot, $callbackQuery);
        } elseif (strpos($text, 'change_price_') !== false) {
            $this->processChangePrice($chatId, $text, $bot);
        } elseif (strpos($text, 'pay_') !== false) {
            $this->processPayment($chatId, $text, $bot);
        } elseif (strpos($text, 'confirm_payment_') !== false) {
            $this->confirmPayment($chatId, $text, $bot);
        } elseif (strpos($text, 'enter_timeslot_') !== false) {
            $this->processEnterTimeslot($chatId, $text, $bot);
        } elseif (strpos($text, 'finish_') !== false) {
            $this->processFinish($chatId, $text, $bot);
        }
    }

    protected function createOrderFromTrackSelection($chatId, $trackId, Client $bot)
    {
        $orderController = new OrderController();
        $djId = Cache::get($chatId)['dj_id'] ?? 1;
        
        $request = new Request([
            'dj_id' => $djId,
            'telegram_id' => $chatId,
            'track_id' => $trackId,
            'message' => '',
        ]);
    
        $response = $orderController->createOrder($request);
        if ($response->status() == 200) {
            
        } else {
            $bot->sendMessage($chatId, '❌ Произошла ошибка при созданиие заказа, попробуйте еще раз.');
        }
    }

    protected function processAccept($chatId, $text, Client $bot)
    {
        $orderId = str_replace('accept_', '', $text);
        // Cache order id to collect price and message
        Cache::put($chatId, ['action' => 'collect_message', 'order_id' => $orderId], now()->addMinutes(5));
        $bot->sendMessage($chatId, '📨 Добавьте сообщение к заказу');
    }
    
    protected function processFinish($chatId, $text, Client $bot)
    {
        $orderId = str_replace('finish_', '', $text);

        $order = Order::find($orderId);
    
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
    
        // Set the track as played
        $order->track_played = true;
        $order->status = 'completed'; // Update status to 'completed' or other appropriate status
    
        $order->save();
        $this->thankClient($order, $bot);
    
        return response()->json(['order' => $order]);
         
    }

    protected function thankClient(Order $order, $bot)
    {
        // Получаем название трека
        $trackName = $order->track->name;

        $telegram_id = $order->user->telegram_id;

        $webAppDirectUrl = config('webapp.direct_url');
        $tgWebAppUrl = "{$webAppDirectUrl}?startapp=dj_{$order->dj_id}";
        // Клавиатура для пользователя
        $userKeyboard = new InlineKeyboardMarkup([
            [['text' => '️🎧Заказать еще', 'url' => $tgWebAppUrl]],
        ]);

        // Отправляем благодарность клиенту
        if ($telegram_id) {
            $bot->sendMessage($telegram_id, "🙏 Спасибо за ваш заказ! Трек \"{$trackName}\" для заказа #{$order->id} был сыгран.", null, false, null, $userKeyboard);
            Log::info("Благодарность отправлена клиенту для заказа {$order->id}"); 
        }
    }

    protected function processDecline($chatId, $text, Client $bot)
    {
        $orderId = str_replace('decline_', '', $text);
        Cache::put($chatId, ['action' => 'collect_decline_message', 'order_id' => $orderId], now()->addMinutes(5));
        $bot->sendMessage($chatId, '📨 Добавьте сообщение к заказу');
    }

    protected function processCancel($chatId, $text, Client $bot, $callbackQuery)
    {
        $orderId = str_replace('cancel_', '', $text);
        $this->cancelOrder($orderId);

        //Удаляем сообщение о созданном заказе у пользователя
        $bot->deleteMessage($chatId, $callbackQuery->getMessage()->getMessageId());
    }

    protected function processChangePrice($chatId, $text, Client $bot)
    {
        $orderId = str_replace('change_price_', '', $text);
        Cache::put($chatId, ['action' => 'collect_price', 'order_id' => $orderId], now()->addMinutes(5));
        $bot->sendMessage($chatId, '🤑 Введите новую цену (только цифры)');
    }

    protected function processPayment($chatId, $text, Client $bot)
    {
        $orderId = str_replace('pay_', '', $text);
        $order = Order::find($orderId);
    
        if (!$order) {
            $bot->sendMessage($chatId, '❌ Заказ не найден.');
            return;
        }
    
        $paymentDetails = $order->getDjPaymentDetails();
    
        if (!$paymentDetails) {
            $bot->sendMessage($chatId, '❌ Не удалось получить информацию о платеже.');
            return;
        }
    
        $amount = $paymentDetails['amount'];
        $paymentUrl = $paymentDetails['payment_url'];
        $djPaymentDetails = $paymentDetails['payment_details'];
    
        $message = "💵 Пожалуйста, оплатите сумму: {$amount}\n\n";
        $message .= "📅 Детали платежа:\n";
        $message .= "{$djPaymentDetails}\n\n";
        $message .= "💻 Перейдите по ссылке для оплаты: [Оплатить]({$paymentUrl})\n\n";
        $message .= "📸 После оплаты, пожалуйста, отправьте скриншот подтверждения оплаты.";
    
        Cache::put($chatId, ['action' => 'collect_payment_screenshot', 'order_id' => $orderId], now()->addMinutes(5));
        $bot->sendMessage($chatId, $message, 'Markdown');
    }

    protected function collectPaymentScreenshot($chatId, $photo, $session, Client $bot)
    {
        $orderId = $session['order_id'];
        // Notify DJ with "Принять" button and send the photo
        $telegramIds = Order::find($orderId)->getTelegramIds();
        $djChatId = $telegramIds['dj'];
    
        // Send the photo to the DJ
        $bot->sendPhoto($djChatId, $photo->getFileId(), "📸 Пользователь отправил скриншот подтверждения оплаты для заказа #заказ_{$orderId}");
    
        // Send a message with "Принять" button
        $this->notifyWithButton($djChatId, "📸 Если вы подтверждаете, оплату, нажмите принять. #заказ_{$orderId}", 'Принять', 'confirm_payment_' . $orderId);
    
        // Clear the cache for the user
        Cache::forget($chatId);
    }

    protected function confirmPayment($chatId, $text, Client $bot)
    {
        $orderId = str_replace('confirm_payment_', '', $text);

        // Process the payment confirmation
        $transactionController = new TransactionController();
        $transactionController->markTransactionPaid($orderId);

        $telegramIds = Order::find($orderId)->getTelegramIds();
        // Notify the DJ that the payment is confirmed
        $bot->sendMessage($chatId, "✅ Оплата для заказа #заказ_{$orderId} подтверждена.");
        // Notify the user that the payment is confirmed
        
        $this->notify($telegramIds['user'], "✅ Ваша оплата для заказа #заказ_{$orderId} подтверждена.\nВ течение 15 минут диджей поставит ваш трек.\nБлагодарим за заказ.");
    }

    protected function getOrder($orderId)
    {
        $order = Order::where('id', $orderId)->first();
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        return $order;
    }

    protected function acceptOrder($orderId, $price, $message, $status)
    {
        $order = $this->getOrder($orderId);
        $telegramIds = $order->getTelegramIds();
        
        // Check if the price is null and use the order's price if it is
        $price = $price ?? $order->price;
        $status = $status ?? Order::STATUS_ACCEPTED;
        $request = new Request([
            'price' => $price,
            'message' => $message,
        ]);
    
        $orderController = new OrderController();
        $orderController->acceptOrder($request, $orderId);
    
        $messageTitle = $status === Order::STATUS_ACCEPTED ? '🎉 Заказ принят' : '💰 Цена изменена';
    
        // Notification of the user implemeted in OrderController
        // $this->notifyWithButton($telegramIds['user'], "{$messageTitle}\nВаш #заказ_{$orderId} принят с ценой: {$price}. Ожидаем оплаты.", 'Оплатить', 'pay_' . $orderId);
    
        // Notify DJ
        $this->notify($telegramIds['dj'], "{$messageTitle}\nЗаказ #заказ_{$orderId} принят с ценой: {$price}\nСообщение: {$message}");
    }
    protected function notifyWithButton($telegramId, $message, $buttonText, $callbackData)
    {
        $bot = new Client(config('telegram.bot_token'));
        $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([
            [['text' => $buttonText, 'callback_data' => $callbackData]]
        ]);
        $bot->sendMessage($telegramId, $message, null, false, null, $keyboard);
    }

    protected function declineOrder($orderId, $message)
    {
        $order = $this->getOrder($orderId);
        $telegramIds = $order->getTelegramIds();

        $request = new Request([
            'message' => $message,
        ]);

        $orderController = new OrderController();
        $orderController->declineOrder($request, $orderId);

        // Notify User
        $this->notify($telegramIds['user'], "Ваш заказ #заказ_{$orderId} отклонён с сообщением: {$message}");

        // Notify DJ
        $this->notify($telegramIds['dj'], "Вы отменили заказ #заказ_{$orderId} отклонён с сообщением: {$message}");
    }

    protected function cancelOrder($orderId)
    {
        $order = $this->getOrder($orderId);
        $telegramIds = $order->getTelegramIds();

        $orderController = new OrderController();
        $orderController->cancelOrder($orderId);

        // Notify User
        $this->notify($telegramIds['user'], "Ваш заказ #заказ_{$orderId} отменен.");
        // Notify DJ
        $this->notify($telegramIds['dj'], "Заказ #заказ_{$orderId} отменен пользователем.");
    }

    protected function notify($telegramId, $message)
    {
        $bot = new Client(config('telegram.bot_token'));
        $bot->sendMessage($telegramId, $message);
    }
}