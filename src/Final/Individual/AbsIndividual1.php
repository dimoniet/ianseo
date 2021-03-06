<?php
	define('debug',false);	// settare a true per l'output di debug

	require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
	require_once('Common/Fun_Number.inc.php');
	require_once('Common/Fun_FormatText.inc.php');

	CheckTourSession(true);

	$JS_SCRIPT=array(
		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Final/Individual/Fun_JS.js"></script>',
		);

	include('Common/Templates/head.php');
?>
<form name="Frm" method="post" action="AbsIndividual2.php">
<table class="Tabella">
<TR><TH class="Title" colspan="2"><?php print get_text('ShootOff4Final') . ' - ' . get_text('Individual');?></TH></TR>
<tr class="Divider"><td colspan="2"></td></tr>
<tr>
<th class="TitleLeft" width="15%"><?php print get_text('Event');?></th>
<td>
<select name="EventCode" id="EventCode">
<?php
// nella query se Eliminatorie vale #2 vuol dire che l'evento prevede l'eliminatoria; '' altrimenti
	$Select
		= "SELECT EvCode,EvTournament,	EvEventName, IF(EvElim1>0 || EvElim2>0,'#2','') AS Eliminatorie "
		. "FROM Events "
		. "WHERE EvTournament=" . StrSafe_DB($_SESSION['TourId']) . " AND EvTeamEvent='0' AND EvFinalFirstPhase!=0 "
		//. "AND EvElim1=0 AND EvElim2=0 "
		. "ORDER BY EvProgr ASC ";
	$Rs=safe_r_sql($Select);

	$CountElim = 0;
	$CountNoElim = 0;

	$opts='';
	if (safe_num_rows($Rs)>0)
	{
		while($MyRow=safe_fetch($Rs))
		{
			$opts.= '<option value="' . $MyRow->EvCode . $MyRow->Eliminatorie . '"';
			if ($MyRow->Eliminatorie=='#2') {
				if(in_array($MyRow->EvCode, $_SESSION['MenuElim1']) or in_array($MyRow->EvCode, $_SESSION['MenuElim2'])) $opts.= ' style="color:red"';
				++$CountElim;
			} else {
				if(in_array($MyRow->EvCode, $_SESSION['MenuFinI'])) $opts.= ' style="color:red"';
				++$CountNoElim;
			}
			$opts.= '>' . $MyRow->EvCode . ' - ' . get_text($MyRow->EvEventName,'','',true) . '</option>' . "\n";
		}
	}

	if ($CountElim==0 && $CountNoElim>0) {
		print '<option value="">' . get_text('AllEvents') . '</option>';
	}
	print $opts;
?>
</select>
</td>
</tr>
<tr><td class="Center" colspan="2"><input type="button" id="CmdNext" value="<?php print get_text('CmdNext');?>" onClick="javascript:SelectAction();"></td></tr>
</table>
</form>
<?php
	include('Common/Templates/tail.php');
?>