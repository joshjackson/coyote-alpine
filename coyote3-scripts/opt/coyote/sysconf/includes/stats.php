<?
// Script: stats
// Purpose: Statistics calc and graphing functions
//
// Author: Joshua Jackson <jjackson@vortech.net>
// Date: 01/12/2004

require_once("functions.php");

$rrd = "/usr/bin/rrdtool";

function KernelIs26() {
	if($fh = fopen("/proc/sys/kernel/osrelease", 'r')) {
		$kver = fgets($fh, 1024);
		fclose($fh);
		$ret = (strpos($kver, "2.6.") == 0) ? true : false;	
	} else {
		$ret = false;
	}
	return $ret;
}

function InitStatsDB($Config) {

	global $rrd;

	// Initialize CPU statistics datafile
	if (!file_exists("/opt/coyote/statsdb/cpu.rrd")) {
		do_exec("$rrd create /opt/coyote/statsdb/cpu.rrd --step 60 ".
			"DS:cpu:GAUGE:120:0:100 ".
			"RRA:AVERAGE:0.5:1:1440"
		);
		if ($fh = fopen("/proc/stat", "r")) {
			$cpuline = fgets($fh, 1024);
			file_put_contents("/opt/coyote/statsdb/cpu.last", $cpuline);
			fclose($fh);
		}
		touch("/opt/coyote/statsdb/cpu.last");
	}

	// Initialize interface statistics datafiles
	foreach($Config->interfaces as $ifentry) {
		if ($ifentry["down"]) {
			continue;
		}
		$statdb="/opt/coyote/statsdb/".$ifentry["device"].".rrd";
		if (!file_exists("$statdb")) {
			do_exec("$rrd create $statdb --step 60 ".
				"DS:in:COUNTER:120:0:U ".
				"DS:out:COUNTER:120:0:U ".
				"RRA:AVERAGE:0.5:1:1440"
			);
		}
	}

}

function GetMemoryAllocation() {

}

function GetMemoryUtilization() {

	$memutil = 0;
	$readflag = 0;

	if ($fh = fopen("/proc/meminfo", "r")) {
		// Memory stat line
		do {
			$retstr = fgets($fh, 1024);
			if ($retstr !== false) {
				list($junk, $buffer) = explode(":", $retstr, 2);
				if ($junk == "MemTotal") {
					list($memdata[0], $junk) = explode(" ", trim($buffer), 2);
					$readflag |= 1;
				}
				if ($junk == "MemFree") {
					list($memdata[1], $junk) = explode(" ", trim($buffer), 2);
					$readflag |= 2;
				}
			} else {
				fclose($fh);
				return 0;
			}
		} while ($readflag != 3);
		$memutil = 100 - (($memdata[1] / $memdata[0]) * 100);
	}

	return number_format($memutil, 2);

}

function GetFirmwareAllocation() {
	$ret = number_format((disk_total_space("/") - disk_free_space("/")) / (1024 * 1024), 2);
	return $ret;
}

function GetBootFSAllocation() {
	$ret = number_format((disk_total_space("/mnt") - disk_free_space("/mnt")) / (1024 * 1024), 2);
}

function GetBootFSUtilization() {

	$dt = disk_total_space("/mnt");
	$du = $dt - disk_free_space("/mnt");

	return number_format(($du / $dt) * 100, 2);
}


function GetCPUUtilization ($do_update=true) {

	$utilization = 0;

	if ($fh = @fopen("/opt/coyote/statsdb/cpu.last", "r")) {
		if (KernelIs26()) {
			$lcpuinfo = split(" ", fgets($fh, 1024), 8);
		} else {
			$lcpuinfo = split(" ", fgets($fh, 1024), 5);
			$lcpuinfo[5] = 0;	// 2.4 kernels don't have these fields, just zero them out
			$lcpuinfo[6] = 0;
			$lcpuinfo[7] = 0;
		}
		fclose($fh);
		if ($fh = fopen("/proc/stat", "r")) {
			$cpuline = fgets($fh, 1024);
			
			// 2.6 kernels have an additional set of fields in /proc/stat
			if (KernelIs26()) {
				$cpuinfo = split(" ", $cpuline, 8);
			} else {
				$cpuinfo = split(" ", $cpuline, 5);
				$cpuinfo[5] = 0;	// 2.4 kernels don't have these fields, just zero them out
				$cpuinfo[6] = 0;
				$cpuinfo[7] = 0;
			}
			
			$lcputotal = $lcpuinfo[1] + $lcpuinfo[2] + $lcpuinfo[3] + $lcpuinfo[4] + $lcpuinfo[5]  + $lcpuinfo[6]  + $lcpuinfo[7];
			$cputotal = $cpuinfo[1] + $cpuinfo[2] + $cpuinfo[3] + $cpuinfo[4] + $cpuinfo[5] + $cpuinfo[6] + $cpuinfo[7];
			$cpudiff = $cputotal - $lcputotal;
			$idlediff = $cpuinfo[4] - $lcpuinfo[4];
			$utilization = ($idlediff / $cpudiff) * 100;

			fclose($fh);

			if ($do_update) {
				if (file_exists("/opt/coyote/statsdb/cpu.last")) {
					unlink("/opt/coyote/statsdb/cpu.last");
				}
				file_put_contents("/opt/coyote/statsdb/cpu.last", $cpuline);
			}
		}
	}

	return $utilization;

}

