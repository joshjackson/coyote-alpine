<?
// Script: hardware
// Purpose: Hardware detection routines
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004
//
// 01/10/2025 - Add GetNetworkInterfaces function to enumerate network interfaces.
// 				This function reads the existing system interfaces instead of attempting to 
//				detect kernel driver modules. (They are already loaded by the time these scripts run)

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
