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
	error_log ( 'CheckP2P: impossible d'ouvrir la base de donnees' );
	exit(1);
}
if ((@sqlite_query ( $sql, 'DELETE FROM state WHERE point=\''.$argv[1].'\';' )) === FALSE)
{
	error_log ( 'CheckP2P: impossible d'executer la requete SQLite (source)' );
	if (DEBUG) error_log ($sqliteerror);
	exit(1);
}
sqlite_close ( $sql );

echo 'Le point \''.$argv[1].'\' a été supprimé de la base.';

exit(0);

?>
