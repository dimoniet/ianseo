<?php
$URL='http://ianseo.net/Upload-Competition.php';

/**
 *
 * I codici dei file sono:
 * IMG --> le immagini della gara
 * ENS --> Start list per piazzola
 * ENC --> Start list per società
 * ENA --> Start list per ordine alfabetico
 * IC --> Classifica di classe individuale
 * TC --> Classifica di classe a squadre
 * IQ(evento) --> Qualificazione individuale dell'evento (evento)
 * TQ(evento) --> Qualificazione a squadre dell'evento (evento)
 * IE(evento) --> Eliminatorie individuali dell'evento (evento)
 * IF(evento) --> Finale individuale dell'evento (evento) (Rank)
 * TF(evento) --> Finale a squadre dell'evento	(Rank)
 * IB(evento) --> Finale individuale dell'evento (evento) (Bracket)
 * TB(evento) --> Finale a squadre dell'evento	(evento) (Bracket)
 *
 * MEDSTD --> Medal standing
 * MEDLST --> Medal list
 */
	require_once(dirname(dirname(__FILE__)) . '/config.php');
	require_once('Qualification/Fun_Qualification.local.inc.php');
	CheckTourSession(true);

	if(!empty($CFG->OVERRIDES) and !empty($CFG->SendToIanseo)) $URL=$CFG->SendToIanseo;

	if(empty($_SESSION['OnlineId']) or (empty($_SESSION['OnlineAuth']) and empty($_SESSION['OnlineAuthA2A'])) or empty($_SESSION['OnlineEventCode'])) {
		cd_redirect('SetCredentials.php?return=Tournament/'.basename(__FILE__));
	}

	$MSG='';
	$ORIS=$_SESSION['ISORIS'];

	if($_POST) {
		//debug_svela($_POST);
		$RET=new StdClass();
		require_once('Common/OrisFunctions.php');
		// WE ONLY SEND ORIS STUFF
		$ORIS=!empty($_POST['oris']);
		//$ORIS=true;

		if(!defined('PRINTLANG')) {
			if($ORIS) {
				define('PRINTLANG', 'EN');
			} else {
				define('PRINTLANG', $_SESSION['TourPrintLang']);
			}
		}

		$RET->ORIS = $ORIS;
		$RET->OnlineId = $_SESSION['OnlineId'];
		$RET->OnlineAuth = $_SESSION['OnlineAuth'];
		$RET->OnlineEventCode = $_SESSION['OnlineEventCode'];
		$RET->lastUpload = date('Y-m-d H:i:s');
		$RET->PDF=array();

		if(empty($_POST['btnDelOnline'])) {
			// Deal with PDFS
			if(!empty($_POST['ScoQual'])) $RET->PDF[]=getScoQuals();
			if(!empty($_POST['ScoElim'])) $RET->PDF[]=getScoElim();
			if(!empty($_POST['ScoInd'])) $RET->PDF[]=getScoInd();
			if(!empty($_POST['ScoTeam'])) $RET->PDF[]=getScoTeams();
			if(!empty($_POST['FOP'])) $RET->PDF[]=getFop();

			// send all the header stuff with images etc...
			if(!empty($_POST['IMG']) or $_SESSION['SendOnlinePDFImages']) $RET->IMG=getPdfHeader();

			// Entire Book
			$RET->BOOK=(!empty($_POST['BOOK']));

			// List by targets
			if(!empty($_POST['ENS'])) $RET->ENS=getStartList($ORIS);

			// List by Countries
			if(!empty($_POST['ENC'])) $RET->ENC=getStartListByCountries($ORIS);

			// List by Entries
			if(!empty($_POST['ENA'])) $RET->ENA=getStartListAlphabetical($ORIS);

			// Stats by Countries
			if(!empty($_POST['STC'])) $RET->STC=getStatEntriesByCountries($ORIS);

			// Stats by Entries
			if(!empty($_POST['STE'])) $RET->STE=getStatEntriesByEvent($ORIS);

			// Ranking by Category, Individual (local rules apply)
			if(!empty($_POST['IC'])) $RET->IC=getDivClasIndividual('', '', $_SESSION['TourType']==14 ? array('SubClassRank' => '1') : array());

			// Ranking by Category, Teams (local rules apply)
			if(!empty($_POST['TC'])) $RET->TC=getDivClasTeam();

			// Qualification, Individual
			if(!empty($_POST['QualificationInd'])) {
				$RET->IQ=new StdClass();
				foreach($_POST['QualificationInd'] as $Event) $RET->IQ->{$Event} = getQualificationIndividual(substr($Event,2),$ORIS);
			}

			// Elimination, Startlist
			if(!empty($_POST['EliminationStartlist'])) {
				$RET->EL=new StdClass();
				foreach($_POST['EliminationStartlist'] as $Event) $RET->EL->{$Event}=getStartList($ORIS, substr($Event, -1), true);
			}

			// Elimination, Individual
			if(!empty($_POST['EliminationInd'])) {
				$RET->IE=new StdClass();
				foreach($_POST['EliminationInd'] as $Event) $RET->IE->{$Event}=getEliminationIndividual(substr($Event,2),$ORIS);
			}

			// Qualification, Team
			if(!empty($_POST['QualificationTeam'])) {
				$RET->TQ=new StdClass();
				foreach($_POST['QualificationTeam'] as $Event) $RET->TQ->{$Event}=getQualificationTeam(substr($Event,2),$ORIS);
			}

			// Brackets, Individual
			if(!empty($_POST['BracketsInd'])) {
				$RET->IB=new StdClass();
				foreach($_POST['BracketsInd'] as $Event) $RET->IB->{$Event}=getBracketsIndividual(substr($Event,2),$ORIS);
			}

			// Brackets, Team
			if(!empty($_POST['BracketsTeam'])) {
				$RET->TB=new StdClass();
				foreach($_POST['BracketsTeam'] as $Event) $RET->TB->{$Event}=getBracketsTeams(substr($Event,2),$ORIS);
			}

			// Final Rank, Individual
			if(!empty($_POST['FinalInd'])) {
				$RET->IF=new StdClass();
				foreach($_POST['FinalInd'] as $Event) $RET->IF->{$Event}=getRankingIndividual(substr($Event,2),$ORIS);
			}

			// Brackets, Team
			if(!empty($_POST['FinalTeam'])) {
				$RET->TF=new StdClass();
				foreach($_POST['FinalTeam'] as $Event) $RET->TF->{$Event}=getRankingTeams(substr($Event,2),$ORIS);
			}

			// Medal standing
			if(!empty($_POST['MEDSTD'])) $RET->MEDSTD=getMedalStand($ORIS);

			// Medallists
			if(!empty($_POST['MEDLST'])) $RET->MEDLST=getMedalList($ORIS);
		} else {
			$RET->delete=array();

			if(!empty($_POST['ENS'])) $RET->delete[]='ENS'; // List by targets
			if(!empty($_POST['ENC'])) $RET->delete[]='ENC'; // List by Countries
			if(!empty($_POST['ENA'])) $RET->delete[]='ENA'; // List by Entries
			if(!empty($_POST['STC'])) $RET->delete[]='STC'; // List by Countries
			if(!empty($_POST['STE'])) $RET->delete[]='STE'; // List by Entries
			if(!empty($_POST['IC'])) $RET->delete[]='IC'; // Ranking by Category, Individual (local rules apply)
			if(!empty($_POST['TC'])) $RET->delete[]='TC'; // Ranking by Category, Teams (local rules apply)
			if(!empty($_POST['QualificationInd'])) foreach($_POST['QualificationInd'] as $Event) $RET->delete[]=''.$Event; // Qualification, Individual
			if(!empty($_POST['EliminationInd'])) foreach($_POST['EliminationInd'] as $Event) $RET->delete[]=''.$Event; // Elimination, Individual
			if(!empty($_POST['QualificationTeam'])) foreach($_POST['QualificationTeam'] as $Event) $RET->delete[]=''.$Event; // Qualification, Team
			if(!empty($_POST['BracketsInd'])) foreach($_POST['BracketsInd'] as $Event) $RET->delete[]=''.$Event; // Brackets, Individual
			if(!empty($_POST['BracketsTeam'])) foreach($_POST['BracketsTeam'] as $Event) $RET->delete[]=''.$Event; // Brackets, Team
			if(!empty($_POST['FinalInd'])) foreach($_POST['FinalInd'] as $Event) $RET->delete[]=''.$Event; // Final Rank, Individual
			if(!empty($_POST['FinalTeam'])) foreach($_POST['FinalTeam'] as $Event) $RET->delete[]=''.$Event; // Brackets, Team
			if(!empty($_POST['MEDSTD'])) $RET->delete[]='MEDSTD'; // Medal standing
			if(!empty($_POST['MEDLST'])) $RET->delete[]='MEDLST'; // Medallists
		}


		$ch=curl_init($URL);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, array(
			"Tour" => gzcompress(serialize($RET)),
		));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$varResponse=explode('|', curl_exec($ch));
		curl_close($ch);

		foreach($varResponse as $k => $v) {
			if($v!='Tutto regolare' and $v!='ERR_OK') {
				$varResponse[$k] = '<span style="color:red; font-size:large">' . $v. get_text($v, 'Tournament', '', true) . '</span>';
			} else {
				$varResponse[$k] = '<span style="color:green">' . get_text($v, 'Tournament') . '</span>';
			}
		}
		$MSG=implode('<br/>', $varResponse);

		if($RET->BOOK) $_POST=array();

		$_SESSION['SendOnlinePDFImages']='';
	}

	require_once('Common/Fun_FormatText.inc.php');
	require_once('Common/Fun_Various.inc.php');


