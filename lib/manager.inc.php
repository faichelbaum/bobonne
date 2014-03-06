<?php

declare(ticks = 1);

function usage($cmd)
{
	echo "$cmd\n";
}

function initDatabase() {
	if (file_exists ( CM_HOME.'/etc/check.db' ))
		return TRUE;
	if (($sql = sqlite_open ( CM_HOME.'/etc/check.db', 0666,$sqliteerror )) === FALSE) {
		error_log ( "CheckP2P: impossible d'ouvrir la base de donnees", 3, "/opt/Bobone/log/debug.log" );
		print "CheckP2P: impossible d'ouvrir la base de donnees\n"; #, 3, "/opt/Bobone/log/debug.log" );
		//@unlink ( SQLITE_DB );
	}
	$source = file_get_contents ( CM_HOME.'/etc/source.sql' );
	if ((sqlite_query ( $sql, $source )) === FALSE)
	{
		error_log ( "CheckP2P: impossible d'executer la requete SQLite (source)", 3, "/opt/Bobone/log/debug.log" );
		print "CheckP2P: impossible d'executer la requete SQLite (source)\n"; #, 3, "/opt/Bobone/log/debug.log" );
		if (DEBUG) error_log ($sqliteerror);
		if (DEBUG) print "[dbg] \$sqliteerror = $sqliteerror\n ";
	}
	sqlite_close ( $sql );
	return TRUE;
}

function checkRemaining() {
	$return = null;
	passthru ( "/bin/kill -9 `pidof vlc` &> /dev/null", $return );
	return $return;
}

function collectPreviousState() {
	if (($sql = sqlite_open ( CM_HOME.'/etc/check.db', 0666,$sqliteerror )) === FALSE) {
		error_log ( "CheckP2P: impossible d'ouvrir la base de donnees" );
		//@unlink ( SQLITE_DB );
	}

	if (($result = sqlite_query ( $sql, "SELECT * FROM state;" )) === FALSE) 
	{
		error_log ( "CheckP2P: impossible d'executer la requete SQLite" );
		return FALSE;
	}
	while ( ($point = sqlite_fetch_object ( $result )) !== FALSE )
		$points[$point->point] = $point;

	sqlite_close ( $sql );
	
	return $points;
}

function initState($base, $state)
{
        if (($sql = sqlite_open ( CM_HOME.'/etc/check.db', 0666,$sqliteerror )) === FALSE) {
		error_log ( "CheckP2P: impossible d'ouvrir la base de donnees", 3, "/opt/Bobone/log/debug.log" );
		print "CheckP2P: impossible d'ouvrir la base de donnees\n"; #, 3, "/opt/Bobone/log/debug.log" );
		//@unlink ( SQLITE_DB );
	}
	if (sqlite_query ( $sql, "INSERT INTO state (state, epoc, point) VALUES ('$state', '".time()."', '$base');" ) === FALSE)
	{
		error_log ( "CheckP2P: impossible d'executer la requete SQLite", 3, "/opt/Bobone/log/debug.log" );
		print "CheckP2P: impossible d'executer la requete SQLite\n"; #, 3, "/opt/Bobone/log/debug.log" );
		return FALSE;
	}
	sqlite_close($sql);
	return TRUE;
}

function updateState($base, $state)
{
	if (($sql = sqlite_open ( CM_HOME.'/etc/check.db', 0666,$sqliteerror )) === FALSE) {
		error_log ( "CheckP2P: impossible d'ouvrir la base de donnees", 3, "/opt/Bobone/log/debug.log" );
		print "CheckP2P: impossible d'ouvrir la base de donnees\n";
		//@unlink ( SQLITE_DB );
	}
	if (sqlite_query ( $sql, "UPDATE state SET state = '$state', epoc='".time()."' WHERE point = '$base';" ) === FALSE) 
	{
		error_log ( "CheckP2P: impossible d'executer la requete SQLite", 3, "/opt/Bobone/log/debug.log" );
		print "CheckP2P: impossible d'executer la requete SQLite\n";
		return FALSE;
	}
	sqlite_close($sql);
	return TRUE;
}

function cleanTooOld()
{
	//global $filters;
	if (($sql = sqlite_open ( CM_HOME.'/etc/check.db', 0666,$sqliteerror )) === FALSE) {
		error_log ( "CheckP2P: impossible d'ouvrir la base de donnees" );
		//@unlink ( SQLITE_DB );
	}
	/*
	foreach ($filters as $name => $filter)
	{
		if (!strstr($name, '_OK')) continue;
	*/
		$limit = time () - 24 * 60 * 60;
		if ((sqlite_query ( $sql, "DELETE FROM state WHERE state NOT LIKE '%_OK' AND epoc <= '$limit';" )) === FALSE)
		{
			error_log ( "CheckP2P: impossible d'executer la requete SQLite (suppression des anciens points)" );
			//return FALSE;
		}
	//}
	return TRUE;
}

function isExcluded($point)
{
	global $excludes;
	
	$match = 0;
	foreach ($excludes as $pattern)
	{
		$neg = FALSE;
		if ( $pattern[0] == "#" )
			continue;
		if ( $pattern[0] == "!" )
		{
			$neg = TRUE;
			$pattern = substr( $pattern, 1 );
		}
		//elseif ( $pattern[0] == " " )
		//	$neg = FALSE;
		//$pattern = substr( $pattern, 1 );
		$pattern = trim($pattern);
		if ($pattern == '') continue;
		$m = preg_match("%$pattern%i", $point);
		if ( $neg )
			$m = !$m;
		$match |= $m;
	}
	//if (DEBUG) error_log("Bobone: $point => $match");
	if (DEBUG) print "[dbg] isExcluded - Bobone: $point => $match\n";
	
	return $match;
}

?>
