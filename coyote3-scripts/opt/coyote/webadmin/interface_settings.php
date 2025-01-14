<?
	include("includes/loadconfig.php");

	$MenuTitle="Interface Settings";
	$MenuType="INTERFACES";
	include("includes/header.php");
?>

<table width="100%">
	<tr>
		<td class="labelcell"><label>Interface name</label></td>
		<td class="labelcellctr"><label>Device</label></td>
		<td class="labelcellctr"><label>Bridging</label></td>
		<td class="labelcellctr"><label>Driver</label></td>
		<td class="labelcellctr"><label>Virtual</label></td>
		<td class="labelcellctr"><label>Active</label></td>
		<td class="labelcellctr"><label>Edit</label></td>
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
		$Virtual = (!empty($ifentry["vlan"]) || !$ifentry["export"]) ? "Y" : "N";
?>

		<tr>
			<td style="text-align: left; background-color: <?=$cellcolor?>;"><?=$ifentry["name"]?></td>
			<td style="text-align: center; background-color: <?=$cellcolor?>;"><?=$ifentry["device"]?></td>
			<td style="text-align: center; background-color: <?=$cellcolor?>;"><?=$Bridge?></td>
			<td style="text-align: center; background-color: <?=$cellcolor?>;"><?=$ifentry["module"]?></td>
			<td style="text-align: center; background-color: <?=$cellcolor?>;"><?=$Virtual?></td>
			<td style="text-align: center; background-color: <?=$cellcolor?>;"><?=$Active?></td>
			<td style="text-align: center; background-color: <?=$cellcolor?>;">
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