// Seleziono la lista degli eventi
	$outputIndAbs='';
	$outputTeamAbs='';
	$outputElim='';
	$outputIndFin='';
	$outputTeamFin='';
	$outputIndBra='';
	$outputTeamBra='';
	$Elim=0;


	// select the ACTUAL Individual Events
	$Select
		= "SELECT distinct EvCode,EvEventName,EvTeamEvent,EvElim1,EvElim2,EvFinalFirstPhase "
		. "FROM EventCategories "
		. " inner join Entries on EcDivision=EnDivision and EcClass=EnClass "
		. "WHERE EvTournament=" . StrSafe_DB($_SESSION['TourId'])
		. " AND EvTeamEvent=0 "
		. "ORDER BY EvProgr ";
	$Rs=safe_r_sql($Select);
	while ($MyRow=safe_fetch($Rs)) {
		$QualCode='IQ' . $MyRow->EvCode;
		$FinCode='IF' . $MyRow->EvCode;
		$BraCode='IB' . $MyRow->EvCode;

		if ($MyRow->EvElim1>0 || $MyRow->EvElim2>0) {
			if(!$Elim) $Elim=1;
			if($MyRow->EvElim2) $Elim=2;
			$ElimCode='IE' . $MyRow->EvCode;
			$outputElim
				.='<input type="checkbox" name="EliminationInd[]" value="'.$ElimCode.'" id="' . $ElimCode . '"'
					.(empty($_POST['EliminationInd']) || !in_array($ElimCode, $_POST['EliminationInd']) ? '' : 'checked="checked"')
					. '>' . $MyRow->EvEventName . '<br/>' . "\n";
		}

		$outputIndAbs
			.='<input type="checkbox" name="QualificationInd[]" value="'.$QualCode.'" id="' . $QualCode . '"'
				.(empty($_POST['QualificationInd']) || !in_array($QualCode, $_POST['QualificationInd']) ? '' : 'checked="checked"')
				.'>' . $MyRow->EvEventName . '<br/>' . "\n";

		// solo chi ha la fase > 0 va avanti
		if(!$MyRow->EvFinalFirstPhase
			or in_array($MyRow->EvCode, $_SESSION['MenuElim1'])
			or in_array($MyRow->EvCode, $_SESSION['MenuElim2'])
			or in_array($MyRow->EvCode, $_SESSION['MenuFinI'])
			) continue;
		$outputIndFin
			.='<input type="checkbox" name="FinalInd[]" value="'.$FinCode.'" id="' . $FinCode . '"'
				.(empty($_POST['FinalInd']) || !in_array($FinCode, $_POST['FinalInd']) ? '' : 'checked="checked"')
				.'>' . $MyRow->EvEventName . '<br/>' . "\n";
		$outputIndBra
			.='<input type="checkbox" name="BracketsInd[]" value="'.$BraCode.'" id="' . $BraCode . '"'
				.(empty($_POST['BracketsInd']) || !in_array($BraCode, $_POST['BracketsInd']) ? '' : 'checked="checked"')
				.'>' . $MyRow->EvEventName . '<br/>' . "\n";
	}

	// select the ACTUAL Team Events
	$Sql = "SELECT EvCode, EvEventName, EvMixedTeam, EvMultiTeam, EvMaxTeamPerson,EvTeamCreationMode,EvFinalFirstPhase FROM Events WHERE EvTournament=" . StrSafe_DB($_SESSION['TourId']) . " AND EvTeamEvent=1 ORDER BY EvProgr";
	$RsEv=safe_r_sql($Sql);
	while($MyRowEv=safe_fetch($RsEv)) {
		$Sql = "SELECT DISTINCT EcCode, EcTeamEvent, EcNumber FROM EventClass WHERE EcCode=" . StrSafe_DB($MyRowEv->EvCode) . " AND EcTeamEvent!=0 AND EcTournament=" . StrSafe_DB($_SESSION['TourId']);
		$RsEc=safe_r_sql($Sql);
		if(safe_num_rows($RsEc)>0) {
			$RuleCnt=0;
			$Sql = "Select * ";
			while($MyRowEc=safe_fetch($RsEc)) {
				$ifc=ifSqlForCountry($MyRowEv->EvTeamCreationMode);
				$Sql .= (++$RuleCnt == 1 ? "FROM ": "INNER JOIN ");
//				$Sql .= "(SELECT IF(EnCountry2=0,EnCountry,EnCountry2) as C" . $RuleCnt . ", SUM(IF(EnSubTeam=0,1,0)) AS QuantiMulti
//					  FROM Entries
//					  INNER JOIN EventClass ON EnClass=EcClass AND EnDivision=EcDivision AND EnTournament=EcTournament AND EcTeamEvent=" . $MyRowEc->EcTeamEvent . " AND EcCode=" . StrSafe_DB($MyRowEc->EcCode) . "
//					  WHERE EnTournament=" . StrSafe_DB($_SESSION['TourId']) . " AND EnTeam" . ($MyRowEv->EvMixedTeam ? 'Mix' : 'F') ."Event=1
//					  group by IF(EnCountry2=0,EnCountry,EnCountry2), EnSubTeam
//					  HAVING COUNT(EnId)>=" . $MyRowEc->EcNumber . ") as sqy";
//				$Sql .= ($RuleCnt == 1 ? " ": $RuleCnt . " ON C1=C". $RuleCnt . " ");
				$Sql .= "(SELECT {$ifc} as C" . $RuleCnt . ", SUM(IF(EnSubTeam=0,1,0)) AS QuantiMulti
					  FROM Entries
					  INNER JOIN EventClass ON EnClass=EcClass AND EnDivision=EcDivision AND EnTournament=EcTournament AND EcTeamEvent=" . $MyRowEc->EcTeamEvent . " AND EcCode=" . StrSafe_DB($MyRowEc->EcCode) . "
					  WHERE {$ifc}<>0 AND EnTournament=" . StrSafe_DB($_SESSION['TourId']) . " AND EnTeam" . ($MyRowEv->EvMixedTeam ? 'Mix' : 'F') ."Event=1
					  group by {$ifc}, EnSubTeam
					  HAVING COUNT(EnId)>=" . $MyRowEc->EcNumber . ") as sqy";
				$Sql .= ($RuleCnt == 1 ? " ": $RuleCnt . " ON C1=C". $RuleCnt . " ");
			}
			$Sql .= " limit 1";

			$Rs=safe_r_sql($Sql);
			if(safe_num_rows($Rs)) {
				$QualCode='TQ' . $MyRowEv->EvCode;
				$FinCode='TF' . $MyRowEv->EvCode;
				$BraCode='TB' . $MyRowEv->EvCode;

				$outputTeamAbs
					.='<input type="checkbox" name="QualificationTeam[]" value="' . $QualCode . '" id="' . $QualCode . '"'
						.(empty($_POST['QualificationTeam']) || !in_array($QualCode, $_POST['QualificationTeam']) ? '' : 'checked="checked"')
						.'>' . $MyRowEv->EvEventName . '<br/>' . "\n";

				// solo chi ha la fase > 0 va avanti
				if(!$MyRowEv->EvFinalFirstPhase or in_array($MyRowEv->EvCode, $_SESSION['MenuFinT'])) continue;
				$outputTeamFin
					.='<input type="checkbox" name="FinalTeam[]" value="' . $FinCode . '" id="' . $FinCode . '"'
						.(empty($_POST['FinalTeam']) || !in_array($FinCode, $_POST['FinalTeam']) ? '' : 'checked="checked"')
						.'>' . $MyRowEv->EvEventName . '<br/>' . "\n";
				$outputTeamBra
					.='<input type="checkbox" name="BracketsTeam[]" value="' . $BraCode . '" id="' . $BraCode . '"'
						.(empty($_POST['BracketsTeam']) || !in_array($BraCode, $_POST['BracketsTeam']) ? '' : 'checked="checked"')
						.'>' . $MyRowEv->EvEventName . '<br/>' . "\n";
			}
		}
	}










	$JS_SCRIPT=array(
		phpVars2js(array(
			'StrInitProcess' => get_text('InitProcess', 'Tournament'),
			'StrOk' => get_text('CmdOk'),
			'StrError' => get_text('Error'),
			'StrCreateFiles' => get_text('CreateFiles', 'Tournament'),
			'StrMakingZip' => get_text('MakingZip', 'Tournament'),
			'StrMakingManifest' => get_text('MakingManifest', 'Tournament'),
			'StrNoCredential' => get_text('NoCredential', 'Tournament'),
			'StrSendData' => get_text('SendData', 'Tournament'),
			'StrErrorCode' => get_text('ErrorCode', 'Tournament'),
			'StrDeleting' => get_text('Deleting', 'Tournament'),
			'OnlineId' => (isset($_SESSION['OnlineId']) ? $_SESSION['OnlineId'] : 0),
			'RootDir' => $CFG->DOCUMENT_PATH,
			'WebDir' => $CFG->ROOT_DIR,
			'StrMsgAreYouSure' => get_text('MsgAreYouSure'),
			)),
//		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Common/ext-2.2/adapter/ext/ext-base.js"></script>',
//		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Common/ext-2.2/ext-all-debug.js"></script>',
//		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Common/ext-2.2/ext.util/ext.util.js"></script>',
//		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Tournament/Fun_AJAX_SendData.js"></script>',
		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Tournament/Fun_JS.js"></script>',
//		'<script type="text/javascript">',
//		'	Ext.onReady',
//		'	(',
//		'		function()',
//		'		{',
//		'		// aggangio gli eventlisteners per i bottoni',
//		'			Ext.get(\'btnOk\').on(\'click\',makeList);',
//		'			Ext.get(\'btnDelOnline\').on(\'click\',deleteOnline);',
//		'		},',
//		'		window',
//		'	);',
//		'</script>',
		);

	$PAGE_TITLE=get_text('Send2Ianseo','Tournament');

	include('Common/Templates/head.php');
