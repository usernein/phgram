<?php
/*
 * This file is part of phgram which is released under MIT license.
 * See file LICENSE or go to https://opensource.org/licenses/MIT for full license details.
 */
 
namespace usernein\phgram;

class Update extends ArrayObject {
    public $update_type;
    public $update_id;
    private $_bot;
    
    public function __construct(iterable $data, Bot $bot = null) {
        if ($data instanceof ArrayObject)
            $data = $data->asArray();
        if (!is_array($data))
            throw new TypeError("Argument 1 passed to Update::__construct must be an array or an instance of ArrayObject, ".type($data)." given");
        
        if (isset($data['update_id'])) {
            $this->update_id = $data['update_id'];
            unset($data['update_id']);
            $this->update_type = array_keys($data)[0];
            $data = ['update_id' => $this->update_id, 'update_type' => $this->update_type] + array_values($data)[0];
        }
        if ($bot)
            $this->_bot = $bot;
        
        parent::__construct($data);
    }
    
    public function getValue($key) {
        return $this->data[$key] ?? $this->data['message'][$key] ?? NULL;
    }
    
    # Magic getters and callers
    public function __get($key) {
        switch ($key) {
            case 'ChatID':
                return $this->getValue('chat')['id'] ?? NULL;
            break;
        
            case 'ChatType':
                return $this->getValue('chat')['type'] ?? NULL;
            break;
         
            case 'Date':
                return new DateTime("@".$this->getValue('date'));
            break;
         
            case 'UserID':
                return $this->getValue('from')['id'];
            break;
         
            case 'FirstName':
                return $this->getValue('from')['first_name'];
            break;
         
            case 'LastName':
                return $this->getValue('from')['last_name'] ?? NULL;
            break;
            
            case 'Name':
                $name = array_filter([$this->FirstName(), $this->LastName()]);
                return join(' ', $name);
            break;
         
            case 'Username':
                return $this->getValue('from')['username'] ?? NULL;
            break;
            
            case 'Language':
                return $this->getValue('from')['language_code'] ?? NULL;
            break;
         
            case 'Entities':
                return $this->getValue('entities') ?? $this->getValue('caption_entities');
            break;
        }
        
        return parent::__get($key);
    }
    
    # Bound methods
    public function __call($method, array $arguments = []) {
        $parameters = [];
        
        switch ($method) {
            case 'reply':
                $parameters = ['chat_id' => $this->ChatID];
                if ($this->ChatType != "private" || isset($parameters['quote']) && $parameters['quote'])
                    $parameters['reply_to_message_id'] = $this->MessageID;
                
                if (isset($parameters['quote']))
                    unset($parameters['quote']);
                $method = 'send';
            break;
            
            case 'edit':
                $parameters = ['chat_id' => $this->ChatID, 'message_id' => $this->message_id, 'inline_message_id' => $this->inline_message_id];
            break;
            
            case 'edit_keyboard':
                $parameters = ['chat_id' => $this->ChatID, 'message_id' => $this->message_id];
            break;
            
            case 'action':
                $parameters = ['chat_id' => $this->ChatID];
            break;
        }
        
        if (!isset($arguments[1]))
            $arguments[1] = [];
        
        $arguments[1] += $parameters;
        return $this->_bot->$method(...$arguments);
    }
}