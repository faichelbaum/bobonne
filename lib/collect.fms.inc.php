<?php

global $type;
$type['fms'] = 'rtmp://';

function get_FMSVhosts($server)
{
	$vhosts = array();
	$output = `wget -O - -q "http://$server:1111/admin/getVHosts?auser=adminyac&apswd=yac,01admin" 2> /dev/null`;
	if (!trim($output)) return array();
	$vhosts_xml = new SimpleXMLElement($output);
	foreach ($vhosts_xml->data->children() as $vhost)
	{
		$vhost = (string) $vhost;
		if ($vhost == '_defaultVHost_') continue;
		$vhosts[] = $vhost;
	}
	return $vhosts;
}

function get_FMSApps($server, $vhost)
{
	if (!isset($apps["$vhost"])) $apps["$vhost"] = array();
	$output = `ssh $server 'wget -O - -q "http://$vhost:1111/admin/getApps?auser=adminyac&apswd=yac,01admin&verbose=true&force=true"' 2> /dev/null`;
	//$vhost = str_replace('-tlc', '', str_replace('-tlh2', '', $vhost));
	if (!trim($output)) return array();
	$apps_xml = simplexml_load_string($output);
	if (!isset($apps_xml->data->total_apps) || ($apps_xml->data->total_apps == 0)) return $apps;
	foreach ($apps_xml->data->children() as $key => $app)
	{
		if ($key == 'total_apps') continue;
		$app = (string) $app;
		switch ($app)
		{
			case 'france24_live':
				$apps[$vhost][] = 'france24_live/fr';		
				$apps[$vhost][] = 'france24_live/frda';		
				$apps[$vhost][] = 'france24_live/en';		
				break;
			case 'france24':
				$apps[$vhost][] = 'france24/fr';		
				$apps[$vhost][] = 'france24/frda';		
				$apps[$vhost][] = 'france24/en';		
				break;
			default:
				$apps[$vhost][] = $app;
				break;
		}
	}
	//echo "[ $server > $vhost ]\n";
	//var_dump($apps);
	return $apps;
}

// http://www.example.com:1111/admin/getLiveStreams?auser=username&apswd=password &appInst=name
function get_FMSStreams($server, $vhost, $app)
{
	global $streams;

	foreach ($app as $inst)
	{
		$output = `ssh $server 'wget -O - -q "http://$vhost:1111/admin/getLiveStreams?auser=adminyac&apswd=yac,01admin&appInst=$inst"' 2> /dev/null`;
		//$vhost = str_replace('-tlc', '', str_replace('-tlh2', '', $vhost));
		if (!trim($output)) continue;
		$streams_xml = simplexml_load_string($output);
		if (!isset($streams_xml->data) || (sizeof($streams_xml->data) == 0)) continue;
		foreach ($streams_xml->data->children() as $key => $stream)
			$streams[] = "fms://$vhost/ tcUrl=rtmp://$vhost/$inst/$stream app=$inst playpath=$stream live=true";
	}
}

function CollectFrom_fms($servers)
{
	global $streams;
	
	if (!sizeof($servers)) return ;
	
	foreach ($servers as $server)
	{
		if (is_array($server))
		{	
			CollectFrom_fms($server);
			continue;
		}
		$vhosts = get_FMSVhosts($server);
		foreach ($vhosts as $vhost)
		{
			$apps = get_FMSApps($server, $vhost);
			foreach ($apps as $app)
				get_FMSStreams($server, $vhost, $app);
		}
	}
}

//if (DEBUG) mail('francois@aichelbaum.com', 'pvr', implode("\r\n", $streams));

?>