?>
<form method="POST">
<div align="center">
	<div class="medium">
		<table class="Tabella">
			<tr>
				<th colspan="4"><?php print get_text('Send2Ianseo','Tournament'); ?></th>
			</tr>
			<tr>
				<td class="Left"><input type="checkbox" name="btnDelOnline"><?php print get_text('CmdDeleteOnline','Tournament'); ?></td>
				<td colspan="2" class="Center" id="msg"><b><?php echo $MSG; ?></b></td>
				<td class="Right">
				<?php print get_text('StdORIS','Tournament'); ?>
				&nbsp;
				<input name="oris" type="checkbox" id="oris" <?php echo $ORIS ? 'checked="checked"' : ''; ?> />
			</td></tr>
			<tr class="Divider"><th colspan="4"></th></tr>

<!-- StartList -->
			<tr>
				<td class="Bold Left"><input type="checkbox" value="IMG" name="IMG" id="IMG" <?php echo empty($_SESSION['SendOnlinePDFImages']) ? '' : 'checked'; ?>/>&nbsp;<?php print get_text('SendLogos','Tournament') ?></td>
				<td class="Bold Center"><input type="checkbox" value="ENS" name="ENS" id="ENS" />&nbsp;<?php print get_text('StartlistSession','Tournament') ?></td>
				<td class="Bold Center"><input type="checkbox" value="ENC" name="ENC" id="ENC" />&nbsp;<?php echo get_text('StartlistCountry','Tournament') ?></td>
				<td class="Bold Right"><input type="checkbox" value="ENA"  name="ENA" id="ENA" />&nbsp;<?php echo get_text('StartlistAlpha','Tournament') ?></td>
			</tr>
			<tr>
				<td class="Bold Center" colspan="2"><input type="checkbox" value="STE" name="STE" id="STE" />&nbsp;<?php print get_text('StatEvents','Tournament'); ?></td>
				<td class="Bold Center" colspan="2"><input type="checkbox" value="STC" name="STC" id="STC" />&nbsp;<?php echo get_text('StatCountries','Tournament'); ?></td>
			</tr>

			<tr>
				<th colspan="2" width="50%"><?php echo get_text('Individual'); ?></th>
				<th colspan="2" width="50%"><?php echo get_text('Team'); ?></th>
			</tr>

