<?php

global $type;
$type['iphone'] = 'http://';

function CollectFrom_iphone($servers)
{
	global $streams;
	
	if (!sizeof($servers)) return ;
	
	foreach ($servers as $server)
	{
		if (is_array($server))
		{       
			CollectFrom_iphone($server);
			continue;
		}
		$list = explode("\n", `wget -q -O - http://$server/iphone/iphone.html | grep -v '#' | grep video | grep http: | cut -d'"' -f2`);
		foreach ($list as $m3u8)
		{
			if (!$m3u8) continue;
			$flux = trim(`wget -q -O - $m3u8 | tail -1`);
			if ($flux[0] == '#') continue;
			if ($flux == '') continue;
			if (($flux[0] == '/') || ($flux[0] == '.'))
				$flux = "iphone://http5.iphone.yacast.net/" . $flux;
			$flux = str_replace(array('/./', '//'), '/', $flux);
			$streams[] = $m3u8.';'.str_replace('http://', 'iphone://', $flux);
		}
	}
}

//if (DEBUG) mail('francois@aichelbaum.com', 'iphone', implode("\r\n", $streams));

?>
