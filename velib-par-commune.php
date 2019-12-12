<!DOCTYPE html>
<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if( isCacheValid('velib-par-commune.php') and isCacheValid('lastUpdateText') )
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
  
    <title>Velib Paris - Velib disponibles par commune / arrondissement (site officieux)</title>
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
					Mon processus de collecte des données Velib est actuellement perturbé.</br>
					Les données de cette page, peu sensible à la régularité de la collecte, sont affectées localement. 
			</div>	
			";
		}
	include "./inc/menu.inc.php";

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
	echo ")";					
	?>	
	</div>		


	<div class="widget">
		<h1 class="searchable-widget-title">Velib disponibles par commune</h1>
		<a href="./api/velibDispoParCP.api.php" target="_blank">
			<input type="button" id="gpx" value="Export" />
		</a>		
		<!--<input type="button" id="expeModeButtonOn" value="Expé On" onclick="expeModeOnOff()" /> -->
		<input type="text" id="allStationsSearchInput" onkeyup="allStationsSearch()" placeholder="Rechercher...">

	<?php
		if($cacheValide == true)
			getPageFromCache('velib-par-commune.php');
		else
		{
			ob_start();			
			// debut mise en cache
			if ($result = getAvailableVelibByCP($link)) {
					if (mysqli_num_rows($result)>0)
					{

							echo "<TABLE id='allStations' class='table-compact sortable'> ";
							
							echo "<TR>";
							echo "<TH>";
							echo "Commune";					
							echo "</TH><TH>";
							echo "CP";
							echo "</TH><TH>";
							echo "Nombre de<br>stations";
							echo "</TH><TH>";	
							echo "Nombre total<br>de bornes";
							echo "</TH><TH>";									
							echo "Nombre de velib<br>dispo ";
							echo "</TH><TH>";
							echo "Nombre de velib<br>dispo en overflow";
							echo "</TH><TH>";
							echo "Nombre de velib<br>dispo (estimé)";
							echo "</TH><TH>";
							echo "Nombre de Velib<br>dispo en overflow (estimé)";
							echo "</TH><TH>";
							echo "</TH>";
							echo "</TR>";
							
							$c = 1;
							
							
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
							{
								echo "<TR>";
								echo "<TD>";
								echo $row["stationCommune"];
								echo "</TD><TD>";
								echo $row["stationCP"];
								echo "</TD><TD>";
								echo $row["nbStations"];
								echo "</TD><TD>";
								echo $row["nbBornes"];
								echo "</TD><TD>";								
								echo $row["officialVelibNumber"];
								echo "</TD><TD>";
								echo $row["officialVelibNumberOverflow"];
								echo "</TD><TD>";
								echo $row["estimatedVelibNumber"];
								echo "</TD><TD>";
								echo $row["estimatedVelibNumberOverflow"];	
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
			updatePageInCache('velib-par-commune.php', $newPage); //mise en cache
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