<!-- Classifica di classe -->
			<tr>
				<td colspan="2" class="Bold Center"><input type="checkbox" name="IC" id="IC" <?php echo empty($_POST['IC']) ? '' : 'checked="checked"'; ?>/>&nbsp;<?php print get_text('ResultClass','Tournament'); ?> - <?php print get_text('Individual'); ?></td>
				<td colspan="2" class="Bold Center"><input type="checkbox" name="TC" id="TC" <?php echo empty($_POST['TC']) ? '' : 'checked="checked"'; ?>/>&nbsp;<?php print get_text('ResultClass','Tournament'); ?> - <?php print get_text('Team'); ?></td>
			</tr>

<?php

$Scores=array(
	'QUAL' => '<input type="checkbox" name="ScoQual">&nbsp;'.get_text('ScorecardsQual','Tournament'),
	'ELIM' => '&nbsp;',
	'IND' => '&nbsp;',
	'TEAM' => '&nbsp;',
	);

if($outputIndAbs or $outputTeamAbs) {
	// divider
	echo '<tr class="Divider"><th colspan="4"></th></tr>';


	echo '<tr>';

	// Individual Qualifications
	echo '<td class="Bold Left">';
	if($outputIndAbs) {
		echo '<input type="checkbox" id="allResultIndAbs" onclick="setAllCheck(\'QualificationInd[]\',this.id);">&nbsp;'.get_text('ResultIndAbs','Tournament');
	} else {
		echo '&nbsp;';
	}
	echo '</td>';

	echo '<td class="Left">';
	echo $outputIndAbs ? $outputIndAbs : '&nbsp;';
	echo '</td>';

	// Team Qualifications
	echo '<td class="Bold Left">';
	echo $outputTeamAbs ? '<input type="checkbox" id="allResultTeamAbs" onclick="setAllCheck(\'QualificationTeam[]\',this.id);">&nbsp;'.get_text('ResultSqAbs','Tournament') : '&nbsp;';
	echo '</td>';

	echo '<td class="Left">';
	echo $outputTeamAbs ? $outputTeamAbs : '&nbsp;';
	echo '</td>';

	echo '</tr>';
}

