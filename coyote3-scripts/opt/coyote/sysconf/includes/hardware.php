<?
// Script: hardware
// Purpose: Hardware detection routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

// function GetNicModuleNames() {

// 	global $IN_DEVELOPMENT;

// 	$maxinterfaces = 999;
	
// 	$modlist = array();

// 	if ($IN_DEVELOPMENT) {
// 		$modfile = fopen("niclist", 'r');
// 	} else {
// 		$modfile = fopen("/opt/coyote/config/niclist", 'r');
// 	}
	
// 	if (!$modfile) {
// 		print("could not open PCI ID list.");
// 		die;
// 	}
// 	$devcnt = 0;
// 	while (!feof($modfile)) {
// 		$modline = chop(fgets($modfile, 1024));
// 		if ($modline) {
// 			$modentry = array ("device" => "", "module" => "");
// 			sscanf($modline, "%s %s", $modentry["device"], $modentry["module"]);
// 			$modlist[$devcnt] = $modentry;
// 			$devcnt++;
// 		}
// 	}
// 	fclose($modfile);

// 	$niclist = array();
// 	$niccnt = 0;
// 	$procfile = fopen("/proc/bus/pci/devices", 'r');
// 	if (!$procfile) {
// 		print("Unable to open PCI bus proc entry.");
// 		die;
// 	}
// 	while (!feof($procfile)) {
// 		list($businfo, $vendor, $model) = sscanf(chop(fgets($procfile, 1024)), "%s %4s%4s");
// 		$devtype = trim($vendor) . ":" . trim($model);
// 		for ($t = 0; $t < count($modlist); $t++) {
// 			if (!strcasecmp($devtype, $modlist[$t]["device"])) {
// 				$niclist[$niccnt] = $modlist[$t]["module"];
// 				$niccnt++;
// 				if ($niccnt == $maxinterfaces) {
// 					break;
// 				}
// 			}
// 		}
// 	}
// 	fclose($procfile);

// 	return $niclist;
// }

function GetNetworkInterfaces(): array {
    $basePath = '/sys/class/net/';
    $interfaces = [];

    if (!is_dir($basePath)) {
        return ['error' => 'The /sys/class/net directory is not accessible.'];
    }

    $interfaceDirs = scandir($basePath);
    foreach ($interfaceDirs as $interface) {
        // Skip . and ..
        if ($interface === '.' || $interface === '..') {
            continue;
        }

        $interfacePath = $basePath . $interface;

        // Ensure it's a directory for a network interface
        if (!is_dir($interfacePath)) {
            continue;
        }

        // Read interface state
        $stateFile = $interfacePath . '/operstate';
		$typefile = $interfacePath . '/type';
        $state = is_readable($stateFile) ? trim(file_get_contents($stateFile)) : 'unknown';
		$type = is_readable($typefile) ? trim(file_get_contents($typefile)) : 'unknown';
        // Collect interface details
		// 1 is ethernet
        if ($type == 1) {
			$interfaces[] = [
				'name' => $interface,
				'state' => $state,
			];
		}
    }
    return $interfaces;
}


?>
