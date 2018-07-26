<?php

function getapiQuery_web($dureeEstimation)
{
	return 
		"
			SELECT 
				concat(`stationName`, '-', `velib_station`.`stationCode`) as station,					
				
				`stationLat`,
				`stationLon`, 
				`stationNbBike`, 					
				`stationNbBikeOverflow`,	
				`stationNbEBike`,
				`stationNbEBikeOverflow`, 
				`nbFreeEDock`,
				`nbFreeDock`,
				timediff(now() , `stationLastChange`)             as timediff                           ,					
				hour(timediff(now() , `stationLastChange`))       as hourdiff ,						
				`stationState`,
				stationAdress,						
				timediff(sysdate() , `stationLastExit`)                        as lastExistDiff         ,					
				hour(timediff(now() , `stationLastExit`))                      as hourLastExistDiff     ,
				`velib_station`.`stationCode`,
				stationSignaleHS,
				DATE_FORMAT(`stationSignaleHSDate`,'%d/%m/%Y') as stationSignaleHSDate                  ,
				DATE_FORMAT(`stationSignaleHSDate`,'%H:%i')    as stationSignaleHSHeure                 ,	
				(case when stationSignaleHS = 1
					then 10 - stationSignaleHSCount
					else 0
				end) as nrRetraitDepuisSignalement,
				`stationSignaledElectrified` as stationConnected, 
				`stationSignaledElectrifiedDate` as stationConnectionDate,					
				stationNbBike + stationNbEBike + stationNbBikeOverflow + stationNbEBikeOverflow  as tot_station_nb_bike,
				(case when stationMinVelibNDay IS NULL
					then stationNbBike+ stationNbEBike
					else stationMinVelibNDay
				end) as stationMinVelibNDay
				,
				(case when stationVelibMinVelibOverflow IS NULL
					then stationNbBikeOverflow+ stationNbEBikeOverflow
					else stationVelibMinVelibOverflow
				end) as stationVelibMinVelibOverflow					
			FROM 
				`velib_station` LEFT JOIN 
				   (
							SELECT
									`stationCode`,
									MIN(`stationVelibMinVelib` - stationVelibMinVelibOverflow) AS stationMinVelibNDay,
									MIN( stationVelibMinVelibOverflow ) AS stationVelibMinVelibOverflow
							FROM
									 `velib_station_min_velib`
							wHERE
									 1
									 and `stationStatDate` > DATE_ADD(NOW(), INTERVAL -'$dureeEstimation' DAY)
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
}

function getapiQuery_heatmap($dureeEstimation)
{
	return
		"
			SELECT
			  vs.`stationCode`,
			  `stationStatDate`,
			  (case when `stationVelibExit` is not null then `stationVelibExit` else 0 end ) as stationVelibExit ,
			  `stationLat`,
			  `stationLon`,
			  `stationState`
			FROM
			  `velib_station` vs 
			  left join 
			  (
				  select * 
				  from `velib_station_min_velib`  
				  where `stationStatDate` between DATE_ADD(NOW(), INTERVAL -'$dureeEstimation'-1 DAY) and DATE_ADD(NOW(), INTERVAL -'$dureeEstimation' DAY)
			   ) vm
				on vs.`stationCode` = vm.`stationCode` 
			WHERE  
			  `stationHidden` = 0  
			order by 1, 2 asc
		";
	
}

?>