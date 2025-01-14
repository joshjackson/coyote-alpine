<?
	require_once("includes/loadconfig.php");

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		$fd_rlogging = filter_input(INPUT_POST, 'rlogging', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
		$fd_rhost = filter_input(INPUT_POST, 'rhost', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
		$fd_laccept = filter_input(INPUT_POST, 'laccept', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
		$fd_ldeny = filter_input(INPUT_POST, 'ldeny', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
		$fd_faccept = filter_input(INPUT_POST, 'faccept', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
		$fd_fdeny = filter_input(INPUT_POST, 'fdeny', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";

		if (isset($fd_rlogging)) {
			//validate
			if(!is_ipaddr($fd_rhost)) {
			  add_critical("Invalid IP addr: ".$fd_rhost);
			}
			$configfile->logging["host"] = $fd_rhost;
		} else {
			$configfile->logging["host"] = "";
		}

		$configfile->logging["local-accept"] = ($fd_laccept == "checked") ? true : false;
		$configfile->logging["local-deny"] = ($fd_ldeny == "checked") ? true : false;
		$configfile->logging["forward-accept"] = ($fd_faccept == "checked") ? true : false;
		$configfile->logging["forward-deny"] = ($fd_fdeny == "checked") ? true : false;

		if(!query_invalid()) {
			$configfile->dirty["logging"] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
				add_warning("Error writing to working configfile!");
		} else {
			add_warning("<br><br>".query_invalid()." parameters could not be validated.");
		}

	} else {
		$fd_rlogging = (!empty($configfile->logging["host"])) ? "checked" : "";
		$fd_rhost = (!empty($configfile->logging["host"])) ? $configfile->logging["host"] : "";
		$fd_laccept = (!empty($configfile->logging["local-accept"])) ? "checked" : "";
		$fd_ldeny = (!empty($configfile->logging["local-deny"])) ? "checked" : "";
		$fd_faccept = (!empty($configfile->logging["forward-accept"])) ? "checked" : "";
		$fd_fdeny = (!empty($configfile->logging["forward-deny"])) ? "checked" : "";
	}

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => "logging.php");

	$MenuTitle="System Logging";
	$MenuType="GENERAL";
	$PageIcon="service.jpg";
	include("includes/header.php");
?>

<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td class="labelcellmid" nowrap><input type="checkbox" name="rlogging" value="checked" <?=$fd_rlogging?>></td>
    <td class="labelcellmid" nowrap width="100%"><label><font size="2">Enable remote system logging </font></label></td>
  </tr>
</table>
<table width="100%">
  <tr>
    <td class="labelcell" nowrap><label>Remote host:</label></td>
    <td width="100%"><input type="text" name="rhost" value="<?=$fd_rhost?>">
      <br>
      <span class="descriptiontext">The remote logging host should be the IP address of a remote machine that will accept syslog data from the firewall.</span></td>
	</tr>
	<tr>
		<td class="labelcellmid" width="100%" colspan=2>
		<label><font size="2">Additional logging options</font></label>
		</td>
	<tr>
  <tr>
    <td class="labelcell" style="text-align: right;" nowrap><input type="checkbox" name="ldeny" value="checked" <?=$fd_ldeny?>></td>
    <td width="100%" class="ctrlcell"><b>Log locally rejected connections</b><br>
      <span class="descriptiontext">This option will log rejected connections directed at the firewall itself.</span></td>
  </tr>
  <tr>
    <td class="labelcell" style="text-align: right;" nowrap><input type="checkbox" name="laccept" value="checked" <?=$fd_laccept?>></td>
    <td width="100%" class="ctrlcell"><b>Log locally accepted connections<br>
    </b><span class="descriptiontext">This option will log connections accepted by the firewall itself.</span></td>
  </tr>
  <tr>
    <td class="labelcell" style="text-align: right;" nowrap><input type="checkbox" name="fdeny" value="checked" <?=$fd_fdeny?>></td>
    <td width="100%" class="ctrlcell"><b>Log rejected forwarding connections</b><br>
    <span class="descriptiontext">This option will log connections destined for hosts outside of the firewall which were rejected due to the firewall configuration. These connections may be rejected due to a <em>deny</em> based access list or by default if no other access list explicitly permitted the connection.</span></td>
  </tr>
  <tr>
    <td class="labelcell" style="text-align: right;" nowrap><input type="checkbox" name="faccept" value="checked" <?=$fd_faccept?>></td>
    <td width="100%"><b>Log accepted forwarding connections</b><br>
    <span class="descriptiontext">This option will log connections destined for hosts outside of the firewall which were accepted based on an <em>accept</em> based access list.</span></td>
  </tr>
</table>
</form>
<?
	print(query_warnings());
	include("includes/footer.php");
?>