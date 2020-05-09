# phgram

Dinamic, fast and simple framework to develop Telegram Bots with PHP7.
Based on [TelegramBotPHP](https://github.com/Eleirbag89/TelegramBotPHP).

## Requirements
* PHP 7

## Installing
* Download `phgram.phar` and save it on your project directory.
* Add `require 'phgram.phar';` at the top of your script.

## Examples
### Webhooks
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE');

$text = $bot->Text();
$chat_id = $bot->ChatID();

if ($text == '/start') {
    $bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
    $bot->send('How are you?');
}
```
### Long polling
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE');

$offset = 0;
while (true) {
    $updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 300])['result'];
    foreach ($updates as $key => $update) {
        $bot->setData($update);

        $text = $bot->Text();
        $chat_id = $bot->ChatID();

        if ($text == '/start') {
            $bot->sendMessage(['chat_id' => $chat_id, 'text' => 'Hello World!']);
            $bot->send('How are you?');
        }
        
        $offset = $update['update_id']+1;
    }
}
```

## Updating
To update phgram to the lastest version, run this command in shell:
`wget https://raw.githubusercontent.com/usernein/phgram/master/phgram.phar`

Or run this php snippet:
`copy('https://raw.githubusercontent.com/usernein/phgram/master/phgram.phar', 'phgram.phar');`

## BotAPI Methods
phgram supports all methods and types described on the [official docs](https://core.telegram.org/bots/api).
Just call any BotAPI method as a \phgram\Bot class method:
```php
$bot->getChat(['chat_id' => '@hpxlist']);
```
```php
$bot->getMe();
```

- The result is an instance of `MethodResult` (you can also set the result type to be an array or stdClass object, editing the value of the attribute $data\_type to 'array' or 'object')
- All methods names are case-insensitive.

## phgram Methods
### Shortcuts
You have a range of pre-defined method shortcuts. You can use it to make your code cleaner and easier to read, write and understand.
e.g. instead of:
```php
$bot->sendMessage(['chat_id' => $chat_id, 'text' => "Hi! I'm sending you a file!", 'parse_mode' => 'HTML', 'disable_web_page_preview' => true]);
$bot->sendChatAction(['chat_id' => $chat_id, 'action' => 'upload_document']);
$bot->sendDocument(['chat_id' => $chat_id, 'document' => curl_file_create('document.txt'), 'caption' => 'Here is it!']);
```
use:
```php
$bot->send("Hi! I'm sending you a file!");
$bot->doc('document.txt');
```

All shortcuts accepts 1 or 2 arguments. The first parameter is always the "main" argument, i.e. the most "important" argument on the method (e.g. 'text' for sendMessage, 'document' for sendDocument, 'action' for sendChatAction). The second argument (optional) is an associative array with custom/additional parameters to use in the method.
Examples:
```php
$bot->send('Hey!');
```
```php
$bot->send('Hey!', ['disable_notification' => TRUE]);
```

**P.S.:** Please remember that phgram support *ALL* methods of BotAPI. The list below is only the list of **shortcuts**. You can normally call all BotAPI methods in the traditional way.

List of pre-defined shortcuts:

Name|Method|Default parameters|Note
---|---|---|---
send|sendMessage|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE|The first parameter is the text.
reply|sendMessage|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE, reply\_to\_message\_id=MessageID()|The first parameter is the text.
edit|editMessageText|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE, message\_id=MessageID()|The first parameter is the text. Note: if you ommit the 'message_id', it will assume as the rceived 'message_id' on the update, and it will work only with CallbackQuery updates.
doc|sendDocument|chat\_id=ChatID(), parse\_mode=HTML, disable\_web\_page\_preview=TRUE|The first parameter is the relative path to the document to upload.
action|sendChatAction|chat\_id=ChatID(), action=typing|The first parameter, also optional, is the action.
delete|deleteMessage|chat\_id=ChatID(), message\_id=MessageID()|The first parameter is the message\_id to delete and the second parameter is the chat\_id. Both are optional

To change the default parse mode (HTML), overwrite the value of the attribute $bot->default_parse_mode:
```php
$bot->send('<i>Hi, with HTML!</i>');
$bot->default_parse_mode = 'Markdown';
$bot->send('_Hi, with Markdown._');
$bot->default_parse_mode = 'HTML';
$bot->send('<i>Hi, with HTML again!</i>);
```

#### Creating custom shortcuts
You can set new shortcuts by adding new elements into the array $bot->shortcuts. The key should be the shortcut name and the value should be an array with its info:
```php
$bot->shortcuts['vid'] = [
    'method' => 'sendVideo',
    'default_parameters' => [
        'chat_id' => function ($bot, $arguments) { return $bot->ChatID(); },
        'parse_mode' => 'HTML',
    ],
    'first_parameter' => 'file_id',
];
$bot->vid($file_id);
```
The definition array should have these three elements: method (the BotAPI method name), default_parameters (array of default parameters in the format parameter => default_value), first_parameter (the parameter that the first argument of the shortcut belongs to)
The values of 'default_parameters' can be either a normal value or a callback to get the value. The callback should accept one argument: the \phgram\Bot class and should return the wanted value for that parameter.
You need to use callbacks when the default_parameters may change after the definition (e.g. in polling scripts)

### Special shortcurts:

Name|Description|Parameters|Return|Note
---|---|---|---|---
read\_file|Get the contents of a file|(String or phgram\ArrayObj object, required) file\_id|(String) Content of the file.|This function don't work with files bigger than 20MB.
download\_file|Get the contents of a file and save it into a local file|(String or phgram\ArrayObj object, required) file\_id, (String, optional) local\_file\_path|If the download has been successful, returns (Integer) size of the file. Otherwise returns FALSE.|This function don't work with files bigger than 20MB. If the second argument is omitted, then the file is saved with its original name in the current working directory.
mention|Generate a mention to a user\_id. If the user has username, it is returned. If not, a HTML/Markdown mention hyperlink is returned.|(Integer, required) user\_id, (String, optional) parse\_mode|(String) A mention to the user.|In case of errors, the passed id is returned. The default parse mode is HTML.
in\_chat|Check if a user is in the specified chat.|(Integer, required) user\_id, (Mixed, required) chat\_id|(Bool)|
is\_admin|Check if a user is an administrator of the specified chat.|(Integer, optional) user\_id, (Mixed, optional) chat\_id|(Bool)|The default value of the first and second parameters are, respectively, the current user id and chat id.
is\_group|Check if the current chat is a supergroup.|(void)|(Bool)|
is\_private|Check if the current chat is a private conversation.|(void)|(Bool)|
respondWebhook|Prints out the method and arguments as response to webhook.|(String, required) method, (Array, required) arguments)|(void)|
Chat|Returns the Chat object of the specified Chat id|(Mixed, optional) the chat id|(phgram\Objects\Chat object) the Chat object|If the first argument is omitted or NULL, it will return the Chat object of the current chat

### Data shortcuts
Instead of using things like:
```php
<?php
$content = file_get_contents('php://input');
$update = json_decode($content, true);
$text = $update['message']['text'];
$chat_id = $update['message']['chat']['id'];
$user_id = $update['message']['from']['id'];
$message_id = $update['message']['message_id'];
```
phgram gives you simple and dinamic method to get these values:
```php
<?php
include 'phgram.phar';
$bot = new Bot('TOKEN:HERE');

$text = $bot->Text();
$chat_id = $bot->ChatID();
$user_id = $bot->UserID();
$message_id = $bot->MessageID();
```
Doesn't really matter the update type. If the value exists, it is returned. If not, NULL is returned.

There are some special cases, as with calback\_query, that has 'message' field. The internal function 'getValue', used by all data shortcuts, will always search the value firstly outside 'message' field. If not found, it will search a correspondent value in inside 'message' field of callback\_query.

e.g. in a callback\_query update, Text() will return the text of the message that has the inline keyboard. By the way, UserID() will return the id of the user that selected the button, because callback\_query has a 'from' field and it gains priority over the 'from' inside 'message'.

This is the complete list of data shortcuts methods:
* Text()
* ChatID()
* ChatType()
* MessageID()
* Date()
* UserID()
* FirstName()
* LastName()
* Name() _(FirstName + LastName)_
* Username() _(without '@')_
* Language()
* ReplyToMessage()
* Caption()
* InlineQuery()
* ChosenInlineResult()
* ShippingQuery()
* PreCheckoutQuery()
* CallbackQuery()
* Location()
* Photo()
* Video()
* Document()
* Entities()
* UpdateID()
* ForwardFrom()
* ForwardFromChat()
* Message()
* ReplyMarkup()

All these methods will try to return the corresponding BotAPI object of the result (all objects are currently supported, they are under the namespace \phgram\Objects). i.e. ReplyMarkup() will return a phgram\Objects\InlineKeyboardMarkup object, Message() will return a phgram\Objects\Message, etc.
Is important to notice that these objects extends phgram\ArrayObj, so you can get its values as ['keys'] and/or as ->properties, mixing or not. Example:
```php
$replied = $bot->ReplyToMessage(); # \phgram\Objects\Message
echo $replied['from']['id'] . "\n"; // correct
echo $replied->from->id . "\n"; // correct
echo $replied['from']->id . "\n"; // correct
echo $replied->from['id'] . "\n"; // correct
```

### Utilities

Name|Description|Parameters|Note
---|---|---|---
getData|Returns the update as an associative array.|(void)|
getUpdateType|Returns a string with the update type ('message', 'channel_post', 'callback_query')|(void)|
setData|Writes a new value to the saved data (the return of getData())|(Array, required) The new data|The value of getUpdateType()

### Reply\_markup functions
Please note that the items below are **functions**, **not methods**.
These functions are used to generate objects (InlineKeyboardMarkup, ReplyKeyboard, etc) for reply\_markup parameter in some methods.
All them are under \phgram namespace.

#### ikb
Use this function to generate a InlineKeyboardMarkup object. Syntax:
```php
$options = [
    [ ['text', 'data', 'callback_data'] ]
];
$keyboard = \phgram\ikb($options);
```
$options is a array of lines. Each line is an array that contains at least one button (also an array).
The button is an array that may contain 2 or 3 elements, depending of the button type.
Syntax:
```php
[TEXT, VALUE, TYPE='callback_data']
```
TEXT is the button text.
VALUE is the data of the button, i.e. the callback_data value, the url address...
TYPE is the type of the button ('callback_data', 'url'...). If TYPE is 'callback\_data', VALUE will be used as callback data. If TYPE is 'url', the VALUE will be the url which the button will link to, and so on.

_Note:_ Only TEXT and VALUE are required. The default value of TYPE is callback\_data.
So, for callback buttons, you can ommit the TYPE parameter. Check this example:
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE');
$options = [
    [ ['Callback 1', 'callback 1'], ['Callback 2', 'callback 2'] ],
    ['URl 1', 't.me/usernein', 'url'], ['URl 2', 't.me/hpxlist', 'url'] ],
    ['Inline 1', 'aleatory query', 'switch_inline_query'], ['Inline 2', 'another query', 'switch_inline_query_current_chat'] ]
];
$keyboard = \phgram\ikb($options);
$bot->send('Testing', ['reply_markup' => $keyboard]);
```
You can mix the buttons types, use emojis, and many cool things.
By default, \phgram\ikb returns a InlineKeyboardMarkup object. You can pass TRUE as second argument ($encode) to get the result as JSON.

#### btn
This function is used by `ikb()` to generate a InlineKeyboardButton object, so you probably will not use it.

Syntax:
```php
btn( TEXT, VALUE, TYPE='callback_data' )
```

The result is a InlineKeyboardButton object as array.

#### kb
Use this function to generate a ReplyKeyboardMarkup object.

The first parameter is required and the other 3 are optional.

Parameters:

Name|Required|Type|Default value|Description
---|---|---|---|---
$options|Yes|Array||An array with the same lines structure of `ikb()`, but different buttons structure.
$encode|No|Boolean|False|If true, the result of the function will be a JSON.
$params|No|Array|array('selective' => true)|Additional parameters for the keyboard (selective, one_time_keyboard, resize_keyboard)

$options is a array of lines. Each line might contain KeyboardButton objects (as array) (generated by `kbtn()`) or for simple buttons, that doesn't request contact nor location, simple strings.

Basic example:
```php
$options = [
    [ 'Button 1', 'Button 2' ],
    [ ['Send contact', 'request_contact' => true] ]
];

