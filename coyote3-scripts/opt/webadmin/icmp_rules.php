<?
	include("includes/loadconfig.php");
	VregCheck();

	$MenuTitle="ICMP Control";
	$MenuType="RULES";

	$buttoninfo[0] = array("label" => "Write changes", "dest" => "javascript:do_submit()");

	function is_icmp_limited() {
		global $fd_enabled;
		if($fd_enabled === 'on')
			return true;
		else
			return false;
	}

	//determine posted
	if($_POST['postcheck'])
		$fd_posted = true;
	else
		$fd_posted = false;

	//1..2048

	//determine enabled
	if($fd_posted) {
		$fd_limit = $_POST['limit'];

		if($fd_limit)
			$fd_enabled = 'on';
		else
			$fd_enabled = 'off';

		//validate

		if(intval($fd_limit) > 2048) {
      add_warning("Warning: icmp limit of ".$fd_limit." may allow DoS attacks against this host.");
		}
		if(intval($fd_limit) < 1) {
		  add_critical("Invalid icmp limit: ".$fd_limit." is not a number.");
		}

		if(query_invalid()) {
			add_warning("<hr>".query_invalid()." parameters could not be validated.  No changes were made to the config.");
		} else {
			//assign
			$configfile->icmp['limit'] = $fd_limit;

			//write
		  $configfile->dirty["acls"] = true;
		  if(WriteWorkingConfig())
				add_warning("Write to working configfile was successful.");
			else
				add_warning("Error writing to working configfile!");
		}
	} else {
		//...
		$fd_limit = $configfile->icmp['limit'];

		if(intval($fd_limit) > 0)
			$fd_enabled = 'on';
		else
			$fd_enabled = 'off';
	}

	//...
	include("includes/header.php");

?>
<script language="javascript">
	function delete_item(id) {
		t = 'edit_icmprule.php?ruleidx='+id+'&action=delete';
		if(!confirm('Delete ICMP rule?')) exit;
		window.location.href = t;
	}
</script>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
	<input type="hidden" id="postcheck" name="postcheck" value="form was posted">
	<table border="0" width="100%" id="table1">
		<tr>
			<td>
				<b>Firewall ICMP Response Control</b><br>
				<span class="descriptiontext"><b>Note:</b> These settings do not control the flow of ICMP requests <i>through</i> the firewall. To restrict ICMP traffic passing through the firewall,
				use a standard access list.</span>
				<br>
				<table border="0" width="100%">
					<tr>
						<td class="labelcell" ><label>Interface</label></td>
						<td class="labelcell" align="center"><label>Source</label></td>
						<td class="labelcell" align="center"><label>Message Type</label></td>
						<td class="labelcell" align="center"><label>Edit</label></td>
						<td class="labelcell" align="center"><label>Del</label></td>
					</tr>


	<?
		$idx=0;

		$maxrule = count($configfile->icmp['rules']) - 1;

		foreach($configfile->icmp['rules'] as $ruleidx => $fwrule) {
			if ($idx % 2) {
				$cellcolor = "#F5F5F5";
			} else {
				$cellcolor = "#FFFFFF";
			}
	?>
					<tr>
						<td bgcolor="<?=$cellcolor?>"><?=$fwrule["interface"]?></td>
						<td bgcolor="<?=$cellcolor?>" align="center"><?=$fwrule["source"]?></td>
						<td bgcolor="<?=$cellcolor?>" align="center"><?=$fwrule["type"]?></td>
						<td align="center" bgcolor="<?=$cellcolor?>">
						<a href="edit_icmprule.php?ruleidx=<?=$ruleidx?>">
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
							<td width="100%"><b><a href="add_icmprule.php">
							Add a new ICMP
							rule</a></b>
						</td>
					</tr>
				</table>
			<br>
			<hr>
			<b>ICMP Rate limiter</b><br>
			<span class="descriptiontext">The number of ICMP requests per second allowed to pass through the firewall
			can be restricted to help reduce the impact of denial of service (DoS)
			attacks.</span>

			<table border="0" width="100%" id="table3">
				<tr>
					<td width="1%" class="labelcellmid">
						<input type="checkbox" name="enabled" <? if(is_icmp_limited()) print("checked");?> >
					</td>
					<td nowrap width="1%" class="labelcellmid">Limit the ICMP request rate to</td>
					<td width="1%" class="labelcellmid">
					<input type="text" name="limit" size="6" value="<?=$fd_limit?>"></td>
					<td class="labelcellmid">requests per second.</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

</form>
<?
  print(query_warnings());
	include("includes/footer.php");
?>