<?
require_once("includes/loadconfig.php");
require_once("includes/webfunctions.php");
require_once("stats.php");

function DhcpLeases() {

	ob_start();

	$leases = GetDHCPLeases();
?>
	<table width="100%" border="0">
<?
	if (count($leases)) {
?>
		<tr>
			<td class="labelcell"><label>IP Address</label></td>
			<td class="labelcell"><label>Hostname</label></td>
			<td class="labelcell"><label>MAC Address</label></td>
			<td class="labelcell"><label>Expires</label></td>
		</tr>
<?	
		$idx = 1;
		foreach($leases as $lease) {
			$bgcolor = ($idx % 2) ? "$FFFFFF" : "$F5F5F5";
			print("<tr><td bgcolor=$bgcolor>".$lease["IP"]."</td>\n");
			print("<td bgcolor=$bgcolor>".$lease["Host"]."</td>\n");
			print("<td bgcolor=$bgcolor>".$lease["MAC"]."</td>\n");
			print("<td bgcolor=$bgcolor>".$lease["Expires"]."</td></tr>\n");
			$idx++;
		}
		print('<tr><td colspan=4><br><font class="descriptiontext">');
		print("This list shows the leases currently being managed by the DHCP service. Both dynamically assigned and reserved addresses");
		print(" are displayed.\n");
		print("</font></td></tr>\n");
	} else {
		print('<tr><td align="center"><label>There are no active DHCP leases available for display.</label></td></tr>');
	}

?>
	</table>
<?
	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}

function InterfaceStatistics() {

	ob_start();
	print("<pre>");
	exec("/sbin/ip addr show up", $outstr, $errcode);
	print("Interface Addressing Information\n");
	print("--------------------------------------------------\n");
	foreach($outstr as $outline) {
		print("$outline \n");
	}

	$outstr = "";

	exec("/sbin/ifconfig", $outstr, $errcode);
	print("\n\nInterface Statistics Information\n");
	print("--------------------------------------------------\n");
	foreach($outstr as $outline) {
		print("$outline \n");
	}
	print("</pre>");
	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}

function ProcessStatistics() {

	ob_start();
	print("<pre>");
	exec("ps axf --columns=80", $outstr, $errcode);
	print("Running Process Information\n");
	print("--------------------------------------------------\n");
	foreach($outstr as $outline) {
		print("$outline \n");
	}
	print("</pre>");
	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}

function CPUMemStatistics() {

	global $configfile;

	ob_start();

	UpdateGraphics($configfile);

?>
<table border="0" width="590px">
	<tr>
		<td class="labelcell" nowrap><label>Current CPU Utilization:</label></td>
		<td width="100%" class="ctrlcell">
		<?
			$cpu_usage = GetCPUUtilization(false);
			drawbar_colour("green", $cpu_usage);
			print(number_format($cpu_usage,2)."%");
		?>
			<br>
			<span class="descriptiontext">The current, average utilization for all processors in this firewall.<br>
		</span>
		&nbsp;</td>
	</tr>
	<tr>
		<td class="labelcell" nowrap><label>Memory Utilization:</label></td>
		<td width="100%" class="ctrlcell">
		<?
			$mem_usage = GetMemoryUtilization(false);
			drawbar_colour("green", $mem_usage);
			print(number_format($mem_usage,2)."%");
		?>
			<br>
			<span class="descriptiontext">The current system memory utilization.<br>
		</span>
		&nbsp;</td>
	</tr>
	<tr>
		<td class="labelcell" nowrap><label>Firmware Memory Allocation:</label></td>
		<td width="100%" class="ctrlcell">
		<?
			$fwalloc = GetFirmwareAllocation();
			print($fwalloc."MB");
		?>
			<br>
			<span class="descriptiontext">The amount of system memory allocated for the firewall firmware.<br>
		</span>
			&nbsp;<td>
	</tr>
	<tr>
		<td class="labelcell" nowrap><label>Current Storage Utilization:</label></td>
		<td width="100%">
			<?
			$boot_usage = GetBootFSUtilization();
			drawbar_colour("green", $boot_usage);
			print(number_format($boot_usage,2)."%");
			?>
			<br>
			<span class="descriptiontext">The amount of physical storage allocated on the firewall boot device.<br>
			</span>

		&nbsp;</td>
	</tr>
</table>
<br>
<?
	print("<label>24 hour CPU utilization statistics:</label><br>");
	print("<img src=stat-graphs/cpu.png>");
	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}


function TrafficStatistics() {

	global $configfile;

	ob_start();

	UpdateGraphics($configfile);

	foreach($configfile->interfaces as $ifentry) {

		if ($ifentry["down"]) {
			continue;
		}

		$ifdev = $ifentry["device"];
		$ifname = $ifentry["name"];
		$ifgraph= "/var/www/stat-graphs/".$ifdev.".png";
		if (!file_exists($ifgraph)) {
			print("<label>No statistics graph is available for $ifname</label><br>");
		} else {
			print("<label>Network Traffic for interface $ifname</label><br>");
			print("<img src=stat-graphs/".$ifdev.".png>");
		}
		print("<br><br>");
	}

	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}

function RouteStatistics() {

	ob_start();
	print("<pre>");
	exec("/sbin/route -n", $outstr, $errcode);
	print("Network routing table\n");
	print("--------------------------------------------------\n");
	foreach($outstr as $outline) {
		print("$outline \n");
	}
	print("</pre>");
	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}

function SystemLog() {

	ob_start();
	print("<pre>");
	exec("/sbin/logread", $outstr, $errcode);
	if ($errcode || !$outstr) {
		exec("/usr/bin/tail /var/log/messages", $outstr, $errcode);
	}
	foreach($outstr as $outline) {
		print("$outline \n");
	}
	print("</pre>");
	$retstr = ob_get_contents();
	ob_end_clean();
	return $retstr;
}

	$StatType=$_GET["stats"];
	switch ($StatType) {
		case "interface":
			$MenuTitle = "Interface Statistics";
			$MenuType = "STATS";
			$HtmlContent = InterfaceStatistics();
			break;
		case "procs":
			$MenuTitle = "Running Processes";
			$MenuType = "STATS";
			$PageIcon = "processes.jpg";
			$HtmlContent = ProcessStatistics();
			break;
		case "routes":
			$MenuTitle = "Network Routes";
			$MenuType = "STATS";
			$PageIcon = "routes.jpg";
			$HtmlContent = RouteStatistics();
			break;
		case "traffic":
			$MenuTitle = "Network Traffic";
			$MenuType = "STATS";
			$PageIcon = "conntrack.jpg";
			$HtmlContent = TrafficStatistics();
			break;
		case "syslog":
			$MenuTitle = "System Log";
			$MenuType = "STATS";
			$PageIcon = "text.jpg";
			$HtmlContent = SystemLog();
			break;
		case "leases":
			$MenuTitle = "DHCP Leases";
			$MenuType = "STATS";
			$PageIcon = "text.jpg";
			$HtmlContent = DhcpLeases();
			break;
		case "conntrack":
			$MenuTitle = "Connection Tracking";
			$MenuType = "STATS";
			$PageIcon = "conntrack.jpg";
//			$HtmlContent = SystemLog();
			break;
		default:
			$MenuTitle = "Firewall Statistics";
			$MenuType = "STATS";
			$PageIcon = "memory.jpg";
			$HtmlContent = CPUMemStatistics();
			break;
	}

	include("includes/header.php");
?>



<table border="0" width="100%" id="table1">
	<tr>
		<td>
<?
		print($HtmlContent);
?>
		</td>
	</tr>
</table>

<?
	include("includes/footer.php");
?>