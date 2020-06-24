<?php
/*
 * This file is part of phgram which is released under MIT license.
 * See file LICENSE or go to https://opensource.org/licenses/MIT for full license details.
 */
namespace usernein\phgram;

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;
use \Monolog\Handler\RotatingFileHandler;
use \Monolog\Formatter\LineFormatter;
use \Monolog\ErrorHandler;
use \Bramus\Monolog\Formatter\ColoredLineFormatter;

/**
 * The bot controller
 * 
 * It is used to make all the requests to the Telegram Bot API and has some helper methods and shortcuts to some BotAPI methods.
 *
 * @author Cezar Pauxis <cezar@amanoteam.com>
 * @license https://opensource.org/licenses/MIT MIT
 */
class Bot {
    /**
     * The Telegram bot token
     */
    private $BOT_TOKEN = '';
    
    /**
     * cURL handler
     */
    private $CH;

    /**
     * The data type for the getters and method results
     */
    public $data_type = 'ArrayObject';
    
    /**
     * Settings for some phgram behaviour
     */
    public $settings = [];
    
    /**
     * Instance of \Monolog\Logger
     */
    public $logger;
    
    /**
     * Update being handled by the bot
     */
    public $update;
    
    /**
     * @param string $bot_token The Telegram bot token
     * @param array $settings Settings for some phgram behaviour
     */
    public function __construct(string $bot_token, array $settings = []) {
        $this->BOT_TOKEN = $bot_token;
        
        $this->CH = curl_init();
        $opts = [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_POST => TRUE
        ];
        curl_setopt_array($this->CH, $opts);
        
        $is_cli = !http_response_code();
        
        $default_settings = [
            'logging' => [
                'logger' => null, # instance of \Monolog\Logger
                'filename' => 'phgram.log', # file name for rotative log files
                'level' => $is_cli? Logger::DEBUG : Logger::NOTICE, # level of the logging
                'max_files' => 7 # maximum quantity of rotated files
            ],
            'throw_errors' => false, # should phgram throw exceptions when a call to a method fails?
            'parse_mode' => 'HTML', # default parse mode for outgoing methods (only used by shortcuts)
            'return_type' => 'ArrayObject', # should the getters and methods return an ArrayObject object or an normal array?
            'disable_web_page_preview' => true, # default value for disable_web_page_preview for outgoing messages (only used by shortcuts)
            'disable_notification' => false # default value for disable_notification for outgoing messages (only used by shortcuts)
        ];
        
        $this->settings = array_replace_recursive($default_settings, $settings); 
        
        if (!$this->settings['logging']['logger']) {
            $this->logger = new Logger('phgram');
            
            $date_format = 'd/m/Y H:i:s O';
            
            $log_filename = $this->settings['logging']['filename'];
            $handler = new RotatingFileHandler($log_filename, $this->settings['logging']['max_files'], $this->settings['logging']['level']);
            $formatter = new LineFormatter(null, $date_format);
            $handler->setFormatter($formatter);
            
            if ($is_cli) {
                $handler = new StreamHandler('php://output', $this->settings['logging']['level']);
                $formatter = new ColoredLineFormatter(null, null, $date_format);
                $handler->setFormatter($formatter);
            }
            $this->logger->pushHandler($handler);
            $this->settings['logging']['logger'] = $this->logger;
            ErrorHandler::register($this->logger);
        }
        
        $version = version;
        $php_version = PHP_VERSION;
        $this->logger->info("phgram v{$version} is running with PHP {$php_version}");
        $this->logger->debug("phgram\Bot instance created with settings = ".json_encode($this->settings));
        
        # Read and bind update
        $this->get_data();
    }
    
    /**
     * Executes the request to the Telegram Bot API
     * 
     * @param string $url The url to set to the cURL option CURLOPT_URL
     * @param array $content The array with parameters to set to the cURL option CURLOPT_POSTFIELDS
     * 
     * @return string The result of the request
     */
    private function sendAPIRequest(string $url, array $content) {
        $content = array_map(function ($val) {
            if (is_array($val) || (is_object($val) && !($val instanceof \CURLFile)))
                return json_encode($val);
            
            return $val;
        }, $content);
        
        $opts = [
            CURLOPT_URL => $url
        ];
        if ($content)
        	$opts[CURLOPT_POSTFIELDS] = $content;

        curl_setopt_array($this->CH, $opts);
        $result = curl_exec($this->CH);
        $response_code = curl_getinfo($this->CH, CURLINFO_HTTP_CODE);
        if ($response_code != 200)
            $this->logger->error("Unexpected HTTP code received: {$response_code}");
        
        if (!$result)
            $this->logger->error("Invalid result from the API: ".var_dump($result, 1));
        return $result ?: '{"ok":false}';
    }
    
