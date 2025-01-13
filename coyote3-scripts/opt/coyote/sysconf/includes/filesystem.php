<?
// Script: filesystem
// Purpose: Filesystem IO routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 	06/10/2004
//			11/30/2012	Updates for use with PHP5
//			10/10/2024	Updates for Coyote Linux 3.1 based on Alpine Linux and
//						for executing as non-root user

require_once("functions.php");

function write_config($filename, $data) {

	global $DEBUG_MODE;

	$ret = 0;

	if (!($DEBUG_MODE && DEBUG_NOEXEC)) {
		$ret = file_put_contents($filename, $data."\n", FILE_APPEND);
	}

	debug_print("$data written to $filename.\n");
	return $ret;
}

function write_proc_value($procentry, $value) {
	
	global $DEBUG_MODE;

	$procfile = fopen("/proc/".$procentry, "w");

	debug_print("write_proc_value: Writing value: $value to $procfile");
	// If we are not running as root, chances are we will not be
	// able to write to a proc entry, sudo it
	if (posix_getuid() > 0) {
		return sudo_exec("echo $value > $procfile");
	} else {
		if (!($DEBUG_MODE && DEBUG_NOEXEC)) {
			// We are already root, no need to invoke sudo
			if ($procfile) {
				fwrite($procfile, $value);
				fclose($procfile);
			} else {
				return 1;
			}
		}
		return 0;
	}
}

function copy_template($template, $location, $need_root = false) {
	
	global $DEBUG_MODE;
	
	debug_print("copy_template: $template -> $location (need_root=$need_root)");
	if ($need_root) {
		return sudo_exec("cp ".COYOTE_TEMPLATE_DIR.$template, $location);
	} else {
		if (!($DEBUG_MODE && DEBUG_NOEXEC)) { 
			return copy(COYOTE_TEMPLATE_DIR.$template, $location);
		} else {
			return 0;
		}
	}
}


// The mount_flash_* functions were uses in Coyote Linux <= 3.0 to mount the 
// system configuration storage for rw/ro. Coyote Linux 3.1 does not have this
// function as it is no longer based on Vortech Embedded Linux.

function mount_flash_rw() {

	debug_print("mount_flash_rw() called, ignoring.");

	// global $IN_DEVELOPMENT;

	// if (!$IN_DEVELOPMENT) {
	// 	sudo_exec("/bin/mount /mnt -o remount,rw");
	// }

}

function mount_flash_ro() {

	debug_print("mount_flash_ro() called, ignoring.");

	// global $IN_DEVELOPMENT;

	// if (!$IN_DEVELOPMENT) {
	// 	sudo_exec("/bin/mount /mnt -o remount,ro");
	// }
}


?>
