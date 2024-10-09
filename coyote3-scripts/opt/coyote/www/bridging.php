<?
	include("includes/loadconfig.php");
	VregCheck();

	/*
	//TODO: implement these two

	$configfile->bridge['spanning-tree'] => status
	$configfile->bridge['address'] => ipaddrblockopt

	*/

	$MenuTitle="Bridging Support";
	$MenuType="INTERFACES";

	include("includes/header.php");

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);

	$action = $_REQUEST['action'];

	if(!$action) {
		//gather
		$fd_ipaddr = $configfile->bridge['address'];
		$fd_enabled = $configfile->bridge['spanning-tree'];
	} else {
		//posted

		$fd_ipaddr = $_REQUEST['fd_ipaddr'];
		$fd_enabled = $_REQUEST['fd_enabled'];

		//validate

		if(!is_ipaddrblockopt($fd_ipaddr)) {
		  add_critical("Invalid IP addr: ".$fd_ipaddr);
		}

		if(!query_invalid()) {
			$configfile->bridge['address'] = $fd_ipaddr;
			$configfile->bridge['spanning-tree'] = $fd_enabled;
			$configfile->dirty['interfaces'] = true;
			if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
				add_warning("Error writing to working configfile!");

		} else {
			add_warning("<hr>".query_invalid()." parameters could not be validated.");
		}
	}

	function is_enabled() {
		global $fd_enabled;
		return $fd_enabled;
	}

	foreach($configfile->interfaces as $ifentry) {
		if ($ifentry["bridge"]) {
			$BridgeEnable = true;
			break;
		}
	}


	if($BridgeEnable) {

?>

<form id="content" method="post" action="bridging.php">

<input type="hidden" name="action" value="apply" />

<table border="0" width="100%" id="table2">
	<tr>
		<td colspan="2" class="labelcell" nowrap><b><label><font size="2">Bridge Configuration</font></label></b></td>
	</tr>
	<tr>
		<td class="labelcell" nowrap><label>Virtual Interface Address:</label></td>
		<td width="100%">
		<input type="text" name="fd_ipaddr" size="20" value="<?=$fd_ipaddr?>" />
		<br>
		<span class="descriptiontext">This is the IP address assigned to the bridge device itself. If all of
		the interfaces in this firewall are part of the bridge group, this would
		be the address used to connect to the firewall for remote
		administration. The address should be in address / prefix notation.<br>
		<i>Example: 192.168.0.100/24</i></span></td>
	</tr>
	<tr>
		<td class="labelcell" nowrap><label>Enable Spanning Tree Protocol:</label></td>
		<td width="100%"><input type="checkbox" name="fd_enabled" <? if(is_enabled()) print("checked"); ?>><br>
		<span class="descriptiontext">If you have multiple bridges on your network which could potentially
		generate forwarding loops, you should enable the spanning tree protocol.
		If this is the only bridge on this network, it is safe to leave it
		disabled.</span></td>
	</tr>
	</table>
<p>

</form>


<?
	} else {
?>



</p>



<table border="0" width="100%" id="table1">
	<tr>
		<td>
		<b>
		<font size="2">Bridging is not currently enabled.</font></b><p>
		<font size="2">To enable bridging support, you must place at least one
		interface in bridging mode. To place an interface in briding mode, edit
		the properties for the interface and set its configuration method to
		&quot;Enable Bridging&quot;.</font></td>
	</tr>
</table>

<?
	}
	if(strlen(query_warnings())) print(query_warnings());
	include("includes/footer.php");
?>