    /**
     * @param string $string The BotAPI method
     * @param array $arguments Array of arguments. The first argument must be an array of arguments to send to the Telegram Bot API
     * 
     * @return MethodResult|array The decoded BotAPI result
     */
    public function __call(string $method, array $arguments = [[]]) {
        if (!$arguments)
            $arguments = [[]];
        
        $arguments = $arguments[0];
        
        # The method will fail if there are these parameters in the same call, so we remove the conflicts, giving priority to inline_message_id
        if (isset($arguments['inline_message_id'])) {
            unset($arguments['chat_id']);
            unset($arguments['message_id']);
        }
        
        $url = "https://api.telegram.org/bot{$this->BOT_TOKEN}/{$method}";
        $this->logger->debug("Method {$method} called");
        $response = $this->sendAPIRequest($url, $arguments);
        $result = new MethodResult($response);
        
        if (!$result['ok']) {
            if ($this->settings['throw_errors'])
                throw new \Exception($result->json);
            $this->logger->error($response, compact('method', 'arguments'));
        }
        
        if ($this->settings['return_type'] == 'array')
            $result = $result->asArray();
        return $result;
    }
    
    /**
     * Return info about the bot
     * 
     * @return string
     */
    public function __toString() {
        return json_encode($this->getMe()['result']);
    }
    
    public function __get($key) {
        # Default arguments for methods that send messages (out stands for outgoing)
        if ($key == '_default_outgoing_arguments') {
            return [
                'chat_id' => $this->update->ChatID,
                'disable_web_page_preview' => $this->settings['disable_web_page_preview'],
                'disable_notification' => $this->settings['disable_notification'],
                'parse_mode' => $this->settings['parse_mode'],
            ];
        }
        
        # Used to retrieve private properties without write permissions
        return $this->$key;
    }
    
    /**
     * Return the decoded received update.
     * 
     * The update can be passed either via a POST request (webhooks) or as an argument in CLI (php yourscript.php api '{"update_id":....}')
     * 
     * @return Update
     */
    public function get_data() {
        if (!$this->update) {
            # you can emulate webhooks through cli:
            # php webhook.php api '{"update_id":....}'
            $update_json = file_get_contents('php://input');
            
            if (isset($GLOBALS['argv']) && isset($GLOBALS['argv'][1]) && $GLOBALS['argv'][1] == 'api')
                $update_json = $GLOBALS['argv'][2] ?? '[]';
            
            if ($update_json)
                $this->bind_data(new Update(json_decode($update_json, TRUE), $this));
        }
        
        return $this->update;
    } 
    
    /**
     * Bind a update to the Bot instance so it can use shortcuts to methods
     * 
     * @param ArrayObject|array $data The decoded update
     */
    public function bind_data(Update $data) {
        $this->update = $data;
    }
    
    /**
     * Generator to yield Update instances of updates fetched with long polling
     * 
     * @param array $params Associative array containing arguments to use in the getUpdates method
     */
    public function polling(array $params = []) {
        $params += ['offset' => 0, 'timeout' => 300];
        
        while (true) {
            $updates = $this->getUpdates($params)['result'];
            if (is_null($updates)) {
                $this->logger->notice('getUpdates failed while looping. Sleeping for 10s');
                    sleep(10);
                    continue;
            }
            
            foreach ($updates as $update) {
                $update = new Update($update, $this);
                $this->bind_data($update);
                yield $update;
                $params['offset'] = $update->update_id+1;
            }
        }
    }
    
    /**
     * Starts an infinite loop that calls $handler for each update fetched from Bot::polling
     * 
     * @param Callable $handler Callback that will handle the updates. It must accept ($bot, $update, ...$args) in its definition.
     * @param array ...$args Additional arguments to pass to $handler 
     */
    public function loop($handler, ...$args) {
        $this->logger->info('Starting long polling loop');
        foreach ($this->polling() as $update) {
            try {
                $handler($this, $update, ...$args);
                $this->logger->debug("Update {$update->update_id} of type {$update->update_type} delivered to the handler");
            } catch (\Throwable $t) {
                $this->logger->error((string)$t);
            }
        }
    }
    