// Eliminations (HF & 3D)
if($outputElim) {
	$Scores['ELIM']='<input type="checkbox" name="ScoElim">&nbsp;'.get_text('ScorecardsElim','Tournament');
	if($Elim==2) {
		$ElimCode='EL2';
		$outputElim='<input type="checkbox" name="EliminationStartlist[]" value="'.$ElimCode.'" id="' . $ElimCode . '"'
				.(empty($_POST['EliminationStartlist']) || !in_array($ElimCode, $_POST['EliminationStartlist']) ? '' : 'checked="checked"')
				. '>' . get_text('StartlistSession', 'Tournament') . ' '.get_text('Eliminations').' 2<br/>' . "\n"
				. $outputElim;
	}
	$ElimCode='EL1';
	$outputElim='<input type="checkbox" name="EliminationStartlist[]" value="'.$ElimCode.'" id="' . $ElimCode . '"'
			.(empty($_POST['EliminationStartlist']) || !in_array($ElimCode, $_POST['EliminationStartlist']) ? '' : 'checked="checked"')
			. '>' . get_text('StartlistSession', 'Tournament') . ' '.get_text('Eliminations'). ' 1<br/>' . "\n"
			. $outputElim;
	echo '<tr>';
	echo '<td class="Bold Left">';
	echo '<input type="checkbox" id="allResultElim" onclick="setAllCheck(\'EliminationInd[]\',this.id);">&nbsp;';
	echo get_text('Elimination');
	echo '</td>';
	echo '<td class="Left">';
	echo $outputElim;
	echo '</td>';
	echo '<td colspan="2" class="Left">&nbsp;</td>';
	echo '</tr>';
}

