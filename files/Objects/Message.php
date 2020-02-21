<?php
namespace phgram\Objects;
class Message extends \phgram\Objects\Base {
	public $arguments;
	
	public function __construct($data, \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['message_id' => $this->message_id, 'chat_id' => $this->chat['id']];
	}
	
	# shortcuts
	public function append($text, ...$params) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['chat_id' => $this->chat->id, 'disable_web_page_preview' => TRUE, 'text' => $text, 'message_id' => $this->message_id, 'parse_mode' => $this->bot->default_parse_mode];
		$params = array_replace($default, \phgram\join_arguments($params));
		$params['text'] = $this->text.$text;
		$entities = isset($this->entities)? $this->entities->asArray() : [];
		if (strtolower($params['parse_mode']) == 'html') {
			$params['text'] = \phgram\entities_to_html($this->text, $entities).$text;
		} else if (strtolower($params['parse_mode']) == 'markdown') {
			$params['text'] = \phgram\entities_to_markdown($this->text, $entities).$text;
		}
		
		$call = $this->bot->editMessageText($params);
		#$this->__construct($call->json, $params, $this->bot);
		return $call;
	}
	
	public function reply($text, ...$params) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['chat_id' => $this->chat->id, 'disable_web_page_preview' => TRUE, 'text' => $text, 'reply_to_message_id' => $this->message_id, 'parse_mode' => $this->bot->default_parse_mode];
		$params = array_replace($default, \phgram\join_arguments($params));
		$params['text'] = $text;
		return $this->bot->sendMessage($params);
	}
	
	public function forward($chat_id, ...$params) {
		if (!isset($this->chat->id) || !isset($this->message_id)) return false;
		$default = ['from_chat_id' => $this->chat->id, 'chat_id' => $chat_id, 'message_id' => $this->message_id];
		$params = array_replace($default, \phgram\join_arguments($params));
		$result = [];
		if (is_array($chat_id)) {
			foreach ($chat_id as $id) {
				$params['chat_id'] = $id;
				$result[] = $this->bot->forwardMessage($params);
			}
		}
		if (count($result) == 1) {
			return $result[0];
		} else {
			return $result;
		}
	}
	
	public function delete() {
		return $this->bot->deleteMessage($this->arguments);
	}
}