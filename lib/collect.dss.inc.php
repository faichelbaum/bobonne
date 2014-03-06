<?php

global $type;
$type['dss'] = 'rtsp://';

function CollectFrom_dss($servers)
{
	global $streams;
	
	if (!sizeof($servers)) return ;
	
	foreach ($servers as $server)
	{
		if (is_array($server))
		{       
			CollectFrom_dss($server);
			continue;
		}
		if (($list = shell_exec("ssh root@".$server." '/usr/bin/find /usr/local/movies/ -name \"*.sdp\" | grep -v -F \"nrj/\" | sort | uniq'")) === FALSE) {
			error_log ( "CheckP2P: can't execute requested command: 'ssh ".$server." 'find /usr/bin/find /usr/local/movies/ -name \"*.sdp\" | grep -v -F \"nrj/\" | sort | uniq'; skipping all for this server" );
            break;
        }
		foreach ( explode ( "\n", $list ) as $point ) {
			$point = trim ( $point );
            $point = str_replace ( '/usr/local/movies/', '', $point );
            if (!empty($point) && (substr($point, -4) == '.sdp') && !in_array("dss://".$server."/".$point, $streams))
				$streams[] = "dss://".$server."/".$point;
        }
	}
}

//if (DEBUG) mail('francois@aichelbaum.com', 'dss', implode("\r\n", $streams));

?>
