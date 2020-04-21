<?php 
namespace phgram;
class BotErrorHandler {
	public static $bot;
	private static $first_bot;
	public static $admin;
	private static $first_admin;
	public static $data = [];
	public static $show_data;
	public static $verbose = false;
	
	public static function register($error_bot, $error_admin, $show_data = true) {
		self::$bot = self::$first_bot = $error_bot;
		self::$admin = self::$first_admin = $error_admin;
		self::$show_data = $show_data;
		
		$json = \file_get_contents('php://input');
		self::$data = null;
		if ($json) {
			self::$data = \json_decode($json, true);
		}
		
		\set_error_handler(['\phgram\BotErrorHandler', 'error_handler']);
		\set_exception_handler(['\phgram\BotErrorHandler', 'exception_handler']);
		\register_shutdown_function(['\phgram\BotErrorHandler', 'shutdown_handler']);
	}

	// for restoring the handlers
	public static function destruct () {
		\restore_error_handler();
		\restore_exception_handler();
	}
	
	// for restoring self::$bot and self::$admin to the initial values
	public static function restore() {
		self::$bot = self::$first_bot;
		self::$admin = self::$first_admin;
	}

	// for calling api methods
	public static function call(string $method, array $args = []) {
		$bot = self::$bot;
		$url = "https://api.telegram.org/bot{$bot}/{$method}";
		
		$ch = \curl_init($url);
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		\curl_setopt($ch, CURLOPT_POST, TRUE);
		\curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		$result = \curl_exec($ch);
		\curl_close($ch);
		return \json_decode($result ?: '[]', true);
	}
	
	// returns the error type name by code
	public static function get_error_type($code) {
		$types = [
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE',
			E_STRICT => 'E_STRICT',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED => 'E_DEPRECATED',
			E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		];
		return ($types[$code] ?? 'unknown');
	}
	
	// handle errors
	public static function error_handler($error_type, $error_message, $error_file, $error_line, $error_args) {
		if (\error_reporting() === 0 && self::$verbose != true) return false;
		
		$str = \htmlspecialchars("{$error_message} in {$error_file} on line {$error_line}");
		$str .= "\nView:\n". phgram_pretty_debug(2);
		
		if (self::$show_data) {
			$data = self::$data;
			$type = @array_keys($data)[1];
			$data = @array_values($data)[1];
			
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
			
			$str .= \htmlspecialchars("\n\n\"{$text}\", ").
				($sender? "sent by <a href='tg://user?id={$sender}'>{$sender_name}</a>, " : '').
				($chat? "in {$chat_id} ({$chat_mention})." : '')." Update type: '{$type}'.";
		}
		
		$error_type = self::get_error_type($error_type);
		$str .= "\nError type: {$error_type}.";
		
		$error_log_str = "{$error_type}: {$error_message} in {$error_file} on line {$error_line}";
		\error_log($error_log_str);
		
		self::log($str);
		
		return false;
	}
	
	// handle exceptions
	public static function exception_handler($e) {
		$str = \htmlspecialchars("{$e->getMessage()} in {$e->getFile()} on line {$e->getline()}");
		$str .= "\nView:\n". phgram_pretty_debug(2);
		
		if (self::$show_data) {
			$data = self::$data;
			$type = @array_keys($data)[1];
			$data = @array_values($data)[1];
			
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
			
			$str .= htmlspecialchars("\n\n\"{$text}\", ").
				($sender? "sent by <a href='tg://user?id={$sender}'>{$sender_name}</a>, " : '').
				($chat? "in {$chat_id} ({$chat_mention})." : '')." Update type: '{$type}'.";
		}
		$error_log_str = "Exception: {$e->getMessage()} in {$e->getFile()} on line {$e->getline()}";
		\error_log($error_log_str);
		
		self::log($str);
		
		return false;
	}
	
	public static function shutdown_handler() {
		$error = error_get_last();
		// fatal error, E_ERROR === 1
		if ($error && $error['type'] === E_ERROR) { 
			#$error_type, $error_message, $error_file, $error_line, $error_args
			self::error_handler($error['type'], $error['message'], $error['file'], $error['line'], []);
			
		}
	}
	
	public static function log($text, $type = 'ERR') {
		$params = ['chat_id' => self::$admin, 'text' => $text, 'parse_mode' => 'html'];
		$method = 'sendMessage';
		
		if (\mb_strlen($text) > 4096) {
			$text = \substr($text, 0, 20400); # 20480 = 20MB (limit of BotAPI)
			$logname = 'BEHlog_'.time().'.txt';
			
			\file_put_contents($logname, $text);
			
			$method = 'sendDocument';
			$document = \curl_file_create(realpath($logname));
			$document->postname = $type.'_report.txt';
			$params['document'] = $document;
		}
		
		if (\is_array(self::$admin)) {
			foreach (self::$admin as $admin) {
				$params['chat_id'] = $admin;
				self::call($method, $params);
			}
		} else {
			self::call($method, $params);
		}
		if (isset($logname)) \unlink($logname);
	}
}
