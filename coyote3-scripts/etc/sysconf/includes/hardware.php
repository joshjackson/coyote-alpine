<?
// Script: hardware
// Purpose: Hardware detection routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

function GetNicModuleNames() {

	global $IN_DEVELOPMENT;

	$maxinterfaces = 999;
	
	$modlist = array();

	if ($IN_DEVELOPMENT) {
		$modfile = fopen("niclist", 'r');
	} else {
		$modfile = fopen("/etc/sysconf/niclist", 'r');
	}
	
	if (!$modfile) {
		print("could not open PCI ID list.");
		die;
	}
	$devcnt = 0;
	while (!feof($modfile)) {
		$modline = chop(fgets($modfile, 1024));
		if ($modline) {
			$modentry = array ("device" => "", "module" => "");
			sscanf($modline, "%s %s", $modentry["device"], $modentry["module"]);
			$modlist[$devcnt] = $modentry;
			$devcnt++;
		}
	}
	fclose($modfile);

	$niclist = array();
	$niccnt = 0;
	$procfile = fopen("/proc/bus/pci/devices", 'r');
	if (!$procfile) {
		print("Unable to open PCI bus proc entry.");
		die;
	}
	while (!feof($procfile)) {
		list($businfo, $vendor, $model) = sscanf(chop(fgets($procfile, 1024)), "%s %4s%4s");
		$devtype = trim($vendor) . ":" . trim($model);
		for ($t = 0; $t < count($modlist); $t++) {
			if (!strcasecmp($devtype, $modlist[$t]["device"])) {
				$niclist[$niccnt] = $modlist[$t]["module"];
				$niccnt++;
				if ($niccnt == $maxinterfaces) {
					break;
				}
			}
		}
	}
	fclose($procfile);

	return $niclist;
}

?>
