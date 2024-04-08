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

    protected function handleUnknownCommand(Stringable $text): void
    {
        if($text->value() == '/start')
        {
            $this->reply('Hello bruh');
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
    }
}
