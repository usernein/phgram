<?php
namespace phgram;
function ikb(array $options = [], $encode = false) {
	$lines = [];
	$options = array_values($options);
	foreach ($options as $line_pos => $line_buttons) {
		$lines[$line_pos] = [];
		foreach ($line_buttons as $button_pos => $button) {
			$lines[$line_pos][$button_pos] = btn(...$button);
		}
	}
	$replyMarkup = [
		'inline_keyboard' => array_values($lines),
	];
	return ($encode? json_encode($replyMarkup, 480) : (new \phgram\Objects\InlineKeyboardMarkup($replyMarkup)));
}

function btn($text, string $value, string $type = 'callback_data') {
	return ['text' => $text, $type => $value];
}

 
function kb(array $options = [], $encode = false, array $params = ['selective' => true]) {
	$lines = [];
	foreach ($options as $line_pos => $line_buttons) {
		$lines[$line_pos] = [];
		foreach ($line_buttons as $button_pos => $button) {
			if (!is_array($button)) $button = [$button];
			$key = isset($button['text'])? 'text' : 0;
			$text = $button[$key];
			unset($button[$key]);
			$lines[$line_pos][$button_pos] = kbtn($text, false, $button);
		}
	}
	$replyMarkup = array_replace([
		'keyboard' => array_values($lines),
		'selective' => $params['selective'] ?? true,
	], $params);
	return $encode? json_encode($replyMarkup, 480) : (new \phgram\Objects\ReplyKeyboardMarkup($replyMarkup));
}

function kbtn($text, $encode = false, array $params = []) {
	$replyMarkup = array_replace([
		'text' => $text,
	], $params);
	return $encode? json_encode($replyMarkup, 480) : (new \phgram\Objects\KeyboardButton($replyMarkup));
}

function hide_kb(bool $selective = TRUE, $encode = false) {
	$replyMarkup = [
		'remove_keyboard' => TRUE,
		'selective' => $selective,
	];
	return $encode? json_encode($replyMarkup, 480) : (new \phgram\Objects\ReplyKeyboardRemove($replyMarkup));
}
 
function forceReply(bool $selective = TRUE, $encode = false) {
	$replyMarkup = [
		'force_reply' => TRUE,
		'selective' => $selective,
	];
	return $encode? json_encode($replyMarkup, 480) : (new \phgram\Objects\ForceReply($replyMarkup));
}

