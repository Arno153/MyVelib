<html>
<head>
	<meta name="robots" content="noindex, follow">
</head>
<body>
<?php
// velib data collection -- cron once per min
date_default_timezone_set("Europe/Paris");
include "./../inc/cacheMgt.inc.php";
include "./../inc/mysql.inc.php";

$debug = true;
$debugURL = false;
$debugVerbose = true;
$debugVelibRawData= false;
$velibExit = 0;
$EvelibExit = 0;
$velibReturn = 0;
$EvelibReturn = 0;

echo date(DATE_RFC2822);
	echo "<br>";
	
error_log(date("Y-m-d H:i:s")." - velibAPIParser - start");

if(velibAPIParser_Locked_by_DbBackup())
{
	echo "DB backup running - stop !!!";
	error_log(date("Y-m-d H:i:s")." - velibAPIParser - DB backup running  - process stoped");
	exit;
}

	
if(velibAPIParser_IsLocked())
{
	echo "No parallel run - stop !!!";
	error_log(date("Y-m-d H:i:s")." - velibAPIParser - No parallel run - process stoped");
	exit;
}
else 
	velibAPIParser_SetLock();

if($debugURL)
{
	error_log(date("Y-m-d H:i:s")." - velibAPIParser - Get URL begin");
}

// velib data collection
try
{	
	//$SomeVelibRawData = file_get_contents('https://www.velib-metropole.fr/webapi/map/details?gpsTopLatitude=49.007249184314254&gpsTopLongitude=2.92510986328125&gpsBotLatitude=48.75890477584505&gpsBotLongitude=1.7832183837890627&zoomLevel=11');

	//	
		// From URL to get webpage contents. 
		$url = "https://www.velib-metropole.fr/webapi/map/details?gpsTopLatitude=49.007249184314254&gpsTopLongitude=2.92510986328125&gpsBotLatitude=48.75890477584505&gpsBotLongitude=1.7832183837890627&zoomLevel=11"; 
		  
		// Initialize a CURL session. 
		$ch = curl_init();  
		  
		// Return Page contents. 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		  
		//grab URL and pass it to the variable. 
		curl_setopt($ch, CURLOPT_URL, $url); 
		  
		$SomeVelibRawData = curl_exec($ch); 
	
	//
	
	if($SomeVelibRawData==false)
	{
		echo "ko"; 
		velibAPIParser_RemoveLock();
		exit;
	}
}catch (Exception $e) {
		echo "ko: url is not reachable";
		velibAPIParser_RemoveLock();
		exit;
}

if($debugURL)
{
	error_log(date("Y-m-d H:i:s")." - velibAPIParser - Get URL End");
}

//DB connect
$link = mysqlConnect();
if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
	velibAPIParser_RemoveLock();
    exit;
}

if($debugVerbose)
{
	echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
	echo "<br>";
	echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;
	echo "<br>";
}

// ---- nettoyage des données oscilatoire
$jsonMd5 = md5($SomeVelibRawData); //on calc le md5 du flux courrant
//on purge des data de pplus de 12h dans le log des MD5 des flux
$r = " Delete from `velib_api_sanitize` WHERE `JsonDate` <= DATE_ADD(NOW(), INTERVAL - 12 HOUR)"; 
if($debugVerbose){ 	echo $r; echo "<br>";}							
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
}	

// on ajoute le md5 du flux courant dans la table de log des MD5 des flux
$r = "
		INSERT INTO `velib_api_sanitize` ( `JsonDate`, `JsonMD5`) 
		VALUES ( sysdate(), '$jsonMd5' )
	";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
}


// si le md5 du flux courant est sorti plus de 25 fois danns les dernières 12h on le blacklist
$r = "
		SELECT 
			`JsonMD5`,
			count(`JsonMD5`) c
		FROM `velib_api_sanitize`
		group by `JsonMD5`
		having count(`JsonMD5`)> 25
	";
$md5BlackListedArray = array();
//on recupère les valeurs black listées
if($result = mysqli_query($link, $r)) 
{
	//on construit un tableau des valeurs blacklistées
	if (mysqli_num_rows($result)>0)
	{						
		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			$md5BlackListedArray[] = $row["JsonMD5"];
		}
	}
}
else						
{
	printf("Errormessage: %s\n", mysqli_error($link));
}

//si le md5 du flux courant est blacklisté on arrette recommance 1 fois puis on arrète là!
if(in_array($jsonMd5, $md5BlackListedArray, false))
{
	if($debugVerbose)
	{
		var_dump($md5BlackListedArray);
	}
	echo "<br> MD5 = ".$jsonMd5." On ignore automatiquement ce json suivant son MD5 <br>";
	echo "<br> retry in 10 sec <br>";
	sleep(10);
	
	// velib data collection (bis)
	try
	{	$SomeVelibRawData = file_get_contents('https://www.velib-metropole.fr/webapi/map/details?gpsTopLatitude=49.007249184314254&gpsTopLongitude=2.92510986328125&gpsBotLatitude=48.75890477584505&gpsBotLongitude=1.7832183837890627&zoomLevel=11');
		if($SomeVelibRawData==false)
		{
			echo "ko"; 
			velibAPIParser_RemoveLock();
			exit;
		}
	}catch (Exception $e) {
			echo "ko: url is not reachable";
			velibAPIParser_RemoveLock();
			exit;
	}
	$jsonMd5 = md5($SomeVelibRawData); //on calc le md5 du flux courrant (bis)
	// on ajoute le md5 du flux courant dans la table de log des MD5 des flux
	$r = "
			INSERT INTO `velib_api_sanitize` ( `JsonDate`, `JsonMD5`) 
			VALUES ( sysdate(), '$jsonMd5' )
		";
	if(!mysqli_query($link, $r))
	{
		printf("Errormessage: %s\n", mysqli_error($link));
	}	
	
	if(in_array($jsonMd5, $md5BlackListedArray, false))
	{
		if($debugVerbose)
		{
			var_dump($md5BlackListedArray);
		}
		echo "<br> MD5 = ".$jsonMd5." On ignore automatiquement ce json suivant son MD5 <br>";
		echo "<br> KO";
		md5BlackListKO();
		velibAPIParser_RemoveLock();
		exit;
	}
		
	
}	
//si le md5 du flux courant n'est pas black-listé par le log on poursuit avec la maj des données
// ---- nettoyage des données oscilatoire
if($debug)
{
error_log( date("Y-m-d H:i:s")." - Collecte des données Velib");
}

if($debugVelibRawData)
{
	echo "vardump SomeVelibRawData</br>";
	echo $SomeVelibRawData;
	echo "</br>";
}

$VelibDataArray = json_decode($SomeVelibRawData, true);