// Brackets
if($outputIndBra or $outputTeamBra) {
	echo '<tr>';

	// Individual brackets
	if($outputIndBra) {
		echo '<td class="Bold Left">'
			. '<input type="checkbox" id="allIndBra" onclick="setAllCheck(\'BracketsInd[]\',this.id);">&nbsp;'
			. get_text('Brackets') . ' - ' . get_text('Individual')
			. '</td>'
			. '<td class="Left">'
			. $outputIndBra
			. '</td>';
	} else {
		echo '<td colspan="2">&nbsp;</td>';
	}

	// Team brackets
	if($outputTeamBra) {
		echo '<td class="Bold Left">'
			. '<input type="checkbox" id="allTeamBra" onclick="setAllCheck(\'BracketsTeam[]\',this.id);">&nbsp;'
			. get_text('Brackets') . ' - ' . get_text('Team')
			. '</td>'
			. '<td class="Left">'
			. $outputTeamBra
			. '</td>';
	} else {
		echo '<td colspan="2">&nbsp;</td>';
	}

	echo '</tr>';
}

if($outputIndFin or $outputTeamFin) {
	// Final Rankings
	echo '<tr>';

	// Individual rank
	if($outputIndFin) {
		$Scores['IND']='<input type="checkbox" name="ScoInd">&nbsp;'.get_text('ScorecardsInd','Tournament');
		echo '<td class="Bold Left">'
			. '<input type="checkbox" id="allIndFin" onclick="setAllCheck(\'FinalInd[]\',this.id);">&nbsp;'
			. get_text('Rankings') . ' - ' . get_text('Individual')
			. '</td>'
			. '<td class="Left">'
			. $outputIndFin
			. '</td>';
	} else {
		echo '<td colspan="2">&nbsp;</td>';
	}

	// Team Rank
	if($outputTeamFin) {
		$Scores['TEAM']='<input type="checkbox" name="ScoTeam">&nbsp;'.get_text('ScorecardsTeams','Tournament');
		echo '<td class="Bold Left">'
			. '<input type="checkbox" id="allTeamFin" onclick="setAllCheck(\'FinalTeam[]\',this.id);">&nbsp;'
			. get_text('Rankings') . ' - ' . get_text('Team')
			. '</td>'
			. '<td class="Left">'
			. $outputTeamFin
			. '</td>';
	} else {
		echo '<td colspan="2">&nbsp;</td>';
	}

	echo '</tr>';
}

