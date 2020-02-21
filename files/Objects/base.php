<?php
namespace phgram\Objects;
use \phgram\ArrayObj;

class Base extends ArrayObj {
	public $data;
	public $bot;
	public $arguments = [];
	
	public function __construct($data, \phgram\Bot $Bot = null) {
		if ($data instanceof ArrayObj) $data = $data->asArray();
		$this->data = $data;
		$this->bot = $Bot;
	}
	public function json($flags = 480) {
		return json_encode($this->data, $flags);
	}
	public function __call($method, $arguments = [[]]) {
		if ($arguments == []) $arguments = [[]];
		$args_key = 0;
		if (!is_array($arguments[$args_key]) && !is_object($arguments[$args_key])) {
			if (!isset($arguments[1])) $arguments[1] = [];
			$args_key = 1;
		}
		$arguments[$args_key] = \phgram\join_arguments([$this, $arguments[$args_key]]);
		#$this->bot->log($arguments);
		return $this->bot->$method(...$arguments);
	}
}