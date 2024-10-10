<?
	require_once("includes/loadconfig.php");

	$action=$_POST["action"];

	if ($action == "post") {
		/*
		print("<pre>");
		print_r($_REQUEST);
		print("</pre>");
	  */

		$fd_rlogging = $_POST["rlogging"];
		$fd_rhost = $_POST["rhost"];
		$fd_laccept = $_POST["laccept"];
		$fd_ldeny = $_POST["ldeny"];
		$fd_faccept = $_POST["faccept"];
		$fd_fdeny = $_POST["fdeny"];

		if ($fd_rlogging) {
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
		$fd_rlogging = ($configfile->logging["host"]) ? "checked" : "";
		$fd_rhost = $configfile->logging["host"];
		$fd_laccept = ($configfile->logging["local-accept"]) ? "checked" : "";
		$fd_ldeny = ($configfile->logging["local-deny"]) ? "checked" : "";
		$fd_faccept = ($configfile->logging["forward-accept"]) ? "checked" : "";
		$fd_fdeny = ($configfile->logging["forward-deny"]) ? "checked" : "";
	}

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => "logging.php");

	$MenuTitle="System Logging";
	$MenuType="GENERAL";
	$PageIcon="service.jpg";
	include("includes/header.php");
?>

<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
<input type="hidden" name="action" value="post">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td class="labelcellmid" nowrap><input type="checkbox" name="rlogging" value="checked" <?=$fd_rlogging?>></td>
    <td class="labelcellmid" nowrap width="100%"><label><font size="2">Enable remote system logging </font></label></td>
  </tr>
</table>
<table width="100%"  border="0">
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
    <td class="labelcell" align="right" nowrap><input type="checkbox" name="ldeny" value="checked" <?=$fd_ldeny?>></td>
    <td width="100%" class="ctrlcell"><b>Log locally rejected connections</b><br>
      <span class="descriptiontext">This option will log rejected connections directed at the firewall itself.</span></td>
  </tr>
  <tr>
    <td class="labelcell" align="right" nowrap><input type="checkbox" name="laccept" value="checked" <?=$fd_laccept?>></td>
    <td width="100%" class="ctrlcell"><b>Log locally accepted connections<br>
    </b><span class="descriptiontext">This option will log connections accepted by the firewall itself.</span></td>
  </tr>
  <tr>
    <td class="labelcell" align="right" nowrap><input type="checkbox" name="fdeny" value="checked" <?=$fd_fdeny?>></td>
    <td width="100%" class="ctrlcell"><b>Log rejected forwarding connections</b><br>
    <span class="descriptiontext">This option will log connections destined for hosts outside of the firewall which were rejected due to the firewall configuration. These connections may be rejected due to a <em>deny</em> based access list or by default if no other access list explicitly permitted the connection.</span></td>
  </tr>
  <tr>
    <td class="labelcell" align="right" nowrap><input type="checkbox" name="faccept" value="checked" <?=$fd_faccept?>></td>
    <td width="100%"><b>Log accepted forwarding connections</b><br>
    <span class="descriptiontext">This option will log connections destined for hosts outside of the firewall which were accepted based on an <em>accept</em> based access list.</span></td>
  </tr>
</table>
</form>
<?
	print(query_warnings());
	include("includes/footer.php");
?>