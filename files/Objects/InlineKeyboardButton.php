<?php
namespace phgram\Objects;
class InlineKeyboardButton extends \phgram\Objects\Base {
	public function __construct($data = [], \phgram\Bot $Bot = null) {
		if ($data instanceof ArrayObj) $data = $data->asArray();
		$this->data = &$data;
		$this->bot = $Bot;
		$this->arguments = [];
	}
	
	public function delete() {
		$this->data['unset'] = true;
	}
}