<?php
namespace phgram\Objects;
class ForceReply extends \phgram\Objects\Base {
	public function __construct($data, \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['reply_markup' => $this->data];
	}
}