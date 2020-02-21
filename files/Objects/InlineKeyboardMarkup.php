<?php
namespace phgram\Objects;
class InlineKeyboardMarkup extends \phgram\Objects\Base {
	public $buttons = [];
	
	public function __construct($data = ['inline_keyboard' => []], \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['reply_markup' => $this->data];
		if (!isset($this->data['inline_keyboard'])) {
			throw new \Error('Invalid InlineKeyboardMarkup object');
		}
	}
	
	public function load($source) {
		if (is_string($source)) {
			# supposed to be a json object
			$source = json_decode($source, true);
			if (!source) throw new \Error('Bad InlineKeyboardMarkup source: '.json_last_error_msg());
		} else if ($source instanceof ArrayObj) {
			$source = $source->asArray();
		} else if (!is_array($source)) {
			throw new \Error('Bad InlineKeyboardMarkup source: unsupported type '.gettype($source).' expecting array, ArrayObj or a json string');
		}
		
		parent::__construct($source, $this->bot);
		if (!isset($this->data['inline_keyboard'])) {
			throw new \Error('Invalid InlineKeyboardMarkup object');
		}
	}
	
	public function &addLine(array $buttons = [], int $offset = null) {
		if ($offset !== null) {
			$line_key = $offset;
			array_splice($this->data['inline_keyboard'], $offset, 0, [[]]);
		} else {
			$line_key = count($this->data['inline_keyboard']);
		}
		$this->data['inline_keyboard'][$line_key] = array_values($buttons);
		return $this->data['inline_keyboard'][$line_key];
	}
	
	public function addButton($button, int $line = null, int $offset = null) {
		if ($line == null) $line = array_key_last($this->data['inline_keyboard']);
		if ($offset !== null) {
			$line_button_key = $offset;
			array_splice($this->data['inline_keyboard'][$line], $offset, 0, [[]]);
		} else {
			$line_button_key = count($this->data['inline_keyboard'][$line]);
		}
		
		$this->data['inline_keyboard'][$line][$line_button_key] = ($button instanceof \phgram\ArrayObj? $button->asArray() : (array)$button);
		if ($button instanceof InlineKeyboardButton) {
			$button->data = &$this->data['inline_keyboard'][$line][$line_button_key];
			$button->bot = $this->bot;
		} else {
			$button = new InlineKeyboardButton($ref = &$this->data['inline_keyboard'][$line][$line_button_key], null, $this->bot);
		}
		
		return $button;
	}
	
	public function getFromData($callback_data) {
		foreach ($this->data['inline_keyboard'] as &$line) {
			foreach ($line as &$button) {
				if (!isset($button['callback_data'])) throw new \Error("Can't use getFromData() in non-inline keyboards");
				if ($callback_data == $button['callback_data']) {
					$button = new InlineKeyboardButton($button, null, $this->bot);
					return $button;
				}
			}
		}
	}
	
	public function getFromText($text) {
		foreach ($this->data['inline_keyboard'] as &$line) {
			foreach ($line as &$button) {
				if ($text == $button['text']) {
					$button = new InlineKeyboardButton($button, null, $this->bot);
					return $button;
				}
			}
		}
	}
	
	public function save($json = true) {
		foreach ($this->data['inline_keyboard'] as &$line) {
			$line = array_filter($line, function($button) {
				return $button && (!isset($button['unset']) || !$button['unset']);
			});
		}
		$this->data['inline_keyboard'] = array_values($this->data['inline_keyboard']);
		if ($json) {
			return json_encode($this->data, 480);
		}
		return $this->data;
	}
	
	public function jsonSerialize() {
		return $this->save(false);
	}
	
	public function offsetSet($offset, $value) {
		if ($offset < 0) $offset = count($this->data['inline_keyboard'])+$offset;
		if (is_null($offset)) {
			$this->data['inline_keyboard'][] = $value;
		} else {
			$this->data['inline_keyboard'][$offset] = $value;
		}
	}
	public function offsetGet($offset) {
		if ($offset < 0) $offset = count($this->data['inline_keyboard'])+$offset;
		$line = &$this->data['inline_keyboard'][$offset];
		if ($line) {
			foreach ($line as &$button) {
				$button = new InlineKeyboardButton($button, null, $this->bot);
			}
		}
		return $line;
	}
	public function offsetExists($offset) {
		if ($offset < 0) $offset = count($this->data['inline_keyboard'])+$offset;
		return isset($this->data['inline_keyboard'][$offset]);
	}
	public function offsetUnset($offset) {
		if ($offset < 0) $offset = count($this->data['inline_keyboard'])+$offset;
		if ($this->offsetExists($offset)) {
			unset($this->data['inline_keyboard'][$offset]);
		}
	}
}