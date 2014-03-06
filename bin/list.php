#!/usr/bin/php
<?php

define('CM_HOME', dirname(dirname(__FILE__)));
require_once(CM_HOME.'/etc/constants.inc.php');
require_once(CM_HOME.'/lib/purge.inc.php');

global $argv, $argc;
$sqliteerror = null;

// Vérification de la ligne de commande
if ($argc != 1)
{
	usage($argv[0]);
	exit(1);
}

if (!file_exists( SQLITE_DB ))
	exit(1);
if (($sql = sqlite_open ( SQLITE_DB, 0666,$sqliteerror )) === FALSE) {
	print "CheckP2P: impossible d'ouvrir la base de donnees " . SQLITE_DB . "!!!\n";
	if (DEBUG) print "[DBG] {$sqliteerror}\n";
	exit(1);
}

$r = sqlite_query ( $sql, 'SELECT * FROM state WHERE 1;', SQLITE_ASSOC, $sqliteerror );
if ( !$r )
{
	print "CheckP2P: impossible d'executer la requete SQLite (source)\n";
	if (DEBUG) print "[DBG] {$sqliteerror}\n";
	exit(1);
}
print_r( sqlite_fetch_all( $r ) );
sqlite_close ( $sql );


exit(0);

?>
