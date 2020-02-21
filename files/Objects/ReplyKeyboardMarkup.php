<?php
namespace phgram\Objects;
class ReplyKeyboardMarkup extends \phgram\Objects\Base {
	public $buttons = [];
	
	public function __construct($data = ['keyboard' => []], \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['reply_markup' => $this->data];
		if (!isset($this->data['keyboard'])) {
			throw new \Error('Invalid ReplyKeyboardMarkup object');
		}
		
		$this->createKeys();
	}
	
	public function load($source) {
		if (is_string($source)) {
			# supposed to be a json object
			$source = json_decode($source, true);
			if (!source) throw new \Error('Bad ReplyKeyboardMarkup source');
		} else if ($source instanceof ArrayObj) {
			$source = $source->asArray();
		} else if (!is_array($source)) {
			throw new \Error('Bad ReplyKeyboardMarkup source');
		}
		
		parent::__construct($source, $this->key, $this->bot);
		if (!isset($this->data['keyboard'])) {
			throw new \Error('Invalid ReplyKeyboardMarkup object');
		}
		
		$this->createKeys();
	}
	
	public function &addLine(array $buttons = [], int $offset = null) {
		if ($offset !== null) {
			$line_key = $offset;
			array_splice($this->data['keyboard'], $offset, 0, [[]]);
		} else {
			$line_key = count($this->data['keyboard']);
		}
		$this->data['keyboard'][$line_key] = array_values($buttons);
		$this->createKeys();
		return $this->data['keyboard'][$line_key];
	}
	
	public function addButton($button, int $line = null, int $offset = null) {
		if ($line == null) $line = array_key_last($this->data['keyboard']);
		if ($offset !== null) {
			$line_button_key = $offset;
			array_splice($this->data['keyboard'][$line], $offset, 0, [[]]);
		} else {
			$line_button_key = count($this->data['keyboard'][$line]);
		}
		
		$this->data['keyboard'][$line][$line_button_key] = ($button instanceof \phgram\ArrayObj? $button->asArray() : (array)$button);
		if ($button instanceof KeyboardButton) {
			$button->data = &$this->data['keyboard'][$line][$line_button_key];
			$button->bot = $this->bot;
		} else {
			$button = new KeyboardButton($ref = &$this->data['keyboard'][$line][$line_button_key], null, $this->bot);
		}
		
		$this->createKeys();
		return $button;
	}
	
	public function createKeys() {
		$lines = &$this->data['keyboard'];
		$button_key = 0;
		$this->buttons = [];
		foreach ($lines as $line_key => $line) {
			foreach ($line as $line_button_key => $button) {
				$button_key_str = "{$line_key}:{$line_button_key}:{$button_key}";
				if (!$button) {
					unset($lines[$line_key][$line_button_key]);
					continue;
				} else if (!is_array($button) && !($button instanceof KeyboardButton)) {
					throw new \Error('Bad KeyboardButton object');
				} else if (is_array($button)) {
					$lines[$line_key][$line_button_key] = new KeyboardButton($lines[$line_key][$line_button_key], $button_key_str, $this->bot);
				}
				$this->buttons[$button_key_str] = &$lines[$line_key][$line_button_key];
				$button_key++;
			}
		}
	}
	
	public function getFromData($callback_data) {
		foreach ($this->buttons as $button_key_str => &$button) {
			if (!isset($button['callback_data'])) throw new \Error("Can't use getFromData() in non-inline keyboards");
			if ($callback_data == $button['callback_data']) return $button;
		}
	}
	
	public function getFromKey($key) {
		foreach ($this->buttons as $button_key_str => &$button) {
			list($line_key, $line_button_key, $button_key) = explode(':', $button_key_str);
			if ($key == $button_key) return $button;
		}
	}
	
	public function getFromText($text) {
		foreach ($this->buttons as $button_key_str => &$button) {
			if ($text == $button['text']) return $button;
		}
	}
	
	public function save($json = true) {
		$keyboard = &$this->data['keyboard'];
		$keyboard = [];
		foreach ($this->buttons as $button_key_str => $button) {
			if (!$button || (isset($button['unset']) && $button['unset'])) {
				unset($this->buttons[$button_key_str]);
				continue;
			}
			list($line_key, $line_button_key, $button_key) = explode(':', $button_key_str);
			$keyboard[$line_key][] = ($button instanceof \phgram\ArrayObj? $button->asArray() : (array)$button);
		}
		if ($json) {
			return json_encode($this->data, 480);
		}
		return $this->data;
	}
	
	public function jsonSerialize() {
		return $this->save(false);
	}
}