$keyboard = \phgram\kb($options);
$bot->send('Testing...', ['reply_markup' => $keyboard]);
```
More info at the [docs](https://core.telegram.org/bots/api#replykeyboardmarkup).

#### kbtn
\phgram\kb uses this function to generate a KeyboardButton object (as array, to use inside $options)

Parameters:

Name|Required|Type|Default value|Description
---|---|---|---|---
$text|Yes|String||_Text of the button. If none of the optional fields are used, it will be sent as a message when the button is pressed._
$encode|No|Boolean|False|If true, the result of the function will be a JSON.
$params|No|Array|array()|Additional parameters for the button (request_contact, request_location)

#### hide_kb
Use this function to generate a ReplyKeyboardRemove object (JSON-encoded).

Parameters:

Name|Required|Type|Default value|Description
---|---|---|---|---
$selective|No|Boolean|True|If true, the keyboard will be only shown to specific users.
$encode|No|Boolean|False|If true, the result of the function will be a JSON.

#### forceReply
Use this function to generate a ForceReply object (JSON-encoded).

Parameters:

Name|Required|Type|Default value|Description
---|---|---|---|---
$selective|No|Boolean|True|If true, the keyboard will be only shown to specific users.
$encode|No|Boolean|False|If true, the result of the function will be a JSON.

## Handling errors
A method call might fail sometimes. To get reports of these errors, you may pass a chat id as second parameter to the Bot class constructor. Example:
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE', 276145711);
```
The bot will send the JSON-encoded response of the unsuccessful call. Example:
_{"ok":false,"error_code":400,"description":"Bad Request: message is not modified"}_

