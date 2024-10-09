<?
	include("includes/loadconfig.php");

	VregCheck();

	$MenuTitle="Interface Settings";
	$MenuType="INTERFACES";
	include("includes/header.php");
?>

<table border="0" width="100%">
	<tr>
		<td class="labelcell"><label>Interface name</label></td>
		<td class="labelcell" align="center"><label>Device</label></td>
		<td class="labelcell" align="center"><label>Bridging</label></td>
		<td class="labelcell" align="center"><label>Driver</label></td>
		<td class="labelcell" align="center"><label>Virtual</label></td>
		<td class="labelcell" align="center"><label>Active</label></td>
		<td class="labelcell" align="center"><label>Edit</label></td>
	</tr>
<?
	$idx=0;
	foreach($configfile->interfaces as $ifidx => $ifentry) {
		if ($idx % 2) {
			$cellcolor = "#F5F5F5";
		} else {
			$cellcolor = "#FFFFFF";
		}

		$Active = ($ifentry["down"]) ? "N" : "Y";
		$Bridge = ($ifentry["bridge"]) ? "Y" : "N";
		$Virtual = ($ifentry["vlan"] || !$ifentry["export"]) ? "Y" : "N";
?>

		<tr>
			<td bgcolor="<?=$cellcolor?>"><?=$ifentry["name"]?></td>
			<td align="center" bgcolor="<?=$cellcolor?>"><?=$ifentry["device"]?></td>
			<td align="center" bgcolor="<?=$cellcolor?>"><?=$Bridge?></td>
			<td align="center" bgcolor="<?=$cellcolor?>"><?=$ifentry["module"]?></td>
			<td align="center" bgcolor="<?=$cellcolor?>"><?=$Virtual?></td>
			<td align="center" bgcolor="<?=$cellcolor?>"><?=$Active?></td>
			<td align="center" bgcolor="<?=$cellcolor?>">
<?
		if ($ifentry["export"]) {
			print('<a href="edit_interface.php?intfidx='.$ifidx.'"><img border="0" src="images/icon-edit.gif" width="16" height="16"></a>');
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
<br>
<span class="descriptiontext"><b>Note:</b> Some virtual interfaces can not be edited. These interfaces
are created by the PPTP and PPPoE daemons automatically and can not have their
configurations directly modified.</span>

<?
	include("includes/footer.php");
?>