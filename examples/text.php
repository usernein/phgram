<?php
require 'phgram.phar';
use \usernein\phgram\Bot;
use function \usernein\phgram\ikb;

$bot = new Bot('TOKEN');

function handler($bot, $message) {
    $text = $message->text;
    $chat_id = $message->ChatID;

    if ($text == '/start') {
        $keyboard = ikb([
            [ ['phgram', 'https://github.com/usernein/phgram', 'url'] ]
        ]);
        $bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!', 'reply_markup' => $keyboard]);
        # or using the shortcut:
        #$message->reply('Hello World!', ['reply_markup' => $keyboard]);
    }
    else if ($text == "/help") {
        $message->reply("Help!");
    }
}

# For polling
$bot->loop('handler');

# For webhooks
#handler($bot, $bot->update);