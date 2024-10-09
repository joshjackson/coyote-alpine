<?

	$SitePath = '/opt/webadmin/htdocs/';
	
	require_once("functions.php");
	require_once("configfile.php");
	require_once("services.php");
	require_once($SitePath."includes/webfunctions.php");

	$working_filename = "/tmp/working-config";
	
	$config_filename = "/etc/config/sysconfig";

	$configfile = new FirewallConfig;


function WriteConfigObject($filename) {

	global $configfile;

	if (!$filename) {
		$filename = "php://stdout";
	}

	$outfile = fopen($filename, "w");
	if (!$outfile) {
		return false;
	}
	
	$outstr = serialize($configfile);
	fwrite($outfile, $outstr);
	fclose($outfile);
	return true;

}

function LoadConfigObject($filename) {

	global $configfile;

	$instr = implode("", @file($filename));
	$configfile = unserialize($instr);
	
}


function WriteWorkingConfig() {
	global $working_filename;
	$ret = WriteConfigObject($working_filename);
	return $ret;
}

function SaveSystemConfig() {

	global $config_filename;
	global $working_filename;
	global $configfile;
	
	mount_flash_rw();
	$ret = $configfile->WriteConfigFile($config_filename);
	mount_flash_ro();
		
	if ($ret) {
		unlink($working_filename);
	}
		
	return $ret;
}		

function GetFirmwareVersion() {

	$fwfile = file("/etc/config/image_version");
	$loaderfile = file("/tmp/loader.ver");
	$fwinfo = array();
	$fwinfo["version"] = PRODUCT_VERSION;
	$fwinfo["build"] = trim($fwfile[0]);
	$fwinfo["date"] = trim($fwfile[1]);
	$fwinfo["loader"] = $loaderfile[0];
	
	return $fwinfo;
}

if (!file_exists($working_filename)) {
	global $configfile;
	if (!$configfile->LoadConfigFile($config_filename)) {
		print("Error loading config file.\n");
		die;
	}
} else {
	LoadConfigObject($working_filename);
}		

require_once($SitePath.'includes/valid.php');

?>
