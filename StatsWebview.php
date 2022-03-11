<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if
	( 
		isCacheValid('index.php.1') 
		and isCacheValid('index.php.2', false) 
		and isCacheValid('index.php.3', false) 
		and isCacheValid('index.php.4', false) 
		and isCacheValid('webview.php.5', false) 
		and isCacheValid('webview.php.6', false) 
		and isCacheValid('lastUpdateText', false) 
	)
	{
		//echo "valide cache";
		$cacheValide = true;		
	}
	else
	{
		//echo "rebuild cache";
		$cacheValide = False;
		$link = mysqlConnect();
	}	
?>	
<!DOCTYPE html>
<html>
  <head>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-113973828-2"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'UA-113973828-2');
	</script>
    <title>Nouveau Velib à Paris - Stations disponibles, ouvertures, nombre de velib... (site officieux)</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Deploiment du nouveau velib 2018, nouvelles stations, nouveaux velos et VAE, stats de fonctionnement..." />
	<meta name="keywords" content="velib, velib 2018, velib2018, nouveau velib, velib 2, cartes, station, vélo, paris, fonctionnent, HS, en panne" />
	
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<meta name="theme-color" content="#ffffff">
	
	<link rel="stylesheet" media="all" href="./css/joujouVelib.css?<?php echo filemtime('./css/joujouVelib.css');?>">	
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
			!!! Mode maintenance actif !!!
		</div>		
		";
	}
		
	?>
	<div class="left-widget left200">	
		<h1 class="widget-title">Nombre de stations</h1>
		<TABLE>
			<TR>
			<TH>Fermées & En travaux</TH>
			<TH>Actives</TH>
			</TR>	
			<?php
				if($cacheValide == true)
					getPageFromCache('index.php.1');
				else
				{
					ob_start();			
					// debut mise en cache
					if ($result = getStationCount($link)) 
					{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
								{
									echo "<TR>";
									echo "<TD>".$row["stations"]." ( Max: ".$row["stations_max"].")</TD>";	
									echo "<TD>".$row["stations_active"]." ( Max: ".$row["stations_active_max"].")</TD>"; 
										echo "</TR>";	
								}				
						}
					}
					//fin mise en cache
					$newPage = ob_get_contents(); //recup contenu à cacher
					updatePageInCache('index.php.1', $newPage); //mise en cache
					ob_end_clean(); //ménage cache memoire
					echo $newPage;	//affichage live		
				}

			?>
		</TABLE>
		<p class="notes">* status officiel</p>
	</div>
	<div class="left-widget left200">	
		<h1 class="widget-title">Nombre de Vélib en station</h1>
		<TABLE class="table-medium">
		<TR>
		<TH>Nombre de Velib</TH>
		<TH>Nombre de Velib Elec.</TH>
		</TR>		
		<?php
			if($cacheValide == true)
				getPageFromCache('index.php.2');
			else
			{
				ob_start();			
				// debut mise en cache		
				if ($result = getVelibCount($link)) 
				{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
								{
									echo "<TR>";	
									echo "<TD>".($row["velibs"]-$row["velibs_overflow"])." ( +overflow: ".$row["velibs_overflow"].")<br> ( Max: ".$row["velibs_max"].")</TD>";	
									echo "<TD>".($row["VAE"]-$row["VAE_overflow"])." ( +overflow: ".$row["VAE_overflow"].")<br> ( Max: ".$row["VAE_Max"].")</TD>"; 
									echo "</TR>";	
								}				
						}
				}
				//fin mise en cache
				$newPage = ob_get_contents(); //recup contenu à cacher
				updatePageInCache('index.php.2', $newPage); //mise en cache
				ob_end_clean(); //ménage cache memoire
				echo $newPage;	//affichage live		
			}				
		?>
		</TABLE>
		<p class="notes">* les velibs utilisés ne sont pas comptés</p>		
	</div>
	
	<div class="left-widget left200 col2">
		<h1 class="widget-title">Stations par dernier mouvement</h1>
		<TABLE class="table-compact">
			<TR>
				<TH>Status officiel</TH>
				<TH>Stations</TH>
				<TH>Dernier mouvement</TH>
			</TR>
			<?php 
				if($cacheValide == true)
					getPageFromCache('index.php.3');
				else
				{
					ob_start();			
					// debut mise en cache				
					if ($result = getStationCountByLastEvent($link, "`stationLastChange`" ))
					{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
							{					
								echo "<TR>";
								echo "<TD>";
								if($row["stationState"]=="Operative")
								{
									echo "Actives";
								}
								Elseif ($row["stationState"]=="Work in progress")
								{
									echo "En Travaux";
								}
								Elseif ($row["stationState"]=="Close")
								{
									echo "Fermées";
								}								
								else
								{
									echo $row["stationState"];
								}								
								echo "</TD><TD>";
								echo $row["nbs"];
								echo "</TD><TD>";
								echo $row["periode"];					
								echo "</TD>";
								echo "</TR>";	
							}				
						}
					}
					//fin mise en cache
					$newPage = ob_get_contents(); //recup contenu à cacher
					updatePageInCache('index.php.3', $newPage); //mise en cache
					ob_end_clean(); //ménage cache memoire
					echo $newPage;	//affichage live		
				}					
			?>
		</TABLE>
		<p class="notes">*l'absence de mouvement ne présume pas du dysfonctionnement d'une station</p>
	</div>
	
		<div class="left-widget left200 col2">
		<h1 class="widget-title">Stations par dernier retrait</h1>
		<TABLE class="table-compact">
			<TR>
				<TH>Status officiel</TH>
				<TH>Stations</TH>
				<TH>Dernier retrait</TH>
			</TR>
			<?php 
				if($cacheValide == true)
					getPageFromCache('index.php.4');
				else
				{
					ob_start();			
					// debut mise en cache					
					if ($result = getStationCountByLastEvent($link, "`stationLastExit`" ))
					{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
							{					
								echo "<TR>";
								echo "<TD>";
								if($row["stationState"]=="Operative")
								{
									echo "Actives";
								}
								Elseif ($row["stationState"]=="Work in progress")
								{
									echo "En Travaux";
								}
								Elseif ($row["stationState"]=="Close")
								{
									echo "Fermées";
								}								
								else
								{
									echo $row["stationState"];
								}								
								echo "</TD><TD>";
								echo $row["nbs"];
								echo "</TD><TD>";
								echo $row["periode"];					
								echo "</TD>";
								echo "</TR>";	
							}				
						}
					}
					//fin mise en cache
					$newPage = ob_get_contents(); //recup contenu à cacher
					updatePageInCache('index.php.4', $newPage); //mise en cache
					ob_end_clean(); //ménage cache memoire
					echo $newPage;	//affichage live		
				}					
		?>
		</TABLE>
		<p class="notes">*l'absence de mouvement ne présume pas du dysfonctionnement d'une station</p>
	</div>
	
		
		<?php
			if($cacheValide == true)
				getPageFromCache('webview.php.5');
			else
			{
				ob_start();			
				// debut mise en cache				
				if ($result = getStationCountByOperativeDate($link)) 
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
				
				echo "<div id='GraphOuvStationSemaine' class='left-widget widgetGraph'>";
				echo "<script>";
				echo "var data = [{";
				
				$nb=count($tablo);
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['week'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					echo '"'.$tablo[$i]['nbStationWeek'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				echo "  type: 'bar'}];";
				
				echo "
				var layout = 
				{ 
					title: 'Ouvertures de stations par semaine', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',
					showlegend: false,
					margin: {
								l: 20,
								r: 20,
								b: 30,
								t: 30,
								pad: 4
							  }
				};";
				
				echo "Plotly.newPlot('GraphOuvStationSemaine', data, layout,{displayModeBar: false,staticPlot: true});";
				echo '</script>';
				echo "</div>";
				
				echo "<div id='GraphProgressionStation' class='left-widget widgetGraph'>";
				echo "<script>";
				echo "var data = [{";
				
				$nb=count($tablo);
				$nbstation = 0;
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['week'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					$nbstation = $tablo[$i]['nbStationWeek'] + $nbstation;
					echo '"'.$nbstation.'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				echo "  type: 'scatter'}];";
				
				echo "
				var layout = 
				{ 
					title: 'Progression du nombre de stations', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',					
					showlegend: false,
					margin: {
								l: 30,
								r: 20,
								b: 30,
								t: 30,
								pad: 4
							  }
				};";
				
				echo "Plotly.newPlot('GraphProgressionStation', data, layout,{displayModeBar: false,staticPlot: true});";
				echo '</script>';
				echo "</div>";		

				
				if ($result = getActivStationPercentage($link)) 
				{
					if (mysqli_num_rows($result)>0)
					{
						//détermine le nombre de colonnes
						$nbcol=mysqli_num_rows($result);				
						while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
						{
							$tablo2[] = $row;
						}				
					}
				}
				//else echo mysqli_error($link);
	
					
				echo "<div id='GraphStationActives' class='left-widget widgetGraph'>";
				//var_dump($tablo2);
				
				echo "<script>";
				echo "var data = [{";
				
				$nb=count($tablo2);
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo2[$i]['statDate'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					echo '"'.$tablo2[$i]['activePercent'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				echo "  type: 'scatter', name: '1 Heure'},{";
				
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo2[$i]['statDate'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					echo '"'.$tablo2[$i]['activePercent3H'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				echo "  type: 'scatter', name: '3 Heure'},{";
				
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo2[$i]['statDate'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					echo '"'.$tablo2[$i]['activePercent6H'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				echo "  type: 'scatter', name: '6 Heure'}";
				
				echo "];";
				
				echo "
				var layout = 
				{ 
					title: 'Stations avec mouvements',
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',					
					showlegend: true,					
					margin: {
								l: 40,
								r: 20,
								b: 30,
								t: 30,
								pad: 4
							  },
					  yaxis: {
						title: '%',
						range: [15, 100]
					  },
					  xaxis: {
						showline: true,
						showgrid: false,
						showticklabels: true,
						linecolor: 'rgb(204,204,204)',
						linewidth: 2,
						autotick: true,
						ticks: 'outside',
						tickcolor: 'rgb(204,204,204)',
						tickwidth: 2,
						ticklen: 5,
						tickfont: {
						  family: 'Arial',
						  size: 12,
						  color: 'rgb(82, 82, 82)'
						}
					  }
				};";
				
				echo "Plotly.newPlot('GraphStationActives', data, layout,{displayModeBar: false,staticPlot: true});";
				echo '</script>';
				echo "</div>";				
				
				//fin mise en cache
				$newPage = ob_get_contents(); //recup contenu à cacher
				updatePageInCache('webview.php.5', $newPage); //mise en cache
				ob_end_clean(); //ménage cache memoire
				echo $newPage;	//affichage live		
			}						
		?>
		
	
	
	<div class="widget-short">	
		<h1 class="searchable-widget-title">Nouvelles stations Velib</h1>
		<TABLE id='newStations' class='table-compact sortable'>
			<TR>
			<TH>Code</TH>
			<TH>Nom</TH>
			<TH class='adapativeHide'>Adresse</TH>
			<TH class='thstatus'>Status</TH>			
			</TR>	
			<?php
				if($cacheValide == true)
					getPageFromCache('webview.php.6');
				else
				{
					ob_start();			
					// debut mise en cache				
					if ($result = getNewStationList($link)) 
					{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
							{
								echo "<TR>";
								echo "<TD>".$row["stationCode"]."</TD>";	
								echo "<TD><a href='carte-des-stations.php?lat=".$row["stationLat"]."&lon=".$row["stationLon"]."&zoom=17'>".$row["stationName"]."</a></TD>";											
								echo "<TD class='adapativeHide'>".$row["stationAdress"]."</TD>";	
								if($row["stationState"]=="Operative")
								{
									echo "<TD>"."En service"."</TD>";
								}
								elseif ($row["stationState"]=="Work in progress")
								{
								echo "<TD>"."En travaux"."</TD>";
								}
								else
								{
									echo "<TD>".$row["stationState"]."</TD>";
								}	
								echo "</TR>";	
							}				
						}
					}
					echo " ";
					//fin mise en cache
					$newPage = ob_get_contents(); //recup contenu à cacher
					updatePageInCache('index.php.6', $newPage); //mise en cache
					ob_end_clean(); //ménage cache memoire
					echo $newPage;	//affichage live		
					mysqlClose($link);
				}
			?>
		</TABLE>
	</div>    
	<div class="disclaimer">
		<br>
		Vous l'aurez sans doute deviné, <b>ce site n'est pas un site officiel de vélib.</b> 
		Les données utilisées proviennent de <a href="https://www.velib-metropole.fr" target="_blank">www.velib-metropole.fr</a> et appartiennent à leur propriétaire.<br>
		Si vous n'avez pas encore vu les tutoriels velib et la liste des symboles de la V-box un petit tour chez <a href="https://www.velib-metropole.fr/discover/tutorials" target="_blank"> velib metropole</a> pourrait vous éviter des problèmes...
		<br> Tous les symboles de la V-BOX <a href="http://blog.velib-metropole.fr/wp-content/uploads/2018/03/PICTOS_LISTE_VELIB-19_02_18.pdf" target="_blank">encore chez velib metropole</a> 
		<br><br>
		J'aurai aimé que velib nous dise quelles stations sont fonctionnelles à l'instant T mais ce n'est pas le cas.<br>
		Alors, à partir du parti pris simpliste, "une station dont les données ne changent pas ne marche pas", j'essaye de déterminer sur quelles stations on peut probablement (ne pas) compter.
		<br><br>
		Ces tableaux et cartes sont une interprétation des données proposées par vélib métropole en espérant ne pas les avoir trop déformées.

	</div>
	<br><br><br><br><br><br>	
	<div id="mypub">
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- velib -->
		<ins class="adsbygoogle"
			 style="display:inline-block;width:970px;height:250px"
			 data-ad-client="ca-pub-4705968908052303"
			 data-ad-slot="5109142449"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
        
		<!-- velib -->
		<ins class="adsbygoogle"
			 style="display:inline-block;width:970px;height:250px"
			 data-ad-client="ca-pub-4705968908052303"
			 data-ad-slot="5109142449"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>    	
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
	  </body>
</html>