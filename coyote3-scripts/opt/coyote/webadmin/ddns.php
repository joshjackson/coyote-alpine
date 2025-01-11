<?
	include("includes/loadconfig.php");

	$action=$_POST["action"];

	if ($action == "post") {

		if ($fd_enabled = $_POST["enabled"]) {

			$fd_servicetype = $_POST["servicetype"];
			$fd_interface = $_POST["interface"];
			$fd_username = $_POST["username"];
			$fd_password = $_POST["password"];
			$fd_hostname = $_POST["hostname"];
			$fd_maxinterval = $_POST["maxinterval"];

			if(!strlen($fd_hostname) || !is_domain($fd_hostname)) {
				add_critical("Invalid hostname: '".$fd_hostname."'");
			}

			if(!strlen($fd_username)) {
				add_critical("Invalid username: '".$fd_username."'");
			}

			if(!strlen($fd_password)) {
				add_critical("Invalid password.");
			}

			if(!strlen($fd_maxinterval) || intval($fd_maxinterval) < 3600) {
				add_critical("Invalid interval.");
			}

			$configfile->dyndns["enable"] = true;
			$configfile->dyndns["service"] = $fd_servicetype;
			$configfile->dyndns["interface"] = $fd_interface;
			$configfile->dyndns["username"] = $fd_username;
			$configfile->dyndns["password"] = $fd_password;
			$configfile->dyndns["hostname"] = $fd_hostname;
			$configfile->dyndns["max-interval"] = $fd_maxinterval;

		} else {
			$configfile->dyndns["enable"] = false;
		}

		if(!query_invalid()) {
			// Output the configuration file
			$configfile->dirty["dyndns"] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
				add_warning("Error writing to working configfile!");
		} else {
			add_warning("<hr>".query_invalid()." paramters could not be validated.");
		}

	} else {
		$fd_enabled = $configfile->dyndns["enable"];
		$fd_servicetype = $configfile->dyndns["service"];
		$fd_interface = $configfile->dyndns["interface"];
		$fd_username = $configfile->dyndns["username"];
		$fd_password = $configfile->dyndns["password"];
		$fd_hostname = $configfile->dyndns["hostname"];
		$fd_maxinterval = $configfile->dyndns["max-interval"];
		if (!$fd_maxinterval) {
			$fd_maxinterval = 2073600;
		}
	}

	$fd_checked = ($fd_enabled) ? "checked" : "";

	$service_types = array("dhs", "dyndns", "easydns", "gnudip", "justlinux", "ods", "pgpow", "tzo");

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => "ddns.php");

	$MenuTitle="Dynamic DNS Service";
	$MenuType="GENERAL";
	$PageIcon="service.jpg";
	include("includes/header.php");
?>

<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
<input type="hidden" name="action" value="post">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td class="labelcellmid" nowrap><input type="checkbox" name="enabled" value="yes" <?=$fd_checked?>></td>
    <td class="labelcellmid" nowrap width="100%"><label><font size="2">Enable the Dynamic DNS client</font></label></td>
  </tr>
</table>
<table width="100%"  border="0">
  <tr>
    <td class="labelcell" nowrap><label>Service type:</label></td>
    <td  class="ctrlcell"><select name="servicetype">
<?	foreach($service_types as $service) {
			$service_sel = ($service == $fd_servicetype) ? " selected" : "";
			?>
				<option value="<?=$service?>"<?=$service_sel?>><?=$service?></option>
			<?
		}
?>
    </select>
      <br>
      <span class="descriptiontext">Select the type of dynamic DNS service you are using.</span></td>
  </tr>
  <tr>
    <td class="labelcell" nowrap><label>Username:</label></td>
    <td  class="ctrlcell"><input type="text" name="username" value="<?=$fd_username?>">
      <br>
      <span class="descriptiontext">The username for your account.</span></td>
  </tr>
  <tr>
    <td class="labelcell" nowrap><label>Password</label></td>
    <td  class="ctrlcell"><input type="text" name="password" value="<?=$fd_password?>">
      <br>
      <span class="descriptiontext">The password for your account.</span></td>
  </tr>
  <tr>
    <td class="labelcell" nowrap><label>Hostname:</label></td>
    <td  class="ctrlcell"><input type="text" name="hostname" value="<?=$fd_hostname?>">
      <br>
      <span class="descriptiontext">This is the hostname you have established with the dynamic DNS service provider.</span></td>
  </tr>
  <tr>
    <td class="labelcell" nowrap><label>Interface:</label></td>
    <td  class="ctrlcell"><select name="interface">
			<?
			//loop through interfaces
			foreach($configfile->interfaces as $ifentry) {
				//no bridged intf
				if($ifentry['bridge']) continue;
				//no downed intf allowed
				if($ifentry['down']) continue;
				//if this is the chosen interface, make sure it is marked as selected
				if($ifentry['name'] == $fd_interface)
					$selected = "selected";
				else
					$selected = "";
				print('<option value="'.$ifentry["name"].'" '.$selected.'>'.$ifentry["name"].'</option>');
			}
			?>
    </select>
      <br>
      <span class="descriptiontext">The interface will be used to determine the address to send. This will typically be your external firewall interface.</span></td>
  </tr>
  <tr>
    <td class="labelcell" nowrap><label>Max update interval:</label></td>
    <td><input name="maxinterval" type="text" value="<?=$fd_maxinterval?>">
      <br>
      <span class="descriptiontext">The maximum update interval is the shortest period of time allowed between updates. If this value is set too low, you may have your account banned for sending too many updates.</span></td>
  </tr>
</table>
<?=query_warnings()?>
</form>
<?
	include("includes/footer.php");
?>