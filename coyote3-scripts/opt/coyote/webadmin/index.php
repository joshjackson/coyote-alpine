<?
	require_once("includes/loadconfig.php");
	$MenuTitle="Main Menu";
	include("includes/header.php");
	
	$firmware = GetFirmwareVersion();
?>

<table width="80%" id="table1">
	<tr>
		<td><p><font size="2"><b>Welcome to the web administrator for Coyote Linux v<? print(PRODUCT_VERSION); ?>.</b></font></p>
		  <table cellspacing="3" cellpadding="1">
        <tr>
          <td colspan="2" class="labelcellmid"><font size="2"><b>Firewall Information</b></font></td>
        </tr>
        <tr>
          <td nowrap class="labelcellmid"><label>Hostname:</label></td>
          <td width="100%"><?=$configfile->hostname?></td>
        </tr>
        <tr>
          <td nowrap class="labelcellmid"><label>Firmware Version: </label></td>
          <td width="100%"><? print($firmware["version"].".".$firmware["build"]) ?></td>
        </tr>
        <tr>
          <td nowrap class="labelcellmid"><label>Firmware Build Date: </label></td>
          <td width="100%"><? print($firmware["date"]) ?></td>
        </tr>
        <tr>
          <td nowrap class="labelcellmid"><label>Kernel Version: </label></td>
          <td width="100%"><?=php_uname('r');?></td>
        </tr>
        <tr>
          <td nowrap class="labelcellmid"><label>Loader Version: </label></td>
          <td width="100%"><? print($firmware["loader"]) ?></td>
        </tr>
        <tr>
          <td nowrap>&nbsp;</td>
          <td width="100%">&nbsp;</td>
        </tr>
        <tr>
          <td nowrap><img src="images/vortech-logo.gif" width="107" height="150"></td>
          <td width="100%" valign="middle"><p>Coyote Linux is a product of Vortech Consulting, LLC. For more information on Coyote, support options, or to access the public help forums, please visit the Vortech Consulting website at <a target=_new href="https://www.vortech.net">https://www.vortech.net</a>.</p>
          <p><strong>NOTICE</strong>: This product is licensed for personal and educational use only. Commercial and governmental use is strictly prohibited. For commercial firewall options from Vortech Consulting, please visit the Vortech Consulting web site or email support@vortech.net.</p></td>
        </tr>
      </table>		  <p><br>
      </p>
	  </td>
	</tr>
</table>

<?
	include("includes/footer.php");
?>
