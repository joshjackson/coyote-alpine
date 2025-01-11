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
	if (!$certfile || !file_exists($certfile)) {
		header("location: ipsecconf.php");
	}

	$certtext = file_get_contents($certfile);
	$certdata = openssl_x509_parse($certtext);	
	
	chdir($curdir);

	$buttoninfo[0] = array("label" => "Back", "dest" => "ipsecconf.php");
	$MenuTitle="View x.509 Certificate";
	$MenuType="VPN";
	include("../includes/header.php");
?>

<table width="100%"  border="0">
  <tr>
    <td nowrap class="labelcell"><label>Certificate:</label></td>
    <td width="100%"><?=$certfile?></td>
  </tr>
  <tr>
    <td nowrap class="labelcell"><label>Subject:</label></td>
    <td><?=trim(str_replace("/", " ", $certdata["name"]))?></td>
  </tr>
  <tr>
    <td nowrap class="labelcell"><label>Valid From: </label></td>
    <td><?=date("F j, Y, g:i a", $certdata["validFrom_time_t"])?></td>
  </tr>
  <tr>
    <td nowrap class="labelcell"><label>Valid Until: </label></td>
    <td><?=date("F j, Y, g:i a", $certdata["validTo_time_t"])?></td>
  </tr>
  <tr>
    <td nowrap class="labelcell"><label>Serial Number: </label></td>
    <td><?=$certdata["serialNumber"]?></td>
  </tr>
  <tr>
    <td nowrap class="labelcell"><label>Issuer: </label></td>
    <td>
<?
			if (is_array($certdata["issuer"])) {
				foreach($certdata["issuer"] as $ik => $iv) {
					print($ik."=".$iv." ");
				}
			}
?>	
		</td>
  </tr>
  <tr>
    <td nowrap class="labelcell"><label>Certificate Data: </label></td>
    <td>
<pre>
<?=$certtext?>
</pre>
		</td>
  </tr>
</table>
<?
	include("../includes/footer.php");
?>