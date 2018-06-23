<?php
include "./../inc/mysql.inc.php";
include "./../inc/cacheMgt.inc.php";

$link = mysqlConnect();
	if (!$link) {
		echo "Error: Unable to connect to MySQL." . PHP_EOL;
		echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
		echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
		exit;
	}
	
$stationCode = $_GET['stationCode'];
$stationCode = strip_tags($stationCode);
$stationCode = stripslashes($stationCode);	
$stationCode = mysqli_real_escape_string($link, $stationCode);
$stationCode = trim($stationCode);

$HSYesNo = $_GET['HS'];
$HSYesNo = strip_tags($HSYesNo);
$HSYesNo = stripslashes($HSYesNo);	
$HSYesNo = mysqli_real_escape_string($link, $HSYesNo);
$HSYesNo = trim($HSYesNo);

if(isset($stationCode))
{
	if ($result = mysqli_query($link, "SELECT id FROM velib_station where stationCode = '$stationCode'")) 
	{
		if (mysqli_num_rows($result)==1)
		{
			if($HSYesNo=="true")
			{			
			$r = "UPDATE `velib_station` 
				SET 							
					`stationSignaleHS` = 1, 
					`stationSignaleHSDate`  = now(),
					`stationSignaleHSCount` = 4
				WHERE `stationCode`='$stationCode'";
			}
			elseif ($HSYesNo=="false")
			{
			$r = "UPDATE `velib_station` 
			SET 							
				`stationSignaleHS` = 0, 
				`stationSignaleHSDate`  = NULL,
				`stationSignaleHSCount` = 0
			WHERE `stationCode`='$stationCode'";
			}
			
			if(!mysqli_query($link, $r))
			{
				echo "ko&".$stationCode."&".$HSYesNo."&Echec du signalement";
				//printf("Errormessage: %s\n", mysqli_error($link));
			}	
			
			else echo "ok&".$stationCode."&".$HSYesNo."&Signalement Enregistré";	
		}
		else echo "ko&".$stationCode."&".$HSYesNo."&Echec du signalement"; 
	}
}
else echo "ko&".$stationCode."&".$HSYesNo."&Echec du signalement";

mysqlClose($link);
InvalidCache();
?>
