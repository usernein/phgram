<?php
namespace phgram\Objects;
class File extends \phgram\Objects\Base {
	public function __construct($data, \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = ['file_id' => $this->file_id];
	}
}