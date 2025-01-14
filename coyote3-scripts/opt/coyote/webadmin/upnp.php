<?
	require_once("includes/loadconfig.php");


	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$upnp = filter_input(INPUT_POST, "UPNP", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		if ($upnp == "ON") {
			$configfile->options["upnp"] = filter_input(INPUT_POST, "interface", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		} else {
			$configfile->options["upnp"] = "";
		}
		$configfile->dirty["upnp"] = true;
		$ret = WriteWorkingConfig();
	} else {
		$ret = "";
	}

	$MenuTitle="UPnP Service";
	$MenuType="GENERAL";
	$PageIcon="service.jpg";
	include("includes/header.php");
	$buttoninfo[0] = array("label" => "Apply", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "Cancel", "dest" => "index.php");

	$checked = ($configfile->options["upnp"]) ? "checked" : "";
?>

<form action="upnp.php" method="post">
<table width="100%" id="table1">
	<tr>
		<td>
<?
	if ($ret) {
		print('<p align="center"><b>Configuration updated successfully.</b></p>');
	}
?>
		<p>This option provides Universal Plug and Play (UPnP) support for networks
		that require this service. UPnP allows protected network hosts to send
		requests to the firewall to have certain ports opened or forwarded when
		needed. This is a convenient way to allow services that support UPnP to
		function properly without the need to create custom firewall rules. This
		service should only be used on home or small office networks where all
		of the protected hosts are considered to be trusted. This option should never be enabled on corporate networks as it presents a potential security
		risk.</p>
		<p>Many software applications that run on Microsoft Windows 2000 and XP
		support UPnP. This feature will enable the use of file transfers, voice
		and video conferencing, and game play which would normally require
		additional firewall rules.</p>
<p><b>Warning:</b> Never enable UPnP on an Interface which is connected to an
untrusted network or the Intenet.</p>
		<table width="100%" id="table2" cellspacing="0" cellpadding="3">
			<tr>
				<td class="labelcellmid">
				<input type="checkbox" name="UPNP" value="ON" <?=$checked?>></td>
				<td nowrap class="labelcellmid"><label>Enable the UPnP service
				for the following interface</label></td>
				<td width="100%" nowrap class="labelcellmid">
			<select id="interface" name="interface">
			<?
			//loop through interfaces
			foreach($configfile->interfaces as $ifentry) {
				//no bridged intf
				if($ifentry['bridge']) continue;

				//no downed intf allowed, duh
				if($ifentry['down']) continue;

				//if this is the chosen interface, make sure it is marked as selected
				if($ifentry['name'] == $configfile->options["upnp"])
					$selected = "selected";
				else
					$selected = "";

				echo '<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>';
			}
			?>
			</select>
			</tr>
		</table>
		<p></td>
	</tr>
</table>

</form>
<?
	include("includes/footer.php");
?>