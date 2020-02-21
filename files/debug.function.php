<?php
function phgram_pretty_debug($offset = 0, $detailed = FALSE, $ident_char = '  ', $marker = '- ') {
	$str = '';
	$debug = debug_backtrace();
	if ($offset) {
		$debug = array_slice($debug, $offset);
	}
	$debug = array_reverse($debug);
	
	foreach ($debug as $key => $item) {
		$ident = str_repeat($ident_char, $key);
		
		$function = $line = $file = $class = $object = $type = '';
		$args = [];
		extract($item);
		
		$args = count($args);
		$args .= ($args != 1? ' args' : ' arg');
		if ($args == '0 args') $args = '';
		$function = $class.$type.$function."({$args})";
		
		if (!$detailed) $file = basename($file);
		$str .= "{$marker}{$ident}".$file.":{$line}, {$function}\n";
	}
	return $str;
}