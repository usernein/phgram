<?php
namespace phgram\Objects;
class User extends \phgram\Objects\Base {
	public function __construct($data, \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['chat_id' => $this->id, 'user_id' => $this->id];
	}
}