<?php
# with callback_query handling
include 'phgram.phar';
use \phgram\Bot;
use function \phgram\ikb;

$bot = new Bot('TOKEN');

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