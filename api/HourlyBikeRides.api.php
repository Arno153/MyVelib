<?php 

	include "./../inc/mysql.inc.php";
	include "./../inc/cacheMgt.inc.php";
	
	header('Content-type: application/json');

	if(isCacheValidToday("HourlyBikeRides.api.json"))
	{
		//load from cach
		getPageFromCache("HourlyBikeRides.api.json");
	}
	else
	{
		//DB connect & retrieve data
		$link = mysqlConnect();
		if (!$link) {
			echo "Error: Unable to connect to MySQL." . PHP_EOL;
			echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			exit;
		}

		$result = getMonthlyRentalByHour($link);
		
		if ($result) 
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
				echo json_encode($resultArray,  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
				$newPage = ob_get_contents();
				updatePageInCache("HourlyBikeRides.api.json", $newPage);
				ob_end_clean(); 
				echo $newPage;	
				
			}
		}
		else
		{
			echo mysqli_error( $link );				
		}

		mysqlClose($link);
	}
?> 