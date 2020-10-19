<!DOCTYPE html> 
<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if	( 
			isCacheValid('15DerniersJours.php.1') 
			and isCacheValid('lastUpdateText') 
		)
	{
		$cacheValide = true;
		//$link = mysqlConnect();
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
    <title>Velib Paris - Stations disponibles, ouvertures, nombre de velib... (site officieux)</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Deploiment du nouveau velib 2018, nouvelles stations, nouveaux velos et VAE, stats de fonctionnement..." />
	<meta name="keywords" content="velib, velib 2018, velib2018, nouveau velib, velib 2, cartes, station, vélo, paris, fonctionnent, HS, en panne" />
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
	<!-- Plotly.js -->
	<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
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
					Les statistiques d'utilisation du 10 au 13 sont indisponible, depuis elles sont sous estimées par rapport au reste de la série. </br>
					Les autres données, moins sensible à la régularité de la collecte, sont affectées plus marginalement. 
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

		
		<?php
			if($cacheValide == true)
				getPageFromCache('15DerniersJours.php.1');
			else
			{
				ob_start();			
				// debut mise en cache				
				
				// graph nb velib
				$tablo=[];
				if ($result = getLast15Day($link)) 
				{
					if (mysqli_num_rows($result)>0)
					{
						//détermine le nombre de colonnes
						$nbcol=mysqli_num_rows($result);				
						while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
						{
							$tablo[] = $row;
						}				
					}
				}
				
				//
				
				echo "<div id='GraphEvolutionNombreVelib' class='widgetGraph2' > <button id='button_GraphEvolutionNombreVelib' class='graphFullScreenButton'>+</button>";
				echo "<script>";
				
				$nb=count($tablo);
				$xGrad = '{x: ["1","2","3","4","5","6","7","8","9","10","11","12","13","14","15","16","17","18","19","20","21","22","23","24"],';
				
				echo 'var data = [';
				
				for($j=0;$j<15;$j++)
				{
					echo $xGrad;
					
					for($i=(0+$j*24);$i<24+$j*24;$i++)
					{
						if($i==(0+$j*24))
							echo 'y: [';
						
						if($i< $nb) echo '"'.$tablo[$i]['nbLocation'].'", ';
					}
					echo ']';
					
					echo ", type: 'scatter',";
					if ( $j< 10 and $j!=7) echo " visible: 'legendonly',";
					echo "name : '".date_format(date_create($tablo[$j*24]['date']), 'd/m/Y')."'}";
					if($j!=15)
						echo ",";
				}
									
				
				echo "];
				var layout = 
				{ 
					title: 'Nombre de utilisations des 15 derniers jours (Electrique et mécanique)', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',
					xaxis:
					{
						tickvals:['1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24'],
						range:[1,24]
					},					
					yaxis: {
							tickfont: {},

						  }, 	
					showlegend: true,
					margin: {
								l: 40,
								r: 20,
								b: 40,
								t: 30,
								pad: 4
							  }
				};";
				
				echo "Plotly.newPlot('GraphEvolutionNombreVelib', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "
				<p class='notes'>
					* Nombre d'utilisations enregistrées. Les chiffres officiels de utilisations sont généralement supérieurs de +/- 10%
				</p>";				
				echo "</div>";

		?>
		
		<div class="widget-short">	
		<h1 class="searchable-widget-title">Nombre de utilisations</h1>

			<TABLE id='newStations' class='table-compact sortable'>
				<TR>
				<TH>Date</TH>
				<TH>Nombre d'utilisations estimées</TH>
				<TH>Nombre d'utilisations estimées à <?php echo date('H')+2;?>:00 heure</TH>
				<TH>Nombre d'utilisations estimées après <?php echo date('H')+2;?>:00 heure</TH>			
				</TR>
				
		<?php
				for($j=0;$j<15;$j++)
				{
					$nbLocations = 0;
					$nbLocationsAvant = 0;
					$nbLocationsApres = 0;
					for($i=(0+$j*24);$i<24+$j*24;$i++)
					{
						if($i< $nb) 
						{
						$nbLocations= $nbLocations + $tablo[$i]['nbLocation'];
						if(date('H')+1>=$tablo[$i]['heure'])
							$nbLocationsAvant= $nbLocationsAvant + $tablo[$i]['nbLocation'];
						else
							$nbLocationsApres= $nbLocationsApres + $tablo[$i]['nbLocation'];
						}
					}
					echo '<tr><td>';
					echo date_format(date_create($tablo[$j*24]['date']), 'd/m/Y');
					echo '</td><td>';
					if($nbLocationsApres!=0) echo $nbLocations;
					echo '</td><td>'.$nbLocationsAvant.'</td><td>';
					if($nbLocationsApres!=0) echo $nbLocationsApres;
					echo '</td></tr>';					
				}				
		?>
			</TABLE>
		</div>    

		<?php
				

				//fin mise en cache
				$newPage = ob_get_contents(); //recup contenu à cacher
				updatePageInCache('15DerniersJours.php.1', $newPage); //mise en cache
				ob_end_clean(); //ménage cache memoire
				echo $newPage;	//affichage live	
				
			}						
		?>
		
	
	
	<div class="disclaimer">
		<br>
		Vous l'aurez sans doute deviné, <b>ce site n'est pas un site officiel de vélib.</b> 
		Les données utilisées proviennent de <a href="https://www.velib-metropole.fr" target="_blank">www.velib-metropole.fr</a> et appartiennent à leur propriétaire.<br>
		Si vous n'avez pas encore vu les tutoriels velib et la liste des symboles de la V-box un petit tour chez <a href="https://www.velib-metropole.fr/discover/tutorials" target="_blank"> velib metropole</a> pourrait vous éviter des problèmes...
		<br> Tous les symboles de la V-BOX <a href="http://blog.velib-metropole.fr/wp-content/uploads/2018/03/PICTOS_LISTE_VELIB-19_02_18.pdf" target="_blank">encore chez velib metropole</a> 
		<br><br>
		Ces tableaux et cartes sont une interprétation des données proposées par vélib métropole en espérant ne pas les avoir trop déformées. 
		<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Licence Creative Commons" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/80x15.png"/></a>		
		<a href="/cron/velibAPIParser.php" style="color:#f8f9fa">velibAPIParser (script de chargement)</a>
	</div>


	<script>
		function newStationsSearch() {
		  // Declare variables
		  var input, filter, table, tr, td, td1, td2, td3, i;
		  input = document.getElementById("newStationsSearchInput");
		  filter = input.value.toUpperCase();
		  table = document.getElementById("newStations");
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
	</script>

	<!-- graph to full screen -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	
	<script>

		var button_GraphEvolutionUtilisation = 1;
		$('#button_GraphEvolutionUtilisation').click
			(
				function(e)
				{				
					$('#GraphEvolutionUtilisation').toggleClass('fullscreen'); 
					var update = 
					{
						width: $('#GraphEvolutionUtilisation').width(), 
						height: $('#GraphEvolutionUtilisation').height()  
					};
					
					Plotly.relayout('GraphEvolutionUtilisation', update)
					
					if( button_GraphEvolutionUtilisation ==0)
					{
						$("#button_GraphEvolutionUtilisation").text('+');
						button_GraphEvolutionUtilisation=1;
						$("#fullscreenhider").hide();
						$('body').css('overflow', 'auto');
						
					}
					else
					{					
						$("#button_GraphEvolutionUtilisation").text('x');
						button_GraphEvolutionUtilisation=0;
						$("#fullscreenhider").fadeIn("slow");
						$('body').css('overflow', 'hidden');
					}
				}
			);		
			
			
			
		var button_GraphOuvStationSemaine = 1;
		$('#button_GraphOuvStationSemaine').click
			(
				function(e)
				{				
					$('#GraphOuvStationSemaine').toggleClass('fullscreen'); 
					var update = 
					{
						width: $('#GraphOuvStationSemaine').width(), 
						height: $('#GraphOuvStationSemaine').height() 
					};
					
					Plotly.relayout('GraphOuvStationSemaine', update)
					
					if( button_GraphOuvStationSemaine ==0)
					{
						$("#button_GraphOuvStationSemaine").text('+');
						button_GraphOuvStationSemaine=1;
						$("#fullscreenhider").hide();
						$('body').css('overflow', 'auto');
					}
					else
					{					
						$("#button_GraphOuvStationSemaine").text('x');
						button_GraphOuvStationSemaine=0;
						$("#fullscreenhider").fadeIn("slow");
						$('body').css('overflow', 'hidden');
					}
				}
			);	
			
		
		var button_GraphStationActives = 1;
		$('#button_GraphStationActives').click
			(
				function(e)
				{				
					$('#GraphStationActives').toggleClass('fullscreen'); 
					var update = 
					{
						width: $('#GraphStationActives').width(), 
						height: $('#GraphStationActives').height() 
					};
					
					Plotly.relayout('GraphStationActives', update)
					
					if( button_GraphStationActives ==0)
					{
						$("#button_GraphStationActives").text('+');
						button_GraphStationActives=1;
						$("#fullscreenhider").hide();
						$('body').css('overflow', 'auto');
					}
					else
					{					
						$("#button_GraphStationActives").text('x');
						button_GraphStationActives=0;
						$("#fullscreenhider").fadeIn("slow");
						$('body').css('overflow', 'hidden');
					}
				}
			);		
			
			
		var button_GraphEvolutionNombreVelib = 1;
		$('#button_GraphEvolutionNombreVelib').click
			(
				function(e)
				{				
					$('#GraphEvolutionNombreVelib').toggleClass('fullscreen'); 
					var update = 
					{
						width: $('#GraphEvolutionNombreVelib').width(), 
						height: $('#GraphEvolutionNombreVelib').height()  
					};
					
					Plotly.relayout('GraphEvolutionNombreVelib', update)
					
					if( button_GraphEvolutionNombreVelib ==0)
					{
						$("#button_GraphEvolutionNombreVelib").text('+');
						button_GraphEvolutionNombreVelib=1;
						$("#fullscreenhider").hide();
						$('body').css('overflow', 'auto');
					}
					else
					{					
						$("#button_GraphEvolutionNombreVelib").text('x');
						button_GraphEvolutionNombreVelib=0;
						$("#fullscreenhider").fadeIn("slow");
						$('body').css('overflow', 'hidden');
					}
				}
			);

		var button_GraphEvolutionNombreEVelib = 1;
		$('#button_GraphEvolutionNombreEVelib').click
			(
				function(e)
				{				
					$('#GraphEvolutionNombreEVelib').toggleClass('fullscreen'); 
					var update = 
					{
						width: $('#GraphEvolutionNombreEVelib').width(), 
						height: $('#GraphEvolutionNombreEVelib').height()  
					};
					
					Plotly.relayout('GraphEvolutionNombreEVelib', update)
					
					if( button_GraphEvolutionNombreVelib ==0)
					{
						$("#button_GraphEvolutionNombreEVelib").text('+');
						button_GraphEvolutionNombreVelib=1;
						$("#fullscreenhider").hide();
						$('body').css('overflow', 'auto');
					}
					else
					{					
						$("#button_GraphEvolutionNombreEVelib").text('x');
						button_GraphEvolutionNombreVelib=0;
						$("#fullscreenhider").fadeIn("slow");
						$('body').css('overflow', 'hidden');
					}
				}
			);			
			
	</script>
	<div id="fullscreenhider" style="display: none;"></div>
	<!-- graph to full screen END-->
	
	<div id="mypub">
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		
		<!-- velib3 -->
		<ins class="adsbygoogle"
			 style="display:inline-block;width:300px;height:600px"
			 data-ad-client="ca-pub-4705968908052303"
			 data-ad-slot="8893891084"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
		
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- velib -->
		<ins class="adsbygoogle"
			 style="display:inline-block;width:970px;height:250px"
			 data-ad-client="ca-pub-4705968908052303"
			 data-ad-slot="5109142449"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
		
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- velib4 -->
		<ins class="adsbygoogle"
			 style="display:inline-block;width:336px;height:280px"
			 data-ad-client="ca-pub-4705968908052303"
			 data-ad-slot="9256444048"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>

		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- velib4 -->
		<ins class="adsbygoogle"
			 style="display:inline-block;width:336px;height:280px"
			 data-ad-client="ca-pub-4705968908052303"
			 data-ad-slot="9256444048"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
	</div>
	
	</body>
</html>