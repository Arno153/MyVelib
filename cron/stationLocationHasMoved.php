<?php

include "./../inc/mysql.inc.php";
$debugVerbose = false;

//DB connect
$link = mysqlConnect();
if (!$link) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

if($debugVerbose)
{
	echo "Success: A proper connection to MySQL was made! The my_db database is great." . PHP_EOL;
	echo "<br>";
	echo "Host information: " . mysqli_get_host_info($link) . PHP_EOL;
	echo "<br>";
}


$result = getMovedStationList($link);
if (mysqli_num_rows($result)>0)
{
	while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
	{
		echo "<br>----------------<br>";
		//var_dump($row);
		echo "stationCode: ".$row['stationCode']."<br>";
		echo "stationName: ".$row['stationName']."<br>";
		echo "stationLat: ".$row['stationLat']."<br>";
		echo "stationLon: ".$row['stationLon']."<br>";
		echo "stationAdress: ".$row['stationAdress']."<br>";
		
		/// recupérer l'adresse --> google geocode API					
		$wsUrl = 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$row['stationLat'].','.$row['stationLon'].'&key=API-Key';
		$googleGeocodeAPIRawData = file_get_contents($wsUrl);
		$googleGeocodeAPIDataArray = json_decode($googleGeocodeAPIRawData, true);

		if($debugVerbose)
		{
			echo "vardump</br>";
			var_dump($googleGeocodeAPIDataArray);	
		}
		
		if(count($googleGeocodeAPIDataArray)!=3) //parce que lorsque le quota est atteint la reponse est un array(3)
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
										$newStationAdress = mysqli_real_escape_string($link, $valueL3); //ici on à l'adresse
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
			
			echo "New Station Adress: ".$newStationAdress."<br>";
			
			$r= 
			"
			UPDATE `velib_station` 
				SET 
					`stationAdress` = left('$newStationAdress',300),
					`stationLocationHasChanged` = 0
				where `id`='$row[id]';
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
			
		}
		else
		{
			echo "<br> something goes wrong with google geocode api";
		}
	}
}
else
		echo "Nothing to do - No station has move";




//DB disconnect
mysqlClose($link);

?>
