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
		if($debugVerbose) var_dump($row);
		echo "stationCode: ".$row['stationCode']."<br>";
		echo "stationName: ".$row['stationName']."<br>";
		echo "stationLat: ".$row['stationLat']."<br>";
		echo "stationLon: ".$row['stationLon']."<br>";
		echo "stationAdress: ".$row['stationAdress']."<br>";
		
		/// recupérer l'adresse --> adresse.data.gouv.fr					
		$wsUrl = 'https://api-adresse.data.gouv.fr/reverse/?lat='.$row['stationLat'].'&lon='.$row['stationLon'].'&type=housenumber';
		if($debugVerbose) echo $wsUrl;
		$newStationAdress = "Not Available";
		$newStationCP;
		$newStationCommune = '';
		
		$GeocodeAPIRawData = file_get_contents($wsUrl);
		$GeocodeAPIDataArray = json_decode($GeocodeAPIRawData, true);

		if($debugVerbose)
		{
			echo "vardump</br>";
			var_dump($GeocodeAPIDataArray);	
		}
		$quitter = 0;
		
		if(count($GeocodeAPIDataArray)!=3) //parce que lorsque le quota est atteint la reponse est un array(3)
		{	
			if($debugVerbose) echo "</br> --- --- ---dépiller le retour ws  --- </br>";
			foreach($GeocodeAPIDataArray as $keyL1 => $valueL1)
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
												$newStationAdress = $valueL3['housenumber'].", ".$valueL3['street'].", ".$valueL3['postcode']." ".$valueL3['city'];
												$newStationCP = $valueL3['postcode'];
												$newStationCommune = $valueL3['city'];												
										}
										else
										{
											$newStationAdress = $valueL3['label'];
											if(isset($valueL3['postcode']))
												$newStationCP = $valueL3['postcode'];
											if(isset($valueL3['city']))
												$newStationCommune = $valueL3['city'];	
										}
										
										$newStationAdress = mysqli_real_escape_string($link, $newStationAdress); //ici on à l'adresse
										$newStationCommune = mysqli_real_escape_string($link, $newStationCommune);
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
			
			echo "New Station Adress: ".$newStationAdress."<br>";
			
			$r= 
			"
			UPDATE `velib_station` 
				SET 
					`stationAdress` = left('$newStationAdress',300),
					stationCP = '$newStationCP',
					stationCommune = left('$newStationCommune',255),
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
			echo "<br> something goes wrong with geocode api";
		}
		sleep(1);
	}
}
else
		echo "Nothing to do - No station has move";




//DB disconnect
mysqlClose($link);

?>
