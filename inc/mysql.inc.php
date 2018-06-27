<?php

	function mysqlConnect()
	{
		
		//DB parameters - prod
		
		$server = 'server';
		$user = "user";
		$password = "password";
		$db = "velib";
		
		//DB parameters - dev
		/*
		$server = '127.0.0.1';
		$user = "root";
		$password = "";
		$db = "velib";	
		*/
		
		
		//DB connect
		@$link = mysqli_connect($server, $user, $password, $db);
		if (!$link) {
			error_log(date("Y-m-d H:i:s")." - Unable to connect mysql :".mysqli_connect_errno());
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 10');//300 seconds
			include 'maintenance.html';			
			exit;
		}
		else return $link;
	}

	function mysqlClose($link)
	{
		mysqli_close($link);
	}
	
	function getLastUpdate($link)
	{	
		if ($result = mysqli_query($link, "SELECT DATE_FORMAT(`Current_Value`,'%d/%m/%Y %H:%i' ) as Current_Value FROM `velib_network` WHERE `network_key` = 'LastUpdate'")) 
		{
			if (mysqli_num_rows($result)>0)
			{
				$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
				return $row["Current_Value"];
			}
		} 
	}
	
	function getStationCount($link)
	{
		$query = 
		"
		select 
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'inactive_station_nbr') stations,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'inactive_station_nbr') stations_max,
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'operative_station_nbr') stations_active,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'operative_station_nbr') stations_active_max
		from `velib_network`
		LIMIT 1
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
	}

	function getStationCountByOperativeDate($link)
	{

		$query = 
		"
			SELECT 
				count(`id`) as nbStationWeek, 
				date_format(`stationOperativeDate`, '%v') as week
			FROM `velib_station` 
			where stationState = 'Operative' 
				and stationHidden = 0
			group by date_format(`stationOperativeDate`, '%v')		
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;

		
	}
	
	function getRentalByDate($link)
	{

		$query = 
		"
			SELECT
					 `date`,
					 SUM(`nbrVelibExit`) nbLocation
			FROM
					 `velib_activ_station_stat`
			WHERE
					 `date` NOT IN
					 (
							SELECT DISTINCT
								   `date`
							FROM
								   `velib_activ_station_stat`
							WHERE
								   `nbrVelibExit` > 5000
								   AND DATE       < DATE(NOW())
					 )
					 AND DATE < DATE(NOW() )
			GROUP BY
					 `date`
			ORDER BY
					 `date` 	
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;

		
	}
	
	function getStationCountByLastEvent($link, $event) //$event : any date : `stationLastChange`, `stationLastExit`, .... 
	{
		$query =  
		"
		SELECT count(`id`) as nbs, `stationState`, 'moins d\'une heure' as periode
		FROM `velib_station` 
		where ".$event." > DATE_ADD(NOW(), INTERVAL -1 HOUR) 
				and stationHidden = 0 
		group by `stationState`
		union
		SELECT count(`id`) as nbs, `stationState`, '1 à 3 heure' as periode 
		FROM `velib_station` 
		where ".$event." between DATE_ADD(NOW(), INTERVAL -3 HOUR) and DATE_ADD(NOW(), INTERVAL -1 HOUR)  
				and stationHidden = 0 
		group by `stationState`
		union
		SELECT count(`id`) as nbs, `stationState`, '3 à 12 heure' as periode
		FROM `velib_station` 
		where ".$event." between DATE_ADD(NOW(), INTERVAL -12 HOUR) and DATE_ADD(NOW(), INTERVAL -3 HOUR)  
				and stationHidden = 0 		
		group by `stationState`
		union
		SELECT count(`id`) as nbs, `stationState`, 'plus de 12 heure' as periode
		FROM `velib_station` 
		where ".$event." < DATE_ADD(NOW(), INTERVAL -12 HOUR)  
				and stationHidden = 0 
		group by `stationState`			
		";		
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;			
	}
	
	function getActivStationPercentage($link)
	{
		// where `date` > '2018-02-13' --> pour ne pas prendre la première journée de stat qui est incomplète !!!
		$query =  
		"
			SELECT 
				`date` statDate,
				round(avg(`nbStationUpdatedInThisHour`/`nbStationAtThisDate`*100),1) as activePercent,
				round(avg(`nbStationUpdatedLAst3Hour`/`nbStationAtThisDate`*100),1) as activePercent3H,
				round(avg(`nbStationUpdatedLAst6Hour`/`nbStationAtThisDate`*100),1) as activePercent6H
			FROM `velib_activ_station_stat` 
			where `date` > '2018-02-13' 
				and `date` <
						case
							when DATE_FORMAT(now(), '%H') < 16 then DATE_ADD(NOW(), INTERVAL -1 DAY) 
							else now()
						end	
			group by `date`				
		";	

		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;		
	}
	
	function getActivStationPercentage2($link)
	{
		// where `date` > '2018-02-13' --> pour ne pas prendre la première journée de stat qui est incomplète !!!
		$query =  
		"
			SELECT 
				`date` statDate,
				round(avg(`nbStationUpdatedInThisHour`/`nbStationAtThisDate`*100),1) as activePercent,
				round(avg(`nbStationUpdatedLAst3Hour`/`nbStationAtThisDate`*100),1) as activePercent3H,
				round(avg(`nbStationUpdatedLAst6Hour`/`nbStationAtThisDate`*100),1) as activePercent6H
			FROM `velib_activ_station_stat` 
			where `date` > '2018-02-13' 
				and `date` <
						case
							when DATE_FORMAT(now(), '%H') < 18 then DATE_ADD(NOW(), INTERVAL -1 DAY) 
							else now()
						end			
			group by `date`				
		";	

		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;		
	}
	
	function getActivStationPercentageH($link)
	{
		// where `date` > '2018-02-13' --> pour ne pas prendre la première journée de stat qui est incomplète !!!
		$query =  
		"
			SELECT 
				str_to_date(concat(DATE_FORMAT(`date`,'%d/%m/%Y' ), ' ', `heure`, ':00'), '%d/%m/%Y %H:%i')
					as statDate,
				round(avg(`nbStationUpdatedInThisHour`/`nbStationAtThisDate`*100),1) as activePercent,
				round(avg(`nbStationUpdatedLAst3Hour`/`nbStationAtThisDate`*100),1) as activePercent3H,
				round(avg(`nbStationUpdatedLAst6Hour`/`nbStationAtThisDate`*100),1) as activePercent6H
			FROM `velib_activ_station_stat` 
			where `date` > '2018-03-13' 
				and `date` <
						case
							when DATE_FORMAT(now(), '%H') < 18 then DATE_ADD(NOW(), INTERVAL -1 DAY) 
							else now()
						end
			group by str_to_date(concat(DATE_FORMAT(`date`,'%d/%m/%Y' ), ' ', `heure`, ':00'), '%d/%m/%Y %H:%i')			
		";	

		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;		
	}
	
	
	function getVelibCount($link)
	{
		$query = 
		"select 
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'velib_nbr') velibs,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'velib_nbr') velibs_max,
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'evelib_nbr') VAE,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'evelib_nbr') VAE_Max,
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'velib_nbr_overflow') velibs_overflow,
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'evelib_nbr_overflow') VAE_overflow			
		from `velib_network`
		LIMIT 1
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
	}
	
	function getEstimatedVelibCount($link)
	{
		$query = 
		"
		SELECT
          sum(`stationNbBike`         + `stationNbEBike` -        stationMinVelibNDay) as estimatedVelibNumber,
          sum(`stationNbBikeOverflow` + `stationNbEBikeOverflow`- stationVelibMinVelibOverflow) as  estimatedVelibNumberOverflow
		FROM
          `velib_station`
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
                    )
                    AS min_Velib
                    ON
                              min_Velib.`stationCode` = `velib_station`.`stationCode`
		WHERE
          `stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
          and stationHidden = 0
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
	}
	
	function getVelibNbrStats($link){
	
		$query = 
			"
			SELECT 
				`date`,
				min(`networkNbBike`) minVelib,
				max(`networkNbBike`) maxVelib,
				round(avg(`networkNbBike`)) avgVelib,
				min(`networkNbBikeOverflow`) minVelibOverflow,
				max(`networkNbBikeOverflow`) maxVelibOverflow,
				round(avg(`networkNbBikeOverflow`)) avgVelibOverflow,    
				min(`networkEstimatedNbBike`) minEstimatedVelib,
				max(`networkEstimatedNbBike`) maxEstimatedVelib,
				round(avg(`networkEstimatedNbBike`)) avgEstimatedVelib,    
				min(`networkEstimatedNbBikeOverflow`) minEstimatedVelibOverflow,
				max(`networkEstimatedNbBikeOverflow`) maxEstimatedVelibOverflow,
				round(avg(`networkEstimatedNbBikeOverflow`)) avgEstimatedVelibOverflow    
			FROM `velib_activ_station_stat` 
			WHERE 
				`date` > '2018-02-13'
			group by `date`
			order by `date`
			";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
	}
	
	function getMovedStationList($link){
		return getStationList($link, "`stationLocationHasChanged` = 1" , "order by `stationInsertedInDb` desc");
	}		
	
	function getAllStationList($link){
		return getStationList($link, "" , "order by `stationLastChange` desc");
	}
	
	function getNewStationList($link){
		return getStationList($link, "`stationInsertedInDb` > DATE_ADD(NOW(), INTERVAL -5 DAY)" , "order by `stationInsertedInDb` desc");
	}	
	
	//private
	function getStationList($link, $filter, $sort)
	{
		
		$query = 	
		"
			SELECT
				   `id`                                                                                    ,
				   `stationName`                                                                           ,
				   `velib_station`.`stationCode`                                                           ,
				   `stationState`                                                                          ,
				   `stationAdress`                                                                         ,
				   `stationLat`                                                                            ,
				   `stationLon`                                                                            ,
				   `stationNbEDock`                                                                        ,
				   `stationNbBike`                                                                         ,
				   `stationNbEBike`                                                                        ,
				   `nbFreeDock`                                                                            ,
				   `nbFreeEDock`                                                                           ,
				   `stationNbBikeOverflow`                                                                 ,
				   `stationNbEBikeOverflow`                                                                ,
				   DATE_FORMAT(`stationLastChange`,'%d/%m/%Y %H:%i') as stationLastChange                  ,
				   DATE_FORMAT(`stationInsertedInDb`,'%d/%m/%Y' )    as stationInsertedInDb                ,
				   timediff(now() , `stationLastChange`)             as timediff                           ,
				   hour(timediff(now() , `stationLastChange`))       as hourdiff                           ,
				   `stationKioskState`                                                                     ,
				   DATE_FORMAT(`stationOperativeDate`,'%d/%m/%Y' ) as stationOperativeDate                 ,
				   timediff(now() , `stationLastComeBack`)         as timeSinceLastComeBack                ,
				   `stationLastChangeAtComeBack`                                                           ,
				   timediff(`stationLastComeBack` , `stationLastChangeAtComeBack`)as stationUnavailableFor ,
				   DATE_FORMAT(stationLastExit,'%d/%m/%Y %H:%i')                  as stationLastExit       ,
				   timediff(sysdate() , `stationLastExit`)                        as lastExistDiff         ,
				   hour(timediff(now() , `stationLastExit`))                      as hourLastExistDiff     ,
				   stationAvgHourBetweenExit                                                               ,
				   stationAvgHourBetweenComeBack                                                           ,
				   stationSignaleHS                                                                        ,
				   DATE_FORMAT(`stationSignaleHSDate`,'%d/%m/%Y') as stationSignaleHSDate                  ,
				   DATE_FORMAT(`stationSignaleHSDate`,'%H:%i')    as stationSignaleHSHeure                 ,
				   4 - stationSignaleHSCount                      as nrRetraitDepuisSignalement            ,
				   `stationSignaledElectrified`                                                            ,
				   `stationSignaledElectrifiedDate`                                                        ,
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
									 and `stationStatDate` > DATE_ADD(NOW(), INTERVAL -4 DAY)
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
				   and stationHidden           = 0
		";
		
		if($filter!="")
			$query= $query." and ".$filter;		
		
		if($sort!="")
			$query= $query." ".$sort;
		
		return $mysqliResult = mysqli_query($link, $query);		
	}



?>