To disable/enable error reporting, you can overwrite the `$debug` attribute with TRUE or FALSE. Set it to FALSE to disable and to TRUE to enable.

To disable error reporting for a single method, put '@' before the call:
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE', 276145711);
$bot->debug = FALSE; # disabled
$bot->debug = TRUE; # enabled again

$bot->send('<Hey!'); # Since HTML is the default parse_mode, this call will fail and report a HTML error.
@$bot->send('<Hey!'); # Quiet error
```
If you set the PHP value 'error_reporting' to 0, you will not receive these reports.

You can also set a new value for `$debug_admin` attribute, to tell the script where should it send the reports.
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE', 276145711);
$bot->debug_admin = 204807919; # not me anymore :(
$bot->debug_admin = 276145711; # me again :)
$bot->debug_admin = 200097591; # changed again.
$bot->debug = FALSE; # nobody
```

You can also configure phgram to report internal PHP errors. There's a class called BotErrorHandler for it:
```php
\phgram\BotErrorHandler::register('TOKEN:HERE', 276145711); # Done!
```
The first parameter is the bot token (to send the alert) and the second is the chat id (to receive the alert);
You can also pass a third parameter if you don't want the update data (the message, the sender, the chat, etc) to be shown:
```php
$handler = new BotErrorHandler('TOKEN:HERE', 276145711, false); # Done!
```
You can change the values by overwriting the attributes:
```php
\phgram\BotErrorHandler::$bot = 'NEW:TOKEN';
\phgram\BotErrorHandler::$admin = 200097591;
\phgram\BotErrorHandler::$show_data = true;
```

