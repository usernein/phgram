<?php
# with inline keyboard
include 'phgram.phar';
use \phgram\Bot;
use function \phgram\ikb;

$bot = new Bot('TOKEN');

$offset = 0;
while (true) {
	$updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 300])['result'];
	foreach ($updates as $update) {
		$bot->setData($update);
		
		$type = $bot->getUpdateType();

		if ($type == 'callback_query') {
			$query = $bot->CallbackQuery();
			$data = $query['data'];
			$id = $query['id'];
			
			if ($data == 'button 1') {
				$bot->answerCallbackQuery(['callback_query_id' => $id, 'text' => "Hey! This is the first button!"]);
				# or:
				# $bot->answer_callback("Hey! This is the first button!");
			} elseif ($data == 'button 2') {
				$bot->answerCallbackQuery(['callback_query_id' => $id, 'text' => "Hey! This is the second button!", 'show_alert' => TRUE]);
			} elseif ($data == 'button 3') {
				$bot->answerCallbackQuery(['callback_query_id' => $id, 'text' => "Hey! This is the third button!"]);
				$bot->sendMessage(['chat_id' => $bot->ChatID(), 'text' => "Button 3 pressed!"]);
			}
		} else if ($type == 'message') {
			$text = $bot->Text();
			$chat_id = $bot->ChatID();
			$user_id = $bot->UserID();
			
			if ($text == '/start') {
				$keyboard = ikb([
					[ ['1', 'button 1'], ['2', 'button 2'], ['3', 'button 3'] ],
					[ ['Me', "t.me/{$bot->getMe()->username}", 'url'] ]
				]);
				$bot->sendMessage(['chat_id' => $chat_id, 'text' => "Hello, {$bot->Name()}!", 'reply_markup' => $keyboard]);
			}
		}
		
		$offset = $update['update_id']+1;
	}
}