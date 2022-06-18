<?php
include "./../inc/cacheMgt.inc.php";
include "./../inc/config.inc.php";
$debug = 1; //debug mode

$backupStep=0;
$Tables = 'velib_activ_station_stat velib_api_sanitize  velib_network velib_station velib_station_status '; 
$HugeTable  = 'velib_station_min_velib';
$Alltables =  $Tables." ".$HugeTable;

$rep = getcwd().'/../backup_files/'; //Répertoire où sauvegarder le dump de la base de données
$fichierDb = $db."-".date("d-m-Y").".sql";
$mysqlDumpconf=getcwd()."/../inc/.sqlpwd";

if(isset($_GET["step"]))
{
	$backupStep = $_GET["step"];
	//error_log("set".$_GET["step"]);
}
else $backupStep = 1;
//error_log("used".$backupStep);


switch ($backupStep) 
{
    case 1:
		velibAPIParser_SetDbBackupLock(); //disable updates during backup
		
		error_log( date("Y-m-d H:i:s")."Begin backup DB : $db in $rep/$fichierDb"  );
		error_log( date("Y-m-d H:i:s")."dump structure : Begin" );
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-data ".$db." ".$Alltables."  > ".$rep.$fichierDb." 2>".$rep.$fichierDb.".err");
		error_log( date("Y-m-d H:i:s")."dump tructure : END" );
        break;		
    case 2:
		error_log( date("Y-m-d H:i:s")."dump Tables data : Begin" );
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-create-info ".$db." ".$Tables."  >> ".$rep.$fichierDb." 2>>".$rep.$fichierDb.".err");
		error_log( date("Y-m-d H:i:s")."dump Tables data : END" );
        break;
    case 3:	
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : Begin" );
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : Part 1: 2018" );
			$Where = ' --where=" stationStatDate < \'2019-01-01\' "';
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-create-info ".$db." ".$HugeTable." ".$Where."  >> ".$rep.$fichierDb." 2>>".$rep.$fichierDb.".err");
        break;		
    case 4:			
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : Part 2: 2019" );
			$Where = ' --where=" stationStatDate < \'2020-01-01\' and stationStatDate >= \'2019-01-01\' "';
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-create-info ".$db." ".$HugeTable." ".$Where."  >> ".$rep.$fichierDb." 2>>".$rep.$fichierDb.".err");		
        break;		
    case 5:
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : Part 3: 2020" );
			$Where = ' --where=" stationStatDate < \'2021-01-01\' and stationStatDate >= \'2020-01-01\' "';
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-create-info ".$db." ".$HugeTable." ".$Where."  >> ".$rep.$fichierDb." 2>>".$rep.$fichierDb.".err");	
        break;			
    case 6:
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : Part 4: 2021" );
			$Where = ' --where=" stationStatDate < \'2022-01-01\' and stationStatDate >= \'2021-01-01\' "';
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-create-info ".$db." ".$HugeTable." ".$Where."  >> ".$rep.$fichierDb." 2>>".$rep.$fichierDb.".err");
		break;		
    case 7:
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : Part 5: 2022" );
			$Where = ' --where=" stationStatDate < \'2023-01-01\' and stationStatDate >= \'2022-01-01\' "';
			exec("mysqldump --defaults-extra-file=".$mysqlDumpconf." --no-create-info ".$db." ".$HugeTable." ".$Where."  >> ".$rep.$fichierDb." 2>>".$rep.$fichierDb.".err");	
		error_log( date("Y-m-d H:i:s")."dump data from velib_station_min_velib  : END" );
		error_log( date("Y-m-d H:i:s")."backup of $db in $rep/$fichierDb"  );   		
		break;			
	case 8:	
		error_log( date("Y-m-d H:i:s")."Begin gz compress");
		$gz = gzcompressfile($rep.$fichierDb);
		if($gz!= false)
		{
			unlink ($rep.$fichierDb);
			error_log( "debut de la purge des anciennes sauvegarde<br>");
			$i = purgeSQLBackup(7,30,$debug);
			error_log("-->".$i." fichier(s) purgé(s)<br>");
			


		}
		error_log( date("Y-m-d H:i:s")."End backup DB : $db in $gz"  );
		break;
}

$NextBackupStep = $backupStep +1;
//error_log("next: ".$NextBackupStep);

if($backupStep >= 8)
{
	echo "end";
	velibAPIParser_RemoveDbBackupLock();//enable updates after backup

}
else
{
	//error_log("header content:"."Location: mysqldump.php?step=$NextBackupStep");
	header("Location: mysqlBackup.php?step=$NextBackupStep");
	die();
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

?>

