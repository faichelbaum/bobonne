<?php

$streams = array();

function CollectAll($servers) {
	global $streams, $previous;

	foreach ($servers as $support => $server)
	{
		echo date('c')." collect $support\n";
		if (isset($server['server']) && is_array($server['server']))
			$server = $server['server'];
		require_once(CM_HOME."/lib/collect.$support.inc.php");
		$function = "CollectFrom_".$support;
		$function($server);
		echo date('c')." end $support\n";
	}

	if ( count( $previous ) > 0 )
		foreach ($previous as $base => $null)
		{
			if (strstr($base, 'iphone')) continue;
			if (!in_array($base, $streams))
				$streams[] = $base;
		}

}

?>
