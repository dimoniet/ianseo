<?php
	require_once(dirname(dirname(__FILE__)) . '/config.php');
	CheckTourSession(true);
	require_once('Common/Fun_FormatText.inc.php');
	require_once('Common/Fun_Various.inc.php');
	require_once('Common/Fun_Sessions.inc.php');

	$session=(isset($_REQUEST['session']) ? $_REQUEST['session'] : null);
	$filter=(isset($_REQUEST['filter']) ? $_REQUEST['filter'] : null);
	$isEvent=(isset($_REQUEST['isEvent']) && $_REQUEST['isEvent']==1 ? $_REQUEST['isEvent'] : 0);
	$delSession=(isset($_REQUEST['delSession']) && $_REQUEST['delSession']==1 ? $_REQUEST['delSession'] : 0);

// sessioni
	$comboSession
		= '<select name="session" id="session">' . "\n"
		. '<option value="0">' . get_text('AllSessions','Tournament') . '</option>' . "\n";

	$q="SELECT * FROM Session WHERE SesTournament=". StrSafe_DB($_SESSION['TourId']) . " AND SesType='Q' ORDER BY SesOrder ASC ";
	$r=safe_r_sql($q);

	$sessions=GetSessions('Q');

	foreach($sessions as $s)
	{
		$comboSession.='<option value="' . $s->SesOrder. '"' . (!is_null($session) && $s->SesOrder==$session ? ' selected' : '') . '>' . $s->SesOrder .': ' . $s->SesName . '</option>' . "\n";
	}

	$comboSession.='</select>' . "\n";

	$msg='';

	if (isset($_REQUEST['command']) && $_REQUEST['command']=='OK')
	{
		if (!IsBlocked(BIT_BLOCK_PARTICIPANT))
		{
			$query="";

			if ($filter!='')
			{
				if ($isEvent==0)
				{
			// query per cancellare i bersagli considerando il filtro un NON evento
					$query
						= "UPDATE "
							. "Entries "
							. "INNER JOIN "
								. "Qualifications "
							. "ON EnId=QuId "
						. "SET "
							. "Qualifications.QuTargetNo='', QuBacknoPrinted=0 ";

						if ($delSession==1)
						{
							$query.=
								",Qualifications.QuSession=0 ";
						}

					$query
						.="WHERE "
							. "Entries.EnTournament=" . StrSafe_DB($_SESSION['TourId']) . " "
							. "AND CONCAT(Entries.EnDivision,Entries.EnClass) LIKE " . StrSafe_DB($filter) . " ";
					if ($session!=0)
					{
						$query
							.=" AND Qualifications.QuSession=" . StrSafe_DB($session) . " ";
					}
				}
				else
				{
			/*
			 *  query per cancellare i bersagli considerando il filtro un evento.
			 *  Qui la query va a toccare solo le righe che rispettano il filtro (e la sessione)
			 *  purchè il flag di partecipazione alle finali ind sia a 1.
			 *  Se una persona rispetta il filtro ma non partecipa alle finali ind, il bersaglio NON
			 *  viene toccato
			 */
					$query
						= "UPDATE "
							. "Entries "
							. "INNER JOIN "
								. "Qualifications "
							. "ON EnId=QuId AND EnIndFEvent=1 "
							. "INNER JOIN "
								. "EventClass "
							. "ON EnDivision=EcDivision AND EnClass=EcClass AND EnTournament=EcTournament AND EcTeamEvent=0 "
						. "SET "
							. "Qualifications.QuTargetNo='', QuBacknoPrinted=0 ";

						if ($delSession==1)
						{
							$query.=
								",Qualifications.QuSession=0 ";
						}

					$query
						.="WHERE "
							. "Entries.EnTournament=" . StrSafe_DB($_SESSION['TourId']) . " "
							. "AND EcCode LIKE " . StrSafe_DB($filter) . " ";

					if ($session!=0)
					{
						$query
							.=" AND Qualifications.QuSession=" . StrSafe_DB($session) . " ";
					}

				}

				$rs=safe_w_SQL($query);

				if ($rs)
				{
					if (safe_w_affected_rows()>0)
						$msg=get_text('TargetDeleted');
					else
						$msg=get_text('NoTargetFound');
				}
				else
				{
					$msg=get_text('Error');
				}
			}
			else
			{
				$msg=get_text('Error');
			}
		}
		else
		{
			$msg=get_text('Error');
		}
	}

	$JS_SCRIPT=array(
		phpVars2js(array(
			'StrAreYouSure' => get_text('MsgAreYouSure')
		)),
		'<script type="text/javascript" src="'.$CFG->ROOT_DIR.'Partecipants/Fun_DeleteTarget.js"></script>'
	);

	include('Common/Templates/head.php');
?>

<form id="frm" method="post" action="<?php print $_SERVER['PHP_SELF'];?>">
	<table class="Tabella">
		<tr><th class="Title"><?php print get_text('MenuLM_DeleteTarget');?></th></tr>
		<tr class="Divider"><TD></TD></tr>

		<tr>
			<td class="Center">
				<?php print get_text('Session');?>: <?php print $comboSession;?>
				&nbsp;&nbsp;
				<?php print get_text('FilterOnDivCl','Tournament'); ?>: <input type="text" name="filter" id="filter" size="5" maxlength="4" value="<?php print (!is_null($filter) ? $filter : '');?>" />
				&nbsp;&nbsp;
				<input type="checkbox" name="isEvent" id="isEvent" value="1" <?php print ($isEvent==1 ? 'checked="yes"' : '');?>/>&nbsp;<?php print get_text('Event');?>
				&nbsp;&nbsp;
				<input type="checkbox" name="delSession" id="delSession" value="1" <?php print ($delSession==1 ? 'checked="yes"' : '');?>/>&nbsp;<?php print get_text('DeleteSession');?>
			</td>
		</tr>
		<tr>
			<td class="Center">
				<input type="hidden" name="command" value="OK"/>
				<input type="button" id="btnOk" value="<?php print get_text('CmdOk');?>"  onClick="doConfirm();"/>
			</td>
		</tr>
		<?php if ($msg!='') { ?>
			<tr class="Divider"><TD></TD></tr>
			<tr><td><?php print $msg;?></td></tr>
		<?php }?>
	</table>
</form>

<?php include('Common/Templates/tail.php');?>
