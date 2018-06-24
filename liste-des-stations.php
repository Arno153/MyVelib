<!DOCTYPE html>
<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if( isCacheValid('liste-des-stations.php') and isCacheValid('lastUpdateText') )
	{
		$cacheValide = true;
		
	}
	else
	{
		$cacheValide = False;
		$link = mysqlConnect();
	}
?>	

<html lang="fr">
  <head>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-113973828-2"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'UA-113973828-2');
	</script>
  
    <title>Velib Paris - Stations disponible, ouvertures, nombre de velib.... (site officieux)</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Deploiment des stations du nouveau velib 2018, nouvelles stations, nouveaux vélo, stations qui fonctionnent vraiement..." />
	<meta name="keywords" content="velib, velib 2018,  velib2018, velib 2, cartes, station, vélo, paris, fonctionnent, HS, en panne" />
	<meta name="robots" content="index, follow">
	
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<meta name="theme-color" content="#ffffff">	
	
	<link rel="stylesheet" media="all" href="./css/joujouVelib.css?<?php echo filemtime('./css/joujouVelib.css');?>">
	<script src="./inc/sorttable.js"></script>
  </head>
  <body>
  	<?php
		$lofFile='./.maintenance';
		if(file_exists ($lofFile) )
		{
			echo 
			"
			<div class='maintenance'>
				<!-- !!! Mode maintenance actif !!! -->
					Les données diffusées actuellement par velib métropole présentent des variations cycliques du nombre de vélo en station probablement non representatives des mouvements réels.
			</div>	
			";
		}
	?>
	<nav class="navbar bg-light">
		<b>
		  <a class="nav-link" href="./">Accueil</a>
		  <a class="nav-link" href="./carte-des-stations.php">Carte</a>
		  <a class="nav-link" href="./liste-des-stations.php">Liste des stations</a>
		</b>  

		<?php
			echo "<div class='nav-refresh'>(Dernière collecte: ";
			if($cacheValide == true)
				getPageFromCache('lastUpdateText');
			else
			{
				ob_start();
				echo getLastUpdate($link);
				$newPage = ob_get_contents();
				updatePageInCache('lastUpdateText', $newPage);
				ob_end_clean(); 
				echo $newPage;				
			}
			echo ")</div>";					
		?>	
		<div class="nav-refresh">Contact: 	
		<a href="https://twitter.com/arno152153">
			<img border="0" alt="Twitter" src="https://abs.twimg.com/favicons/favicon.ico" width="15px" height="15px">
		</a>
	</div>		
    </nav>

	<div class="widget">
		<h1 class="searchable-widget-title">Toutes les stations</h1>
		<a href="./api/gpxStationList.api.php">
			<input type="button" id="gpx" value="Gpx Export" />
		</a>
		
		<!--<input type="button" id="expeModeButtonOn" value="Expé On" onclick="expeModeOnOff()" /> -->
		<input type="text" id="allStationsSearchInput" onkeyup="allStationsSearch()" placeholder="Rechercher...">
	<?php
		if($cacheValide == true)
			getPageFromCache('liste-des-stations.php');
		else
		{
			ob_start();			
			// debut mise en cache
			if ($result = getAllStationList($link)) {
					if (mysqli_num_rows($result)>0)
					{

							echo "<TABLE id='allStations' class='table-compact sortable'> ";
							
							echo "<TR>";
							echo "<TH>";
							echo "Code";					
							echo "</TH><TH>";
							echo "Station";
							echo "</TH><TH hidden=true>";
							echo "Adresse";
							echo "</TH><TH>";							
							echo "Status";
							echo "</TH><TH>";
							echo "Signalé<br>HS";
							echo "</TH><TH>";
							echo "Dock <br>libre";
							echo "</TH><TH>";
							echo "Nb<br> Velib";
							echo "</TH><TH>";
							echo "Nb<br>VAE";
							echo "</TH><TH class='adapativeHide2'>";
							echo "Nb Velib <br>Overflow";
							echo "</TH><TH class='adapativeHide2'> ";
							echo "Nb VAE <br>Overflow";
							echo "</TH><TH>";
							echo "Dernier <br>mouvement";	
							echo "</TH><TH>";					
							echo "Dernier <br>retrait";							
							echo "</TH><TH class='adapativeHide2'>";
							echo "Durée depuis le <br>dernier mouvement";					
							echo "</TH><TH>";
							echo "Date <br>d'ajout";
							echo "</TH><TH>";
							echo "Date <br>d'activation";		
							echo "</TH><TH class='expe'>";
							echo "TimeSince <br>LastComeBack";						
							echo "</TH><TH  class='expe'>";
							echo "LastUpdate <br>BefComeBack";				
							echo "</TH><TH  class='expe'>";
							echo "inactivity duration <br>BefComeBack ";			
							echo "</TH><TH  class='expe'>";
							echo "DM2OUT";
							echo "</TH><TH  class='expe'>";
							echo "DM2IN";
							echo "</TH>";
							echo "</TR>";
							
							$c = 1;
							
							// ----- comment 
							/*
							echo "					
							<tr style='display: none;'><td>00000</td><td><a href='carte-des-stations.php?lat=48.0&amp;lon=2.0&amp;zoom=17'>Ce tableau n'a pas vocation à servir d'API Velib officieuse...</a></td><td>aaa</td><td>4</td><td>1</td><td>28</td><td>0</td><td>0</td><td>18/02/2018 18:57</td><td>18/02/2018 18:55</td><td>00:00:57</td><td>25/01/2018</td><td>25/01/2018</td><td class='' style='display: none;'></td><td class='' style='display: none;'></td><td class='' style='display: none;'></td><td class='' style='display: none;'></td><td class='' style='display: none;'></td></tr>
							";
							echo "					
							<tr style='display: none;'><td>00000</td><td><a href='carte-des-stations.php?lat=48.0&amp;lon=2.0&amp;zoom=17'>En tout cas pas sans m'en avoir parlé --> Twitter: @arno152153</a></td><td>En tout cas pas sans m'en avoir parlé</td><td>4</td><td>1</td><td>28</td><td>0</td><td>0</td><td>18/02/2018 18:57</td><td>18/02/2018 18:55</td><td>00:00:57</td><td>25/01/2018</td><td>25/01/2018</td><td class='' style='display: none;'></td><td class='' style='display: none;'></td><td class='' style='display: none;'></td><td class='' style='display: none;'></td><td class='' style='display: none;'></td></tr>
							";					
							*/
							// ----- end comment
							
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
							{
								if($row["hourdiff"]<1){
									echo "<TR  style=background-color:GreenYellow>";
								}
								elseif ($row["hourdiff"]<3){
									echo "<TR  style=background-color:yellow>";
								}
								elseif($row["hourdiff"]<12){
									echo "<TR  style=background-color:orange>";						
								}
								else{
									echo "<TR  style=background-color:Tomato>";						
								}	
								echo "<TD>";
								echo $row["stationCode"];
								//echo $c;
								echo "</TD>";
								echo "<TD><a href='carte-des-stations.php?lat=".$row["stationLat"]."&lon=".$row["stationLon"]."&zoom=17'>".$row["stationName"]."</a>";
								if($row["stationSignaledElectrified"]=='1')
								{
									echo  '<img src="./images/electified.png" width="20">';
								}
								echo "</TD>";	
								echo "<TD hidden=true>";
								echo $row["stationAdress"];
								echo "</TD>";
								echo "<TD>";
								if($row["stationState"]=="Operative")
								{
									echo "En service";
								}
								elseif($row["stationState"]=="Work in progress")
								{
									echo "En travaux";
								}
								Elseif ($row["stationState"]=="Close")
								{
									echo "Fermée";
								}						
								else
								{
									echo $row["stationState"];
								}	
								echo "</TD><TD>";
								/*echo $row["stationLat"];
								echo "</TD><TD>";
								echo $row["stationLon"];
								echo "</TD><TD>";*/
								//echo $row["stationNbEDock"];
								//echo "</TD><TD>";
								
								if($row["stationSignaleHS"]=="1")	{ 	echo "Oui"; 	}						
								else 								{ 	echo "Non";		}
								echo "</TD><TD>";						
								echo $row["nbFreeEDock"];
								echo "</TD><TD>";
								echo $row["stationNbBike"];
								echo "</TD><TD>";
								echo $row["stationNbEBike"];
								echo "</TD><TD class='adapativeHide2'>";

								echo $row["stationNbBikeOverflow"];
								echo "</TD><TD class='adapativeHide2'>";
								echo $row["stationNbEBikeOverflow"];	
								echo "</TD><TD>";
								echo $row["stationLastChange"];	
								echo "</TD><TD>";					
								echo $row["stationLastExit"];							
								echo "</TD><TD class='adapativeHide2'>";
								echo $row["timediff"];
								echo "</TD><TD>";					
								echo $row["stationInsertedInDb"];
								echo "</TD><TD>";					
								echo $row["stationOperativeDate"];		
								echo "</TD><TD class='expe'>";					
								echo $row["timeSinceLastComeBack"];
								echo "</TD><TD class='expe'>";					
								echo $row["stationLastChangeAtComeBack"];
								echo "</TD><TD class='expe'>";					
								echo $row["stationUnavailableFor"];		
								echo "</TD><TD class='expe'>";					
								echo $row["stationAvgHourBetweenExit"];	
								echo "</TD><TD class='expe'>";					
								echo $row["stationAvgHourBetweenComeBack"];							
								echo "</TD>";						
								echo "</TR>";
								
								$c=$c+1;
							}
							echo "</TABLE> ";
							
					}
			}
			//mysqli_close($link);
			mysqlClose($link);
			
			//fin mise en cache
			$newPage = ob_get_contents(); //recup contenu à cacher
			updatePageInCache('liste-des-stations.php', $newPage); //mise en cache
			ob_end_clean(); //ménage cache memoire
			echo $newPage;	//affciahge live
		}
	?>
	</div>
	<div class="disclaimer">
	<b>Ce site n'est bien évidement pas un site officiel de vélib.</b> Les données utilisées proviennent de <a href="www.velib-metropole.fr">www.velib-metropole.fr</a> et appartiennent à leur propriétaire.
	</div>

	<script>
		//document.getElementById('expe').style.display='block';
		  var cols = document.getElementsByClassName('expe');
		  for(i=0; i<cols.length; i++) {
			cols[i].style.display = "none";
		  }

		function allStationsSearch() {
		  // Declare variables
		  var input, filter, table, tr, td, td1, td2, td3, i;
		  input = document.getElementById("allStationsSearchInput");
		  filter = input.value.toUpperCase();
		  table = document.getElementById("allStations");
		  tr = table.getElementsByTagName("tr");

		  // Loop through all table rows, and hide those who don't match the search query
		  for (i = 0; i < tr.length; i++) {
			td = tr[i].getElementsByTagName("td")[0];
			td1 = tr[i].getElementsByTagName("td")[1];
			td2 = tr[i].getElementsByTagName("td")[2];
			td3 = tr[i].getElementsByTagName("td")[3];
			if (td) {
			  if (td.innerHTML.toUpperCase().indexOf(filter) > -1 || td1.innerHTML.toUpperCase().indexOf(filter) > -1 || td2.innerHTML.toUpperCase().indexOf(filter) > -1|| td3.innerHTML.toUpperCase().indexOf(filter) > -1) {
				tr[i].style.display = "";
			  } else {
				tr[i].style.display = "none";
			  }
			}
		  }
		}
		
		function expeModeOnOff()
		{			
			if (document.getElementById("expeModeButtonOn").value=="Expé On")
			{	
				document.getElementById("expeModeButtonOn").value="Expé Off";
				var cols = document.getElementsByClassName('expe');
				for(i=0; i<cols.length; i++) 
				{
					cols[i].style.display = "";
				}
			}
			else{
				document.getElementById("expeModeButtonOn").value="Expé On";
				var cols = document.getElementsByClassName('expe');
				for(i=0; i<cols.length; i++) 
				{
					cols[i].style.display = "none";
				}
			}
		}
	</script>
	
	<div id="mypub">
		<iframe id="gads" src="./inc/ads.inc.html" width="100%" height="600px" />  	
	</div>
</body>
</html>