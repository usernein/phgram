<?php
namespace phgram\Objects;
class Chat extends \phgram\Objects\Base {
	public function __construct($data, \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['chat_id' => $this->id];
	}
}