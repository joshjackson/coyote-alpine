<?
	include("includes/loadconfig.php");

	$MenuTitle="Firewall Rules";
	$MenuType="RULES";

	$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";

	if($action == 'del') {
		$aclidx = filter_input(INPUT_GET, 'aclidx', FILTER_SANITIZE_SPECIAL_CHARS) ?? "";

		if(!strlen($aclidx)) {
			//redirect here
		}

		$tmp = array();
		//rebuild the array without aclidx
		foreach($configfile->acls as $key => $tacl) {
			if($key != $aclidx) {
				$tmp[$key] = $tacl;
			}
		}
		$configfile->acls = $tmp;
		$tmp = array();

		//save config change
		$configfile->dirty["acls"] = true;
		WriteWorkingConfig();
	}

	if($action == 'reorder') {

		// $dir = $_GET['dir'];
		// $target = $_GET['target'];
		$dir = filter_input(INPUT_GET, 'dir', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";
		$target = filter_input(INPUT_GET, 'target', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? "";

		$tmp = array();

		$keys= array_keys($configfile->acls);

		$swapdone = false;
		$idx = 0;
		while($idx < (count($keys) - 1)) {
			switch ($dir) {
				case "up":
					if ($keys[$idx + 1] == $target) {
						$keytmp=$keys[$idx];
						$keys[$idx] = $keys[$idx + 1];
						$keys[$idx + 1] = $keytmp;
						$idx++;
					}
					break;
				;;
				case "down":
					if ($keys[$idx] == $target) {
						$keytmp=$keys[$idx + 1];
						$keys[$idx + 1] = $keys[$idx];
						$keys[$idx] = $keytmp;
						$idx++;
					}
					break;
				;;
			}
			$idx++;
		}

		foreach($keys as $key) {
			$tmp[$key] = $configfile->acls[$key];
		}

		$configfile->acls = $tmp;
		$configfile->dirty["acls"] = true;
  		//write changes we made
  		WriteWorkingConfig();
	}

	include("includes/header.php");
?>

<script language="javascript">
  function delete_item(id) {
    f = document.forms[0];
    found = 0;

    if(!confirm('Delete ACL '+id+'?')) exit;

    document.location.href = ('firewall_rules.php?action=del&aclidx='+id);
  }
</script>

<form name="content" action="<?=$_SERVER['PHP_SELF']; ?>">
<input type="hidden" id="action" name="action" value="">
<table width="100%" id="table1">
	<tr>
		<td>
			<table width="100%">
				<tr>
					<td class="labelcell" width="70%"><label>Access List</label></td>
					<td class="labelcellctr"><label>Edit</label></td>
					<td class="labelcellctr"><label>Del</label></td>
					<td class="labelcellctr"><label>Up</label></td>
					<td class="labelcellctr"><label>Down</label></td>
				</tr>

<?
	//print_r($configfile->acls);

	$fd_maxacl = count($configfile->acls) - 1;
	$idx = 0;
	$aclnames = array_keys($configfile->acls);

	foreach($configfile->acls as $aclname => $aclentry) {
		if ($idx % 2) {
			$cellcolor = "#F5F5F5";
		} else {
			$cellcolor = "#FFFFFF";
		}

		if($idx)
			$prevacl = $aclnames[$idx - 1];

		if($idx < $fd_maxacl)
			$nextacl = $aclnames[$idx + 1];

?>
	<tr>
		<td width="70%" style="text-align: left; background-color: <?=$cellcolor?>;"><?=$aclname?></td>
		<td style="text-align: center; background-color: <?=$cellcolor?>;">
		<a href="access_list.php?aclidx=<?=$aclname?>">
		<img src="images/icon-edit.gif" width="16" height="16"></a></td>
		<td style="text-align: center; background-color: <?=$cellcolor?>;">
		<a href="javascript:delete_item('<?=$aclname?>')">
		<img src="images/icon-del.gif" width="16" height="16"></a></td>
		<td style="text-align: center; background-color: <?=$cellcolor?>;">
		<?
			if ($idx) {
				print('<a href="firewall_rules.php?action=reorder&dir=up&target='.$aclname.'"><img border="0" src="images/icon-mvup.gif" width="16" height="16"></a>');
			} else {
				print('&nbsp;');
			}
		?>
		</td>
		<td style="text-align: center; background-color: <?=$cellcolor?>;">
		<?
			if ($idx < $fd_maxacl) {
				print('<a href="firewall_rules.php?action=reorder&dir=down&target='.$aclname.'"><img border="0" src="images/icon-mvdn.gif" width="16" height="16"></a>');
			} else {
				print('&nbsp;');
			}
		?>
		</td>
	</tr>

<?

		$idx++;
	}
?>
</table>
		<table width="100%" id="table2">
			<tr>
				<td><a href="add_rule.php">
				<img src="images/icon-plus.gif" width="16" height="16"></a>
				</td>
				<td width="100%"><b><a href="add_rule.php?newacl=Y">Add a new access list</a></b></td>
		</tr>
	</table>

		<p><span class="descriptiontext"><b><hr>
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