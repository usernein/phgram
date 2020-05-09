<?php
/*
 * This file is part of phgram which is released under MIT license.
 * See file LICENSE or go to https://opensource.org/licenses/MIT for full license details.
 */
 
namespace usernein\phgram;

class MethodResult extends ArrayObject {
    public $json = '[]';

    public function __construct($json) {
        $this->json = $json;
        $data = json_decode($json, true);
        parent::__construct($data);
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
        return $this->__get($index);
    }

    public function offsetSet($index, $value) {
        return $this->__set($index, $value);
    }

    public function offsetExists($index) {
        return $this->__isset($index);
    }

    public function offsetUnset($index) {
        return $this->__unset($index);
    }
}
