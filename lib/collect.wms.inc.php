<?php

global $type;
$type['wms'] = 'rtsp://';

function CollectFrom_wms($servers)
{
	global $streams;
	
	if (!sizeof($servers)) return ;
	
	foreach ($servers as $server)
	{
		if (is_array($server))
		{       
			CollectFrom_wms($server);
			continue;
		}
		if (($list = shell_exec( "ssh root@172.16.97.15 cat c:/nrpe/bin/$server.txt" )) === FALSE) {
			error_log ( "CheckP2P: can't execute requested command: 'ssh 172.16.97.15 cat c:/nrpe/bin/$server.txt'; skipping all for this server" );
			return ;
		}
		foreach ( explode ( "\n", $list ) as $point ) {
			$point = trim ( $point );
			if (! empty ( $point ) && !in_array("wms://".$server."/".$point, $streams))
				$streams[] = "wms://".$server."/".$point;
		}
	}
}

//if (DEBUG) mail('francois@aichelbaum.com', 'wms', implode("\r\n", $streams));

?>
