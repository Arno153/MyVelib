<?php
	function mysqlConnect()
	{
		include "config.inc.php";					
		//DB connect
		@$link = mysqli_connect($server, $user, $password, $db);
		if (!$link) {
			error_log(date("Y-m-d H:i:s")." - Unable to connect mysql :".mysqli_connect_errno()." - ".mysqli_connect_error());
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			header('Retry-After: 30');//300 seconds
			if(dirname($_SERVER['PHP_SELF'])!="/cron")
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

		/*$query = 
		"
			SELECT 
				count(`id`) as nbStationWeek, 
				date_format(`stationOperativeDate`, '%Y-%v') as week
			FROM `velib_station` 
			where stationState = 'Operative' 
				and stationHidden = 0
			group by date_format(`stationOperativeDate`, '%Y-%v')		
		";*/
		
		
		$query = 
		"
			SELECT
				COUNT(vss.id) AS nbStationWeek,
				STR_TO_DATE(
					CONCAT(vss.week, ' Monday'),
					'%x%v %W'
				) AS week
			FROM
				(
				SELECT
					velib_station_status.`id`,
					DATE_FORMAT(MIN(`stationStatusDate`),
					'%Y%v') AS WEEK
				FROM
					`velib_station_status`
				WHERE
					`velib_station_status`.stationState = 'Operative'
				GROUP BY
					`velib_station_status`.id
			) vss
			INNER JOIN velib_station vs ON
				vs.id = vss.id
			WHERE
				vs.stationHidden = 0 AND vs.stationState = 'Operative' AND vs.`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR)
			GROUP BY
				STR_TO_DATE(
					CONCAT(vss.week, ' Monday'),
					'%x%v %W'
				)
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
					 SUM(`nbrVelibExit`) nbLocation,
					 SUM(`nbrEVelibExit`) nbLocationVAE,
					 SUM(`nbrVelibExit`) - SUM(`nbrEVelibExit`) nbLocationMeca
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
								   AND DATE       < '2018-08-01'
					 )
					 AND DATE < DATE(NOW() )
					and `date` > '2018-02-13'
			GROUP BY
					 `date`
			ORDER BY
					 `date` 	
		";
		
		/* La clause ci dessous de la requette permet d'éliminer de la série les incidents du printemps qui par leurs oscillation donnait des chiffres abérant
							 `date` NOT IN
					 (
							SELECT DISTINCT
								   `date`
							FROM
								   `velib_activ_station_stat`
							WHERE
								   `nbrVelibExit` > 5000
								   AND DATE       < '2018-08-01'
					 )
		*/
		
		
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
		{
			error_log(date("Y-m-d H:i:s")." - invalid request :".mysqli_error( $link ));
			return False;			
		}
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
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'velib_nbr2') velibs_max_072018,
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'evelib_nbr') VAE,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'evelib_nbr') VAE_Max,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'evelib_nbr2') VAE_Max_072018,
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
	
	function getEstimatedVelibCount2D($link)
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
                                      AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -2 DAY)
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
	
	function getEstimatedVelibInUse($link)
	{
		$query = 
		"select 
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'nbrVelibUtilises') velibInUse,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'nbrVelibUtilises') maxVelibInUse,			
			(SELECT current_value FROM `velib_network` WHERE `network_key` = 'nbrEVelibUtilises') eVelibInUse,
			(SELECT max_value FROM `velib_network` WHERE `network_key` = 'nbrEVelibUtilises') maxEVelibInUse
		from `velib_network`
		LIMIT 1
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
				min(`networkNbEBike`) minEVelib,
				max(`networkNbEBike`) maxEVelib,
				round(avg(`networkNbEBike`)) avgEVelib,				
				min(`networkNbBikeOverflow`) minVelibOverflow,
				max(`networkNbBikeOverflow`) maxVelibOverflow,
				round(avg(`networkNbBikeOverflow`)) avgVelibOverflow,    
				min(`networkEstimatedNbBike`) minEstimatedVelib,
				max(`networkEstimatedNbBike`) maxEstimatedVelib,
				round(avg(`networkEstimatedNbBike`)) avgEstimatedVelib, 
				min(`networkEstimatedNbEBike`) minEstimatedEVelib,
				max(`networkEstimatedNbEBike`) maxEstimatedEVelib,
				round(avg(`networkEstimatedNbEBike`)) avgEstimatedEVelib, 				
				min(`networkEstimatedNbBikeOverflow`) minEstimatedVelibOverflow,
				max(`networkEstimatedNbBikeOverflow`) maxEstimatedVelibOverflow,
				round(avg(`networkEstimatedNbBikeOverflow`)) avgEstimatedVelibOverflow,
				min(`networkNbBike` - `networkEstimatedNbBike`) minEstimatedUnavailableVelib,
				max(`networkNbBike` - `networkEstimatedNbBike`) maxEstimatedUnavailableVelib,
				round(avg(`networkNbBike`-`networkEstimatedNbBike`)) avgEstimatedUnavailableVelib				
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
	
	function getEVelibNbrStats($link){
	
		$query = 
			"
			SELECT 
				`date`,
				min(`networkNbEBike`) minVelib,
				max(`networkNbEBike`) maxVelib,
				round(avg(`networkNbEBike`)) avgVelib,
				min(`networkNbEBikeOverflow`) minVelibOverflow,
				max(`networkNbEBikeOverflow`) maxVelibOverflow,
				round(avg(`networkNbEBikeOverflow`)) avgVelibOverflow,    
				min(`networkEstimatedNbEBike`) minEstimatedVelib,
				max(`networkEstimatedNbEBike`) maxEstimatedVelib,
				round(avg(`networkEstimatedNbEBike`)) avgEstimatedVelib,    
				min(`networkEstimatedNbEBikeOverflow`) minEstimatedVelibOverflow,
				max(`networkEstimatedNbEBikeOverflow`) maxEstimatedVelibOverflow,
				round(avg(`networkEstimatedNbEBikeOverflow`)) avgEstimatedVelibOverflow,
				min(`networkNbEBike` - `networkEstimatedNbEBike`) minEstimatedUnavailableVelib,
				max(`networkNbEBike` - `networkEstimatedNbEBike`) maxEstimatedUnavailableVelib,
				round(avg(`networkNbEBike`-`networkEstimatedNbEBike`)) avgEstimatedUnavailableVelib				
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
	
	function getTodayAnd10bigestDay($link)
	{
		$query=
		"
		SELECT
			 a.date,
			 heure,
			 SUM(`nbrVelibExit`) nbLocation,
			 SUM(`nbrEVelibExit`) nbLocationVAE,
			 SUM(`nbrVelibExit`) - SUM(`nbrEVelibExit`) nbLocationMeca
		FROM
			 `velib_activ_station_stat` a inner join
			 (
				SELECT date
				FROM velib_activ_station_stat
				WHERE
					date NOT IN
					(
					SELECT DISTINCT date
					FROM velib_activ_station_stat
					WHERE nbrVelibExit > 5000 AND DATE < '2018-08-01'
					)
					and date <> DATE(NOW() )
				GROUP BY date
				ORDER BY SUM(`nbrVelibExit`) DESC
				limit 5
			) b on a.date = b.date
		GROUP BY
				 a.date, heure
		union
		SELECT
			 c.date,
			 heure,
			 SUM(`nbrVelibExit`) nbLocation,
			 SUM(`nbrEVelibExit`) nbLocationVAE,
			 SUM(`nbrVelibExit`) - SUM(`nbrEVelibExit`) nbLocationMeca
		FROM
			 `velib_activ_station_stat` c
		where c.DATE = DATE(NOW() ) and c.heure!= HOUR(NOW())
		GROUP BY
				 c.date, heure
		ORDER BY
				 date, heure  
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
		
	}

	function getLast15Day($link)
	{
		$query=
		"
		SELECT
			 c.date,
			 heure,
			 SUM(`nbrVelibExit`) nbLocation,
			 SUM(`nbrEVelibExit`) nbLocationVAE,
			 SUM(`nbrVelibExit`) - SUM(`nbrEVelibExit`) nbLocationMeca
		FROM
			 `velib_activ_station_stat` c
		where 
			(c.DATE = DATE(NOW() ) and c.heure!= HOUR(NOW()))
			or
			(
				c.DATE != DATE(NOW() ) 
				and 
				c.DATE > ADDDATE(NOW(), INTERVAL -15 DAY)
			)
		GROUP BY
				 c.date, heure
		ORDER BY
				 date	, heure  
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
		
	}
		
	function getAvailableVelibByCP($link)
	{
		$query=
		"
			SELECT
				stationCP,
				stationCommune,
				count(id) nbStations,
				SUM(`stationNbBike` + `stationNbEBike`) AS officialVelibNumber,
				SUM(`stationNbBikeOverflow` + `stationNbEBikeOverflow`) AS officialVelibNumberOverflow,
				SUM(`stationNbBike` + `stationNbEBike` - stationMinVelibNDay) AS estimatedVelibNumber,
				SUM(`stationNbBikeOverflow` + `stationNbEBikeOverflow` - stationVelibMinVelibOverflow) AS estimatedVelibNumberOverflow,
				sum(`stationNbBike`+`stationNbEBike` + `nbFreeDock`+`nbFreeEDock`) as nbBornes
			FROM velib_station
						LEFT JOIN(
							SELECT 
								`stationCode`,
								MIN(`stationVelibMinVelib` - stationVelibMinVelibOverflow) AS stationMinVelibNDay,
								MIN(stationVelibMinVelibOverflow) AS stationVelibMinVelibOverflow
							FROM `velib_station_min_velib`
							WHERE 1 AND `stationStatDate` > DATE_ADD(NOW(), INTERVAL -3 DAY)
							GROUP BY `stationCode`
						) AS min_Velib ON min_Velib.`stationCode` = `velib_station`.`stationCode`
			WHERE 
				`stationLastView` > DATE_ADD(NOW(), INTERVAL -48 HOUR) 
				AND stationHidden = 0
                and `stationState` not in( 'Close', 'Work in progress')				
			group by stationCP, stationCommune
			order by stationCommune, stationCP
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;
		
	}
	
	function getNetworkRationAtHour($link, $hour)
	{
		$query=
		"
			SELECT 
				`date`,			
				`networkNbBike`+`networkNbBikeOverflow` as networkNbBike,
				`networkEstimatedNbBike`+`networkEstimatedNbBikeOverflow` as networkEstimatedNbBike,
				`networkNbDock`,
				`networkNbDock` / (`networkNbBike`+`networkNbBikeOverflow`) as dockBikeRation,
				`networkNbDock` / (`networkEstimatedNbBike`+`networkEstimatedNbBikeOverflow`) as estimatedDockBikeRatio
			FROM `velib_activ_station_stat`  
			where 
				`heure` = ".$hour."				
			and `networkNbDock` is not null
			ORDER BY `velib_activ_station_stat`.`date`  DESC
		";
		
		$query=
		"		
			SELECT 
				`date`,			
				`networkNbBike`+`networkNbBikeOverflow` as networkNbBike,
				`networkEstimatedNbBike`+`networkEstimatedNbBikeOverflow` as networkEstimatedNbBike,
				`networkNbDock`,
				`networkNbDock` / (`networkNbBike`+`networkNbBikeOverflow`) as dockBikeRation,
				`networkNbDock` / (`networkEstimatedNbBike`+`networkEstimatedNbBikeOverflow`) as estimatedDockBikeRatio
			FROM `velib_activ_station_stat`  
			where 
            (
				(`heure` = ".$hour." and date < DATE_FORMAT(now(), '%Y-%m-%d')	)
                or 
                (`heure` = ".$hour." and date = DATE_FORMAT(now(), '%Y-%m-%d') and DATE_FORMAT(now(), '%H') <> heure)
            )
			and `networkNbDock` is not null
			ORDER BY `velib_activ_station_stat`.`date`  DESC
		";
		
		if ($result = mysqli_query($link, $query)) 
			return $result;
		else	
			return False;

		
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
				   and `stationLastChange` > DATE_ADD(NOW(), INTERVAL -92 DAY)
		";
		
		if($filter!="")
			$query= $query." and ".$filter;		
		
		if($sort!="")
			$query= $query." ".$sort;
		
		return $mysqliResult = mysqli_query($link, $query);		
	}



?>