// Scorecards
/*
echo '<tr class="Divider"><th colspan="4"></th></tr>';
echo '<tr>';
echo '<td class="Bold Center">' . $Scores['QUAL'] . '</td>';
echo '<td class="Bold Center">' . $Scores['ELIM'] . '</td>';
echo '<td class="Bold Center">' . $Scores['IND'] . '</td>';
echo '<td class="Bold Center">' . $Scores['TEAM'] . '</td>';
echo '</tr>';

if($outputIndFin or $outputTeamFin) {
	// FOP!
	echo '<tr class="Divider"><th colspan="4"></th></tr>';
	echo '<tr>';
	echo '<td colspan="4" class="Bold Center"><input type="checkbox" name="FOP">&nbsp;'.get_text('FopSetup').'</td>';
	echo '</tr>';
}
*/
?>

<!-- medal -->
			<tr class="Divider"><th colspan="4"></th></tr>
			<tr>
				<td class="Bold Center"><input type="checkbox" name="MEDSTD" id="MEDSTD">&nbsp;<?php print get_text('MedalStanding'); ?></td>
				<td colspan="2" class="Bold Center"><input type="checkbox" name="BOOK" id="BOOK" onclick="SelectBook(this)">&nbsp;<?php print get_text('CompleteResultBook'); ?></td>
				<td class="Bold Center"><input type="checkbox" name="MEDLST" id="MEDLST">&nbsp;<?php print get_text('MedalList'); ?></td>
			</tr>


			<tr class="Divider"><th colspan="4"></th></tr>
			<tr><td class="Left" id="idStatus" colspan="4">&nbsp;</td></tr>
