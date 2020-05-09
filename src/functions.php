<?php
/*
 * This file is part of phgram which is released under MIT license.
 * See file LICENSE or go to https://opensource.org/licenses/MIT for full license details.
 */
 
namespace usernein\phgram;
function ikb(array $options = []) {
    $lines = [];
    $options = array_values($options);
    foreach ($options as $line_pos => $line_buttons) {
        $lines[$line_pos] = [];
        foreach ($line_buttons as $button_pos => $button) {
            $lines[$line_pos][$button_pos] = btn(...$button);
        }
    }
    $replyMarkup = [
        'inline_keyboard' => array_values($lines),
    ];
    return $replyMarkup;
}

function btn($text, string $value, string $type = 'callback_data') {
    return ['text' => $text, $type => $value];
}

function kb(array $options = [], array $params = ['selective' => true]) {
    $lines = [];
    foreach ($options as $line_pos => $line_buttons) {
        $lines[$line_pos] = [];
        foreach ($line_buttons as $button_pos => $button) {
            if (!is_array($button)) $button = [$button];
            $key = isset($button['text'])? 'text' : 0;
            $text = $button[$key];
            unset($button[$key]);
            $lines[$line_pos][$button_pos] = kbtn($text, $button);
        }
    }
    $replyMarkup = array_replace([
        'keyboard' => array_values($lines),
        'selective' => true,
    ], $params);
    return $replyMarkup;
}

function kbtn($text, array $params = []) {
    $replyMarkup = array_replace([
        'text' => $text,
    ], $params);
    return $replyMarkup;
}

function hide_kb(array $params = []) {
    $replyMarkup = array_replace([
        'remove_keyboard' => true,
        'selective' => true,
    ], $params);
    return $replyMarkup;
}

function force_reply(array $params = []) {
    $replyMarkup = array_replace([
        'force_reply' => true,
        'selective' => true,
    ], $params);
    return $replyMarkup;
}

function entities_to_html(string $text, $entities = []) {
    if ($entities instanceof ArrayObject) $entities = $entities->asArray();
    $to16 = function($text) {
        return mb_convert_encoding($text, "UTF-16", "UTF-8"); //or utf-16le
    };
    $to8 = function($text) {
        return mb_convert_encoding($text, "UTF-8", "UTF-16"); //or utf-16le
    };
    $message_encode = $to16($text); //or utf-16le

    foreach (array_reverse($entities) as $entity) {
        #if ($entity instanceof \phgram\ArrayObject) $entity = $entity->asArray();
        $original = htmlspecialchars($to8(substr($message_encode, $entity['offset']*2, $entity['length']*2)));
        $url = isset($entity['url'])? htmlspecialchars($entity['url']) : '';
        $id = @$entity['user']['id'];

        switch ($entity['type']) {
            case 'bold':
                $message_encode = substr_replace($message_encode, $to16("<b>$original</b>"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'italic':
                $message_encode = substr_replace($message_encode, $to16("<i>$original</i>"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'code':
                $message_encode = substr_replace($message_encode, $to16("<code>$original</code>"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'pre':
                $message_encode = substr_replace($message_encode, $to16("<pre>$original</pre>"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'text_link':
                $message_encode = substr_replace($message_encode, $to16("<a href='{$url}'>$original</a>"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'text_mention':
                $message_encode = substr_replace($message_encode, $to16("<a href='tg://user?id={$id}'>$original</a>"), $entity['offset']*2, $entity['length']*2);
                break;
        }
    }

    $html = $to8($message_encode);
    return $html;
}
function entities_to_markdown(string $text, $entities = []) {
    if ($entities instanceof ArrayObject) $entities = $entities->asArray();
    $to16 = function($text) {
        return mb_convert_encoding($text, "UTF-16", "UTF-8"); //or utf-16le
    };
    $to8 = function($text) {
        return mb_convert_encoding($text, "UTF-8", "UTF-16"); //or utf-16le
    };
    $md_escape = function ($text) {
        return preg_replace('#([*`_\(\)\[\]])#', "\\\\\\1", $text);
    };
    $message_encode = $to16($text); //or utf-16le

    foreach (array_reverse($entities) as $entity) {
        $original = $md_escape($to8(substr($message_encode, $entity['offset']*2, $entity['length']*2)));
        $url = isset($entity['url'])? htmlspecialchars($entity['url']) : '';
        $id = @$entity['user']['id'];

        switch ($entity['type']) {
            case 'bold':
                $message_encode = substr_replace($message_encode, $to16("*$original*"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'italic':
                $message_encode = substr_replace($message_encode, $to16("_{$original}_"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'code':
                $message_encode = substr_replace($message_encode, $to16("`$original`"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'pre':
                $message_encode = substr_replace($message_encode, $to16("```$original```"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'text_link':
                $message_encode = substr_replace($message_encode, $to16("[$original]($url)"), $entity['offset']*2, $entity['length']*2);
                break;
            case 'text_mention':
                $message_encode = substr_replace($message_encode, $to16("[$original](tg://user?id={$id})"), $entity['offset']*2, $entity['length']*2);
                break;
        }
    }

    $md = $to8($message_encode);
    return $md;
}
function var_dump($value, $return_string = false) {
    ob_start();
    \var_dump($value);
    $result = ob_get_contents();
    ob_end_clean();
    
    if ($return_string)
        return $result;
    echo $result;
}

function respond_webhook(array $arguments = []) {
    header("Content-Type: application/json");
    echo json_encode($arguments); // send the response
    http_response_code(200);
}

function close_connection(){
    // buffer all upcoming output
    ob_start();
    echo "OK\n";
    // get the size of the output
    $size = ob_get_length();
    
    // send headers to tell the browser to close the connection
    header("Content-Length: $size", true, 200);
    header('Connection: close', true, 200);
    
    // flush all output
    ob_end_flush();
    ob_flush();
    flush();
}
function escape_markdown($text, $parse_mode = 'markdown') {
    $parse_mode = strtolower($parse_mode);
    
    $specials = '*_`[]()';
    if ($parse_mode == 'markdownv2')
        $specials = '_*[]()~`>#+-=|{}.!';
    return str_replace($specials, '', $text);
}