function entities_to_html(string $text, $entities = []) {
	if ($entities instanceof ArrayObj) $entities = $entities->asArray();
	$to16 = function($text) {
		return mb_convert_encoding($text, "UTF-16", "UTF-8"); //or utf-16le
	};
	$to8 = function($text) {
		return mb_convert_encoding($text, "UTF-8", "UTF-16"); //or utf-16le
	};
	$message_encode = $to16($text); //or utf-16le
	
	foreach (array_reverse($entities) as $entity) {
		#if ($entity instanceof \phgram\ArrayObj) $entity = $entity->asArray();
		$original = htmlspecialchars($to8(substr($message_encode, $entity['offset']*2, $entity['length']*2)));
		$url = isset($entity['url'])? htmlspecialchars($entity['url']) : '';
		$id = @$entity['user']['id'];
		
		switch ($entity['type']) {
			case 'bold':
				$message_encode = substr_replace($message_encode, $to16("<b>$original</b>"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'italic':
				$message_encode = substr_replace($message_encode, $to16("<i>$original</i>"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'code':
				$message_encode = substr_replace($message_encode, $to16("<code>$original</code>"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'pre':
				$message_encode = substr_replace($message_encode, $to16("<pre>$original</pre>"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'text_link':
				$message_encode = substr_replace($message_encode, $to16("<a href='{$url}'>$original</a>"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'text_mention':
				$message_encode = substr_replace($message_encode, $to16("<a href='tg://user?id={$id}'>$original</a>"), $entity['offset']*2, $entity['length']*2);
				break;
		}
	}
	
	$html = $to8($message_encode);
	return $html;
}
function entities_to_markdown(string $text, $entities = []) {
	if ($entities instanceof ArrayObj) $entities = $entities->asArray();
	$to16 = function($text) {
		return mb_convert_encoding($text, "UTF-16", "UTF-8"); //or utf-16le
	};
	$to8 = function($text) {
		return mb_convert_encoding($text, "UTF-8", "UTF-16"); //or utf-16le
	};
	$md_escape = function ($text) {
		return preg_replace('#([*`_\(\)\[\]])#', "\\\\\\1", $text);
	};
	$message_encode = $to16($text); //or utf-16le
	
	foreach (array_reverse($entities) as $entity) {
		$original = $md_escape($to8(substr($message_encode, $entity['offset']*2, $entity['length']*2)));
		$url = isset($entity['url'])? htmlspecialchars($entity['url']) : '';
		$id = @$entity['user']['id'];
		
		switch ($entity['type']) {
			case 'bold':
				$message_encode = substr_replace($message_encode, $to16("*$original*"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'italic':
				$message_encode = substr_replace($message_encode, $to16("_{$original}_"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'code':
				$message_encode = substr_replace($message_encode, $to16("`$original`"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'pre':
				$message_encode = substr_replace($message_encode, $to16("```$original```"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'text_link':
				$message_encode = substr_replace($message_encode, $to16("[$original]($url)"), $entity['offset']*2, $entity['length']*2);
				break;
			case 'text_mention':
				$message_encode = substr_replace($message_encode, $to16("[$original](tg://user?id={$id})"), $entity['offset']*2, $entity['length']*2);
				break;
		}
	}
	
	$md = $to8($message_encode);
	return $md;
}
function get_level ($Array, $count = 0) {
   if(is_array($Array)) {
      return get_level(current($Array), ++$count);
   } else {
      return $count;
   }
}
function apply_to_level ($array, $callback, $target_level, $current_level = 1) {
	if ($current_level == $target_level) {
		foreach ($array as &$item) {
			$item = $callback($item);
		}
	} else {
		if (is_array($array)) {
			foreach ($array as &$item) {
				$item = apply_to_level($item, $callback, $target_level, $current_level+1);
			}
		}
	}
	return $array;
}
function array_flatten ($array) { 
	if (!is_array($array)) {
		return FALSE;
	}
	$result = [];
	foreach ($array as $key => $value) {
		if (is_array($value)) {
			$result = array_merge($result, array_flatten($value));
		} else {
			$result[] = $value;
		}
	}
	return $result;
}
function show($args) {
	$str = '';
	foreach ($args as $k => $v) {
		ob_start();
		var_dump($v);
		$dump = trim(ob_get_contents());
		ob_end_clean();
		$str .= "\$$k=$dump ";
	}
	$str .= "\n";
	echo $str;
	return $str;
}
function asArray($ArrayObj) {
	return json_decode(json_encode($ArrayObj), true);
}
function join_arguments($params) {
	$arguments = [];
	foreach ($params as $param) {
		if (is_array($param)) {
			$arguments = array_replace($arguments, $param);
		} else if (is_object($param) && $param instanceof \phgram\Objects\Base) {
			#$param->bot->log('inside');
			$arguments = array_replace($arguments, $param->arguments);
		}
	}
	return $arguments;
}
function dump (...$vals) {
	$result = [];
	foreach ($vals as $val) {
		ob_start();
		var_dump($v);
		$result[] = trim(ob_get_contents());
		ob_end_clean();
	}
	echo join("\n", $result);
}

// from https://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
class Colors { 
  private $foreground_colors = array(); 
  private $background_colors = array(); 
 
  public function __construct() { 
   // Set up shell colors 
   $this->foreground_colors['black'] = '0;30'; 
   $this->foreground_colors['dark_gray'] = '1;30'; 
   $this->foreground_colors['blue'] = '0;34'; 
   $this->foreground_colors['light_blue'] = '1;34'; 
   $this->foreground_colors['green'] = '0;32'; 
   $this->foreground_colors['light_green'] = '1;32'; 
   $this->foreground_colors['cyan'] = '0;36'; 
   $this->foreground_colors['light_cyan'] = '1;36'; 
   $this->foreground_colors['red'] = '0;31'; 
   $this->foreground_colors['light_red'] = '1;31'; 
   $this->foreground_colors['purple'] = '0;35'; 
   $this->foreground_colors['light_purple'] = '1;35'; 
   $this->foreground_colors['brown'] = '0;33'; 
   $this->foreground_colors['yellow'] = '1;33'; 
   $this->foreground_colors['light_gray'] = '0;37'; 
   $this->foreground_colors['white'] = '1;37'; 
 
   $this->background_colors['black'] = '40'; 
   $this->background_colors['red'] = '41'; 
   $this->background_colors['green'] = '42'; 
   $this->background_colors['yellow'] = '43'; 
   $this->background_colors['blue'] = '44'; 
   $this->background_colors['magenta'] = '45'; 
   $this->background_colors['cyan'] = '46'; 
   $this->background_colors['light_gray'] = '47'; 
  } 
 
  // Returns colored string 
  public function getColoredString($string, $foreground_color = null, $background_color = null) { 
   $colored_string = ""; 
 
   // Check if given foreground color found 
   if (isset($this->foreground_colors[$foreground_color])) { 
    $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m"; 
   } 
   // Check if given background color found 
   if (isset($this->background_colors[$background_color])) { 
    $colored_string .= "\033[" . $this->background_colors[$background_color] . "m"; 
   } 
 
   // Add string and end coloring 
   $colored_string .=  $string . "\033[0m"; 
 
   return $colored_string; 
  } 
 
  // Returns all foreground color names 
  public function getForegroundColors() { 
   return array_keys($this->foreground_colors); 
  } 
 
  // Returns all background color names 
  public function getBackgroundColors() { 
   return array_keys($this->background_colors); 
  } 
 } 