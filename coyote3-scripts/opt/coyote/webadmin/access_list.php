<?
	require_once("includes/loadconfig.php");
	require_once("runconfig.php");

	$AccessListName = filter_input(INPUT_GET, "aclidx", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$ruleidx = filter_input(INPUT_GET, "ruleidx", FILTER_VALIDATE_INT);
	//$ruleidx = $_GET["ruleidx"];

	$action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_FULL_SPECIAL_CHARS);

	if (!$AccessListName || !array_key_exists($AccessListName, $configfile->acls)) {
		header("Location: firewall_rules.php");
		die;
	}

	$MenuTitle=$gtAccessList."Access List: $AccessListName";
	$MenuType="RULES";

	if($action == 'reorder') {
		$srcidx = $_GET['src'];
		$tidx = $_GET['target'];

		$cacl = $configfile->acls[$AccessListName];

		$src = $cacl[$srcidx];
		$target = $cacl[$tidx];

		$configfile->acls[$AccessListName][$srcidx] = $target;
		$configfile->acls[$AccessListName][$tidx] = $src;

		// Flag the ACLs list as dirty
		$configfile->dirty["acls"] = true;

		//write changes
		WriteWorkingConfig();
	}

	if($action == 'delete') {
		//...
		$tmp = array();

		//rebuild the array without aclidx
		foreach($configfile->acls[$AccessListName] as $key => $crule) {
			if(!strlen($key)) continue;
			if($key != $ruleidx) {
				$tmp[count($tmp)] = $crule;
			}
		}

		$configfile->acls[$AccessListName] = $tmp;
		$tmp = array();

		// Flag the ACLs list as dirty
		$configfile->dirty["acls"] = true;
		WriteWorkingConfig();
	}

	//warn
	if($fd_invalid) {
		//...
	} else {
		//...
	}

	include("includes/header.php");

?>
<script language="javascript">
  function delete_item(id) {
    f = document.forms[0];
    found = 0;
    acl = '<?=$AccessListName?>';

    if(!confirm('<?=$atDelConfirm?>')) exit;

    document.location.href = ('access_list.php?action=delete&aclidx='+acl+'&ruleidx='+id);
  }
</script>

<form name="content" method="post" action="<?=$_SERVER['PHP_SELF']; ?>">
	<table width="100%" id="table1">
		<tr>
			<td>
				<table width="100%">
					<tr>
						<td class="labelcell" ><label><?=$gtTarget?></label></td>
						<td class="labelcellctr"><label><?=$gtProtocol?></label></td>
						<td class="labelcellctr"><label><?=$gtSource?></label></td>
						<td class="labelcellctr"><label><?=$gtDestination?></label></td>
						<td class="labelcellctr"><label><?=$gtStartPort?></label></td>
						<td class="labelcellctr"><label><?=$gtEndPort?></label></td>
						<td class="labelcellctr"><label><?=$gtEdit?></label></td>
						<td class="labelcellctr"><label><?=$gtDel?></label></td>
						<td class="labelcellctr"><label><?=$gtUp?></label></td>
						<td class="labelcellctr"><label><?=$gtDown?></label></td>
					</tr>
	<?
		$idx=0;

		$maxrule = count($configfile->acls["$AccessListName"]) - 1;

		foreach($configfile->acls[$AccessListName] as $ruleidx => $fwrule) {
			if ($idx % 2) {
				$cellcolor = "#F5F5F5";
			} else {
				$cellcolor = "#FFFFFF";
			}
			$permit = ($fwrule["permit"]) ? "<font color=\"green\">".$gtPermit."</font>" : "<font color=\"red\">".$gtDeny."</font>";

			@list($startport, $endport) = explode(":", $fwrule["ports"]);

	?>
					<tr>
						<td style="background-color: <?=$cellcolor?>;"><?=$permit?></td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;"><?=$fwrule["protocol"]?></td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;"><?=$fwrule["source"]?></td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;"><?=$fwrule["dest"]?></td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;"><?=$startport?></td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;"><?=$endport?></td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;">
							<a href="edit_rule.php?aclidx=<?=$AccessListName?>&ruleidx=<?=$idx?>">
							<img src="images/icon-edit.gif" width="16" height="16"></a>
						</td>
						<td style="text-align:center; background-color: <?=$cellcolor?>;">
							<a href="javascript:delete_item('<?=$idx?>')">
							<img src="images/icon-del.gif" width="16" height="16"></a>
						</td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;">
			<?
				if ($idx) {
					$target = intval($idx)-1;
					print('<a href="'.$_SERVER['PHP_SELF'].'?action=reorder&aclidx='.$AccessListName.'&src='.$idx.'&target='.$target.'">');
					print('<img src="images/icon-mvup.gif" width="16" height="16">');
					print('</a>');
				} else {
					print('&nbsp;');
				}
			?>
						</td>
						<td style="background-color: <?=$cellcolor?>; text-align: center;">
			<?
				if ($idx < $maxrule) {
					$target = intval($idx)+1;
					print('<a href="'.$_SERVER['PHP_SELF'].'?action=reorder&aclidx='.$AccessListName.'&src='.$idx.'&target='.$target.'">');
					print('<img src="images/icon-mvdn.gif" width="16" height="16">');
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
						<td>
							<a href="add_rule.php">
							<img src="images/icon-plus.gif" width="16" height="16"></a>
						</td>
						<td width="100%">
							<b><a href="add_rule.php?aclidx=<?=$AccessListName?>&ruleidx=<?=$idx?>"><?=$atAddRuleToList?></a></b>
						</td>
					</tr>
				</table>
				<span class="descriptiontext"><b><hr>
				<?=$gtNote?></b> <?=$atACLNote1?></span></td>
			</td>
		</tr>
	</table>
</form>
<?
	include("includes/footer.php");
?>