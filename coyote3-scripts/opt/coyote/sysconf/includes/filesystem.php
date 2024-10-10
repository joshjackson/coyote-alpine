<?
// Script: filesystem
// Purpose: Filesystem IO routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 	06/10/2004
//			11/30/2012 - Updates for use with PHP5 and Coyote Linux 4

function write_config($filename, $data) {

	global $DEBUG_MODE;

	$ret = 0;

	switch ($DEBUG_MODE) {
		case 1:
			$ret = file_put_contents($filename, $data."\n", FILE_APPEND);
		case 2:
			print("$data written to $filename.\n");
			return $ret;
			break;
		;;
		case 0:
			return file_put_contents($filename, $data."\n", FILE_APPEND);
			break;
		;;
	}
}

function write_proc_value($procentry, $value) {

	$procfile = fopen("/proc/".$procentry, "w");

	if ($procfile) {
		fwrite($procfile, $value);
		fclose($procfile);
		return 1;
	}

	return 0;
}

function copy_template($template, $location) {
	return copy("/opt/coyote/sysconf/templates/$template", $location);
}

// FIXME - These functions need to call a helper app which has the proper
// permissions to remount the filesystem. The web server should drop root privs!
function mount_flash_rw() {

	global $IN_DEVELOPMENT;

	if (!$IN_DEVELOPMENT) {
		sudo_exec("/bin/mount /mnt -o remount,rw");
	}

}

function mount_flash_ro() {

	global $IN_DEVELOPMENT;

	if (!$IN_DEVELOPMENT) {
		sudo_exec("/bin/mount /mnt -o remount,ro");
	}

}


?>
