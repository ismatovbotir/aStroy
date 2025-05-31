<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\telegram;
use App\Models\Request as RequestMessages;
use App\models\DraftRequest;
use Illuminate\Support\Facades\Http;


class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $data = $request->all();

        // 1. Идентификатор пользователя
        $chatId = $data['message']['from']['id'] ?? null;
        $text = $data['message']['text'] ?? '';
        $contact = $data['message']['contact'] ?? null;

        if (!$chatId) return response('No chat ID', 400);

        // 2. Обработка /start
        if ($text === '/start') {
            return $this->start($chatId);
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
        $user = telegram::find($chatId);
        if (!$user) {
            return response('User not found', 404);
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

    public function contact($chatId, $contact)
    {
        $user = telegram::find($chatId);
        if ($user) {
            $user->update([
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
        return response('ok');
    }

    public function message($chatId, $text)
    {
        $user = telegram::find($chatId);
        if (!$user) {
            $this->sendMessage($chatId, "no user found");

            return response('User not found', 404);
        }

        // Сохраняем сообщение в базу данных
        DraftRequest::create([
            'telegram_id' => $chatId,
            'text' => $text,
        ]);

        // Ответ пользователю
        $this->sendMessage($chatId, "Ваше сообщение сохранено: {$text}");

        return response('ok');
    }
}
