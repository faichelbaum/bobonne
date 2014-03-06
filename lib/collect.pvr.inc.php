<?php

global $type;
$type['pvr'] = 'http://';

function CollectFrom_pvr($servers)
{
	global $streams;
	
	if (!sizeof($servers)) return ;
	
	foreach ($servers as $server)
	{
		if (is_array($server))
		{	
			CollectFrom_pvr($server);
			continue;
		}

		$points = file_get_contents("http://".$server.":8080/stat.html" );
		preg_match_all('/<TR><TD><A HREF="\/(.*)">(.*)<\/A> <td align=right> /', $points, $matches);
		foreach ($matches[1] as $point)
		{
			$point = trim ( $point );
			if (strstr($point, "html")) continue;
			if ((strlen ( $point ) > 1) && !in_array("pvr://".$server."/".$point, $streams))
				$streams[] = "pvr://".$server."/" .  $point;
		}
	}
}

//if (DEBUG) mail('francois@aichelbaum.com', 'pvr', implode("\r\n", $streams));

?>
