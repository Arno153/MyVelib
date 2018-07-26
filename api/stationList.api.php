<?php
	include "./../inc/cacheMgt.inc.php";
	include "./../inc/mysql.inc.php";
	include "./../inc/sqlQuery.inc.php";
	
	header('Content-type: application/json');
	
	if(isset($_GET['v'])){
		$version = $_GET['v'];
		$version = strip_tags($version);
		$version = stripslashes($version);		
		//$version = mysqli_real_escape_string($link, $version); //supprimer pour ne pas ouvrir une connexion sql lors de la rucp en cache
		$version = trim($version);
	}
	else {
		$version = "v1";
	}
	
	if(isset($_GET['d'])){
		$dureeEstimation = $_GET['d'];
		$dureeEstimation = strip_tags($dureeEstimation);
		$dureeEstimation = stripslashes($dureeEstimation);		
		$dureeEstimation = trim($dureeEstimation);
		if(!is_numeric($dureeEstimation))
			$dureeEstimation = 0;
	}
	else {
		$dureeEstimation = 0;
	}	
	
	
	//error_log( date("Y-m-d H:i:s")." - v=".$version." d=".$dureeEstimation);
	
	switch($version)
	{
		case "v2" : 
			$query = "
				SELECT 
					`stationCode`,
					`stationName`,
					`stationState`,
					`stationLat`,
					`stationLon`, 
					`stationKioskState`,	
					stationAdress, 	
					`stationNbEDock`, 
					`nbFreeDock`, 
					`nbFreeEDock`,	
					`stationNbBike`, 
					`stationNbEBike`,
					`stationNbBikeOverflow`,
					`stationNbEBikeOverflow`,
					stationInsertedInDb ,
					stationOperativeDate,
					stationLastChange,				   
					stationLastExit,
					stationSignaleHS,
					stationSignaleHSDate,
					`stationSignaledElectrified` as stationConnected, 
					`stationSignaledElectrifiedDate` as stationConnectionDate
				FROM `velib_station` 
				where 
					`stationNbEDock`+
					  `stationNbBike`+
					  `stationNbEBike`+
					  `nbFreeDock`+
					  `nbFreeEDock` > 0 
					and stationHidden = 0
			";
			break;
		case "v1" :
			$query = "
				SELECT 
					`velib_station`.`stationCode`,
					`stationName`,
					`stationState`,
					`stationLat`,
					`stationLon`, 
					`stationKioskState`,	
					stationAdress, 	
					`stationNbEDock`, 
					`nbFreeDock`, 
					`nbFreeEDock`,	
					`stationNbBike`, 
					`stationNbEBike`,
					`stationNbBikeOverflow`,
					`stationNbEBikeOverflow`,
					stationInsertedInDb ,
					stationOperativeDate,
					stationLastChange,
					timediff(now() , `stationLastChange`)             as timediff                           ,					
					hour(timediff(now() , `stationLastChange`))       as hourdiff ,
					stationLastExit,
				   timediff(sysdate() , `stationLastExit`)                        as lastExistDiff         ,					
					hour(timediff(now() , `stationLastExit`))                      as hourLastExistDiff     ,
					stationSignaleHS,
				   DATE_FORMAT(`stationSignaleHSDate`,'%d/%m/%Y') as stationSignaleHSDate                  ,
				   DATE_FORMAT(`stationSignaleHSDate`,'%H:%i')    as stationSignaleHSHeure                 ,
				   10 - stationSignaleHSCount              as nrRetraitDepuisSignalement						,
					`stationSignaledElectrified` as stationConnected, 
					`stationSignaledElectrifiedDate` as stationConnectionDate,
				   stationNbBike + stationNbEBike + stationNbBikeOverflow + stationNbEBikeOverflow  as station_nb_bike,
				   stationMinVelibNDay					
				FROM 
					`velib_station` LEFT JOIN 
					   (
								SELECT
										 `stationCode`,
										 min(`stationVelibMinVelib`) as stationMinVelibNDay
								FROM
										 `velib_station_min_velib`
								wHERE
										 1
										 and `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
								group by
										 `stationCode`
					   ) as min_Velib 
					   ON min_Velib.`stationCode`  = `velib_station`.`stationCode` 
				where 
					`stationNbEDock`+
					  `stationNbBike`+
					  `stationNbEBike`+
					  `nbFreeDock`+
					  `nbFreeEDock` > 0 
					and stationHidden = 0
			";
			break;
		case "web" :
			if($dureeEstimation ==0) $dureeEstimation = 3; //si donnée off, l'api envoie l'estimation 3J pour affichage dans l'infoWindows
			$query = getapiQuery_web($dureeEstimation);		
			break;
		case "heatmap" :
			$query = getapiQuery_heatmap($dureeEstimation);			
			break;
	}
	
	if(isCacheValid("stationList.api.".$version."-".$dureeEstimation.".json"))
	{
		//load from cach
		//echo "load from cache";
		getPageFromCache("stationList.api.".$version."-".$dureeEstimation.".json");
		
		if($version=="web")
			error_log( date("Y-m-d H:i:s")." - données d'api obtenue depuis le cache - v=".$version." d=".$dureeEstimation);
	}
	else
	{
		//DB connect

		$link = mysqlConnect();
		
		if (!$link) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}
		
		if (isset($query))
		{

			//echo $query;
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
					updatePageInCache("stationList.api.".$version."-".$dureeEstimation.".json", $newPage);
					ob_end_clean(); 
					echo $newPage;	

					if($version=="web")
						error_log( date("Y-m-d H:i:s")." - données d'api obtenue depuis la base - v=".$version." d=".$dureeEstimation);
				}
			}
			else
			{
				//echo mysqli_error( $link );				
			}
		
		}
		else echo "empty";
		
		mysqlClose($link);
	}	
?>