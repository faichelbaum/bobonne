<?php

$pattern = "f(r?|rance)24"; /* /${pattern}/i matches f24 / fr24 / france24 */
#$pattern = "([^f][^2][^4]|[^f][^r][^a][^n][^c][^e][^2][^4])";

$str = array(
	"wms://prim24/bfmtv_partners" ,
	"fms://stream2.lcp-tlc.yacast.net/ tcUrl=rtmp://stream2.lcp-tlc.yacast.net/lcp_live/lcp24 app=lcp_live playpath=lcp24 live=true" ,
	"fms://stream2.france24-tlh2.yacast.net/ tcUrl=rtmp://stream2.france24-tlh2.yacast.net/france24_live/fr/f24_livefr app=france24_live/fr playpath=f24_livefr live=true" ,
	"dss://172.16.99.32/france24_fr_320x240_32.sdp" ,
	"wms://prim24/f24_liveen"
);

foreach ( $str as $s )
{
	$matches = NULL;
	#if ( preg_match_all( "/{$pattern}/i", $s, $matches ) )
	#if ( preg_match( "/{$pattern}/i", $s, $matches ) )
	if ( preg_match( "/{$pattern}/i", $s ) )
	{
		echo "EX: $s matches /{$pattern}/i\n";
		/*print_r( $matches );
		echo "\n";*/
	}
	else
		echo "KT: $s doesn't match /{$pattern}/i\n";
}