if($debugVelibRawData)
{
	echo "vardump VelibDataArray</br>";
	var_dump($VelibDataArray);
	echo "</br>";
}

if(!is_array($VelibDataArray))
{
	echo "<br> Retour inattendu de l'api Velib";
	error_log( date("Y-m-d H:i:s")." - Retour inattendu de l'api Velib");
	error_log(date("Y-m-d H:i:s")." - json decode error - ".json_last_error ().":".json_last_error_msg ());
	error_log(date("Y-m-d H:i:s").$SomeVelibRawData);
	velibAPIParser_RemoveLock();
	exit;
}

// update log
// 0 : 
$logstring = "";
$lofFile='./../log/updatelog.csv';
if(!file_exists ($lofFile) )
  $logstring = "date;requete;\r\n";


// 1 : on ouvre le fichier
if(!($openLogFile = fopen($lofFile, 'a+')))
  echo("log file error");


function get_ip() {
	// IP si internet partagé
	if (isset($_SERVER['HTTP_CLIENT_IP'])) {
		return $_SERVER['HTTP_CLIENT_IP'];
	}
	// IP derrière un proxy
	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	// Sinon : IP normale
	else {
		return (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
	}
}





// mise à jour des stations --> debut
echo "create and update stations from velib 2018 data flow";
if($debugVerbose)
	echo "</BR></BR>--- ---- array parsing --- --- </BR>";
foreach($VelibDataArray as $keyL1 => $valueL1){
	if($debugVerbose)
	{
		echo "</br> --- --- ---This is a  station --- </br>";
		echo "<br>station data from velib flow :<br>";		
		//echo "station ".$keyL1."</br>";
		var_dump($valueL1) ;	
		echo "</br>";
	}
	$stationNbEDock=0;
	$stationNbDock=0;
	foreach($valueL1 as $keyL2 => $valueL2){
		if(is_array($valueL2))
		{
			if($debugVerbose)
				echo "station name and gps data : </br>";
			foreach($valueL2 as $keyL3 => $valueL3){
				if(is_array($valueL3))
				{
					if($debugVerbose)
						echo "gps data : ";
					foreach($valueL3 as $keyL4 => $valueL4)
					{
						if($debugVerbose)
							echo "</br>".$keyL4." : ".$valueL4;
						if($keyL4 == "latitude"){ $stationLat = $valueL4 + 0;} 
						if($keyL4 == "longitude"){ $stationLon = $valueL4 + 0;}
					}
				}
				else
				{
				if($keyL3 == "state")
				{ 
					if($valueL3=="Neutralised")
						$stationState="Close";
					else
						$stationState = $valueL3;
				} 	
				if($keyL3 == "name"){ $stationName = $valueL3;}	
				if($keyL3 == "code"){ $stationCode = ltrim($valueL3, '0');}	
				if($debugVerbose)
					echo "</br>".$keyL3."  : ".$valueL3 ;	
				}
			}
		}
		else
		{
			if($keyL2 == "nbBike"){ $stationNbBike  = $valueL2;} 
			if($keyL2 == "nbEbike"){ $stationNbEBike  = $valueL2;} 
			if($keyL2 == "nbFreeDock"){ $stationNbFreeDock   = $valueL2;} 
			if($keyL2 == "nbFreeEDock"){ $stationNbFreeEDock   = $valueL2;}
			if($keyL2 == "nbDock"){ $stationNbDock   = $valueL2;}
			if($keyL2 == "nbEDock"){ $stationNbEDock   = $valueL2;}	
			if($keyL2 == "nbBikeOverflow"){ $stationNbBikeOverflow  = $valueL2;} 
			if($keyL2 == "nbEBikeOverflow"){ $stationNbEBikeOverflow  = $valueL2;} 
			if($keyL2 == "kioskState"){ $stationKioskState  = $valueL2;} 
				//echo "</br>".$keyL2."  : ".$valueL2 ;	
		}
	}	

	if($debugVerbose)
	{

		//echo "</br>stationName:".$stationName;
		//echo " - "."stationCode:".$stationCode;
		//echo "</br>"."stationState:".$stationState;
		//echo "</br>"."stationLat:".$stationLat;
		//echo "</br>"."stationLon:".$stationLon;
		echo "</br>"."stationNbEDock:".($stationNbEDock+$stationNbDock);
		echo "</br>"."stationNbBike:".$stationNbBike;
		echo "</br>"."stationNbEBike:".$stationNbEBike;
		echo "</br>"."nbFreeDock:".$stationNbFreeDock;
		echo "</br>"."nbFreeEDock:".$stationNbFreeEDock;
		echo "</br>"."stationNbBikeOverflow:".$stationNbBikeOverflow;
		echo "</br>"."stationNbEBikeOverflow:".$stationNbEBikeOverflow;	
	}
	
	if ($result = mysqli_query($link, "SELECT * FROM velib_station where stationCode = '$stationCode'")) {
		if (mysqli_num_rows($result)>0)
		{//la station existe
				
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				if($debugVerbose)
				{
					echo "</br></br> Station already exist in db : here we will have to compare and update";
					echo "</br>station data from local db :";
					echo "</br>"."TechId:".$row["id"];
					echo "</br>"."stationName:".$row["stationName"];
					echo "</br>"."stationCode:".$row["stationCode"];
					echo "</br>"."stationState:".$row["stationState"];
					echo "</br>"."stationLat:".$row["stationLat"];
					echo "</br>"."stationLon:".$row["stationLon"];
					echo "</br>"."stationNbEDock:".$row["stationNbEDock"];
					echo "</br>"."stationNbBike:".$row["stationNbBike"];
					echo "</br>"."stationNbEBike:".$row["stationNbEBike"];
					echo "</br>"."nbFreeDock:".$row["nbFreeDock"];
					echo "</br>"."nbFreeEDock:".$row["nbFreeEDock"];
					echo "</br>"."stationNbBikeOverflow:".$row["stationNbBikeOverflow"];
					echo "</br>"."stationNbEBikeOverflow:".$row["stationNbEBikeOverflow"];	
				}
				
				if 
				(
					$stationState == $row["stationState"] and
					$stationNbEDock+$stationNbDock	== $row["stationNbEDock"] and
					$stationNbBike == $row["stationNbBike"] and
					$stationNbEBike == $row["stationNbEBike"] and
					$stationNbFreeDock == $row["nbFreeDock"] and
					$stationNbFreeEDock == $row["nbFreeEDock"] and 
					$stationNbBikeOverflow == $row["stationNbBikeOverflow"] and 
					$stationNbEBikeOverflow == $row["stationNbEBikeOverflow"] and 
					(round($stationLat - ($row["stationLat"]+0),5)) == 0 and
					(round($stationLon - ($row["stationLon"]+0),5)) == 0
				)
				{ // Pas de changement - update pour topper que la station est tjs là";
					
					if($debug)
					{
					echo "</br>stationName : ".$stationName;
					echo " - "."stationCode : ".$stationCode;
					echo " - "."stationState : ".$stationState;
					echo "</br>pas de changement -> La station est tjs là<br>";	
					}
					
					$r = "UPDATE `velib_station` 
					SET 
						`stationLastView`=now()
					WHERE `id`='$row[id]'";
					echo $r;
					if(!mysqli_query($link, $r))
					{
						printf("Errormessage: %s\n", mysqli_error($link));
					}
				}
				else
				{ // quelque chose à changé
					echo "</br>stationName : ".$stationName;
					echo " - "."stationCode : ".$stationCode;
					echo " - "."stationState : ".$stationState;
					echo "</br>Les données ont changé";	
					$row["stationLat"] = $row["stationLat"] +0;
					$row["stationLon"] = $row["stationLon"] +0;
					
						/*
						echo "<br> cond1:".(round($stationLat - $row["stationLat"],5));
						echo "<br> cond2:".(round($stationLon - $row["stationLon"],5));
						
						echo "<br> Lat - Before:".$row["stationLat"]." - After:" . $stationLat;
						echo "<br> Lat - Before:".gettype($row["stationLat"])." - After:" . gettype($stationLat);
						echo "<br> Lon - Before:".$row["stationLon"]." - After:" . $stationLon;
						echo "<br> Lon - Before:".gettype($row["stationLon"])." - After:" . gettype($stationLon);
						*/
					
					// check lat/lon round à 10 décimale pour les stations avec doc et si changement mettre à jour aussi l'adresse via Google geocode API...
					if( ((round($stationLat - $row["stationLat"],5)) != 0) or ((round($stationLon - $row["stationLon"],5)) !=0 ))
					{//la position de la station a changé
						echo "<br> La position a changé";
						echo "<br> cond1:".$stationLat <> $row["stationLat"];
						echo "<br> cond1:".$stationLon <> $row["stationLon"];
						echo "<br> Lat - Before:".$row["stationLat"]." - After:" . $stationLat;
						echo "<br> Lat - Before:".gettype($row["stationLat"])." - After:" . gettype($stationLat);
						echo "<br> Lon - Before:".$row["stationLon"]." - After:" . $stationLon;
						echo "<br> Lon - Before:".gettype($row["stationLon"])." - After:" . gettype($stationLon);
						
						$stationLocationHasChanged  = 1;
						
						if(false)//désactivé
						{
							/// recupérer l'adresse --> google geocode API
								
							$wsUrl = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$stationLat.','.$stationLon.'&key=AIzaSyBhVM63uEbuaccNCZ687XuAMVavQK4o-VQ';
							if($debugVerbose)
								echo "<br>".$wsUrl."<br>";	
							$googleGeocodeAPIRawData = file_get_contents($wsUrl);
							$googleGeocodeAPIDataArray = json_decode($googleGeocodeAPIRawData, true);

							if($debugVerbose)
							{
								echo "vardump</br>";
								var_dump($googleGeocodeAPIDataArray);	
							}
							
							if(count($googleGeocodeAPIDataArray)>3) //parce que lorsque le quota est atteint la reponse est un array(3)
							{
								//echo "</br> --- --- ---dépiller le retour google  --- </br>";
								foreach($googleGeocodeAPIDataArray as $keyL1 => $valueL1)
								{			
									foreach($valueL1 as $keyL2 => $valueL2){
										if(is_array($valueL2))
										{
											foreach($valueL2 as $keyL3 => $valueL3){
												if(!is_array($valueL3))
												{
													if($keyL3 == 'formatted_address')
														{
															$stationAdress = mysqli_real_escape_string($link, $valueL3); //ici on à l'adresse
															$quitter = 1;
															break;
														}
												}
											}
											if($quitter){
												break;
											}
										}
									}
									if($quitter){
										break;
									}				
								}
								echo "<br> Adresse - Before:".$row["stationAdress"]." - After:" . $stationAdress;	
							}						
							else
							{
							$stationAdress = mysqli_real_escape_string($link, $row["stationAdress"]);
							}
						}
					}
					else
					{//la position de la station n'a pas changé
						$stationAdress = mysqli_real_escape_string($link, $row["stationAdress"]);
						$stationLocationHasChanged = $row["stationLocationHasChanged"];
					}

					
					
					if(($stationNbEDock+$stationNbDock + $stationNbFreeDock + $stationNbFreeEDock+$stationNbBike+$stationNbEBike !=0) or $stationLocationHasChanged  == 1)
					{	
						// si la station est signalée HS, alors on recupère le max de  "delta(max-min) quotidien" de la station depuis le signalement
						// 
						
						$resetStationHS = 0;
						if($row["stationSignaleHS"]==1)
						{
							error_log( date("Y-m-d H:i:s")." - Changement dans la station ".$stationCode." signalée HS");
							$resultHSQ = "SELECT max(`stationVelibMaxVelib`-`stationVelibMinVelib`) as maxDelta FROM `velib_station_min_velib` where `stationCode` = '$stationCode' AND `stationStatDate` >= date('$row[stationSignaleHSDate]')";
							//error_log( date("Y-m-d H:i:s").$resultHSQ);
							
							if ($resultHS = mysqli_query($link, $resultHSQ)	) 
							{
								if (mysqli_num_rows($resultHS)>0)
								{
									//la station a un historique
									$rowHS = mysqli_fetch_array($resultHS, MYSQLI_ASSOC);
									if($rowHS["maxDelta"] > 3)
									{
										$resetStationHS = 1 ;
										error_log("- et le deltaMaxMin est supérieur à 3(".$rowHS["maxDelta"].") --> suppr de l'indicateur HS");
									}
								}
							}
						}

				
						// mise à jour de la station				
						$r = "UPDATE `velib_station` 
						SET 
							`stationState`='$stationState' ,
							`stationLat` = '$stationLat', 
							`stationLon` = '$stationLon', 
							`stationNbEDock`='$stationNbEDock'+'$stationNbDock',
							`stationNbBike`='$stationNbBike',
							`stationNbEBike`='$stationNbEBike',
							`nbFreeDock`='$stationNbFreeDock',
							`nbFreeEDock`='$stationNbFreeEDock',
							`stationNbBikeOverflow`='$stationNbBikeOverflow',
							`stationNbEBikeOverflow`='$stationNbEBikeOverflow',";
						

						$r = $r . "
							`stationLastChange`=now(),";

						$r = $r . 
							"`stationLastView`=now(),
							`stationKioskState` = '$stationKioskState',
							`stationOperativeDate` = 
								(
								case 
									WHEN '$stationState' = 'Operative' and '$row[stationOperativeDate]'='' then now() 
									WHEN '$stationState' = 'Operative' and '$row[stationOperativeDate]' <>'' then 	'$row[stationOperativeDate]'				
								end
								),
							`stationLastComeBack` = 
								(
								case 
									when ('$row[stationLastChange]' < DATE_ADD(NOW(), INTERVAL -24 HOUR)) then now() 
									when ('$row[stationLastChange]' > DATE_ADD(NOW(), INTERVAL -24 HOUR)) and '$row[stationLastComeBack]' <> '' then '$row[stationLastComeBack]'
								end 
								),
							`stationLastChangeAtComeBack` = 
								(
								case 
									when ('$row[stationLastChange]' < DATE_ADD(NOW(), INTERVAL -24 HOUR)) then '$row[stationLastChange]'
									when ('$row[stationLastChange]' > DATE_ADD(NOW(), INTERVAL -24 HOUR)) and '$row[stationLastChangeAtComeBack]' <> ''  then '$row[stationLastChangeAtComeBack]'
								end 
								)
							,	
							`stationLastExit` = 
								(
								case 
									when ('$row[nbFreeEDock]' < '$stationNbFreeEDock' or '$row[nbFreeDock]' < '$stationNbFreeDock' or '$row[stationNbBikeOverflow]' > '$stationNbBikeOverflow' or '$row[stationNbEBikeOverflow]' > '$stationNbEBikeOverflow' ) 
										then now() 
										else '$row[stationLastExit]'
								end 
								),
							`stationSignaleHS` = 								
								(
								case 
									when 
									(
										(
											'$row[nbFreeEDock]' < '$stationNbFreeEDock' or '$row[nbFreeDock]' < '$stationNbFreeDock' or '$row[stationNbBikeOverflow]' > '$stationNbBikeOverflow' or '$row[stationNbEBikeOverflow]' > '$stationNbEBikeOverflow'
										) 
										and 
										(
											'$row[stationSignaleHSCount]'=1 
											or
											(
											'$resetStationHS'=1
											and 
											'$row[stationSignaleHSCount]' < 6 
											)
										)
									) 
									then 0
									else '$row[stationSignaleHS]'
								end 
								), 
							`stationSignaleHSDate`  =
								(
								case 
									when 
									(
										(
											'$row[nbFreeEDock]' < '$stationNbFreeEDock' or '$row[nbFreeDock]' < '$stationNbFreeDock' or '$row[stationNbBikeOverflow]' > '$stationNbBikeOverflow' or '$row[stationNbEBikeOverflow]' > '$stationNbEBikeOverflow'
										) 
										and 
										(
											'$row[stationSignaleHSCount]'=1 
											or
											(
											'$resetStationHS'=1
											and 
											'$row[stationSignaleHSCount]' < 6 
											)
										)
									) 
									then NULL
									else 
										case when ( '$row[stationSignaleHSDate]' = '')
											then NULL
											else '$row[stationSignaleHSDate]'
										end
								end 
								),
							`stationSignaleHSCount` =
								(
								case 
									when ('$row[nbFreeEDock]' < '$stationNbFreeEDock' or '$row[nbFreeDock]' < '$stationNbFreeDock' or '$row[stationNbBikeOverflow]' > '$stationNbBikeOverflow' or '$row[stationNbEBikeOverflow]' > '$stationNbEBikeOverflow' ) 
									then greatest(0,'$row[stationSignaleHSCount]' -1)
									else '$row[stationSignaleHSCount]'
								end
								),
							`stationLocationHasChanged` = '$stationLocationHasChanged'
						WHERE `id`='$row[id]'";
													
						if($debugVerbose)
						{
							echo "<br>";
							echo $r;
						}
						
						if(!mysqli_query($link, $r))
						{
							error_log( date("Y-m-d H:i:s")." - erreur lors de la mise à jour de la station ".$stationCode);
							printf("Errormessage: %s\n", mysqli_error($link));
						}
						
						
						if($stationState == $row["stationState"])
						{
							if($debugVerbose) echo "<br> Le status de la station n'a pas changé";					
						}
						else
						{
							$r = 
								"
									INSERT INTO `velib_station_status`
									(
										`id`,
										`stationCode`,
										`stationState`,
										`stationStatusDate`
									)
									VALUES
									(
										'$row[id]',
										'$row[stationCode]' ,
										'$stationState' ,
										now()			
									)
								";		
									
							if(!mysqli_query($link, $r))
							{		
								echo "<br>CreateStatusRow error";
								printf("Errormessage: %s\n", mysqli_error($link));
								if($debugVerbose) echo $r;
							}	
							else
							{
								if($debugVerbose) echo "<br> CreateStatusRow ok";
							}							
						}
						
					
						// 2 : génération du log fichier
						//$logstring = $logstring.date('H:i:s j/m/y').";".rtrim($r).";\r";
						$nbdocktmp = $stationNbEDock+$stationNbDock;
						
						$logstring = $logstring.date('j/m/y').";".date('H').";".date('i').";".date('s').";".$stationCode.";".$stationName.";".$stationState.";".$stationKioskState.";".$nbdocktmp.";";
						$logstring = $logstring.$stationNbBike.";".$stationNbEBike.";".$stationNbFreeDock.";".$stationNbFreeEDock.";".$stationNbBikeOverflow.";".$stationNbEBikeOverflow.";";
						$logstring = $logstring.$row["stationInsertedInDb"].";";
						if($row["stationOperativeDate"]=="")
						{
							$logstring = $logstring.date('y-m-j H:i:s').";";
						}
						else{					
							$logstring = $logstring.$row["stationOperativeDate"].";";	
						}
						$logstring = $logstring."\n";
						$stationVelibExit = max(0, $row['stationNbEBike'] - $stationNbEBike) + 
								max(0, $row['stationNbBike'] - $stationNbBike) + 
								max(0, $row['stationNbBikeOverflow'] - $stationNbBikeOverflow) + 
								max(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);

						$stationEVelibExit = max(0, $row['stationNbEBike'] - $stationNbEBike) + 
								max(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);								
						
						if($debugVerbose) echo "<br> stationVelibExit : $stationVelibExit dont VAE : $stationEVelibExit ";
						
						// Alimentation statistiques mvt de la station
						$r = 
						"
						INSERT 
							INTO `velib_station_min_velib` 
								(
									`stationCode`, 
									`stationStatDate`, 
									`stationVelibMinVelib`, 
									`stationVelibMaxVelib`, 
									`stationVelibMinEVelib`, 
									`stationVelibMinVelibOverflow`, 
									`stationVelibMaxVelibOverflow`, 
									`stationVelibMinEVelibOverflow`, 
									`stationVelibExit`,
									`stationEVelibExit`,
									`updateDate`
								) 
							VALUES 
								(
									'$stationCode',
									now(),
									'$stationNbBike' + '$stationNbBikeOverflow' +'$stationNbEBike' + '$stationNbEBikeOverflow' ,
									'$stationNbBike' + '$stationNbBikeOverflow' +'$stationNbEBike' + '$stationNbEBikeOverflow' ,
									'$stationNbEBike' + '$stationNbEBikeOverflow' ,									
									'$stationNbBikeOverflow' + '$stationNbEBikeOverflow' ,
									'$stationNbBikeOverflow' + '$stationNbEBikeOverflow' ,	
									'$stationNbEBikeOverflow' ,
									'$stationVelibExit',
									'$stationEVelibExit',
									now()
								) 
							ON DUPLICATE KEY UPDATE 
								stationCode = '$stationCode', 
								stationStatDate = now(), 
								stationVelibMinVelib = LEAST(stationVelibMinVelib, '$stationNbBike' + '$stationNbBikeOverflow' +'$stationNbEBike' + '$stationNbEBikeOverflow' ),
								stationVelibMaxVelib = greatest(stationVelibMaxVelib, '$stationNbBike' + '$stationNbBikeOverflow' +'$stationNbEBike' + '$stationNbEBikeOverflow' ),
								stationVelibMinEVelib = LEAST(stationVelibMinEVelib, '$stationNbEBike' + '$stationNbEBikeOverflow' ),
								stationVelibMinVelibOverflow = LEAST(stationVelibMinVelibOverflow, '$stationNbBikeOverflow' + '$stationNbEBikeOverflow' ),
								stationVelibMaxVelibOverflow = greatest(stationVelibMaxVelibOverflow, '$stationNbBikeOverflow' + '$stationNbEBikeOverflow' ),	
								stationVelibMinVelibOverflow = LEAST(stationVelibMinVelibOverflow, '$stationNbEBikeOverflow' ),
								stationVelibExit = stationVelibExit + '$stationVelibExit',								
								stationEVelibExit = stationEVelibExit + '$stationEVelibExit',	
								updateDate = now()
						";
						if($debugVerbose)
						{
							echo "<br>";
							echo $r;
						}
						if(!mysqli_query($link, $r))
						{
							printf("Errormessage: %s\n", mysqli_error($link));
						}			
						// Calcul du nombre de retrait détécté à chaque exécution du parser							
						// si il y a eu un retrait alors on incrémente le compteur		
						 		
						
						if(
							$row['stationNbBike'] > $stationNbBike
							or $row['stationNbEBike'] > $stationNbEBike
							or $row['stationNbBikeOverflow'] > $stationNbBikeOverflow 
							or $row['stationNbEBikeOverflow'] > $stationNbEBikeOverflow 							
						) 						
						{
							if($debugVerbose)							
							{		
								echo "<br> retrait ici? OUI";						
								echo "</br> velibExit init value =".$velibExit."</br>";	
								echo $row['stationNbBike'] ."</br>";
								echo $stationNbBike ."</br>";
								echo $row['stationNbEBike'] ."</br>";
								echo $stationNbEBike."</br>"; 
								echo "</br> nombre de retrait ici ="; 
								echo max(0,  $row['stationNbEBike'] - $stationNbEBike) 
									+ max(0, $row['stationNbBike'] - $stationNbBike) 
									+ max(0, $row['stationNbBikeOverflow'] - $stationNbBikeOverflow) 
									+ max(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);							
							}				
							$velibExit = $velibExit + 
								max(0, $row['stationNbEBike'] - $stationNbEBike) + 
								max(0, $row['stationNbBike'] - $stationNbBike) + 
								max(0, $row['stationNbBikeOverflow'] - $stationNbBikeOverflow) + 
								max(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);
								
							$EvelibExit = $EvelibExit + 
								max(0, $row['stationNbEBike'] - $stationNbEBike) + 
								max(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);	
								
						}
						
						if(
							$row['stationNbBike'] < $stationNbBike
							or $row['stationNbEBike'] < $stationNbEBike
							or $row['stationNbBikeOverflow'] < $stationNbBikeOverflow 
							or $row['stationNbEBikeOverflow'] < $stationNbEBikeOverflow 							
						) 
						{							
							if($debugVerbose)							
							{	
							echo "<br> Retour ici? Oui"; 
							echo "</br> velibReturn init value =".$velibReturn."</br>";	
							}
							
							$velibReturn = $velibReturn +
								min(0, $row['stationNbEBike'] - $stationNbEBike) + 
								min(0, $row['stationNbBike'] - $stationNbBike) + 
								min(0, $row['stationNbBikeOverflow'] - $stationNbBikeOverflow) + 
								min(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);	

							$EvelibReturn = $EvelibReturn + 
								min(0, $row['stationNbEBike'] - $stationNbEBike) + 
								min(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);									

								
							if($debugVerbose)							
							{									
								echo $row['stationNbBike'] ."</br>";
								echo $stationNbBike ."</br>";
								echo $row['stationNbEBike'] ."</br>";
								echo $stationNbEBike."</br>"; 
								echo "</br> nombre de retour ici ="; 
								echo min(0,  $row['stationNbEBike'] - $stationNbEBike) 
									+ min(0, $row['stationNbBike'] - $stationNbBike) 
									+ min(0, $row['stationNbBikeOverflow'] - $stationNbBikeOverflow) 
									+ min(0, $row['stationNbEBikeOverflow'] - $stationNbEBikeOverflow);							
							}							
						
						}
						echo " <br>--> updated<br>";
					}
					else echo "<br>pas de diapason - update skipped<br>";
				}	
		}
		else
		{//la station n'existe pas
			if ($stationNbEDock+$stationNbDock + $stationNbFreeDock + $stationNbFreeEDock+$stationNbBike+$stationNbEBike  > 0)
			{	
				$stationName = mysqli_real_escape_string($link, $stationName);
				echo "</br>stationName : ".$stationName;
				echo " - "."stationCode : ".$stationCode;
				echo " - "."stationState : ".$stationState;						
				echo " - Lat :".$stationLat;			
				echo " - Lon : ".$stationLon;
				
				
				/// recupérer l'adresse --> adresse.data.gouv.fr					
				$wsUrl = 'https://api-adresse.data.gouv.fr/reverse/?lat='.$stationLat.'&lon='.$stationLon.'&type=housenumber';
				if($debugVerbose) echo $wsUrl;
				$stationAdress = "Not Available";
				$stationCP;
				$stationCommune = '';
				
				$googleGeocodeAPIRawData = file_get_contents($wsUrl);
				$googleGeocodeAPIDataArray = json_decode($googleGeocodeAPIRawData, true);

				if($debugVerbose)
				{
					echo "vardump</br>";
					var_dump($googleGeocodeAPIDataArray);	
				}
				$quitter = 0;
			
	
				if($debugVerbose) echo "</br> --- --- ---dépiller le retour ws  --- </br>";
				foreach($googleGeocodeAPIDataArray as $keyL1 => $valueL1)
				{
					if($keyL1 == 'features')
					{
						if($debugVerbose) echo "<br> inside features ";
						foreach($valueL1 as $keyL2 => $valueL2)
						{
							if($keyL2 == '0')
							{
								if($debugVerbose) echo "<br> inside 0 ";
								foreach($valueL2 as $keyL3 => $valueL3)
								{
									if($keyL3 == 'properties')
									{			
										if($debugVerbose) echo "<br> inside properties ";
										if($debugVerbose) var_dump($valueL3);									
										
										if(is_array($valueL3))
										{
											if( isset($valueL3['housenumber']) && isset($valueL3['street']) && isset($valueL3['postcode']) && isset($valueL3['city']))
											{
												$stationAdress = $valueL3['housenumber'].", ".$valueL3['street'].", ".$valueL3['postcode']." ".$valueL3['city'];
												$stationCP = $valueL3['postcode'];
												$stationCommune = $valueL3['city'];
											}
											else
											{
												$stationAdress = $valueL3['label'];
												if(isset($valueL3['postcode']))
													$stationCP = $valueL3['postcode'];
												if(isset($valueL3['city']))
													$stationCommune = $valueL3['city'];												
											}
											
											$stationAdress = mysqli_real_escape_string($link, $stationAdress); //ici on à l'adresse
											$stationCommune = mysqli_real_escape_string($link, $stationCommune);
											$quitter = 1;
											break;
											
										}
									}
									if($quitter){
										break;
									}
								}
							}
							if($quitter){
								break;
							}						
						}	
					}
					if($quitter){
						break;
					}				
				}
			
					echo "Station Adress: ".$stationAdress."<br>";	
				
				$r = "
				INSERT 
				INTO `velib_station`(
					`stationName`, 
					`stationCode`, 
					`stationState`, 
					`stationLat`, 
					`stationLon`, 
					`stationNbEDock`, 
					`stationNbBike`, 
					`stationNbEBike`, 
					`nbFreeDock`, 
					`nbFreeEDock`, 
					`stationNbBikeOverflow`, 
					`stationNbEBikeOverflow`, 
					`stationLastChange`, 
					`stationLastView`,
					`stationKioskState`,
					`stationAdress`, 
					`stationOperativeDate`, 
					`stationLastExit`,
					`stationLocationHasChanged` ,
					stationCP,
					stationCommune
					) 
				VALUES (
					'$stationName', 
					'$stationCode', 
					'$stationState', 
					'$stationLat', 
					'$stationLon', 
					'$stationNbEDock'+'$stationNbDock',
					'$stationNbBike', 
					'$stationNbEBike', 
					'$stationNbFreeDock', 
					'$stationNbFreeEDock', 
					'$stationNbBikeOverflow', 
					'$stationNbEBikeOverflow', 
					now(), 
					now(), 
					'$stationKioskState', 
					left('$stationAdress',300),
					case WHEN '$stationState' = 'Operative' then now() else null end,
					now(),
					1,
					'$stationCP',
					'$stationCommune'
					)";
				
				if(!mysqli_query($link, $r))
				{
					printf("Errormessage: %s\n", mysqli_error($link));
				}
				else
				{
				printf ("New Record has id %d.\n", mysqli_insert_id($link));
				}
				

				$r = 
					"
						INSERT INTO `velib_station_status`
						(
							`id`,
							`stationCode`,
							`stationState`,
							`stationStatusDate`
						)
						VALUES
						(
							LAST_INSERT_ID(),
							'$stationCode' ,
							'$stationState' ,
							now()			
						)
					";		
						
				if(!mysqli_query($link, $r))
				{		
					echo "<br>CreateStatusRow error";
					printf("Errormessage: %s\n", mysqli_error($link));
					if($debugVerbose) echo $r;
				}	
				else
				{
					if($debugVerbose) echo "<br> CreateStatusRow ok";
				}
				
			
				
				// 2 : génération du log
				//$logstring = $logstring.date('H:i:s j/m/y').";".rtrim($r).";\r";
				$tmpdock = $stationNbEDock+$stationNbDock;
				$logstring = $logstring.date('j/m/y').";".date('H').";".date('i').";".date('s').";".$stationCode.";".$stationName.";".$stationState.";".$stationKioskState.";".$tmpdock.";";
				$logstring = $logstring.$stationNbBike.";".$stationNbEBike.";".$stationNbFreeDock.";".$stationNbFreeEDock.";".$stationNbBikeOverflow.";".$stationNbEBikeOverflow.";";
				$logstring = $logstring.date('y-m-j H:i:s').";";
				if($stationState == "Operative")
				{
					$logstring = $logstring.date('y-m-j H:i:s').";";
				}
				else{					
					$logstring = $logstring.";";	
				}
				$logstring = $logstring."\n";
				
				echo "</br> station not found in db --> Created";

			}	
			else
			{
				echo "</br>stationName : ".$stationName;
				echo " - "."stationCode : ".$stationCode;
				echo " - "."stationState : ".$stationState;			
				$stationName = mysqli_real_escape_string($link, $stationName);
				echo " - Lat :".$stationLat;			
				echo " - Lon : ".$stationLon;
				echo "</br> station not found in db --> station has no dock --> skip<br>";
			}
		/* free result set */
		mysqli_free_result($result);
		}
	}	
	else{
		printf("Errormessage: %s\n", mysqli_error($link));
	}	
	//echo "</br>";
}
// mise à jour des stations --> Fin

// Mise à jour de la table de stats sur les stations actives
// Alimentation statistiques mvt stations
	$r = 
	"
		INSERT INTO `velib_activ_station_stat`
		   ( `date`                           ,
				  `heure`                     ,
				  `nbStationUpdatedInThisHour`,
				  `nbStationUpdatedLAst3Hour` ,
				  `nbStationUpdatedLAst6Hour` ,
				  `nbStationAtThisDate`,
				  `nbrVelibExit`,
				  `nbrEVelibExit`,
				  `networkNbBike`,
				  `networkNbBikeOverflow`,
				  `networkEstimatedNbBike`,
				  `networkEstimatedNbBikeOverflow`,
				  `networkNbEBike`,
				  `networkNbEBikeOverflow`,
				  `networkEstimatedNbEBike`,
				  `networkEstimatedNbEBikeOverflow`,
				  `networkNbDock`
		   )
		   values
		   ( 	
				  now()           ,
				  hour(now()),
				  (
						 SELECT
								count(`id`) as nbs
						 FROM
								`velib_station`
						 where
								stationLastChange     > DATE_ADD(NOW(), INTERVAL -1 HOUR)
								and stationHidden     = 0
								and stationState      = 'Operative'
								and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
				  )
				  ,
				  (
						 SELECT
								count(`id`) as nbs
						 FROM
								`velib_station`
						 where
								stationLastChange     > DATE_ADD(NOW(), INTERVAL -3 HOUR)
								and stationHidden     = 0
								and stationState      = 'Operative'
								and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
				  )
				  ,
				  (
						 SELECT
								count(`id`) as nbs
						 FROM
								`velib_station`
						 where
								stationLastChange     > DATE_ADD(NOW(), INTERVAL -6 HOUR)
								and stationHidden     = 0
								and stationState      = 'Operative'
								and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
				  )
				  ,
				  (
						 SELECT
								count(distinct `id`) as nbs
						 FROM
								`velib_station`
						 where
								stationHidden         = 0
								and stationState      = 'Operative'
								and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
				  ),
				  $velibExit,
				  $EvelibExit,
				  (select sum(stationNbBike)+sum(stationNbEBike) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative'  ), 
				  (select sum(stationNbBikeOverflow)+sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative'  ),
				  (		
					SELECT
						sum(`stationNbBike`         + `stationNbEBike` -        stationMinVelibNDay) as estimatedVelibNumber
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinVelib` - stationVelibMinVelibOverflow ) AS stationMinVelibNDay,
                                      MIN(stationVelibMinVelibOverflow)                            AS stationVelibMinVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0
						and  `stationState` = 'Operative'
				  ),
				 (		
					SELECT
						sum(`stationNbBikeOverflow` + `stationNbEBikeOverflow`- stationVelibMinVelibOverflow) as  estimatedVelibNumberOverflowr
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinVelib` - stationVelibMinVelibOverflow ) AS stationMinVelibNDay,
                                      MIN(stationVelibMinVelibOverflow)                            AS stationVelibMinVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0
						and  `stationState` = 'Operative'
				  ),
				  (select sum(stationNbEBike) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative' ), 
				  (select sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative' ),
				  (		
					SELECT
						sum( `stationNbEBike` - stationMinEVelibNDay) as estimatedEVelibNumber
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinEVelib` - stationVelibMinEVelibOverflow ) AS stationMinEVelibNDay,
                                      MIN(stationVelibMinEVelibOverflow)                            AS stationVelibMinEVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0	
						and  `stationState` = 'Operative'
				  ),
				 (		
					SELECT
						sum(`stationNbEBikeOverflow`- stationVelibMinEVelibOverflow) as  estimatedEVelibNumberOverflowr
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinEVelib` - stationVelibMinEVelibOverflow ) AS stationMinEVelibNDay,
                                      MIN(stationVelibMinEVelibOverflow)                            AS stationVelibMinEVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0		
						and  `stationState` = 'Operative'
				  )	,
				  (
					SELECT 
						sum(`stationNbBike` + `stationNbEBike`+`nbFreeDock`+`nbFreeEDock`) as nbActivDock
					FROM `velib_station`
					where `stationState` = 'Operative'
				  )
		   )
		ON DUPLICATE KEY UPDATE
		   `date`  =`date`  ,
		   `heure` = `heure`,
		   `nbStationUpdatedInThisHour`=(
				  SELECT
						 count(`id`) as nbs
				  FROM
						 `velib_station`
				  where
						 stationLastChange     > DATE_ADD(NOW(), INTERVAL -1 HOUR)
						 and stationHidden     = 0
						 and stationState      = 'Operative'
						 and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
		   )
		   ,
		   `nbStationUpdatedLAst3Hour`=(
				  SELECT
						 count(`id`) as nbs
				  FROM
						 `velib_station`
				  where
						 stationLastChange     > DATE_ADD(NOW(), INTERVAL -3 HOUR)
						 and stationHidden     = 0
						 and stationState      = 'Operative'
						 and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
		   )
		   ,
		   `nbStationUpdatedLAst6Hour`=(
				  SELECT
						 count(`id`) as nbs
				  FROM
						 `velib_station`
				  where
						 stationLastChange     > DATE_ADD(NOW(), INTERVAL -6 HOUR)
						 and stationHidden     = 0
						 and stationState      = 'Operative'
						 and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
		   )
		   ,
		   `nbStationAtThisDate`=(
				  SELECT
						 count(distinct `id`) as nbs
				  FROM
						 `velib_station`
				  where
						 stationHidden         = 0
						 and stationState      = 'Operative'
						 and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
		   ),
			`nbrVelibExit` = `nbrVelibExit` + $velibExit,
			`nbrEVelibExit` = `nbrEVelibExit` + $EvelibExit,			
			`networkNbBike` = (select sum(stationNbBike)+sum(stationNbEBike) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative' ), 
			`networkNbBikeOverflow` = (select sum(stationNbBikeOverflow)+sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative'  ),
			`networkEstimatedNbBike`	 = (		
					SELECT
						sum(`stationNbBike`         + `stationNbEBike` -        stationMinVelibNDay) as estimatedVelibNumber
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinVelib` - stationVelibMinVelibOverflow ) AS stationMinVelibNDay,
                                      MIN(stationVelibMinVelibOverflow)                            AS stationVelibMinVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0	
						and  `stationState` = 'Operative'						
				  ),
			`networkEstimatedNbBikeOverflow`= (		
					SELECT
						sum(`stationNbBikeOverflow` + `stationNbEBikeOverflow`- stationVelibMinVelibOverflow) as  estimatedVelibNumberOverflowr
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinVelib` - stationVelibMinVelibOverflow ) AS stationMinVelibNDay,
                                      MIN(stationVelibMinVelibOverflow)                            AS stationVelibMinVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0	
						and  `stationState` = 'Operative'
				  ),
			`networkNbEBike` = (select sum(stationNbEBike) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  and  `stationState` = 'Operative'), 
			`networkNbEBikeOverflow` = (select sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 and  `stationState` = 'Operative' ),
			`networkEstimatedNbEBike`	 = (		
					SELECT
						sum( `stationNbEBike` -        stationMinEVelibNDay) as estimatedEVelibNumber
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinEVelib` - stationVelibMinEVelibOverflow ) AS stationMinEVelibNDay,
                                      MIN(stationVelibMinEVelibOverflow)                            AS stationVelibMinEVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0	
						and  `stationState` = 'Operative'
				  ),
			`networkEstimatedNbEBikeOverflow`= (		
					SELECT
						sum( `stationNbEBikeOverflow`- stationVelibMinEVelibOverflow) as  estimatedEVelibNumberOverflowr
					FROM `velib_station`
						LEFT JOIN
							(
                             SELECT
                                      `stationCode`                                                                      ,
                                      MIN( `stationVelibMinEVelib` - stationVelibMinEVelibOverflow ) AS stationMinEVelibNDay,
                                      MIN(stationVelibMinEVelibOverflow)                            AS stationVelibMinEVelibOverflow
                             FROM
                                      `velib_station_min_velib`
                             WHERE
                                      1
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
                             GROUP BY
                                      `stationCode`
							) AS min_Velib
						ON min_Velib.`stationCode` = `velib_station`.`stationCode`
					WHERE
						`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
						and stationHidden = 0
						and  `stationState` = 'Operative'
				  )	,
			`networkNbDock` = (
					SELECT 
						sum(`stationNbBike` + `stationNbEBike`+`nbFreeDock`+`nbFreeEDock`) as nbActivDock
					FROM `velib_station`
					where `stationState` = 'Operative'
				  )
	";
	//echo $r;
	if(!mysqli_query($link, $r))
	{
		printf("Errormessage: %s\n", mysqli_error($link));
	}

// Mise à jour de la table de stats sur les stations actives --> Fin

// mise à jour des infos reseau velib
// dernière mise à jour
$r = " UPDATE `velib_network` SET `Current_Value`=now(),`Max_Value`=now() WHERE `network_key` = 'LastUpdate'";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
}	

// maj nbr station active officiellement
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select count(id) from velib_station where `stationState` = 'operative' and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ), 
	`Max_Value` = GREATEST(Max_Value,(select count(id) from velib_station where `stationState` = 'operative' and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ) ) 
WHERE `network_key` = 'operative_station_nbr' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr station inactive officiellement
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select count(id) from velib_station where `stationState` != 'operative' and `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0 ), 
	`Max_Value` = GREATEST(Max_Value,(select count(id) from velib_station where `stationState` != 'operative' and   `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  )),
	`Min_Value` = LEAST(Min_Value,(select count(id) from velib_station where  `stationState` != 'operative' and  `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)  and stationHidden = 0  )	) 
WHERE `network_key` = 'inactive_station_nbr' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib en stations
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select sum(stationNbBike)+sum(stationNbBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ), 
	`Max_Value` = GREATEST(Max_Value,(select sum(stationNbBike)+sum(stationNbBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ) ) 
WHERE `network_key` = 'velib_nbr' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib en stations depuis le 01/07
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select sum(stationNbBike)+sum(stationNbBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ), 
	`Max_Value` = GREATEST(Max_Value,(select sum(stationNbBike)+sum(stationNbBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ) ) 
WHERE `network_key` = 'velib_nbr2' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib VAE en stations
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select sum(stationNbEBike)+sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ), 
	`Max_Value` = GREATEST(Max_Value,(select sum(stationNbEBike)+sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ) ) 
WHERE `network_key` = 'evelib_nbr' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib VAE en stations depuis le 01/07
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select sum(stationNbEBike)+sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ), 
	`Max_Value` = GREATEST(Max_Value,(select sum(stationNbEBike)+sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  ) ) 
WHERE `network_key` = 'evelib_nbr2' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib en overflow
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select sum(stationNbBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  )
WHERE `network_key` = 'velib_nbr_overflow' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib VAE en overflow
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = (select sum(stationNbEBikeOverflow) from velib_station where `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) and stationHidden = 0  )
WHERE `network_key` = 'evelib_nbr_overflow' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