    /**
     * Downloads a file stored in Telegram servers using its file id
     * 
     * @param ArrayObject|string $document The file id or an ArrayObject object containing it
     * @param string $local_file_path The file path to save in the local server
     * 
     * @return int Quantity of bytes saved
     */
    public function download_file($document, string $local_file_path = NULL) {
        if ($document instanceof ArrayObject)
            $document = $document->find('file_id');
        
        $contents = $this->read_file($document);
        if (!$local_file_path) {
            if (!($local_file_path = $this->getFile(['file_id' => $document])['file_name']))
                return false;
        }
        
        return file_put_contents($local_file_path, $contents);
    }    
    
    /**
     * Get the contents of a file stored in Telegram servers
     * 
     * @param ArrayObject|string $document The file id or an ArrayObject object containing it
     * 
     * @return string The contents of the file_id
     */
    public function read_file($document) {
        if ($document instanceof ArrayObject) 
            $document = $document->find('file_id');
        
        if (!($file_path = $this->getFile(['file_id' => $document])['file_path']))
            return false;
        $file_url = "https://api.telegram.org/file/bot{$this->BOT_TOKEN}/{$file_path}";
        return file_get_contents($file_url);
    }    
    
    /**
     * Shortcut to sendMessage
     * 
     * @param string $text The parameter 'text' to use in sendMessage
     * @param array $params Associative array containing arguments to pass to sendMessage
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function send($text, array $params = []) {
        $params += ['text' => $text] + $this->_default_outgoing_arguments;
        return $this->sendMessage($params);
    }
    
    /**
     * Shortcut to editMessageText
     * 
     * @param string $text The parameter 'text' to use in editMessageText
     * @param array $params Associative array containing arguments to pass to editMessageText
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function edit($text, array $params = []) {
        $params += ['text' => $text, 'message_id' => $this->update->find('message_id'), 'inline_message_id' => $this->update->inline_message_id] + $this->_default_outgoing_arguments;
        return $this->editMessageText($params);
    }
    
    /**
     * Shortcut to editMessageReplyMarkup
     * 
     * @param array|string $reply_markup The parameter 'reply_markup' to use in editMessageReplyMarkup. If it is an array it will be automatically encoded to JSON. 
     * @param array $params Associative array containing arguments to pass to editMessageReplyMarkup
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function edit_keyboard($reply_markup, array $params = []) {
        $params += ['reply_markup' => $reply_markup, 'message_id' => $this->update->message_id] + $this->_default_outgoing_arguments;
        return $this->editMessageReplyMarkup($params);
    }

    /**
     * Shortcut to sendChatAction
     * 
     * @param string $action The parameter 'action' to use in sendChatAction
     * @param array $params Associative array containing arguments to pass to sendChatAction
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function action(string $action = 'typing', array $params = []) {
        $params += ['action' => $action] + $this->_default_outgoing_arguments;
        return $this->sendChatAction($params);
    }
    
    /**
     * Create a mention to a user
     * 
     * @param int $user_id
     * @param array $params Associative array of optional parameters (parse_mode and use_last_name)
     * 
     * @return string
     */
    public function mention(int $user_id, array $params = []) {
        $parse_mode = "html";
        $use_last_name = false;
        extract($params, EXTR_IF_EXISTS);
        
        $name = $use_last_name? $this->update->Name : $this->update->FirstName;
        $username = $this->update->Username;
        if (!$name)
            return $user_id;
        
        if ($username)
            return "@{$username}";
        
        if (strtolower($parse_mode) == 'html') {
            $name = htmlspecialchars($name);
            return "<a href='tg://user?id={$user_id}'>{$name}</a>";
        }
        
        $name = escape_markdown($name, $parse_mode);
        return "[{$name}](tg://user?id={$user_id})";
    }
    
