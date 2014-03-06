<?php // $Id$
define('CM_HOME', dirname(dirname(__FILE__)));
require_once(CM_HOME.'/etc/constants.inc.php');
require_once(CM_HOME.'/lib/expect.lib.php');
require_once(CM_HOME.'/lib/collect.lib.php');
require_once(CM_HOME.'/lib/manager.inc.php');

global $argv, $argc;
$streams = array();
$servers = array();
$type = array();
$pid = array();
$base = array();
$report = array('complete' => array(), 'partial' => array(), 'recovery' => array());

// V?rification de la ligne de commande
if ($argc != 1)
{
	usage($argv[0]);
	exit(1);
}

checkRemaining();
initDatabase();
//cleanTooOld();
//$previous = collectPreviousState();

$filters = parse_ini_file(CM_HOME.'/etc/filters.ini', true);
//$excludes = parse_ini_file(CM_HOME.'/etc/excludes.ini', true);
$excludes = explode("\n", file_get_contents(CM_HOME.'/etc/excludes.ini'));
$contacts = parse_ini_file(CM_HOME.'/etc/contacts.ini', true);
$servers = parse_ini_file(CM_HOME.'/etc/servers.ini', true);
$type = Array();

print "\$excludes = "; print_r( $excludes );

cleanTooOld();
$previous = collectPreviousState();

CollectAll($servers);

//var_dump($streams);
//die();

$active = 0;
$todo = 0;

file_put_contents(CM_HOME.'/etc/list', implode("\n", $streams));

foreach ($streams as $stream)
{
	if (strstr($stream, ';'))
	{
		$stream = explode(';', $stream);
		$flux = $stream[0];
		$base = $stream[1];
	}
	else
	{
		$flux = $stream;
		$base = $stream;
	}

	if (strstr($base, 'rtmp'))
	{
	        preg_match("@.+(rtmp.+) app.+@", $base, $match);
		$base = $match[1];
		$match=null;unset($match);
	}

	if (trim($base) == '') continue;
	if (@strstr($stream, '#')) continue; # ? des fois $stream est un array
	if (isExcluded($base))
	{
		print "[DBG] exclude $base\n";
		continue;
	}
	else
	{
		print "[DBG] test: $base\n";
	}
	
	foreach ($type as $t => $proto)
		$flux = str_replace("$t://", $proto, $flux);

	if ( DEBUG ) { print "[DBG]\tstart test (".date( "c" ).")\n"; }

	$todo++;

	$pid[$base] = null;
	$pid[$base] = pcntl_fork();
	if ($pid[$base] == -1)
	{
		if ( DEBUG ) { print "[DBG]\t\tpcntl_fork error\n"; }
		die('duplication impossible');
	}
	else if (!$pid[$base])
	{
		$active++;
		if (count($pid) >= MAX_PROC)
		{
			if ( DEBUG ) { print "[DBG]\t\tMAX_PROC reached!\n"; }
			while (!file_exists('/tmp/'.str_replace(array(':', '/'), '_', $base).'.code') && ($active > MAX_PROC))
			{
				pcntl_wait($status);
				sleep(SLEEP);
			}
		}
		$active--;
	} else {
		if (DEBUG) echo 'new streamer: '.PHP_BIN.' '.CM_HOME."/bin/streamer.php $flux $base\n";
		if ( !pcntl_exec(PHP_BIN, array(CM_HOME.'/bin/streamer.php', $flux, $base)) )
		{
			if (DEBUG) { print "[DBG]\t\tpcnt_exec failed!\n"; }
		}
		exit(0);
	}
	if ( DEBUG ) { print "[DBG]\tend test (".date( "c" ).")\n"; }
}

/*
while (count(glob("/tmp/*.code")) < $todo)
        sleep(1);
exec('killall -9 vlc tee &> /dev/null');
*/

if (DEBUG) { print "[DBG] \$pid = "; var_dump($pid); }