## MethodResult
Every method called by phgram (even shortcuts) returns to you a \phgram\MethodResult instance.
This doesn't affect your old-styled (with arrays) code. Since it implements \phgram\ArrayObj, you can access its values by ['indexes']. And as it is also an object, you can also access its values as $object->attributes.

Another cool thing here is that you don't need to pass through 'result' to access its values:
```php
<?php
require 'phgram.phar';
$bot = new \phgram\Bot('TOKEN:HERE');

$result = $bot->send('Hey!');
echo $result['result']['message_id'] ."\n"; # this works!
echo $result->result->message_id ."\n"; # this also works!
echo $result['message_id'] ."\n"; # yeah, it works!
echo $result->message_id ."\n"; # why shouldn't it work? :)
```

Also phgram will try to parse the result with \phgram::parseResult to recursively convert it into BotAPI objects (under \phgram\Objects namespace). Some of them has custom methods you can use to make the things evem cooler. Let's see these examples with a Message object:
```php
$msg = $bot->send('Hey!');
$msg->edit('Hi.'); # will edit the sent message
$msg->append(' How are you?'); # now the message text is "Hi. How are you?"
$msg->reply('Do you love phgram?'); # will send a new message replying to the $msg message
$msg->forward(200097591); # will forward the message to the user 200097591
$msg->forward([276145711, 200097591, 204807919]); # you can also pass an array of recipients
$msg->delete(); # will delete the message
```

## ArrayObj
\phgram\ArrayObj is just a class that lets the code access the values of an array using ['keys'] and ->attributes, and other cool things:
Methods:

Method|Parameters|Result|Description
---|---|---|---
asArray||Array|Recursively converts the object to an array and returns the converted array. You can use it in two ways: `$array = $ArrayObj->asArray();` or `$array = $ArrayObj();`
find|$search|Mixed|Recursively searchs for the key $search and returns the first occurrence

ArrayObj can also be converted to json (passing the object directly to json_encode or casting it as a string) and dumped by print_r and var_dump. That's pretty, you should try.

