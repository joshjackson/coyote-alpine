<?
	include("includes/loadconfig.php");
	
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {

		$qos_enabled = filter_input(INPUT_POST, 'cbEnabled', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$qos_upstream = filter_input(INPUT_POST, 'edtUpStream', FILTER_VALIDATE_INT);
		$qos_downstream = filter_input(INPUT_POST, 'edtDownStream', FILTER_VALIDATE_INT);
		$qos_prio = filter_input(INPUT_POST, 'lstPrio', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		
		if ($qos_enabled == "checked") {
			// Make sure the up and down stream values are integers
			if (!is_numeric($qos_upstream)) {
				add_critical("QoS Upstream value must be numeric.");
			} else {
				$qos_upstream = ceil($qos_upstream); // must be a whole number
			}
			
			if (!is_numeric($qos_downstream)) {
				add_critical("QoS Downstream value must be numeric.");
			} else {
				$qos_downstream = ceil($qos_downstream); // must be a whole number
			}
		}
		if(query_invalid()) {
			add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the configfile.");
		} else {
			$configfile->qos["enable"] = ($qos_enabled == "checked") ? true : false;
			$configfile->qos["interface"] = $qos_interface;
			$configfile->qos["upstream"] = $qos_upstream;
			$configfile->qos["downstream"] = $qos_downstream;
			$configfile->qos["default-prio"] = get_qos_prio($qos_prio, true);
			//write config
			$configfile->dirty["qos"] = true;
			if(WriteWorkingConfig()) {
				header("Location:edit_qos.php");
				die;
			} else {
				add_warning("Error writing to working configfile!");
			}
		}
	} else {
		$qos_enabled = ($configfile->qos["enable"]) ? "checked" : "";
		$qos_upstream = $configfile->qos["upstream"];
		$qos_downstream = $configfile->qos["downstream"];
		$qos_prio = get_qos_output_prio($configfile->qos["default-prio"]);
	}

	$MenuTitle="Traffic Shaping / QoS";
	$MenuType="NETWORK";
	$PageIcon="service.jpg";
	include("includes/header.php");
	$buttoninfo[0] = array("label" => "Apply", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "Cancel", "dest" => "network_settings.php");

	$priolist = array("Low", "Normal", "High", "Lan (bypass QoS)");
	$prioval = array("low", "normal", "high", "lan");

?>
<script language="javascript">
	function delete_item(id) {
		t = 'edit_qosrule.php?ruleidx='+id+'&action=delete';
		if(!confirm('Delete QoS rule?')) exit;
		window.location.href = t;
	}
</script>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
  <table width="100%" border="0">
    <tr>
      <td class="labelcell" colspan="2"><p><strong>Note:</strong> Traffic shaping is currently an experimental feature and is being actively developed. Please report any problems or suggestions to the appropriate forum on the Coyote Linux web site. It is not currently possible to specify the exact amount of bandwidth allocated for a given host, network or service - only the traffic priority. In order for the traffic shaping system to work properly, you need to specify the amount of available bandwidth for your Internet connection. Currently, eth0 is assumed to be the public (Internet) and eth1 is your private / LAN interface. Future implementations will allow this to be custom configured. </p>
      </td>
    </tr>
    <tr>
      <td colspan="2" valign="middle" class="labelcellmid">
	  <input type="checkbox" name="cbEnabled" value="checked" <?=$qos_enabled?> />
	  <label>Enable Traffic Shaping / QoS</label></td>
    </tr>
    <tr>
      <td class="labelcell" nowrap="nowrap"><label>Upstream Capacity: </label></td>
      <td width="100%"><input type="text" name="edtUpStream" value="<?=$qos_upstream?>"/>kbit
      </td>
    </tr>
    <tr>
      <td class="labelcell" nowrap="nowrap"><label>Downstream Capacity:</label> </td>
      <td>
        <input type="text" name="edtDownStream" value="<?=$qos_downstream?>" />kbit
      </td>
    </tr>
    <tr>
      <td class="labelcell"><label>Default Priority: </label></td>
      <td>
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
      </td>
    </tr>
  </table>
  <hr />
<table width="100%" border="0">
  <tr>
    <td><p><label>Note:</label> The current traffic shaping implementation creates 3 traffic classes: high, normal and low priority. Traffic placed in the high-priority class will be sent before either of the other two classes and is guaranteed up to 90% of your available bandwidth. The low priority class is only guaranteed 5% of your available bandwidth but has the capability of bursting to 90% of available if the high and normal priority class traffic queues are empty. </p>
      <p>The default traffic type for unclassified traffic is low priority.</p></td>
  </tr>
</table>

<b>Traffic classification control</b><br>
<span class="descriptiontext"><b>Note:</b> These settings control how traffic is classified when it passes through the firewall. Traffic rules apply to trafic <em>leaving</em> an interface.</span>
				<br>
				<table border="0" width="100%">
					<tr>
						<td class="labelcell" ><label>Interface</label></td>
						<td class="labelcell" align="center"><label>Protocol</label></td>
						<td class="labelcell" align="center"><label>Port</label></td>
						<td class="labelcell" align="center"><label>Classification</label></td>
						<td class="labelcell" align="center"><label>Edit</label></td>
						<td class="labelcell" align="center"><label>Del</label></td>
					</tr>


	<?
		$idx=0;
		if (!isset($configfile->qos['filters'])) {
			$configfile->qos['filters'] = array();
		}
		$maxrule = count($configfile->qos['filters']) - 1;

		foreach($configfile->qos['filters'] as $ruleidx => $qrule) {
			if ($idx % 2) {
				$cellcolor = "#F5F5F5";
			} else {
				$cellcolor = "#FFFFFF";
			}
	?>
					<tr>
						<td bgcolor="<?=$cellcolor?>"><?=$qrule["interface"]?></td>
						<td bgcolor="<?=$cellcolor?>" align="center"><?=$qrule["proto"]?></td>
						<td bgcolor="<?=$cellcolor?>" align="center"><?=$qrule["ports"]?></td>
						<td align="center" bgcolor="<?=$cellcolor?>"><?=get_qos_output_prio($qrule["prio"]);?></td>
						<td align="center" bgcolor="<?=$cellcolor?>">
						<a href="edit_qosrule.php?ruleidx=<?=$ruleidx?>">
						<img border="0" src="images/icon-edit.gif" width="16" height="16"></a></td>
						<td align="center" bgcolor="<?=$cellcolor?>">
						<a href="javascript:delete_item(<?=$ruleidx?>)">
						<img border="0" src="images/icon-del.gif" width="16" height="16"></a></td>
					</tr>

	<?
			$idx++;
		}
	?>
				</table>
				<table border="0" width="100%" id="table2">
					<tr>
						<td>
							<a href="add_icmprule.php">
							<img border="0" src="images/icon-plus.gif" width="16" height="16">
							</a>
					  	</td>
						<td width="100%"><b><a href="edit_qosrule.php?ruleidx=add">
							Add a new rule</a></b>						
						</td>
					</tr>
				</table>

</form>
<?
	include("includes/footer.php");
?>