function GetInterfaceStats() {

	$stats= array();

	if ($fh = fopen("/proc/net/dev", "r")) {
		while($buffer = fgets($fh, 1024)) {
			if (preg_match("/^\s*\w+\d:/", $buffer)) {
				list($ifname, $buffer) = explode(":", $buffer, 2);
				$ifname = trim($ifname);
				$data = preg_split("/\s+/", trim($buffer), 16);
				$stats[$ifname] = $data;
			}
		}
	}
	return $stats;
}

function UpdateStats($Config) {

	global $rrd;

	// Update the CPU statistics
	if (file_exists("/opt/coyote/statsdb/cpu.rrd")) {
		// Update the database
		$utilization = GetCPUUtilization();
		do_exec("$rrd update /opt/coyote/statsdb/cpu.rrd N:$utilization");
	}

	// Update the Interface statistics
	$ifstats = GetInterfaceStats();

	debug_print($ifstate, true);

	foreach($Config->interfaces as $ifentry) {
		if ($ifentry["down"]) {
			continue;
		}
		$statdb="/opt/coyote/statsdb/".$ifentry["device"].".rrd";
		if (file_exists("$statdb")) {
			if (array_key_exists($ifentry["device"], $ifstats)) {
				$inb = $ifstats[$ifentry["device"]][0];
				$outb = $ifstats[$ifentry["device"]][8];
				do_exec("$rrd update $statdb N:$inb:$outb");
			}
		}
	}
}


function UpdateGraphics($Config) {

	global $rrd;

	// Update the CPU graph
	if (file_exists("/opt/coyote/statsdb/cpu.rrd")) {
		$cmd = "$rrd graph /var/www/stat-graphs/cpu.png ".
			"--title \"CPU Utilization\" --lazy --imgformat PNG --lower-limit 0 ".
			"--upper-limit 100 --width=500 --height=100 --vertical-label \"CPU %\" --rigid ".
			"DEF:cpu=/opt/coyote/statsdb/cpu.rrd:cpu:AVERAGE ".
			"CDEF:usage=cpu ".
			"AREA:usage#FF0000:\"CPU Utilization\" ".
			'GPRINT:usage:MAX:"Max\: %6.2lf%% " '.
			'GPRINT:usage:MIN:"Min\: %6.2lf%% " '.
			'GPRINT:usage:LAST:"Current\: %6.2lf%%"';
		do_exec($cmd);
	}

	// Update the interface graphs
	foreach($Config->interfaces as $ifentry) {
		if ($ifentry["down"]) {
			continue;
		}
		$statdb="/opt/coyote/statsdb/".$ifentry["device"].".rrd";
		if (file_exists("$statdb")) {
			$cmd = "$rrd graph /var/www/stat-graphs/".$ifentry["device"].".png ".
				"--title \"".$ifentry["name"]." Traffic\" --lazy --imgformat PNG --lower-limit 0 ".
				"--width=500 --height=100 --vertical-label \"kB/sec\" --upper-limit 1 ".
				"DEF:in=$statdb:in:AVERAGE ".
				"DEF:out=$statdb:out:AVERAGE ".
				"CDEF:kbin=in,1000,/ ".
				"CDEF:kbout=out,1000,/ ".
				"AREA:kbin#00FF00:\"kB/sec RX\" ".
				'GPRINT:kbin:MAX:"  Max\: %6.2lfkBps " '.
				'GPRINT:kbin:AVERAGE:"Average\: %6.2lfkBps " '.
				'GPRINT:kbin:LAST:"Current\: %6.2lfkBps\n" '.
				"LINE1:kbout#FF0000:\"kB/sec TX\" ".
				'GPRINT:kbout:MAX:"  Max\: %6.2lfkBps " '.
				'GPRINT:kbout:AVERAGE:"Average\: %6.2lfkBps " '.
				'GPRINT:kbout:LAST:"Current\: %6.2lfkBps"';

			do_exec($cmd);
		}
	}

}

?>
