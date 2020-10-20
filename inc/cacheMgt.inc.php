<?php

// ---- cache Mgt -- BEGIN ---- //
function InvalidCache()
{
	file_put_contents(dirname(__FILE__).'/../cache/invalidCache.cache', time()) ;
}

function isCacheValid($page)
{
	$debugCacheMgt = false;
	$noCacheMode = false;
	isVelibAPIParserKO();	
	
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

function isCacheValidThisHour($page)
{
	$debugCacheMgt = false;
	$noCacheMode = false;
	
	if( date('H',time()) == date('H',filemtime(dirname(__FILE__).'/../cache/'.$page.'.cache')))
	{
		if($debugCacheMgt) error_log("This Hour Cache Mgt - Page: ".$page." is valid and will not be rebuilded");
		if($noCacheMode)
			return False;
		else
			return True;
	}
	else
	{	
		if($debugCacheMgt) error_log("This Hour Cache Mgt - Page: ".$page." is not valid anymore and will be rebuilded");
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
// ---- cache Mgt -- END ---- //

// ---- Alerte collecte KO -- BEGIN ---- //
function isVelibAPIParserKO()
{	
	//plus de 5 minutes sans refresh des data
	if( time() > filemtime(dirname(__FILE__).'/../cache/invalidCache.cache')+300	) 
	{
		error_log( date("Y-m-d H:i:s")." - pas de collecte depuis plus de 5 minutes");
		// dernier mail à plus de 1h 
		if( time() > filemtime(dirname(__FILE__).'/../cache/isVelibAPIParserKOMail.cache')+	60*60)
		{
			if( time() < filemtime(dirname(__FILE__).'/../cache/md5BlackListKO.cache')+100	) 
			{
				error_log( date("Y-m-d H:i:s")." - pas de collecte depuis plus de 5 minutes - rejet MD5 - envoi email");
				mail('webmaster@philibert.info', 'Alerte : Velib.philibert.info : VelibAPIParser est KO', 'le parser velib n\'a pas trourné depuis plus de 5 minutes - rejet MD5');
			}
			else
			{
				error_log( date("Y-m-d H:i:s")." - pas de collecte depuis plus de 5 minutes - envoi email");
				mail('webmaster@philibert.info', 'Alerte : Velib.philibert.info : VelibAPIParser est KO', 'le parser velib n\'a pas trourné depuis plus de 5 minutes');
			}
			file_put_contents(dirname(__FILE__).'/../cache/isVelibAPIParserKOMail.cache', time()) ;
		}
	}
	//else error_log( date("Y-m-d H:i:s")." - collecte il y a moins de 5 minutes");
		
}

function md5BlackListKO()
{
	file_put_contents(dirname(__FILE__).'/../cache/md5BlackListKO.cache', time()) ;
}
// ---- Alerte collecte KO -- END ---- //

// ---- Limitation du nombre de process velibAPIParser à 1 -- BEGIN ---- //
function velibAPIParser_SetLock()
{
	file_put_contents(dirname(__FILE__).'/../cache/velibAPIParser.lock', 'LOCKED') ;
}

function velibAPIParser_RemoveLock()
{
	file_put_contents(dirname(__FILE__).'/../cache/velibAPIParser.lock', 'UNLOCKED') ;
}

function velibAPIParser_IsLocked()
{
	if( file_get_contents(dirname(__FILE__).'/../cache/velibAPIParser.lock') == "LOCKED")
	{
		if( filemtime(dirname(__FILE__).'/../cache/velibAPIParser.lock') < (time() - 90 ))
			return False;
		else
			return True;
	}
	else
		return False;
}
// ---- Limitation du nombre de process velibAPIParser à 1 -- BEGIN ---- //


?>