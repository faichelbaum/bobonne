<?php

global $type;
$type['icecast'] = 'http://';

function CollectFrom_icecast($servers)
{
	global $streams;
	
	if (!sizeof($servers)) return ;
	
	foreach ($servers as $server)
	{
		if (is_array($server))
		{	
			CollectFrom_icecast($server);
			continue;
		}

		$points = simplexml_load_file("http://admin:PASSWORD@".$server."/admin/stats.xml" );
		foreach ($points->source as $point)
		{
			$point = trim ( $point ['mount'] );
			if (strstr($point, '_dalle') || strstr($point, '-alt')) continue;
			if ((strlen ( $point ) > 1) && !in_array("icecast://".$server."/".substr($point, 1), $streams))
				$streams[] = "icecast://".$server."/" . substr ( $point, 1 );
		}
	}
}

//if (DEBUG) mail('francois@aichelbaum.com', 'icecast', implode("\r\n", $streams));

?>