    /**
     * Shortcut to answerInlineQuery
     * 
     * @param array $results The parameter 'results' to use in answerInlineQuery
     * @param array $params Associative array containing arguments to pass to answerInlineQuery
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function answer_inline($results = [], array $params = []) {
        $params += ['results' => $results, 'inline_query_id' => $this->update->id, 'cache_time' => 0];
        return $this->answerInlineQuery($params);
    }
    
    /**
     * Shortcut to answerCallbackQuery
     * 
     * @param array $text The parameter 'text' to use in answerCallbackQuery
     * @param array $params Associative array containing arguments to pass to answerCallbackQuery
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function answer_callback($text = '', array $params = []) {
        $params += ['callback_query_id' => $this->update->id, 'text' => $text];
        return $this->answerCallbackQuery($params);
    }
    
    /**
     * Shortcut to deleteMessage
     * 
     * @param array $message_id The parameter 'message_id' to use in deleteMessage
     * @param array $params Associative array containing arguments to pass to deleteMessage
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function delete($message_id = null, array $params = []) {
        $params += ['message_id' => $message_id ?? $this->update->message_id, 'chat_id' => $this->update->ChatID];
        return $this->deleteMessage($params);
    }
    
    /**
     * Quickly send a local or remote (from Telegram servers) file to a Telegram chat
     * 
     * @param string $filename The file path or Telegram file id
     * @param array $params Associative array containing arguments to pass to sendDocument
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function doc($filename, array $params = []) {
        $this->action("upload_document", $params);
        $params += $this->_default_outgoing_arguments;
        
        $params['document'] = $filename;
        if (file_exists(realpath($filename)) && !is_dir(realpath($filename))) {
            $document = curl_file_create(realpath($filename));
            if (isset($params['postname'])) {
                $document->setPostFilename($params['postname']);
                unset($params['postname']);
            }
            $params['document'] = $document;
        }
        
        return $this->sendDocument($params);
    }
    
    /**
     * Write some text into a file and sends it to a Telegram chat
     * 
     * @param mixed $contents The contents to write into the file. If it is not a string neither an integer, the output of var_dump is used
     * @param array $params Associative array containing arguments to pass to sendDocument
     * 
     * @return MethodResult|array The decoded result of the method
     */
    public function indoc($contents, array $params = []) {
        if (!is_string($contents) && !is_int($contents)) {
            $contents = var_dump($contents, 1);
        }
        
        for ($i=0; $i<100; $i++) {
            $tempname = "phgram_indoc_{$i}.txt";
            if (!file_exists($tempname)) break;
        }
        
        file_put_contents($tempname, $contents);
        $result = $this->doc($tempname, $params);
        unlink($tempname);
        return $result;
    }
    
    /**
     * Check whether a user is member of a chat
     * 
     * @param int $user_id
     * @param mixed $chat_id
     */
    public function in_chat(int $user_id, $chat_id) {
        $member = $this->getChatMember(['chat_id' => $chat_id, 'user_id' => $user_id]);
        return $member['ok'] && !in_array($member['result']['status'], ['left', 'kicked']);
    }
    
    /**
     * Check whether a user is administrator of a chat
     * 
     * @param int $user_id
     * @param mixed $chat_id
     */
    public function is_admin($user_id = NULL, $chat_id = NULL) {
        $member = $this->getChatMember(['chat_id' => $chat_id ?? $this->update->ChatID, 'user_id' => $user_id ?? $this->update->UserID]);
        return in_array($member['result']['status'], ['administrator', 'creator']);
    }
    
    /**
     * Check whether a chat is a group
     * 
     * @param mixed $chat_id
     * 
     * @return bool
     */
    public function is_group($chat_id = null) {
        $Chat = $this->Chat($chat_id);
        return in_array($Chat['type'], ['supergroup', 'group']);
    } 
    
    /**
     * Check whether a chat is a private chat
     * 
     * @param mixed $chat_id
     * 
     * @return bool
     */
    public function is_private($chat_id = null) {
        $Chat = $this->Chat($chat_id);
        return $Chat['type'] == 'private';
    }
    
    /**
     * Shortcut for getChat
     * 
     * @param $chat_id
     */
    public function Chat($chat_id = NULL) {
        if (!$chat_id)
            return $this->update->getValue('chat');
        
        $Chat = $this->getChat(['chat_id' => $chat_id]);
        return $Chat['ok']? $Chat['result'] : false;
    }
    
    /**
     * Avoid responding simultaneouly to the same update_id.
     * It creates a file using the current update id and delete it after the update is successfully handled. If the file already exists, an Exception is thrown. 
     */
    function protect() {
        $protection = __DIR__."/{$this->update->update_id}.run";
        if (!file_exists($protection)) file_put_contents($protection, '1');
        else throw new Exception("Update of id {$this->update->update_id} is already being handled");
        register_shutdown_function(function() use ($protection) { @unlink($protection); });
        
        return $protection;
    }
}