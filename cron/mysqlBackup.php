<?php

include "./../inc/mysql.inc.php";
include "./../inc/cacheMgt.inc.php";
$debug = 1; //debug mode

echo "Début sauvegarde BDD<br>";
	velibAPIParser_SetDbBackupLock(); //disable updates during backup
	$sourceFile = backupDatabaseTables($debug, "velib_"); //backup
	velibAPIParser_RemoveDbBackupLock();//enable updates after backup
echo "Fin de la sauvegarde BDD<br><br>";

// compression de la sauvegarde
echo "Début de la compression de la sauvegarde BDD<br>";
	if($debug == 1)error_log( "memory used ".memory_get_usage()/1048576);
	$gz = gzcompressfile($sourceFile);
	if($gz!= false)
	{
		unlink ($sourceFile);
		echo "--> succès<br>";
	}
	if($debug == 1)error_log( "memory used ".memory_get_usage()/1048576);
echo "Fin de la compression de la sauvegarde BDD<br><br>";

if($gz != false)
{
	echo "debut de la purge des anciennes sauvegarde<br>";
		$i = purgeSQLBackup(7,30,$debug);
		echo "-->".$i." fichier(s) purgé(s)<br>";
	echo "fin de la purge des anciennes sauvegarde<br>";
}

/**
 * @function    backupDatabaseTables
 * @author      CodexWorld
 * @link        http://www.codexworld.com
 * @usage       Backup database tables and save in SQL file
 */
function backupDatabaseTables($debug, $tables = '*'){
    //connect & select the database
	$db = mysqlConnect();
	
	if ($db->connect_error) {
		die('Erreur de connexion (' . $mysqli->connect_errno . ') '
				. $mysqli->connect_error);
	}
	
	$return = "";
	
    //get all of the tables
    if($tables == '*'){
        $tables = array();
        $result = $db->query("show TABLES");
        while($row = $result->fetch_row()){
            $tables[] = $row[0];
        }
    }else{
        $result = $db->query("show TABLES like '$tables%' ");
		$tables = array();
        while($row = $result->fetch_row()){
            $tables[] = $row[0];
        }		
    }

	$fileTime = time();
	$fileName = './../backup_files/db-backup-'.$fileTime.'.sql';
	//open file
	$handle = fopen($fileName,'w+');	
	
    //loop through the tables
    foreach($tables as $table)
	{
		if($debug == 1)error_log( "backup table: ".$table);
		if($debug == 1)error_log( "memory used ".memory_get_usage()/1048576);
        $result = $db->query("SELECT * FROM $table");
        $numColumns = $result->field_count;

        $return .= "DROP TABLE IF EXISTS $table;";

        $result2 = $db->query("SHOW CREATE TABLE $table");
        $row2 = $result2->fetch_row(); 

        $return .= "\n\n".$row2[1].";\n\n";
		$i = 0;
		
		while($row = $result->fetch_row())
		{
			$return .= "INSERT INTO $table VALUES(";

			for($j=0; $j < $numColumns; $j++){
				$row[$j] = addslashes($row[$j]);
				
				$row[$j] = preg_replace("/\n/","\\n",$row[$j]);

				//if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
				if (isset($row[$j]) && $row[$j] != "") { $return .= '"'.$row[$j].'"' ; } else { $return .= 'NULL'; }
				
				if ($j < ($numColumns-1)) { $return.= ','; }
			}
			$return .= ");\n";
			$i=$i+1;
			
			if($i==25000) //ecriture fichier toute les 100k lignes pour limiter la conso mémoire
			{
				//write to file
				if($debug == 1)error_log( "memory used ".memory_get_usage()/1048576);
				fwrite($handle,$return);
				if($debug == 1)error_log( "table: ".$table." partially saved to file ");
				if($debug == 1)error_log( "memory used ".memory_get_usage()/1048576);		
				$return = "";
				$i=0;				
			}		

		}

        $return .= "\n\n\n";
		
		//write to file
		fwrite($handle,$return);		
		$return = "";
		
		// echo "memory used ".memory_get_usage()."<br>";
		echo "--> table: ".$table." exportée<br>";		
    }
	
	//close file
	fclose($handle);
	//close db connection
	mysqlClose($db);
	
	if($debug == 1)error_log("max memory used: ".memory_get_peak_usage()/1048576);
	return $fileName;
}

function purgeSQLBackup($delaiPurge, $delaiPurgeTotale, $debug)
{
	$nbFichierPurges = 0;
	$folderName = './../backup_files/';
	date_default_timezone_set("Europe/Paris");
	setlocale(LC_TIME, 'fr_FR');
	
	//contenu du dossier
	$files1 = scandir($folderName);
	
	//traitement du contenu du dossier
	foreach ($files1 as $key => $value)
	{
		if (!in_array($value,array(".","..","index.html"))) //éliminer certain fichiers/dossiers
		{
			$comment='';
			$datefichier = filemtime ( $folderName.'/'.$value );
			$interval = date_diff(new DateTime(date("Y-m-d H:i:s",$datefichier)), new DateTime("now") );	
			$nbJourFichier = intval($interval->format('%a'));
			$jourFichier =  date("w",$datefichier);	
			
			if($nbJourFichier > $delaiPurgeTotale)
			{
				$comment = " > $delaiPurgeTotale j : purge inconditionnelle";
				unlink ($folderName.$value);
				$nbFichierPurges = $nbFichierPurges +1;
			}
			else if($nbJourFichier > $delaiPurge)
			{
				$comment = " > $delaiPurge j: purge sauf dimanche :";
				$comment = $comment." date(w,datefichier): ".$jourFichier.": ";
				if($jourFichier!=0)
				{
					$comment = $comment."purge!";
					unlink ($folderName.$value);
					$nbFichierPurges = $nbFichierPurges +1;
				}
				else $comment = $comment."On garde !";
			}
			else $comment = $comment." <= $delaiPurge j: on garde!";
			
			if($debug == 1)
				error_log(
					$value
					." : "
					.date ("F d Y H:i:s",$datefichier)
					." : "
					.$nbJourFichier
					." : "
					.$comment
						  );
		}
	} 

	return $nbFichierPurges;
}

function gzcompressfile($source,$level=false){
    $dest=$source.'.gz';
    $mode='wb'.$level;
    $error=false;
    if($fp_out=gzopen($dest,$mode)){
        if($fp_in=fopen($source,'rb')){
            while(!feof($fp_in))
                gzwrite($fp_out,fread($fp_in,1024*512));
            fclose($fp_in);
            }
          else $error=true;
        gzclose($fp_out);
        }
      else $error=true;
    if($error) return false;
      else return $dest;
    } 
