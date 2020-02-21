<?php
/**
 * Class to help Telegram bot development with PHP.
 *
 * Based on TelegramBotPHP (https://github.com/Eleirbag89/TelegramBotPHP)
 *
 * @author Cezar Pauxis (https://t.me/usernein)
 * @license https://github.com/usernein/phgram/blob/master/LICENSE
*/
namespace phgram;
class Bot {
	# The bot token
	private $bot_token = '';
	
	# The array of the update
	private $data = [];
	
	# Log that will be sent when the object gets destructed
	public $final_log;
	
	# Type of the current update
	private $update_type = '';
	
	# Values for error reporting
	public $debug = FALSE;
	public $debug_admin;
	
	# Execution properties
	public $report_mode = 'message';
	public $report_show_view = 1;
	public $report_show_data = 1;
	public $report_obey_level = 1;
	public $default_parse_mode = 'HTML';
	public $report_max_args_len = 300;
	
	# cURL connection handler
	private $ch;
	
	# path of the running phgram.phar
	private $path = '.';
	
	# MethodResult of the last method result
	public $lastResult;
	
	# Data type for getters
	public $data_type = 'ArrayObj';
	
	# Used by parseResult. This array define to which object should the array be converted, based on its key.
	public static $objects_keys = [
		'message' => 'Message',
		'poll' => 'Poll',
		'edited_message' => 'Message',
		'channel_post' => 'Message',
		'edited_channel_post' => 'Message',
		'inline_query' => 'InlineQuery',
		'chosen_inline_result' => 'ChosenInlineResult',
		'callback_query' => 'CallbackQuery',
		'shipping_query' => 'ShippingQuery',
		'pre_checkout_query' => 'PreCheckoutQuery',
		'photo' => ['PhotoSize'],
		'pinned_message' => 'Message',
		'from' => 'User',
		'chat' => 'Chat',
		'forward_from' => 'User',
		'forward_from_chat' => 'Chat',
		'reply_to_message' => 'Message',
		'entities' => ['MessageEntity'],
		'caption_entities' => ['MessageEntity'],
		'audio' => 'Audio',
		'document' => 'Document',
		'animation' => 'Animation',
		'game' => 'Game',
		'sticker' => 'Sticker',
		'video' => 'Video',
		'voice' => 'Voice',
		'video_note' => 'VideoNote',
		'contact' => 'Contact',
		'location' => 'Location',
		'new_chat_members' => ['User'],
		'left_chat_member' => 'User',
		'invoice' => 'Invoice',
		'successful_payment' => 'SuccessfulPayment',
		'passport_data' => 'PassportData',
		'user' => 'User',
		'thumb' => 'PhotoSize',
		'photos' => [['PhotoSize']],
		'keyboard' => [['KeyboardButton']],
		'inline_keyboard' => [['InlineKeyboardButton']],
		'callback_game' => 'CallbackGame',
		'mask_position' => 'MaskPosition',
		'stickers' => ['Sticker'],
		'input_message_content' => 'InputMessageContent',
		'reply_markup' => 'InlineKeyboardMarkup',
		'shipping_address' => 'ShippingAddress',
		'prices' => ['LabeledPrice'],
		'order_info' => 'OrderInfo',
		'credentials' => 'EncryptedCredentials',
		'files' => ['PassportFile'],
		'front_side' => 'PassportFile',
		'reverse_side' => 'PassportFile',
		'selfie' => 'PassportFile',
		'text_entities' => ['MessageEntity'],
	];
	# Used by parseResult. This array define to which object should the array be converted, based on its values.
	public static $objects_values = [
		'message_id' => 'Message',
		'update_id' => 'Update',
	];
	# Used by parseResult. This array define to which object should the result array be converted, based on the method name.
	public static $methods_returns = [
		'getUpdates' => ['Update'],
		'getWebhookInfo' => 'WebhookInfo',
		'getMe' => 'User',
		'sendMessage' => 'Message',
		'forwardMessage' => 'Message',
		'sendPhoto' => 'Message',
		'sendAudio' => 'Message',
		'sendDocument' => 'Message',
		'sendVideo' => 'Message',
		'sendVoice' => 'Message',
		'sendVideoNote' => 'Message',
		'sendMediaGroup' => ['Message'],
		'sendLocation' => 'Message',
		'editMessageLiveLocation' => 'Message',
		'stopMessageLiveLocation' => 'Message',
		'sendVenue' => 'Message',
		'sendContact' => 'Message',
		'getUserProfilePhotos' => 'UserProfilePhotos',
		'getFile' => 'File',
		'getChat' => 'Chat',
		'getChatAdministrators' => ['ChatMember'],
		'getChatMember' => 'ChatMember',
		'editMessageText' => 'Message',
		'editMessageCaption' => 'Message',
		'editMessageReplyMarkup' => 'Message',
		'sendSticker' => 'Message',
		'getStickerSet' => 'StickerSet',
		'uploadStickerFile' => 'File',
		'sendInvoice' => 'Message',
		'sendGame' => 'Message',
		'setGameScore' => 'Message',
		'getGameHighScores' => ['GameHighScore'],
		'sendAnimation' => 'Message',
		'editMessageMedia' => 'Message',
		'sendPoll' => 'Message',
		'stopPoll' => 'Poll'
	];
	
