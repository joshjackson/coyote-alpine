<?
	require_once("includes/loadconfig.php");

	//$action = $_POST["action"];
	$fd_ispost = false;
	$aclidx = "";
	$acl_target = "";
	$acl_protocol = "tcp";
	$acl_source = "";
	$acl_dest = "";
	$acl_start = "";
	$acl_end = "";
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$fd_ispost = true;
		$newacl = ($_POST["newacl"] == "Y") ? true : false;
		if (!$newacl) {
			$aclidx = filter_input(INPUT_POST, "aclidx", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
//			$aclidx = $_POST["aclidx"];
		}
	} else {
		$newacl = (filter_input(INPUT_GET, "newacl", FILTER_SANITIZE_FULL_SPECIAL_CHARS) == "Y");
		if (!$newacl) {
			$aclidx = filter_input(INPUT_GET, "aclidx", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		}
	}

	$ruleidx = filter_input(INPUT_GET, "ruleidx", FILTER_VALIDATE_INT);

	if (!$newacl && !(array_key_exists($aclidx, $configfile->acls))) {
		header("Location: /index.php");
		die;
	} else {
		$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
		$retidx = ($aclidx) ? "" : "?aclidx=$aclidx";
		$buttoninfo[1] = array("label" => "cancel", "dest" => "access_list.php$retidx");

		if ($fd_ispost) {

			if($newacl && !strlen($aclidx)) {
				$aclidx = filter_input(INPUT_POST, 'AccessList', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				$ruleidx = 0;
			} else {
				$aclidx = filter_input(INPUT_POST, 'aclidx', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				$ruleidx = filter_input(INPUT_POST, 'ruleidx', FILTER_VALIDATE_INT);
			}

			//FIXME: Do validation
			$acl_target = filter_input(INPUT_POST, 'RuleTarget', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$acl_protocol = filter_input(INPUT_POST, 'Protocol', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$acl_source = filter_input(INPUT_POST, 'SourceAddr', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			// $acl_target = $_POST["RuleTarget"];
			// $acl_protocol = $_POST["Protocol"];
			// $acl_source = $_POST["SourceAddr"];
			
			if(!is_ipaddrblockopt($acl_source) && !($acl_source == "any")) {
			  add_critical("Invalid Source Address: ".$acl_source);
			}
			
			$acl_dest = filter_input(INPUT_POST, 'DestAddr', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			//$acl_dest = $_POST["DestAddr"];
			if(!is_ipaddrblockopt($acl_dest) && !($acl_dest == "any")) {
			  add_critical("Invalid Destination Address: ".$acl_dest);
			}

			if (($acl_protocol == "udp") || ($acl_protocol == "tcp")) {
				$ack_start = filter_input(INPUT_POST, 'StartPort', FILTER_VALIDATE_INT);
				$ack_end = filter_input(INPUT_POST, 'EndPort', FILTER_VALIDATE_INT);

				// $acl_start = $_POST["StartPort"];
				// $acl_end = $_POST["EndPort"];
				
				if (!empty($acl_start)) {
					if ((intval($acl_start) < 1) || (intval($acl_start) > 65535)) {
						add_critical("Start port must be an integer value between 1 and 65535");

					}
					if (strlen($acl_end) && (intval($acl_end) < 1) || (intval($acl_end) > 65535)) {
						add_critical("End port must be an integer value between 1 and 65535");
					}
	
					if (intval($acl_end) < intval($acl_start)) {
						add_critical("The ending port number must be greater than or equal to the starting port number.");
					}
	
					if (($acl_start == $acl_end) && $acl_end) {
						$acl_end = "";
					}
	
					if ($acl_end) {
						$acl_ports = $acl_start .":". $acl_end;
					} else {
						$acl_ports = $acl_start;
					}
				}

			} else {
				$acl_start = "";
				$acl_end = "";
				$acl_ports = "";
			}


			if(query_invalid()) {
			  add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the configfile.");
			} else {
				$configfile->acls[$aclidx][$ruleidx]["permit"] = ($acl_target == "PERMIT") ? true : false;
				$configfile->acls[$aclidx][$ruleidx]["protocol"] = $acl_protocol;
				$configfile->acls[$aclidx][$ruleidx]["source"] = $acl_source;
				$configfile->acls[$aclidx][$ruleidx]["dest"] = $acl_dest;
				$configfile->acls[$aclidx][$ruleidx]["ports"] = $acl_ports;
				$configfile->dirty["acls"] = true;
				WriteWorkingConfig();
			
				if($newacl)
					header("Location:firewall_rules.php");
				else
					header("Location:access_list.php$retidx");
				die;
			}
		} else {
			if (!empty($aclidx)) {
				$acl_target = ($configfile->acls["$aclidx"]["$ruleidx"]["permit"]) ? "PERMIT" : "DENY";
				$acl_protocol = $configfile->acls["$aclidx"]["$ruleidx"]["protocol"];
				$acl_source = $configfile->acls["$aclidx"]["$ruleidx"]["source"];
				$acl_dest = $configfile->acls["$aclidx"]["$ruleidx"]["dest"];
				if (($acl_protocol == "tcp") || ($acl_protocol == "udp")) {
					list($acl_start, $acl_end) = @explode(":", $configfile->acls["$aclidx"]["$ruleidx"]["ports"]);
				}
			} 
		}
	}

	if ($newacl) {
		$MenuTitle="Add Access List";
	} else {
		$MenuTitle="Add Firewall Rule";
	}
	$MenuType="RULES";
	include("includes/header.php");
?>


<form method="post" action="add_rule.php">
<input type="hidden" name="ruleidx" value="<?=$ruleidx?>" />
<input type="hidden" name="newacl" value="<?=$newacl?>" />
<?
	if ($aclidx) {
		print('<input type="hidden" name="AccessList" value="'.$aclidx.' />');
	}
	if ($newacl) {
		print('<input type="hidden" name="newacl" value="Y">');
	} else {
		if ($aclidx) {
			print('<input type="hidden" name="aclidx" value="'.$aclidx.' />');
		}
	}
?>
<table width="100%" id="table1">
	<tr>
		<td>
		<table width="100%" id="table2">
			<tr>
				<td class="labelcell" nowrap><label>Access List:</label></td>
				<td>
<?
	if ($newacl) {
		print('<input type="text" name="AccessList" size="20">');
		print('<br><span class="descriptiontext">The name for the new access list. You must also add an initial rule to ');
		print('create a new acl.</span>');
		print('</td></tr><tr><td class="labelcellmid" colspan="2"><label>Initial firewall rule for this access list:</label>');
	} else {
		print('<select size="1" name="RuleTarget">');
		foreach($configfile->acls as $s_aclidx => $s_aclentry) {
			if ($s_aclidx == $aclidx) {
				print('<option value="'.$s_aclidx.'" selected>'.$s_aclidx.'</option>');
			} else {
				print('<option value="'.$s_aclidx.'">'.$s_aclidx.'</option>');
			}
		}
		print("</select>\n");
		print('<br><span class="descriptiontext">The existing access list to which this rule will be added.</span>');
	}
?>
		</td>
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
	include("includes/footer.php");
?>