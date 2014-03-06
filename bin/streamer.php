<?php

define('CM_HOME', dirname(dirname(__FILE__)));
require_once(CM_HOME.'/etc/constants.inc.php');
require_once(CM_HOME.'/lib/expect.lib.php');
require_once(CM_HOME.'/lib/streamer.inc.php');

global $argv, $argc;

// Vérification de la ligne de commande
if ($argc != 3)
{
	usage($argv[0]);
	exit(1);
}

//@unlink('/tmp/'.str_replace(array(':', '/'), '_', $basename).'.code');

// Initialisation des variables
$url = trim($argv[1]);
$basename = trim($argv[2]);
$filters = parse_ini_file(CM_HOME.'/etc/filters.ini', true);
$code = '';
$retry = 0;

$coin = "/tmp/" . str_replace( array( ':', '/' ), '_', $basename ) . ".code";
if ( DEBUG ) { print "[dbg] test result put in '{$coin}'\n"; }

#@unlink('/tmp/'.str_replace(array(':', '/'), '_', $basename).'.code');
@unlink( $coin );

if (strstr($basename, '.m3u8') || strstr($basename, 'isml'))
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $basename);
	curl_setopt($ch, CURLOPT_FILETIME, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	@curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	if (time() - $info['filetime'] > TS_AGE)
	{
#		file_put_contents('/tmp/'.str_replace(array(':', '/', ' '), '_', $basename).'.code', $filters['FILTER_TS_TOO_OLD']['code']);
		file_put_contents( $coin, $filters['FILTER_TS_TOO_OLD']['code'] );
#		if (DEBUG) echo "CODE: TS TOO OLD\n";
		exit(0);
	}
}
if (strstr($basename, '.m3u8'))
{
	$m3u8 = preg_replace('^http://(.*)yacast.net/^', '/mnt/isilon/wwwroot/', $basename);
	if (time() - filectime($m3u8) > TS_AGE)
	{
#		file_put_contents('/tmp/'.str_replace(array(':', '/', ' '), '_', $basename).'.code', $filters['FILTER_TS_TOO_OLD']['code']);
		file_put_contents( $coin, $filters['FILTER_TS_TOO_OLD']['code'] );
#		if (DEBUG) echo "CODE: TS TOO OLD\n";
		exit(0);
	}
}

// Exécution du test
if (DEBUG)
{
	echo "### ARG ###\n";
	var_dump($argc, $argv);
}
//$proc = new Expect($url, $basename, CM_HOME);
while (($retry < MAX_RETRY) && !strstr($code, 'OK'))
{
	$proc = new Expect($url, $basename, CM_HOME);
/*	if (DEBUG)
	{
		echo "@round #$retry\n";
		var_dump($retry, $url, $basename, $code);
	} */
	$code = $proc->execute($filters);
	$retry++;
	$proc->stop();
	$proc = null; unset($proc);
}
if (DEBUG) echo "CODE: $code ($retry)\n";

#file_put_contents('/tmp/'.str_replace(array(':', '/'), '_', $basename).'.code', $filters[$code]['code']);
file_put_contents( $coin, $filters[$code]['code'] );
// Sortie avec le code du test
//exit($filters[$code]['code']);
exit(0);

?>