## BotAPI Objects
When you call a method, you receive a MethodResult object. If the JSON of the method result has a 'result' field ans it's an object or array, phgram will recursively try to convert it and its values to its corresponding objects under \phgram\Objects namespace, i.e. the BotAPI objects.
e.g. the method getChat will return a Chat object, according to BotAPI. In phgram, it is a \phgram\Objects\Chat object.
Some of the BotAPI objects in phgram have custom shortcuts, as the Message object (as you saw in the MethodResult section).
But all of them holds the main \phgram\Bot class in a internal attribute. What does this mean? You can call any BotAPI method using the BotAPI object just like you do with the \phgram\Bot object.
That's not all. Some of the BotAPI objects in phgram have a list of arguments to use when you call a method using it (then you don't need to pass the object as a argument). e.g. the argument list of the object \phgram\Objects\Chat is: array('chat_id' => $this->chat->id). i.e. it uses its chat_id in its argument list, so if you call any method using the object, you don't need to pass the chat_id. The object will do it by itself:
```php
$Message = $bot->send('Sending a message to the current chat.'); # \phgram\MethodResult that has a \phgram\Objects\Message object as result
$Chat = $Message->chat; # \phgram\Objects\Chat

# $Chat->arguments now is the same of ['chat_id' => $Chat->id]. If we call getChatMember through it, it will use its arguments (combined to ours), so we don't need to pass chat_id:
$ChatMember = $Chat->getChatMember(['user_id' => 276145711]); # MethodResult with ChatMember as result

# How it would be in the old way:
$ChatMember = $bot->getChatMember(['chat_id' => $Chat->id, 'user_id' => 276145711]);
```
We just saw that the BotAPI objects know exactly what they are supposed to do with its data (not all objects, because not them all are edited with a list of arguments), using its internal $arguments attribute.
i.e. InlineKeyboardMarkup knows that its data is supposed to be used in a 'reply_markup' parameter, Chat know that its id is supposed to be used in a 'chat_id' parameter, User know that its id is supposed to be used in a 'user_id' object and so on.
Another very cool thing you can do with these objects is to pass them to any method call, outside an associative array of parameters. Let's see an example with InlineKeyboardMarkup:
```php
$keyboard = ikb([
    [ ['Button', 'Data'] ]
]); # \phgram\Objects\InlineKeyboardMarkup

# Normal mode
$bot->send('Text', ['reply_markup' => $keyboard]);
# Passing outside the arguments array
$bot->send('Text', $keyboard);
# Also correct
$bot->send('Text', $keyboard, ['disable_notification' => true]);
```
The internal function \phgram\join_arguments will join $keyboard->arguments and any other arguments passed, before performing the request.
This trick can be done with any BotAPI object, as many you want (you can pass multiple objects to the same call, but if there's any duplicated parameter, the last one will override the other)

All BotAPI objects are supported, but not them all have custom shortcuts and arguments list. Check the list below to know the custom shorcuts and arguments list of each object:

Object|Shortcuts|Arguments
---|---|---
Chat||['chat_id' => $this->id]
User||['chat_id' => $this->id, 'user_id' => $this->id]
Message|append($text, ...$params), reply($text, ...$params), forward($chat_id, ...$params)|['message_id' => $this->message_id, 'chat_id' => $this->chat['id']]
InlineKeyboardMarkup|load($source), &addLine($buttons = [], $offset = null), addButton($button, $line = null, $offset = null), getFromData($callback_data), getFromKey($key), getFromText($text), save($json = true)|['reply_markup' => $this->data]
InlineKeyboardButton|delete()|[]
ReplyKeyboardMarkup|same of InlineKeyboardMarkup|same of InlineKeyboardMarkup
KeyboardButton|same of InlineKeyboardButton|same of InlineKeyboardButton
File||['file_id' => $this->file_id]
PhotoSize||['file_id' => $this->file_id]

## Settings
phgram has some attributes that can be set to adjust its behavior. To edit them, just set a new value for $bot->$attribute. e.g: `$bot->default_parse_mode = 'Markdown';`

Name|Default value|Description
---|---|---
report_mode|message|How do you want to receive the BotAPI errors reports? Can be 'message' or 'notice'
report_show_view|1|Should the error report include a tree showing the file that thrown the error?
report_show_data|1|Should the error reporting include information about the update (the text, sender, chat, etc)?
report_obey_level|1|If the error_reporting is 0, should \phgram\Bot obey it and don't report the error? (if false, calls with '@' before will report errors, if there's any);
default_parse_mode|HTML|The default parse mode for the shortcuts. Can be 'Markdown', 'HTML' or NULL (when NULL, no parse_mode will be used)
report_max_args_len|300|If report_show_data is True, all strings will be cutted to the first $report_max_args_len characters
data_type|ArrayObj|If 'array', all BotAPI methods calls and shortcuts will return the result as array. Same for 'object'. If it's 'ArrayObj', it will behave as the default way, using MethodResult, ArrayObj and BotAPI objects.

## Contact

* [Telegram](https://t.me/phgramgroup)