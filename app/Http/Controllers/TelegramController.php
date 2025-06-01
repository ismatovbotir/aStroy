<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\telegram;
use App\Models\Request as RequestMessages;
use App\Models\RequestItem;
use Illuminate\Support\Facades\Http;


class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $data = $request->all();
        $this->sendMessage('1936361', $data['message']['text'] . ":" . $data['message']['from']['id']);

        // 1. Идентификатор пользователя
        $chatId = $data['message']['from']['id'] ?? null;
        $text = $data['message']['text'] ?? '';
        $contact = $data['message']['contact'] ?? null;

        if (!$chatId) return response('No chat ID', 200);

        // 2. Обработка /start
        if ($text === '/start') {
            $this->start($chatId);
            return response('ok', 200);
        }

        // 3. Контакт (через кнопку "Поделиться контактом")
        if ($contact) {
            return $this->contact($chatId, $contact);
        }

        // 4. Нажата "Создать заявку"
        if ($text === '📝 Создать заявку') {
            return $this->startRequest($chatId);
        }

        // 5. Нажата "✅ Завершить заявку"
        if ($text === '✅ Завершить заявку') {
            return $this->finishRequest($chatId);
        }

        // 6. Обычное сообщение
        return $this->message($chatId, $text);
    }
    private function sendMessage($chatId, $text, $replyMarkup = null)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        Http::post($url, $payload);
        return response('ok');
    }

    public function start($chatId)
    {
        $user = telegram::firstOrCreate(
            ['id' => $chatId],
            ['status' => 'pending']
        );

        $text = "Добро пожаловать! Пожалуйста, поделитесь своим контактом с помощью кнопки ниже.";
        $keyboard = [
            'keyboard' => [
                [[
                    'text' => '📱 Поделиться контактом',
                    'request_contact' => true
                ]]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true
        ];

        $this->sendMessage($chatId, $text, $keyboard);

        return response('ok');
    }

    public function startRequest($chatId)
    {
        $user = telegram::where("id", $chatId)->first();
        if (!$user) {
            return response('User not found', 200);
        }

        $text = "Пожалуйста, опишите вашу заявку.";
        $keyboard = [
            'keyboard' => [
                [['text' => '✅ Завершить заявку']]
            ],
            'resize_keyboard' => true
        ];

        $this->sendMessage($chatId, $text, $keyboard);

        return response('ok');
    }

    public function finishRequest($chatId)
    {
        $user = telegram::find($chatId);
        if (!$user) {
            return response('ok', 200);
        }

        // Получаем все черновики для этого пользователя
        $requestItems = RequestItem::where('telegram_id', $chatId)->whereNull('request_id')->get();

        if ($requestItems->isEmpty()) {
            $this->sendMessage($chatId, "У вас нет черновиков для завершения.");
            return response('ok', 200);
        }

        // Обрабатываем каждый черновик
        $newOrder = RequestMessages::create([
            'telegram_id' => $chatId

        ]);
        RequestItem::where('telegram_id', $chatId)
            ->whereNull('request_id')
            ->update(['request_id' => $newOrder->id]);


        $text = "Ваши заявки успешно созданы! #" . $newOrder->id;
        $keyboard = [
            'keyboard' => [
                [['text' => '📝 Создать заявку']]
            ],
            'resize_keyboard' => true
        ];

        $this->sendMessage($chatId, $text, $keyboard);

        return response('ok', 200);
    }

    public function contact($chatId, $contact)
    {
        $user = telegram::find($chatId);
        if ($user) {
            $user->update([
                'phone' => $contact['phone_number'],


                'status' => 'approved',
            ]);
        } else {
            // Если пользователь не найден — можно создать нового (по желанию)
            $user = telegram::create([
                'id' => $chatId,
                'phone' => $contact['phone_number'],
                'status' => 'approved',
            ]);
        }

        $text = "Вы успешно зарегистрированы как прораб. Нажмите кнопку ниже, чтобы создать заявку.";
        $keyboard = [
            'keyboard' => [
                [['text' => '📝 Создать заявку']]
            ],
            'resize_keyboard' => true
        ];

        $this->sendMessage($chatId, $text, $keyboard);
        return response('ok', 200);
    }

    public function message($chatId, $text)
    {
        $user = telegram::find($chatId);
        if (!$user) {
            //$this->sendMessage($chatId, "no user found");

            return response('ok', 200);
        }

        // Сохраняем сообщение в базу данных
        try {
            RequestItem::create([
                'telegram_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Exception $e) {
            $this->sendMessage($chatId, "Ошибка при сохранении сообщения: " . $e->getMessage());
            return response('ok', 200);
        }


        // Ответ пользователю
        //$this->sendMessage($chatId, "Ваше сообщение сохранено: {$text}");

        return response('ok', 200);
    }
}
