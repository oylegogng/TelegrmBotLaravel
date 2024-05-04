<?php

namespace App\Telegram;

use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Stringable;
use App\Models\File;
class Handler extends WebhookHandler
{
    public function hello(): void
    {
        $this->reply('Hello');
    }

    public function sendImage($url, $image_path): mixed
    {
        try {
            // Подготовка данных для отправки
            $image_data = file_get_contents($image_path);
            if ($image_data === false) {
                throw new \Exception("Не удалось получить содержимое файла изображения: $image_path");
            }

            $base64_image = base64_encode($image_data);
            $data = array('image' => $base64_image);

            // Настройка запроса
            $options = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data)
                )
            );

            $context = stream_context_create($options);

            // Отправка запроса на второй сервис
            $result = file_get_contents($url, false, $context);
            if ($result === false) {
                throw new \Exception("Не удалось отправить запрос на URL: $url");
            }

            // Обработка ответа
            $response = json_decode($result, true);
            if (!isset($response['success']) || $response['success'] !== true) {
                throw new \Exception("Ошибка при обработке ответа от второго сервиса: " . json_encode($response));
            }

            return $response;
        } catch (\Exception $e) {
            error_log("Ошибка при отправке изображения: " . $e->getMessage());
            return $response;
        }
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        if($text->value() == '/start')
        {

            $this->reply('Hello bruh');
        }
        else if($text->value() == '/test')
        {

        }
        else{
            $this->reply('bullshit');
        }
    }



    protected function handleChatMessage(Stringable $text): void
    {
        $photo = collect($this->message->photos())->last();
        $file_name = (string) $this->message->id();
        Telegraph::store($photo, Storage::path('incoming/photos'), $file_name.'.jpg');
        $this->reply('ok');
        $file = new File();
        $file->file_path = 'incoming/photos/' . $file_name;
        $file->user_name = (string)$this->chat->chat_id;
        $file->save();

        // URL второго сервиса, который принимает картинку
        $receiver_url = 'https://c51e-91-132-107-157.ngrok-free.app/receive';

        // Путь к файлу с изображением, которое нужно отправить
        $image_path = '/var/www/tgbot/storage/app/incoming/photos/'.$file_name.'.jpg';

        // Отправка картинки на второй сервис
        $response =  $this->sendImage($receiver_url, $image_path);

        // Обработка ответа
        if ($response['parsedText'] == null) {
            $this->reply('eror');
        } else {
            $this->reply($response['parsedText']);
        }


    }
}
