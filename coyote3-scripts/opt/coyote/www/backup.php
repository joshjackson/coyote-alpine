<?
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
	} else {
		$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
	}
	
	if ($action == "backup") {
		$curdir = getcwd();
		chdir("/opt/coyote/config");
		exec("/bin/tar -cf /tmp/fwconfig.tar .");
		header("Content-type: application/x-tar");
		header('Content-Disposition: attachment; filename="fwconfig.tar"');
		readfile('/tmp/fwconfig.tar');
		unlink('/tmp/fwconfig.tar');
		chdir($curdir);
		die;
	} elseif ($action == "restore") {
		$curdir = getcwd();
		$uploadfile = '/tmp/' . $_FILES['userfile']['name'];
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			exec('/usr/config/restoreconf '.$uploadfile);
			$msg = "System configuration restored. Please reboot the firewall.";
			@unlink($uploadfile);
		} else {
			$msg = "Failed to process uploaded file.";
		}
	}
	
	$MenuTitle="System Backup Options";
	$MenuType="SYSTEM";
	$PageIcon="service.jpg";
	include("includes/header.php");
	
	if (!empty($msg)) {
		print("<center><font size=2>$msg</font></center><br><br>");
	}	
?>

<table width="100%"  border="0">
  <tr>
    <td class="labelcellmid"><label><font size="2">System Configuration Backup</font></label> </td>
  </tr>
  <tr>
    <td><p><a href="<?=$_SERVER['PHP_SELF']?>?action=backup">Click here to download an archive of this firewall's system configuration.</a></p>
    <p><strong>Warning </strong>- This file should be stored in a secure location as it contains information which could be used to compromise the security of this firewall and/or the network it protects. </p></td>
  </tr>
</table>

<table width="100%"  border="0">
  <tr>
    <td class="labelcellmid"><label><font size="2">System Configuration Restore</font></label> </td>
  </tr>
  <tr>
    <td>
	  <form method="POST" action="<?=$_SERVER['PHP_SELF']?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="restore">
		  <p>
		    Configuration file to restore: 
		    <input type="file" name="userfile">
		  </p>
		  <p>
		    <input type="submit" name="Submit" value="Restore Configuration">
		  </p>
	  </form>		
		
		</td>
  </tr>
</table>

<?
	include("includes/footer.php");
?>