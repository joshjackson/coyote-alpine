<?
// Script: consoleio
// Purpose: Console IO routines
//
// FIXME - do something useful with IO routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

function write_error($errstr) {
	print($errstr."\n");
}

function do_print($str, $human_readable = false) {
	if ($human_readable) {
		print_r($str);
	} else {
		print($str);
	}
}

function debug_print($str, $human_readable = false) {
	
	global $DEBUG_MODE;

	if ($DEBUG_MODE && DEBUG_PRINT) {
		do_print($str, $human_readable);
	}

}
?>