	public $shortcuts = [];
	
	public function __construct(string $bot_token, $debug_chat = FALSE) {
		$this->bot_token = $bot_token;
		$this->data = $this->getData();
		$this->update_type = @array_keys($this->data)[1];
		
		if ($debug_chat) {
			$this->debug_admin = $debug_chat;
			$this->debug = TRUE;
		}
		
		# Setting cURl handler
		$this->ch = curl_init();
		$opts = [
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_POST => TRUE,
		];
		curl_setopt_array($this->ch, $opts);
		
		$this->shortcuts['send'] = [
			'method' => 'sendMessage',
			'default_parameters' => [
				'chat_id' => function($bot) { return $bot->ChatID() ?? $bot->UserID(); },
				'parse_mode' => function($bot) { return $bot->default_parse_mode; },
				'disable_web_page_preview' => false,
			],
			'first_parameter' => 'text'
		];
		
		$path = '.';
		if (!empty($pharPath = \Phar::running(false))) {
			$path = dirname($pharPath);
		}
		$this->path = $path;
	}
	
	public function __destruct() {
		if (!is_null($this->final_log)) {
			$this->log($this->final_log);
		}
	}
	
	private function sendAPIRequest(string $url, array $content) {
		$content = array_map(function ($val) {
			if (is_array($val) || (is_object($val) && !($val instanceof \CURLFile))) {
				return json_encode($val);
			}
			return $val;
		}, $content);
		
		$opts = [
			CURLOPT_URL => $url,
			CURLOPT_POSTFIELDS => $content,
		];
		curl_setopt_array($this->ch, $opts);
		$result = curl_exec($this->ch);
		return $result;
	}	
	