if($debug)
{
error_log( date("Y-m-d H:i:s")." - exit: ".$velibExit."(".$EvelibExit.") - return: ".-$velibReturn."(".-$EvelibReturn.")");
}

// maj nbr velib utilisés
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = GREATEST(0,`Current_Value` + $velibExit - $EvelibExit + $velibReturn - $EvelibReturn),
	`Max_Value` = GREATEST(Max_Value,GREATEST(0,`Current_Value` + $velibExit - $EvelibExit + $velibReturn - $EvelibReturn)),
	`Min_Value` = LEAST(Min_Value,GREATEST(0,`Current_Value` + $velibExit - $EvelibExit + $velibReturn - $EvelibReturn))
WHERE `network_key` = 'nbrVelibUtilises' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}

// maj nbr velib VAE utilisés
$r = 
"UPDATE `velib_network` 
SET 
	`Current_Value` = GREATEST(0,`Current_Value` + $EvelibExit + $EvelibReturn),
	`Max_Value` = GREATEST(Max_Value,GREATEST(0,`Current_Value` + $EvelibExit + $EvelibReturn)),
	`Min_Value` = LEAST(Min_Value,GREATEST(0,`Current_Value` + $EvelibExit + $EvelibReturn))
WHERE `network_key` = 'nbrEVelibUtilises' ";
if(!mysqli_query($link, $r))
{
	printf("Errormessage: %s\n", mysqli_error($link));
	//echo $r;
}



