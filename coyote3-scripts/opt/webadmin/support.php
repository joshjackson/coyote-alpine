<?
	require_once("includes/loadconfig.php");

	if ($_POST["action"] == "post") {
		$configfile->options["vortech-support"] = ($_POST["support"] == "enable") ? true : false;
		$configfile->dirty["passwords"] = true;
		$configfile->dirty["acls"] = true;
		if(WriteWorkingConfig())
			add_warning("Write to working configfile was successful.");
		else
		  add_warning("Error writing to working configfile!");
	}

	$MenuTitle="Support Options";
	$PageIcon = "settings.jpg";
	$MenuType='SUPPORT';

	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "reset form", "dest" => $_SERVER['PHP_SELF']);
	
	$vsupport_mode = ($configfile->options["vortech-support"]) ? "enabled" : "disabled";		
	
	include("includes/header.php");

		
?>
	<p><br>
    To obtain support for this product, please visit the Vortech Consulting user forums at <a href="http://www.vortech.net/phorums/">http://www.vortech.net/phorums/</a> or email customer support at <a href="mailto:support@vortech.net">support@vortech.net</a>. </p>
	<p>Vortech support option is currently:
      <?=$vsupport_mode?>
</p>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
	<input type="hidden" name="action" value="post">
	<? if (!$configfile->options["vortech-support"]) { ?>
	<input type="hidden" name="support" value="enable">
	<p>If you are currently working with Vortech Consulting support to resolve an issue, you can enable the Vortech support option here. This should only be performed if you have been instructed to do so.</p>
	<p>
	  <input type="submit" name="Submit" value="Enable Vortech support">
	</p>
	<? } else { ?>
	<input type="hidden" name="support" value="disable">
	<p>Vortech support is currently enabled. If you are not currently working with Vortech support, you should disable this option. </p>
  	<input type="submit" name="Submit" value="Disable Vortech support">
	<? } ?>
</form>	
<?
	include("includes/footer.php");
?>