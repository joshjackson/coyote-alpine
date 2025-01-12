<script language="javascript">
	function do_submit() { document.forms[0].submit(); }
</script>

     </TD>
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_06.gif" WIDTH=167 HEIGHT=174 ></TD>
  </TR>
  <TR>
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_07.gif" WIDTH=172 HEIGHT=136 ></TD>
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_08.gif" HEIGHT=136 > <br> <center>
        <TABLE BORDER=0 CELLPADDING=0 CELLSPACING=0>
          <tr>


			<?
	// Build the buttons if needed
	if(!empty($buttoninfo) && count($buttoninfo) > 0) {
		for ($t=0; $t < count($buttoninfo); $t++) {
			$plabel = $buttoninfo[$t]["label"];
			$pdest = $buttoninfo[$t]["dest"];
			print("<td class=norep background=/theme/".$SiteTheme."/wolf-bttns-flat_01.gif width=26 height=48></td>");
			print("<td background=/theme/".$SiteTheme."/wolf-bttns-flat_02.gif height=48><a class=footerbutton href=$pdest><nobr>$plabel</nobr></a></td>");
			print("<td class=norep background=/theme/".$SiteTheme."/wolf-bttns-flat_03.gif width=26 height=48></td>");
			print("<td>&nbsp;&nbsp;&nbsp;</td>");
		}
	}

			?>
          </tr>
        </table>
      </center>
      </TD>
    <TD background="/theme/<?=$SiteTheme?>/wolf-back-actual_09.gif" WIDTH=167 HEIGHT=136 ></TD>
  </TR>
</TABLE>
</BODY>
</HTML>