<?php
if (!IsBlocked(BIT_BLOCK_PUBBLICATION)) {
	echo '<tr><td colspan="4" class="Center"><input type="submit" id="btnOk" value="' . get_text('CmdOk') . '" onclick="document.getElementById(\'msg\').innerHTML=\'&nbsp;\'"></td></tr>';
}
?>
		</table>
	</div>
</div>
</form>
<?php
	include('Common/Templates/tail.php');


function getQueryResult($SQL, $BT='') {
	$ret=array();
	$q=safe_r_sql($SQL);
	while($r=safe_fetch($q)) $ret[]=$r;
	return $ret;
}

function getRankResult($type, $Event='') {
	$options=array('dist'=>0);
	if($Event) $options['events'] = $Event;
	if($type=='IC') {
		$rank=Obj_RankFactory::create('DivClass',$options);
		$rank->read();
		$rankData=$rank->getData();
	} elseif($type=='TC') {
		$rank=Obj_RankFactory::create('DivClassTeam',$options);
		$rank->read();
		$rankData=$rank->getData();
	} elseif($type=='IQ') {
		$rank=Obj_RankFactory::create('Abs',$options);
		$rank->read();
		$rankData=$rank->getData();
	} elseif($type=='TQ') {
		$rank=Obj_RankFactory::create('AbsTeam',$options);
		$rank->read();
		$rankData=$rank->getData();
	} elseif($type=='IF') {
		if($Event) $options['eventsR'] = $Event;
		$rank=Obj_RankFactory::create('FinalInd',$options);
		$rank->read();
		$rankData=$rank->getData();
	} elseif($type=='TF') {
		if($Event) $options['eventsR'] = $Event;
		$rank=Obj_RankFactory::create('FinalTeam',$options);
		$rank->read();
		$rankData=$rank->getData();
	}

	return $rankData;
}
?>