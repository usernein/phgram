<?php
$base = <<<EOF
<?php
namespace phgram\Objects;
use \phgram\ArrayObj;

class Base extends ArrayObj {
	public \$data;
	public \$bot;
	public \$arguments = [];
	
	public function __construct(\$data, \phgram\Bot \$Bot = null) {
		if (\$data instanceof ArrayObj) \$data = \$data->asArray();
		\$this->data = \$data;
		\$this->bot = \$Bot;
	}
	public function json(\$flags = 480) {
		return json_encode(\$this->data, \$flags);
	}
	public function __call(\$method, \$arguments = [[]]) {
		\$args = array_replace(\$this->arguments, \$arguments[0]);
		return \$this->bot->\$method(\$args);
	}
}
EOF;
#file_put_contents('base.php', $base);
$class_base = <<<EOF
<?php
namespace phgram\Objects;
class %s extends \phgram\Objects\Base {
	public function __construct(\$data, \phgram\Bot \$Bot = null) {
		parent::__construct(\$data, \$Bot);
		\$this->arguments = [];
	}
}
EOF;
$class_regex = '<?php
namespace phgram\Objects;
class %s extends \phgram\Objects\Base {
	public function __construct($data, \phgram\Bot $Bot = null) {
		parent::__construct($data, $Bot);
		$this->arguments = [];
	}
}';
$class_regex = '#^'.str_replace('%s', '.+', preg_quote($class_regex)).'$#';
$glob = glob('./*.php');
foreach ($glob as $file) {
	$ignore = ['base.php', 'Message.php', 'cr.php', 'InlineKeyboardMarkup.php', 'InlineKeyboardButton.php', 'User.php', 'Chat.php'
];
	if (
		!is_file($file) ||
		in_array(basename($file), $ignore) ||
		#!preg_match($class_regex, file_get_contents($file))
	) continue;
	$name = str_replace('.php', '', basename($file));
	file_put_contents($file, sprintf($class_base, $name));
	#file_put_contents($file, str_replace(['', ''], '', file_get_contents($file)));
}