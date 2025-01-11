<?
	require_once("includes/loadconfig.php");
	//did we freshly load this page or are we loading on result of a post
	if(!empty($_FILES['userfile']) && !empty($_FILES['userfile']['name'])) {
		$uploaddir = '/mnt/tmp/';
		$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);		
		
		mount_flash_rw();
		
		if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
			mount_flash_ro();
			ob_start();
			print("<html><head><meta http-equiv=\"refresh\" content=\"90;url=/index.php\"><title>Rebooting...</title></head>\n");
			print("<body>Please wait, the firewall is rebooting. The web admin will reload in 90 seconds.</body></html>");
			ob_end_flush();
			flush();
			exec("/usr/config/applyupdate $uploadfile 1> /dev/null 2> /dev/null&");
			die;
		}
		mount_flash_ro();
	}

	//define our buttons
	$buttoninfo[0] = array("label" => "Send Update", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => "sysupgrade.php");

	$MenuTitle="Firmware Upgrade";
	$MenuType="SYSTEM";
	$PageIcon="service.jpg";

	include("includes/header.php");

?>
<!-- The data encoding type, enctype, MUST be specified as below -->

<table>
	<tr>
		<td class="labelcellmid">
		<label><font size="2">System Firmware Upgrade</font></label>
		</td>
	</tr><tr>
	  <td>
<form enctype="multipart/form-data" action="<?=$_SERVER['PHP_SELF']; ?>" method="POST">
    <!-- MAX_FILE_SIZE must precede the file input field -->
    <input type="hidden" name="MAX_FILE_SIZE" value="16384000">
	<input type="hidden" name="postcheck" value="posted">
    <!-- Name of input element determines name in $_FILES array -->
    <strong>Firmware Image File:</strong>    
    <input name="userfile" type="file" />
</form>
<p><br>
  Please specify the location of the  firmware image. Please note that as soon as the update is complete, the firewall will be automatically rebooted, causing a temporary interruption in network services. Please make sure that the firmware file being uploaded matches the revision of this product you are licensed to run**. If you upload a later revision firmware, the firewall may enter an expired state and require console interaction to properly reboot. </p>
<p>Upgrade firmware images should only be downloaded directly from Vortech Consulting. Uploading a modified or 3rd party firmware image is not supported**.</p>
<p>** Only applicable to commercial software. Not applicable to Coyote Linux. </p>
</td>
	</tr>
</table>
<?
	include("includes/footer.php");
?>