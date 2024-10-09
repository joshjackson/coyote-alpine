<?
	require_once("includes/loadconfig.php");
	VregCheck();


	$MenuTitle="Edit ICMP Rule";
	$MenuType="RULES";

	$action = $_REQUEST['action'];
	$ruleidx = $_REQUEST['ruleidx'];

	if (!array_key_exists($ruleidx, $configfile->icmp['rules'])) {
		header("Location: icmp_rules.php");
		die;
	} else {
		$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
		$buttoninfo[1] = array("label" => "cancel", "dest" => "icmp_rules.php");

		if ($action == "apply") {
			$ruleidx = $_POST["ruleidx"];

			//fill from post

			$icmp_interface = $_POST['icmp_interface'];
			$icmp_source = $_POST['icmp_source'];
			$icmp_type = $_POST['icmp_type'];

			//the only manually-entered value is source addr
			if(!is_ipaddrblockopt($icmp_source) && ($icmp_source != 'any')) {
			  add_critical("Invalid IP addr: ".$icmp_source);
			}

			//assign to config
			$configfile->icmp['rules'][$ruleidx]['interface'] = $icmp_interface;
			$configfile->icmp['rules'][$ruleidx]['source'] = $icmp_source;
			$configfile->icmp['rules'][$ruleidx]['type'] = $icmp_type;

			if(query_invalid()) {
				add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
			} else {
			  //write config
			  if(WriteWorkingConfig()) {
		  		//$fd_warnings = "<br>Write to '$working_filename' succeeded.";
					header("Location:icmp_rules.php");
					die;
			  } else {
				  add_warning("Error writing to working configfile!");
				}
			}
		} else if ($action == 'delete') {
			//copy each rule
			//$configfile->icmp['rules'] = array();
			$tmp = array();
			foreach($configfile->icmp['rules'] as $key => $drule) {
				if($key == $ruleidx) continue;
				$tmp[count($tmp)] = $drule;
			}

			//assign tmp back to configfile
			$configfile->icmp['rules'] = $tmp;

			//write config
			WriteWorkingConfig();
			header("Location:icmp_rules.php");
			die;
		} else {
			//fill from rule definition
			$icmp_interface = $configfile->icmp['rules'][$ruleidx]['interface'];
			$icmp_source = $configfile->icmp['rules'][$ruleidx]['source'];
			$icmp_type = $configfile->icmp['rules'][$ruleidx]['type'];
		}

	}
	include("includes/header.php");
?>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
	<input type="hidden" name="action" value="apply">
	<input type="hidden" name="ruleidx" value="<?=$ruleidx?>">
	<table border="0" width="100%" id="table1">
		<tr>
			<td class="labelcell" nowrap>
				<label>Interface:</label>
			</td>
			<td class="ctrlcell" width="100%" nowrap>
				<select id="icmp_interface" name="icmp_interface">
					<?
					//loop through interfaces
					foreach($configfile->interfaces as $ifentry) {
						//no ICMP response rules can be applied to a bridged intf
						if($ifentry['bridge']) continue;

						//no ICMP response rules can be applied to a downed intf
						if($ifentry['down']) continue;

						//if this is the chosen interface, make sure it is marked as selected
						if($ifentry['name'] == $icmp_interface)
							$selected = "selected";
						else
							$selected = "";

						print("<option value=\"".$ifentry["name"]."\" ".$selected.">".$ifentry["name"]."</option>");
					}
					?>
				</select><br>
				<span class="descriptiontext">
					Select the interface for this rule.
				</span>
			</td>
		</tr>
		<tr>
			<td class="labelcell" nowrap>
				<label>Source Address:</label>
			</td>
			<td class="ctrlcell" width="100%" nowrap>
				<input type="text" id="icmp_source" name="icmp_source" value="<?= $icmp_source ?>" size=16 />
				<br>
				<span class="descriptiontext">
					Specify source IP address or keyword 'any' (example: 208.135.7.109)
				</span>
			</td>
		</tr>
		<tr>
			<td class="labelcell" nowrap>
				<label>ICMP type:</label>
			</td>
			<td class="ctrlcell" width="100%" nowrap>
				<select id="icmp_type" name="icmp_type">
					<option value="any" <?if($icmp_type == 'any') print("selected") ?>>any</option>
					<? print(GetICMPList($icmp_type)) ?>
				</select><br>
				<span class="descriptiontext">
					Specify the ICMP request type to allow.
				</span>
			</td>
		</tr>
	</table>
	<p>
	<? print(query_warnings()); ?>
</form>
<?
	include("includes/footer.php");
?>