	public function __call(string $method, array $arguments = [[]]) {
		global $lastResult;
		if (!$arguments) {
			$arguments = [[]];
		}
		$first_param = $arguments[0];
		$arguments = join_arguments($arguments);
		if (isset($this->shortcuts[$method])) {
			$default = $this->shortcuts[$method]['default_parameters'] ?? [];
			$bot = $this;
			$default = array_map(function($val) use ($bot, $arguments) {
				if (is_callable($val)) {
					return $val($bot, $arguments);
				}
				return $val;
			}, $default);
			$first_param_key = $this->shortcuts[$method]['first_parameter'];
			$called_method = $method;
			$method = $this->shortcuts[$method]['method'];
			$arguments = array_replace($default, $arguments, [$first_param_key => $first_param]);
		}
		#show(compact('method', 'arguments'));
		/*$arguments = array_filter($arguments, function ($val) {
			return $val != null;
		});*/
		if (isset($arguments['inline_message_id'])) {
			unset($arguments['chat_id']);
			unset($arguments['message_id']);
		}
		$url = "https://api.telegram.org/bot{$this->bot_token}/{$method}";
		$response = $this->sendAPIRequest($url, $arguments);
		
		if ($response == '{"ok":false,"error_code":401,"description":"Unauthorized"}') {
			throw new Error('Unauthorized token');
		}
		$object = self::$methods_returns[$method] ?? null;
		$bot = $this;
		$result = $this->lastResult = new MethodResult($response, $arguments, $bot, $object, $method);
		if (!$result['ok'] && $this->debug && ($this->report_obey_level xor error_reporting() <= 0)) {
			$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
			$debug = $debug[1] ?? $debug[0] ?? $debug;
			$class = @$debug['class'];
			$type = @$debug['type'];
			$function = ($class? 'method ' : 'function ').$class.$type.$debug['function'];
			$debug['method'] = $method;
			
			$args = htmlspecialchars(json_encode($arguments, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
			$error_info = "Error thrown by the method {$debug['method']}, in {$debug['file']} on line {$debug['line']}, while calling the {$function}";
			$text_log = "{$result->json}\n\n{$error_info}\n\nArguments: {$args}";
			if ($this->report_show_data) {
				$data = $this->data;
				$type = @array_keys($data)[1];
				$data = @array_values($data)[1];
				if ($data) {
					$max_len = $this->report_max_args_len;
					$data = array_map(function($item) use ($max_len) {
						if (is_string($item)) {
							return substr($item, 0, $max_len);
						}
					}, $data);
					$text = $data['data'] ?? $data['query'] ?? $data['text'] ?? $data['caption'] ?? $data['result_id'] ?? $type;
		
					$sender = $data['from']['id'] ?? null;
					$sender_name = $data['from']['first_name'] ?? null;
			
					$chat = $data['chat'] ?? $data['message']['chat'] ?? null;
					$chat_id = $chat['id'] ?? null;
					$message_id = $data['message_id'] ?? $data['message']['message_id'] ?? null;
					if ($chat['type'] == 'private') {
						$chat_mention = isset($chat['username'])? "@{$chat['username']}" : "<a href='tg://user?id={$sender}'>{$sender_name}</a>";
					} else {
						$chat_mention = isset($chat['username'])? "<a href='t.me/{$chat['username']}/{$message_id}'>@{$chat['username']}</a>" : "<i>{$chat['title']}</i>";
					}
					
					if ($this->report_mode == 'message' && $this->default_parse_mode == 'HTML') {
						$text_log .= htmlspecialchars("\n\n\"{$text}\", ").
							($sender? "sent by <a href='tg://user?id={$sender}'>{$sender_name}</a>, " : '').
							($chat? "in <code>{$chat_id}</code> ({$chat_mention})." : '')." Update type: '{$type}'.";
					} else {
						$chat_mention = isset($chat['username'])? "@{$chat['username']}" : '';
						$text_log .= ("\n\n\"<code>{$text}</code>\", ").
							($sender? "sent by {$sender} ({$sender_name}), " : '').
							($chat? "in {$chat_id} ({$chat_mention})." : '')." Update type: '{$type}'.";
					}
				}
			}
			if ($this->report_show_view) {
				$text_log .= "\n\n". phgram_pretty_debug(2);
			}
			
			if ($this->report_mode == 'message') {
				@$this->log($text_log, 'HTML');
			} else if ($this->report_mode == 'notice') {
				trigger_error($text_log);
			}
		}
		
		if ($this->data_type == 'array') {
			return $result->asArray();
		} else if ($this->data_type == 'object') {
			return (object)$result->asArray();
		}
		return $result;
	}
	
	public static function parseResult($data, \phgram\Bot $Bot = null, $is_child = false) {
		if (!is_iterable($data)) return $data;
		foreach ($data as $key => $value) {
			if (!is_iterable($value)) continue;
			$data[$key] = self::parseResult($data[$key], $Bot, true);
			
			if (isset(self::$objects_keys[$key])) {
				$object = self::$objects_keys[$key];
				
				if (is_string($object)) {
					$class = '\phgram\Objects\\'.$object;
					if (is_array($data[$key]) || is_object($data[$key]))
						$data[$key] = new $class($data[$key], $Bot);
				} else if (is_array($object)) {
					$dimensions = get_level($object);
					$object = array_flatten($object)[0];
					$classify = function ($val) use ($Bot, $object) {
						$class = '\phgram\Objects\\'.$object;
						if (is_array($val) || is_object($val))
							return (new $class($val, $Bot));
						else return $val;
					};
					$data[$key] = apply_to_level($data[$key], $classify, $dimensions);
				}
			} else if (array_diff(array_keys(self::$objects_values), array_keys($data[$key] instanceof ArrayObj? $data[$key]->asArray() : (array)$data[$key])) != count(self::$objects_values)) {
				foreach (self::$objects_values as $unique_key => $object_name) {
					if (isset($data[$key][$unique_key])) {
						$class = '\phgram\Objects\\'. $object_name;
						$data[$key] = (new $class($data[$key], $Bot));
						break;
					}
				}
			}
		}
		
		if (!$is_child && !($data instanceof \phgram\Objects\Base) && count(array_diff(array_keys(self::$objects_values), array_keys(($data instanceof ArrayObj? $data->asArray() : (array)$data)))) != count(self::$objects_values)) {
			foreach (self::$objects_values as $unique => $object_name) {
				if (isset($data[$unique]) || isset($data->$unique)) {
					$class = '\phgram\Objects\\'. $object_name;
					$data = (new $class($data, $Bot));
					break;
				}
			}
		}
		return $data;
	}
	
	public function log($value, $parse_mode = null) {
		# using sendAPIRequest to avoid recursion in __call()
		$url = "https://api.telegram.org/bot{$this->bot_token}/sendMessage";
		$text = $value;
		if ($value instanceof Throwable) {
			$text = (string)$value;
		} else if (!is_string($value) && !is_int($value)) {
			ob_start();
			var_dump($value);
			$text = ob_get_contents();
			ob_end_clean();
			
			$text = $text ?? print_r($value, 1) ?? 'undefined';
		}
		$params = ['parse_mode' => $parse_mode, 'disable_web_page_preview' => TRUE, 'text' => $text];
		
		if (mb_strlen($text) > 4096) {
			$logname = 'phlog_'.$this->UpdateID().'.txt';
			
			file_put_contents($logname, $text);
			
			$url = "https://api.telegram.org/bot{$this->bot_token}/sendDocument";
			$params = [];
			$document = curl_file_create(realpath($logname));
			$params['document'] = $document;
		}
		
		if (is_array($this->debug_admin)) {
			foreach ($this->debug_admin as $admin) {
				$params['chat_id'] = $admin;
				$this->sendAPIRequest($url, $params);
			}
		} else {
			$params['chat_id'] = $this->debug_admin;
			$res = $this->sendAPIRequest($url, $params);
			if (!json_decode($res)->ok)
				file_put_contents('phlog_error', $text."\n\n".$res);
		}
		
		if (isset($logname)) unlink($logname);
		return $value;
	}
	
	public function __toString() {
		return json_encode($this->getMe()->result);
	}
	
	public function __get($key) {
		return $this->$key;
	}	
	
	public static function respondWebhook(array $arguments = []) {
		header("Content-Type: application/json");
		echo json_encode($arguments); // send the response
		http_response_code(200);
	}
	
	public static function closeConnection($body = 'OK', $responseCode = 200, $limit = 600){
		// buffer all upcoming output
		ob_start();
		echo "[]\n";
		// get the size of the output
		$size = ob_get_length();
		
		// send headers to tell the browser to close the connection
		header("Content-Length: $size", true, 200);
		header('Connection: close', true, 200);
		
		// flush all output
		ob_end_flush();
		ob_flush();
		flush();
	}
	
	public function download_file($file_id, string $local_file_path = NULL) {
		if ($file_id instanceof ArrayObj) {
			$file_id = $file_id->find('file_id');
		}
		$contents = $this->read_file($file_id);
		if (!$local_file_path) {
			$local_file_path = @$this->getFile(['file_id' => $file_id])->file_name;
		}
		if (!$local_file_path) {
			return false;
		}
		return file_put_contents($local_file_path, $contents);
	}	
	
	public function read_file($file_id) {
		if ($file_id instanceof ArrayObj) {
			$file_id = $file_id->find('file_id');
		}
		$file_path = $this->getFile(['file_id' => $file_id])->result->file_path;
		$file_url = "https://api.telegram.org/file/bot{$this->bot_token}/{$file_path}";
		return file_get_contents($file_url);
	}	
	
	public function send(string $text, ...$params) {
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'parse_mode' => $this->default_parse_mode, 'disable_web_page_preview' => TRUE, 'text' => $text];
		$params = array_replace($default, join_arguments($params));
		$params['text'] = $text;
		return $this->sendMessage($params);
	}
	
	public function reply(string $text, ...$params) {
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'parse_mode' => $this->default_parse_mode, 'disable_web_page_preview' => TRUE, 'reply_to_message_id' => $this->MessageID(), 'text' => $text];
		$params = array_replace($default, join_arguments($params));
		$params['text'] = $text;
		return $this->sendMessage($params);
	}	
	