echo "</br>data updated";

// mise en cache des données pour l'api dans la session sql du parser pour reduire le nbr de connexions sql
include "./../inc/sqlQuery.inc.php";
$query = getapiQuery_web(3);	
if ($result = mysqli_query($link, $query)) 
{
	if (mysqli_num_rows($result)>0)
	{
		$n = 1;
		$size = mysqli_num_rows($result);
		$resultArray;

		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			$resultArray[]=$row;
			$n = $n+1;			
		}	

		ob_start();
		echo json_encode($resultArray, JSON_HEX_APOS);
		$newPage = ob_get_contents();
		updatePageInCache("stationList.api."."web"."-"."3".".json", $newPage);
		ob_end_clean();
		
		//error_log( date("Y-m-d H:i:s")." - données d'api mise en cache par le parser - v="."web"." d=3");
	}
}

$query = getapiQuery_web(2);	
if ($result = mysqli_query($link, $query)) 
{
	if (mysqli_num_rows($result)>0)
	{
		$n = 1;
		$size = mysqli_num_rows($result);
		$resultArray;

		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			$resultArray[]=$row;
			$n = $n+1;			
		}	

		ob_start();
		echo json_encode($resultArray, JSON_HEX_APOS);
		$newPage = ob_get_contents();
		updatePageInCache("stationList.api."."web"."-"."2".".json", $newPage);
		ob_end_clean();
		
		//error_log( date("Y-m-d H:i:s")." - données d'api mise en cache par le parser - v="."web"." d=2");
	}
}


mysqlClose($link);
InvalidCache();
velibAPIParser_RemoveLock();

// 3 : opérations sur le fichier...
if(fputs($openLogFile, $logstring)===FALSE)
echo("write log error");

// 4 : quand on a fini de l'utiliser, on ferme le fichier
fclose($openLogFile);

error_log(date("Y-m-d H:i:s")." - velibAPIParser - stop");


?>

</body>
</html>