<?php

define('CM_HOME', dirname(dirname(__FILE__)));
require_once(CM_HOME.'/etc/constants.inc.php');
require_once(CM_HOME.'/lib/purge.inc.php');

global $argv, $argc;
$sqliteerror = null;

// Vérification de la ligne de commande
if ($argc != 1)
{
	echo "?";
	usage($argv[0]);
	exit(1);
}

if (!file_exists( SQLITE_DB ))
{
	echo "ERR: Can't find `" . SQLITE_DB . "'.\n";
	exit(1);
}
if (($sql = sqlite_open ( SQLITE_DB, 0666,$sqliteerror )) === FALSE) {
	echo "CheckP2P: impossible d'ouvrir la base de donnees\n";
	exit(1);
}
if ((@sqlite_query ( $sql, 'DELETE FROM state;' )) === FALSE)
{
	//error_log ( 'CheckP2P: impossible d\'executer la requete SQLite (source)' );
	echo "CheckP2P: impossible d\'executer la requete SQLite (DELETE FROM state WHERE 1;)\n";
	echo $sqliteerror . "\n";
	exit(1);
}
sqlite_close ( $sql );

echo "La base est purgee.\n";

exit(0);

?>