	public function edit(string $text, ...$params) {
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'parse_mode' => $this->default_parse_mode, 'disable_web_page_preview' => TRUE, 'text' => $text, 'message_id' => $this->MessageID(), 'inline_message_id' => $this->InlineMessageID()];
		$params = array_replace($default, join_arguments($params));
		$params['text'] = $text;
		return $this->editMessageText($params);
	}
	
	public function editKeyboard(string $reply_markup, ...$params) {
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'parse_mode' => $this->default_parse_mode, 'disable_web_page_preview' => TRUE, 'message_id' => $this->MessageID(), 'reply_markup' => $reply_markup];
		$params = array_replace($default, join_arguments($params));
		return $this->editMessageReplyMarkup($params);
	} 
	
	public function act(string $text, ...$params) {
		$method = $this->update_type == 'callback_query'? 'editMessageText' : 'sendMessage';
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'parse_mode' => $this->default_parse_mode, 'disable_web_page_preview' => TRUE, 'text' => $text, 'message_id' => $this->MessageID()];
		$params = array_replace($default, join_arguments($params));
		$params['text'] = $text;
		return $this->__call($method, [$params]);
	}
	
	public function doc(string $filename, ...$params) {
		@$this->action("upload_document");
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'parse_mode' => 'HTML', 'disable_web_page_preview' => TRUE];
		$params = array_replace($default, join_arguments($params));
		
		if (file_exists(realpath($filename)) && !is_dir(realpath($filename))) {
			$document = curl_file_create(realpath($filename));
			if (isset($params['postname'])) {
				$document->setPostFilename($params['postname']);
				unset($params['postname']);
			}
			$params['document'] = $document;
		} else {
			$params['document'] = $filename;
		}
		
		return $this->sendDocument($params);
	}
	
	public function action(string $action = 'typing', ...$params) {
		$default = ['chat_id' => $this->ChatID() ?? $this->UserID(), 'action' => $action];
		$params = array_replace($default, join_arguments($params));
		$params['action'] = $action;
		return $this->sendChatAction($default);
	}	
	
	public function mention($user_id, $parse_mode = 'html', $use_last_name = false) {
		$parse_mode = strtolower($parse_mode);
		$info = @$this->Chat($user_id);
		if (!$info || !$info['first_name']) {
			return $user_id;
		}
		if ($use_last_name) $info['first_name'] .= (isset($info['last_name']) && !is_null($info['last_name'])? " {$info['last_name']}" : '');
		
		$mention = isset($info['username'])? "@{$info['username']}" : ($parse_mode == 'html'? "<a href='tg://user?id={$user_id}'>".htmlspecialchars($info['first_name'])."</a>" : "[{$info['first_name']}](tg://user?id={$user_id})");
		return $mention;
	}
	
	public function indoc($text, $name = null, $params = []) {
		if (!is_string($text) && !is_int($text)) {
			ob_start();
			var_dump($text);
			$text = ob_get_contents();
			ob_end_clean();
		}
		
		for ($i=0; $i<50; $i++) {
			#$hash = phgram_toBase($i);
			$tempname = "indoc_{$i}.txt";
			if (!file_exists($tempname)) break;
		}
		
		file_put_contents($tempname, $text);
		$res = $this->doc($tempname, array_replace($params, ['postname' => $name]));
		unlink($tempname);
		return $res;
	}
	
	public function answer_inline(array $results = [], ...$params) {
		$default = ['inline_query_id' => $this->InlineQuery()['id'], 'cache_time' => 0];
		$params = array_replace($default, join_arguments($params));
		$params['results'] = json_encode($results);
		return $this->answerInlineQuery($params);
	}
	
	public function answer_callback($text = '', ...$params) {
		$default = ['callback_query_id' => $this->CallbackQuery()['id']];
		
		$params = array_replace($default, join_arguments($params));
		$params['text'] = $text;
		return $this->answerCallbackQuery($params);
	}
	
	public function delete($message_id = null, $chat_id = null) {
		$message_id = $message_id ?? $this->MessageID();
		$chat_id = $chat_id ?? $this->ChatID();
		return $this->deleteMessage(['chat_id' => $chat_id, 'message_id' => $message_id]);
	}
	
	public function getData() {
		if (!$this->data) {
			# you can emulate webhooks through cli:
			# php webhook.pkp api '{"update_id":....}'
			if (isset($GLOBALS['argv']) && isset($GLOBALS['argv'][1]) && $GLOBALS['argv'][1] == 'api') {
				$update_as_json = $GLOBALS['argv'][2] ?: '[]';
				$this->data = json_decode($update_as_json, TRUE);
			} else {
				$update_as_json = file_get_contents('php://input') ?: '[]';
				$this->data = json_decode($update_as_json, TRUE);
			}
		}
		
		return $this->data;
	} 
	
	public function setData($data) {
		if (!is_array($data) && !($data instanceof ArrayObj)) {
			throw new Exception('Bad data type passed to setData');
			return false;
		}
		if ($data instanceof ArrayObj)
			$data = $data->asArray();
		
		$this->data = $data;
		$this->update_type = @array_keys($this->data)[1];
	}	
	
	public function getUpdateType() {
		return $this->update_type;
	}	
	
	public function getValue(string $search) {
		$value = $this->data[$this->update_type][$search] ?? $this->data[$this->update_type]['message'][$search] ?? null;
		if (!$value) return $value;
		
		switch ($this->data_type) {
			case 'ArrayObj':
				if ((is_array($value) || is_object($value))) {
					$obj = new ArrayObj($value);
					return $obj;
				} else {
					return $value;
				}
			break;
			case 'array':
				if (is_object($value)) {
					return (array)$value;
				} else {
					return $value;
				}
			break;
			case 'object':
				if (is_array($value)) {
					return (object)$value;
				} else {
					return $value;
				}
			break;
		}
	}
	
	public function in_chat(int $user_id, $chat_id) {
		$member = @$this->getChatMember(['chat_id' => $chat_id, 'user_id' => $user_id]);
		if (!$member['ok'] || in_array($member['result']['status'], ['left', 'kicked'])) {
			return FALSE;
		}
		
		return TRUE;
	}	
	
	public function is_group() {
		$chat = $this->getValue('chat');
		if (!$chat) {
			return FALSE;
		}
		return ($chat['type'] == 'supergroup') || ($chat['type'] == 'group');
	}	
	
	public function is_private() {
		$chat = $this->getValue('chat');
		if (!$chat) {
			return FALSE;
		}
		return $chat['type'] == 'private';
	}	
	
	public function is_admin($user_id = NULL, $chat_id = NULL) {
		if (!$user_id) {
			$user_id = $this->UserID();
		}
		if (!$chat_id) {
			$chat_id = $this->ChatID();
		}
		$member = @$this->getChatMember(['chat_id' => $chat_id, 'user_id' => $user_id]);
		return in_array($member['result']['status'], ['administrator', 'creator']);
	}
	
	##### Data shortcuts #####
	public function Message() {
		$value = $this->data['message'];
		if (!$value) return $value;
		
		switch ($this->data_type) {
			case 'ArrayObj':
				if ((is_array($value) || is_object($value))) {
					$obj = new ArrayObj($value);
					return $obj;
				} else {
					return $value;
				}
			break;
			case 'array':
				if (is_object($value)) {
					return (array)$value;
				} else {
					return $value;
				}
			break;
			case 'object':
				if (is_array($value)) {
					return (object)$value;
				} else {
					return $value;
				}
			break;
		}
	}
	
	public function Text() {
		return $this->getValue('text');
	}
 
	public function ChatID() {
		return $this->getValue('chat')['id'] ?? NULL;
	}

	public function ChatType() {
		return $this->getValue('chat')['type'] ?? NULL;
	}
	
	public function MessageID() {
		return $this->getValue('message_id');
	}
 
	public function Date() {
		return $this->getValue('date');
	}
 
	public function UserID() {
		return $this->getValue('from')['id'] ?? NULL;
	}
 
	public function FirstName() {
		return $this->getValue('from')['first_name'] ?? NULL;
	}
 
	public function LastName() {
		return $this->getValue('from')['last_name'] ?? NULL;
	}
	
	public function Name() {
		$first_name = $this->FirstName();
		$last_name = $this->LastName();
		if ($first_name) {
			$name = $first_name.($last_name? " {$last_name}" : '');
			return $name;
		}
		
		return NULL;
	}
 
	public function Username() {
		return $this->getValue('from')['username'] ?? NULL;
	}
	
	public function Language() {
		return $this->getValue('from')['language_code'] ?? NULL;
	}
 
	public function ReplyToMessage() {
		return $this->getValue('reply_to_message');
	}
 
	public function Caption() {
		return $this->getValue('caption');
	}
	
	public function InlineQuery() {
		return $this->data['inline_query'] ?? NULL;
	}
 
	public function ChosenInlineResult() {
		return $this->data['chosen_inline_result'] ?? NULL;
	}
 
	public function ShippingQuery() {
		return $this->data['shipping_query'] ?? NULL;
	}
 
	public function PreCheckoutQuery() {
		return $this->data['pre_checkout_query'] ?? NULL;
	}
 
	public function CallbackQuery() {
		return $this->data['callback_query'] ?? NULL;
	}
 
	public function Location() {
		return $this->getValue('location');
	}
 
	public function Photo() {
		return $this->getValue('photo');
	}
 
	public function Video() {
		return $this->getValue('video');
	}
 
	public function Document() {
		return $this->getValue('document');
	}
 
	public function UpdateID() {
		return $this->data['update_id'] ?? NULL;
	}
	
	public function ForwardFrom() {
		return $this->getValue('forward_from');
	}
 
	public function ForwardFromChat() {
		return $this->getValue('forward_from_chat');
	}
	
	public function Entities() {
		return $this->getValue('entities') ?? $this->getValue('caption_entities');
	}
	
	public function ReplyMarkup() {
		return $this->getValue('reply_markup');
	}
	
	public function InlineMessageID() {
		return $this->getValue('inline_message_id');
	}
	##### ####### #####
	
	public function Chat($chat_id = NULL) {
		if (!$chat_id) {
			$chat_id = $this->ChatID();
		}
		$chat = @$this->getChat(['chat_id' => $chat_id]);
		if ($chat['ok']) {
			return (new ArrayObj($chat['result']));
		}
		return FALSE;
	}
	
	public function protect() {
		$protection = $this->path."/{$this->UpdateID()}.run";
		if (!file_exists($protection)) file_put_contents($protection, '1');
		else exit;
		$string = "register_shutdown_function(function() { @unlink('{$protection}'); });";
		eval($string);
		return $protection;
	}
}