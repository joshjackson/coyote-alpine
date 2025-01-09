<?
	require_once("../includes/loadconfig.php");

	// Extract the vpnsvc addon configuration object
	$vpnconf =& $configfile->get_addon('VPNSVCAddon', $vpnconf);
	if ($vpnconf === false) {
		// WTF?
		header("location:/index.php");
		exit;
	}

	$certfile=$_GET["cert"];

	$curdir=getcwd();
	if ($IN_DEVELOPMENT) {
		chdir("/home/jafo/wolverine");
	} else {
		chdir("/etc/ipsec.d");
	}

	if ($certfile) {
		if (!$certfile || !file_exists($certfile)) {
			chdir($curdir);
			header("location: ipsecconf.php");
		}
		list($fd_hostname) = split("_", $certfile, 2);
		$fd_certdata = file_get_contents($certfile);
		$MenuTitle = "Edit x.509 Certificate";
	} else {
		if ($_POST["action"] == "apply") {

			$fd_hostname = strtolower($_POST["hostname"]);
			if ($fd_hostname == $configfile->hostname) {
			  add_critcal("The specified hostname cannot be the same as this firewall's hostname.");
			} elseif (!is_hostname($fd_hostname)) {
			  add_critical("The specified hostname is invalid.");
			} else {
				$certfile=$fd_hostname . "_cert.pem";
				$fd_certdata = $_POST["certdata"];
				mount_flash_rw();
				$certres = @openssl_x509_read($fd_certdata);
				if (!$certres || !openssl_x509_export_to_file($certres, $certfile, true)) {
				  add_critical("The specified certificate data does not appear to be valid.");
				}
				mount_flash_ro();
			}

			if (!query_invalid()) {
				chdir($curdir);
				header("location:ipsecconf.php");
				die;
			}

		}
		$MenuTitle="x.509 Certificate Import";
	}

	chdir($curdir);

	$MenuType="VPN";
	$buttoninfo[0] = array("label" => "write changes", "dest" => "javascript:do_submit()");
	$buttoninfo[1] = array("label" => "Cancel", "dest" => "ipsecconf.php");
	include("../includes/header.php");

?>

<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
<input type="hidden" name="action" value="apply">
<center><font size=2><b>Install a new x.509 certificate</b></font></center><br>
<table width="100%"  border="0">
  <tr>
    <td class="labelcell"><label>Hostname: </label></td>
    <td><input type="text" name="hostname" value="<?=$fd_hostname?>">
      <br>
      <span class="descriptiontext">This is the hostname for the remote firewall that uses this certificate. It will be used to generate the filename for the stored certificate data.</span> </td>
  </tr>
  <tr>
    <td class="labelcell"><label>Certificate Data: </label></td>
    <td><p>
      <textarea name="certdata" cols="100" rows="15"><?=$fd_certdata?></textarea>
			<br>
			<span class="descriptiontext">The certificate data must be the text content of an x.509 certificate in PEM format. </span></p>
      </td>
  </tr>
</table>
<?
	print("<b><font color='red'>".query_warnings()."</font></b>");
?>
</form>
<?
	include("../includes/footer.php");
?>