<?php
namespace phgram;
use \phgram\ArrayObj;
class MethodResult extends ArrayObj {
	public $json = '[]';
	private $bot = null;
	public $params = [];
	public $method = '';

	public function __construct($json, $params, $bot, $object = null, $method = '') {
		global $lastResult;
		$this->json = $json;
		$this->method = $method;
		$data = json_decode($json, true);
		if (isset($data['result'])) {
			if (is_string($object)) {
				$class = '\phgram\Objects\\'.$object;
				if (is_array($data['result']) || is_object($data['result']))
					$data['result'] = new $class($data['result'], $bot);
			} else if (is_array($object)) {
				$dimensions = get_level($object);
				$object = array_flatten($object)[0];
				$classify = function ($val) use ($bot, $object) {
					$class = '\phgram\Objects\\'.$object;
					if (is_array($val) || is_object($val))
						return (new $class($val, $bot));
					else return $val;
				};
				$data['result'] = apply_to_level($data['result'], $classify, $dimensions);
			}
			$data['result'] = \phgram\Bot::parseResult($data['result'], $bot);
		}
		parent::__construct($data);
		$lastResult = $this;
		$this->params = $params;
		$this->bot = $bot;
	}
	
	public function __call($method, $arguments = [[]]) {
		if (isset($this->data['result']) && is_object($this->data['result']) && ($this->data['result'] instanceof \phgram\Objects\Base)) {
			$call = $this->data['result']->$method(...$arguments);
			$this->__construct($call->json, $call->params, $this->bot);
			return $call;
		}
	}
	
	public function __get($index) {
		return $this->data[$index] ?? $this->data['result'][$index] ?? $this->data['result']['message'][$index] ?? NULL;
	}
	
	public function __isset($key) {
		return isset($this->data[$key]) || isset($this->data['result'][$key]) || isset($this->data['result']['message'][$key]);
	}
	
	public function __set($key, $val) {
		if (isset($this->data['result']['message'][$key])) {
			$this->data['result']['message'][$val] = $val;
		} else if (isset($this->data['result'][$key])) {
			$this->data['result'][$key] = $val;
		} else {
			$this->data[$key] = $val;
		}
	}
	
	public function __unset($key) {
		if (isset($this->data['result']['message'][$key])) {
			unset($this->data["result"]['message'][$key]);
		} else if (isset($this->data['result'][$key])) {
			unset($this->data["result"][$key]);
		} else {
			unset($this->data[$key]);
		}
	}
	
	##### Functions implemented by ArrayAccess #####
	public function offsetGet($index) {
		return $this->data[$index] ?? $this->data['result'][$index] ?? $this->data['result']['message'][$index] ?? NULL;
	}

	public function offsetSet($index, $value) {
		if (isset($this->data['result']['message'][$key])) {
			$this->data['result']['message'][$val] = $val;
		} else if (isset($this->data['result'][$key])) {
			$this->data['result'][$key] = $val;
		} else {
			$this->data[$key] = $val;
		}
	}

	public function offsetExists($index) {
		return isset($this->data[$key]) || isset($this->data['result'][$key]) || isset($this->data['result']['message'][$key]);
	}

	public function offsetUnset($index) {
		if (isset($this->data['result']['message'][$key])) {
			unset($this->data["result"]['message'][$key]);
		} else if (isset($this->data['result'][$key])) {
			unset($this->data["result"][$key]);
		} else {
			unset($this->data[$key]);
		}
	}
}

$lastResult = NULL;