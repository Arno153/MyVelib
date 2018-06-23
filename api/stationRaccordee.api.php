<?php
include "./../inc/mysql.inc.php";
include "./../inc/cacheMgt.inc.php";
$link = mysqlConnect();
	
if(isset($_GET['stationCode']) and isset($_GET['electrified']))
{
$stationCode = $_GET['stationCode'];
$stationCode = strip_tags($stationCode);
$stationCode = stripslashes($stationCode);	
$stationCode = mysqli_real_escape_string($link, $stationCode);
$stationCode = trim($stationCode);

$stationElectrified = $_GET['electrified'];
$stationElectrified = strip_tags($stationElectrified);
$stationElectrified = stripslashes($stationElectrified);
$stationElectrified = mysqli_real_escape_string($link, $stationElectrified);
$stationElectrified = trim($stationElectrified);
}


if(isset($stationCode) and isset($stationElectrified))
{
	if ($result = mysqli_query($link, "SELECT id FROM velib_station where stationCode = '$stationCode'")) 
	{
		if (mysqli_num_rows($result)==1)
		{
			if($stationElectrified == 'true')
			{	
			$r = "UPDATE `velib_station` 
				SET 				
					`stationSignaledElectrified` = 1, 
					`stationSignaledElectrifiedDate`  = now()
				WHERE `stationCode`='$stationCode'";
			}
			elseif($stationElectrified == 'false')
			{	
			$r = "UPDATE `velib_station` 
				SET 				
					`stationSignaledElectrified` = 0, 
					`stationSignaledElectrifiedDate`  = NULL
				WHERE `stationCode`='$stationCode'";
			}
			else
			{
				mysqlClose($link);
				echo "ko&".$stationCode."&Echec du signalement";
				exit;
			}
				
				
			if(!mysqli_query($link, $r))
			{
				echo "ko&".$stationCode."&Echec du signalement";
				//printf("Errormessage: %s\n", mysqli_error($link));
			}	
			else echo "ok&".$stationCode."&Signalement Enregistré";	
		}
		else echo "ko&".$stationCode."&Echec du signalement"; 
	}
}
else echo "ko&".$stationCode."&Echec du signalement";

mysqlClose($link);
InvalidCache();
?>
