<?
	$action = $_POST["action"];
	if ($action == "reboot") {
		print("<html><head><meta http-equiv=\"refresh\" content=\"60;url=/index.php\"><title>Rebooting...</title></head></html>\n");
		print("<body>Please wait, rebooting the firewall. The web admin will reload in 60 seconds.</body></html>");
		exec("sudo /sbin/reboot");
		die;
	}

	$MenuTitle="Reboot";
	$MenuType="REBOOT";
	include("includes/header.php");
	//configure our buttonset for the bottom
	$buttoninfo[0] = array("label" => "Yes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "Cancel", "dest" => "index.php");

?>

<form method="post" action="reboot.php">
<input type="hidden" name="action" value="reboot">
<div align="center">
<table width="100%" id="table1">
	<tr>
		<td valign="middle" align="center">
		&nbsp;<p>&nbsp;</p>
		<p>&nbsp;</p>
		<p><b><font size="4">Rebooting the firewall will cause a temporary interruption in network
		services.</font></b></p>
		<p><b><font size="4">Are you sure you want to reboot?</font></b></td>
	</tr>
</table>

</div>
</form>

<?
	include("includes/footer.php");
?>