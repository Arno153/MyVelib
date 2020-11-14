<?php
echo "purgeCacheOSM : Start<br>";
purgeCacheOSM();
echo "purgeCacheOSM : Done<br>";

function purgeCacheOSM()
{
	$folderName = './../tiles/'; 
	
	//contenu du dossier
	$files1 = scandir($folderName);
	
	//traitement du contenu du dossier
	foreach ($files1 as $key => $value)
	{
		if (!in_array($value,array(".","..","index.html"))) //éliminer certain fichiers/dossiers
		{
			if(is_dir($folderName.$value)) //c'est un dossier?
			{
				echo "--> purge du dossier ".$folderName.$value."<br>";
				array_map( 'unlink', array_filter((array) glob($folderName.$value."/*") ) );
			}
		}
	} 	
}	
?>