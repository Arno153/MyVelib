<?php


function InvalidCache()
{
	file_put_contents(dirname(__FILE__).'/../cache/invalidCache.cache', time()) ;
}

function isCacheValid($page)
{
	$debugCacheMgt = false;
	$noCacheMode = false;
	
	if( filemtime(dirname(__FILE__).'/../cache/invalidCache.cache') <= filemtime(dirname(__FILE__).'/../cache/'.$page.'.cache')	)
	{
		if($debugCacheMgt) error_log("Live Cache Mgt - Page: ".$page." is valid and will not be rebuilded");	
		if($noCacheMode)
			return False;
		else
			return True;
	}
	else
	{
		if($debugCacheMgt) error_log("Live Cache Mgt - Page: ".$page." is not valid anymore and will be rebuilded");	
		return False;
	}
}

function isCacheValid1H($page)
{
	$debugCacheMgt = false;
	$noCacheMode = false;
	
	if( time() <= filemtime(dirname(__FILE__).'/../cache/'.$page.'.cache')+3600	)
	{
		if($debugCacheMgt) error_log("1H Cache Mgt - Page: ".$page." is valid and will not be rebuilded");
		if($noCacheMode)
			return False;
		else
			return True;
	}
	else
	{	
		if($debugCacheMgt) error_log("1H Cache Mgt - Page: ".$page." is not valid anymore and will be rebuilded");
		return False;
	}	
}

function getPageFromCache($page)
{
	readfile(dirname(__FILE__).'/../cache/'.$page.'.cache');
	
}

function updatePageInCache($page, $pageContent)
{
	file_put_contents(dirname(__FILE__).'/../cache/'.$page.'.cache', $pageContent) ;
}


?>