foreach ($pid as $base => $p)
{
	//if (pcntl_wifexited($p))
	//{
		if ( DEBUG ) { print "XXXXXXXXXXXX : call file_get_contents( $pouet );\n"; }
		$pouet = "/tmp/" . str_replace( array( ':' , '/' ), '_', $base ) . ".code";
		$code = file_get_contents( $pouet );
		if ( DEBUG ) { print "XXXXXXXXXXXX : returns \$code = `{$code}'\n"; }
		#$code = @file_get_contents('/tmp/'.str_replace(array(':', '/'), '_', $base).'.code'); #pcntl_wexitstatus;
		if (trim($code) == '')
		{
			if ( DEBUG ) { print "[dbg] {$base}:{$p} -> \$code est vide!\n"; }
			continue;
		}
		foreach ($filters as $index => $filter)
		{
			if ($filters[$index]['code'] == $code)
			{
				break;
			}
		}
		$report['complete'][$base] = sprintf($filters[$index]['message'], $base);
		if (!strstr($index, "_OK"))
		{
			if ( DEBUG ) print "[dbg] update \$report['detailed'][$index]\n";
			$report['detailed'][$index][] = $base;
		}

		if (!isset($previous[$base]))
		{
			if ( DEBUG ) print "[dbg] Bobone: init state of $base (N)\n"; #, 3, "/opt/Bobone/logs/debug.log" );
			initState($base, $index);
		}

		if (!strstr($index, "_OK") && strstr($previous[$base]->state, "_OK")) // $filters[$index]['type'] != 'success')
		{
			$report['partial'][$base] = $report['complete'][$base];
			if ( DEBUG ) print "[dbg] Bobone: update state of $base (P)\n"; #, 3, "/opt/Bobone/logs/debug.log" );
			updateState($base, $index);
		}
		elseif (isset($previous[$base]) && strstr($index, "_OK") && !strstr($previous[$base]->state, '_OK')) // != $index))
		{
			$report['recovery'][$base] = sprintf($filters[$index]['message'], $base); 
			if ( DEBUG ) print "[dbg] Bobone: update state of $base (R)\n"; #, 3, "/opt/Bobone/logs/debug.log" );
			updateState($base, $index);
		}
		if ( DEBUG ) { print "[dbg] out of foreach( \$pid ... ): next item\n"; }
}
if ( DEBUG ) { print "[dbg] out of foreach( \$pid ... )\n"; }

#if (DEBUG) var_dump($report);

if (DEBUG) { print "[DBG] \$contacts = " ; var_dump($contacts); }

if ( DEBUG ) { print "[DBG] \$report = "; print_r( $report ); }

$in_error = 0;

// Rapport horaire
if (date ( 'i' ) < 10)
{
	$message = "";
	if ( count( $report['detailed'] ) > 0 )
		foreach ($report['detailed'] as $index => $list)
		{
			$message .= "[ ".$filters[$index]['detail']." ]\n\n";
			$message .= implode("\n", $list);
			$message .= "\n\n\n";
			$in_error += count($list);
		}
	#else
	#	$message .= "detailed report is empty.";
	//$message = implode("\r\n", $report['complete']);
	foreach ($contacts as $email => $contact)
	{
		if ($contact['active'] || $contact['debug'])
			mail($email, sprintf('[ RAPPORT HORAIRE ] Il y a toujours %d point(s) en dysfonctionnement (sur %d en tout)', $in_error, count($report['complete'])), $message, "From: Bobone <bobone@yacast.fr>\r\n"); 
	}
}

if ( count($report['partial']) > 0 )
{
	$message = implode("\r\n", $report['partial']);
	foreach ($contacts as $email => $contact)
	{
		if ($contact['active'] || $contact['debug'])
			mail($email, sprintf('Il y a %d point(s) nouvellement en dysfonctionnement', count($report['partial'])), $message, "From: Bobone <bobone@yacast.fr>\r\n"); 
	}
}
$last = '';
$detailed = 0;

if ( DEBUG ) print "[DBG] (2) \$report = " ; print_r( $report );

if ( count( $report['detailed'] ) > 0 )
{
	foreach ($report['detailed'] as $url)
	{
		$detailed += count($url);
		$last .= implode("\r\n", $url)."\n";
	}
}
else
{
	$detailed += 0;
	#$last .= " *** detailed report is empty!?!... ***" ;
	echo " *** detailed report is empty!?!... ***\n" ;
}
file_put_contents(CM_HOME.'/etc/last', $last);

if (count($report['recovery']))
{
	$message = implode("\r\n", $report['recovery']);
	foreach ($contacts as $email => $contact)
	{
		if ($contact['active'] || $contact['debug'])
			mail($email, sprintf('Il y a %d point(s) a nouveau en fonctionnement', count($report['recovery'])), $message, "From: Bobone <bobone@yacast.fr>\r\n"); 
	}
}

$index = sprintf("Total:Total\nError:Error\nRecovery:Recovery\n");
$count = sprintf("Total:%0d\nError:%0d\nRecovery:%0d\n", count($report['complete']), $detailed, count($report['recovery']));
file_put_contents(CM_HOME.'/etc/index', $index);
file_put_contents(CM_HOME.'/etc/count', $count);
file_put_contents(CM_HOME.'/etc/total', count($report['complete']));
file_put_contents(CM_HOME.'/etc/error', $detailed);
file_put_contents(CM_HOME.'/etc/recovery', count($report['recovery']));

exit(0);

?>
