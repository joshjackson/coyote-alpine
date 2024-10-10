<?
	include("includes/loadconfig.php");
	
	$action = $_REQUEST["action"];
	$ruleidx = $_REQUEST["ruleidx"];
	if ($ruleidx == "") {
		$ruleidx = "add";
	}

		if ($action == "apply") {
			$ruleidx = $_POST["ruleidx"];
			//fill from post
			$qos_interface = $_POST['qos_interface'];
			$qos_protocol = $_POST['lstProtocol'];
			$qos_startport = $_POST['edtStartPort'];
			$qos_endport = $_POST['edtEndPort'];
			$qos_prio = $_POST['lstPrio'];
			
			// Create a new rule
			$qos_rule = array(
				"interface" => $qos_interface,
				"proto" => $qos_protocol,
				"ports" => "",
				"prio" => get_qos_prio($qos_prio)
			);

			if (($qos_protocol != "tcp") && ($qos_protocol != "udp")) {
				$qos_startport = "";
				$qos_endport = "";
			} else {
				$pstr = "";
				if ($qos_startport) {
					$pstr = $qos_startport;
					if ($qos_endport && ($qos_endport != $qos_startport)) {
						$pstr .= ":".$qos_endport;
					}
				}
				$qos_rule["ports"] = $pstr;
			}
			if(query_invalid()) {
				add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the configfile.");
			} else {
				// Insert the rule into the config
				if ($ruleidx == "add") {
					array_push($configfile->qos["filters"], $qos_rule);
				} else {
					$configfile->qos["filters"][$ruleidx] = $qos_rule;
				}
				//write config
				$configfile->dirty["qos"] = true;
				if(WriteWorkingConfig()) {
					header("Location:edit_qos.php");
					die;
				} else {
					add_warning("Error writing to working configfile!");
				}
			}
		} elseif ($action == "delete") {
			$ruleidx = intval($ruleidx);
			if ( ($ruleidx < 0) || ($ruleidx > (count($configfile->qos["filters"]) - 1)) ) {
				header("location:edit_qos.php");
				die;
			} else {
				$tmp = array();
				foreach($configfile->qos['filters'] as $key => $qrule) {
					if($key == $ruleidx) continue;
					array_push($tmp, $qrule);
				}
				//assign tmp back to configfile
				$configfile->qos['filters'] = $tmp;
				//write config
				WriteWorkingConfig();
				header("location:edit_qos.php");
				die;
			}		
		} else {
			if ($ruleidx == "add") {
				$qos_prio = get_qos_output_prio($configfile->qos['default-prio']);
			} else {
				//fill from rule definition
				$qos_interface = $configfile->qos['filters'][$ruleidx]['interface'];
				$qos_protocol = $configfile->qos['filters'][$ruleidx]['proto'];
				$pstr = $configfile->qos['filters'][$ruleidx]['ports'];
				list($qos_startport, $qos_endport) = split(":", $pstr, 2);
				$qos_prio = get_qos_output_prio($configfile->qos['filters'][$ruleidx]['prio']);
			}
		}


	if ($ruleidx == "add") {
		$MenuTitle="Add QoS Filter Rule";
	} else {
		$MenuTitle = "Edit QoS Filter Rule";
	}
	$MenuType="NETWORK";
	$PageIcon="service.jpg";
	include("includes/header.php");
	$buttoninfo[0] = array("label" => "Apply", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "Cancel", "dest" => "network_settings.php");

	$priolist = array("Low", "Normal", "High", "Lan (bypass QoS)");
	$prioval = array("low", "normal", "high", "lan");
?>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
  <input type="hidden" name="action" value="apply" />
	<input type="hidden" name="ruleidx" value="<?=$ruleidx?>">
	<table border="0" width="100%" id="table1">
		<tr>
			<td class="labelcell" nowrap>
				<label>Interface:</label>
			</td>
		  <td class="ctrlcell" width="100%" nowrap>
				<select id="qos_interface" name="qos_interface">
					<?
					//loop through interfaces
					foreach($configfile->interfaces as $ifentry) {
						//no ICMP response rules can be applied to a bridged intf
						if($ifentry['bridge']) continue;

						//no ICMP response rules can be applied to a downed intf
						if($ifentry['down']) continue;

						//if this is the chosen interface, make sure it is marked as selected
						if($ifentry['name'] == $qos_interface)
							$selected = "selected";
						else
							$selected = "";

						print("<option value=\"".$ifentry["name"]."\" ".$selected.">".$ifentry["name"]."</option>");
					}
					?>
				</select><br>
			  <span class="descriptiontext">
			  Specify the <em>outgoing</em> interface for this rule. </span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap>
				<label>Protocol:</label>
			</td>
			<td class="ctrlcell" width="100%" nowrap>
				<select name="lstProtocol">
					<? print(GetProtocolList($qos_protocol)); ?>
			  	</select>
			<br>
				<span class="descriptiontext">
		  Specify the protocol to apply this rule to </span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap>
				<label>Start Port :</label>
			</td>
			<td width="100%" nowrap>
				<input type="text" name="edtStartPort" id="edtStartPort" value="<?=$qos_startport?>" /><br>
				<span class="descriptiontext">
					This is the starting port for this rule. Ports can only be specified when TCP or UDP traffic type is selected. </span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap>
				<label>End Port :</label>
			</td>
			<td width="100%" nowrap>
				<input type="text" id="edtEndPort" name="edtEndPort" value="<?=$qos_endport?>" />
				
				<br>
				<span class="descriptiontext">
					The ending port for this rule. If you are specifying a single port, leave this field blank. </span></td>
		</tr>
		<tr>
			<td class="labelcell" nowrap>
				<label>Priority :</label>
			</td>
			<td width="100%" nowrap>
			  <select name="lstPrio">
			  <?
					$idx = 0;
					foreach ($prioval as $pi) {
						$dosel = ($pi == $qos_prio) ? "selected" : "";
						print('<option value="'.$pi.'" '.$dosel.'>'.$priolist[$idx].'</option>');
						$idx++;
					}
				?>	
			  </select>
			<br>
				<span class="descriptiontext">
		  The ending port for this rule. If you are specifying a single port, leave this field blank. </span></td>
		</tr>
	</table>
	<span class="descriptiontext"><strong>Note:</strong> These rules currently only control traffic flowing between the public interface and the first internal interface (eth1). The QoS support is in active development and is currently incomplete.</span>
	<p>
	<? if(strlen(query_warnings())) print(query_warnings()); ?>

</form>

<?
	include("includes/footer.php");
?>