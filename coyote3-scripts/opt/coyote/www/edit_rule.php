<?
	require_once("includes/loadconfig.php");

	$action = $_POST["action"];

	if ($action) {
		$aclidx = $_POST["aclidx"];
		$ruleidx = $_POST["ruleidx"];
	} else {
		$aclidx = $_GET["aclidx"];
		$ruleidx = $_GET["ruleidx"];
	}


	if (!(array_key_exists($aclidx, $configfile->acls) && array_key_exists("$ruleidx", $configfile->acls["$aclidx"]))) {
		header("Location: /index.php");
		die;
	} else {

		$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
		$buttoninfo[1] = array("label" => "cancel", "dest" => "access_list.php?aclidx=$aclidx");

		if ($action == "apply") {

			//selected from our list, no need to validate these?
			$acl_target = $_POST["RuleTarget"];
			$acl_protocol = $_POST["Protocol"];

			//source must be ipaddr, block optional, or keyword "any"
			$acl_source = $_POST["SourceAddr"];
			if(!is_ipaddrblockopt($acl_source)) {
				if(!$acl_source === 'any') {
				  add_critical("Invalid IP addr: ".$acl_source." (must be addr/# or keyword 'any').");
				}
			}

			//dest must be ip addr, block optional, or keyword any
			$acl_dest = $_POST["DestAddr"];
			if(!is_ipaddrblockopt($acl_dest)) {
				if(is_null($acl_dest) || (!$acl_dest === 'any')) {
				  add_critical("Invalid IP addr: ".$acl_dest." (must be add/# or keyword 'any').");
				}
			}

			if (($acl_protocol == "udp") || ($acl_protocol == "tcp")) {
				$acl_start = $_POST["StartPort"];
				$acl_end = $_POST["EndPort"];

				//verify start port is within valid range (0..65535)
				if(intval($acl_start) < 0 || intval($acl_start) > 65535) {
				  add_critical("Invalid port (start): ".$acl_start." out of IPv4 range [0..65535].");
				}

				//verify end port is within valid range (0..65535)
				if(intval($acl_end) < 0 || intval($acl_end) > 65535) {
				  add_critical("Invalid port (end): ".$acl_start." out of IPv4 range [0..65535].");
				}

				if (($acl_start == $acl_end) && $acl_end) {
					$acl_end = "";
				}

				if ($acl_end)
					$acl_ports = $acl_start .":". $acl_end;
				else
					$acl_ports = $acl_start;

			} else {
				$acl_start = '';
				$acl_end = '';
				$acl_ports = '';
			}

			$configfile->acls["$aclidx"]["$ruleidx"]["permit"] = ($acl_target == "PERMIT") ? true : false;
			$configfile->acls["$aclidx"]["$ruleidx"]["protocol"] = $acl_protocol;
			$configfile->acls["$aclidx"]["$ruleidx"]["source"] = $acl_source;
			$configfile->acls["$aclidx"]["$ruleidx"]["dest"] = $acl_dest;
			$configfile->acls["$aclidx"]["$ruleidx"]["ports"] = $acl_ports;

		  //write config
  		  $configfile->dirty["acls"] = true;
		  if(!query_invalid() && WriteWorkingConfig()) {
				header("Location: access_list.php?aclidx=".$aclidx);
		  } else {
				add_warning("Error writing to working configfile!");
			}
		} else {
			$acl_target = ($configfile->acls["$aclidx"]["$ruleidx"]["permit"]) ? "PERMIT" : "DENY";
			$acl_protocol = $configfile->acls["$aclidx"]["$ruleidx"]["protocol"];
			$acl_source = $configfile->acls["$aclidx"]["$ruleidx"]["source"];
			$acl_dest = $configfile->acls["$aclidx"]["$ruleidx"]["dest"];
			if (($acl_protocol == "tcp") || ($acl_protocol == "udp")) {
				list($acl_start, $acl_end) = @split(":", $configfile->acls["$aclidx"]["$ruleidx"]["ports"]);
			}
		}
	}

	$MenuTitle="Edit Firewall Rule";
	$MenuType="RULES";

	include("includes/header.php");
?>


<form method="post" action="edit_rule.php">
<input type="hidden" name="action" value="apply">
<input type="hidden" name="aclidx" value="<?=$aclidx?>">
<input type="hidden" name="ruleidx" value="<?=$ruleidx?>">
<table border="0" width="100%" id="table1">
	<tr>
		<td>
		<table border="0" width="100%" id="table2">
			<tr>
				<td colspan="2" class="labelcell"><label>Access List:</label> <?=$aclidx?><label> Rule Index:</label> <?=$ruleidx?></td>
			</tr>
			<tr>
				<td class="labelcell"><label>Rule Target</label></td>
				<td class="ctrlcell"><select size="1" name="RuleTarget">
<?
	if ($acl_target == "PERMIT") {
?>
				<option selected value="PERMIT">Permit</option>
				<option value="DENY">Deny</option>
<?	} else { ?>
				<option value="PERMIT">Permit</option>
				<option selected value="DENY">Deny</option>
<?	}	?>
				</select><br>
				<span class="descriptiontext">The target operation for packets matching this firewall rule.</span></td>
			</tr>
			<tr>
				<td class="labelcell"><label>Protocol</label></td>
				<td class="ctrlcell"><select size="1" name="Protocol">
				<? print(GetProtocolList($acl_protocol)); ?>
				</select><br>
				<span class="descriptiontext">The protocol to match for this firewall rule.</span></td>
			</tr>
			<tr>
				<td class="labelcell"><label>Source</label></td>
				<td class="ctrlcell"><input type="text" name="SourceAddr" value="<?=$acl_source?>" size="20"><br>
				<span class="descriptiontext">Source host or network IP address. The keyword &quot;any&quot; can be used
				to specify a match against any IP address.</span></td>
			</tr>
			<tr>
				<td class="labelcell"><label>Destination</label></td>
				<td class="ctrlcell"><input type="text" name="DestAddr" value="<?=$acl_dest?>" size="20"><br>
				<span class="descriptiontext">Destination host or network IP address. The keyword &quot;any&quot; can be
				used to specify a match against any IP address.</span></td>
			</tr>
			<tr>
				<td class="labelcell"><label>Start port</label></td>
				<td class="ctrlcell"><input type="text" name="StartPort" value="<?=$acl_start?>" size="20"><br>
				<span class="descriptiontext">The starting port for this rule. This option is only valid if
				either TCP or UDP has been specified for the protocol.</span></td>
			</tr>
			<tr>
				<td class="labelcell"><label>End port</label></td>
				<td><input type="text" name="EndPort" value="<?=$acl_end?>" size="20"><br>
				<span class="descriptiontext">The ending port for this rule. If the rule only requires a
				single port, this field can be left blank. This option is only
				valid if either TCP or UDP has been specified for the protocol.</span></td>
			</tr>
		</table>
		<span class="descriptiontext"><b><hr>
		Note:</b> Access lists are processed in top-down order. The first matching rule within an access list will be applied to a packet.
		If you add any deny rules to your access lists, pay close attention to
the order of your rules and access lists. All traffic that is not explicitly
allowed in an access list will be dropped by default.</span></td>
	</tr>
</table>
</form>
<?
  print(query_warnings());
	include("includes/footer.php");
?>