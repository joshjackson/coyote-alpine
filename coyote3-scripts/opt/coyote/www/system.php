<?
	require_once("includes/loadconfig.php");

	VregCheck();

	$MenuTitle="System Settings";
	$MenuType="SYSTEM";
	include("includes/header.php");
?>

<table border="0" width="100%" id="table1">
	<tr>
		<td><font size="2">To configure the various system options, please make a selection from the System Settings sub-menu.</font></td>
	</tr>
</table>

<?
	include("includes/footer.php");
?>