<!DOCTYPE html> 
<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if	( 
			isCacheValid('index.php.1') 
			and isCacheValid('index.php.2') 
			and isCacheValid('index.php.3') 
			and isCacheValid('index.php.4') 
			and isCacheValid('index.php.6') 
			and isCacheValid('lastUpdateText') 
		)
		$cacheValide = true;
	else
	{
		$cacheValide = False;
		//$link = mysqlConnect();
	}	
	
	if( isCacheValid1H('index.php.5'))
		$cacheValide1H = true;
	else
		$cacheValide1H = False;

	if( !($cacheValide and $cacheValide1H))
	{
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
					Les données diffusées actuellement par velib métropole présentent des variations cycliques du nombre de vélo en station probablement non representatives des mouvements réels.
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
	Contact: 	
		<a href="https://twitter.com/arno152153">
			<img border="0" alt="Twitter" src="https://abs.twimg.com/favicons/favicon.ico" width="15px" height="15px">
		</a>
	</div>


	<div class="left-widget left200">	
		<h1 class="widget-title">Nombre de stations</h1>
		<TABLE class="table-compact">
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
	<div class="left-widget left200 col3">	
	
		<?php
			if($cacheValide == true)
				getPageFromCache('index.php.2');
			else
			{
				ob_start();			
				// debut mise en cache	
		?>
		<h1 class="widget-title">Nombre de Vélib en station</h1>
		<TABLE class="table-compact">
		<TR>
		<TH>Nombre de Velib</TH>
		<TH>Nombre de Velib Elec.</TH>
		</TR>	
		<?php
				if ($result = getVelibCount($link)) 
				{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
								{
									echo "<TR>";	
									echo "<TD>".($row["velibs"]-$row["velibs_overflow"])." ( + Park+: ".$row["velibs_overflow"].")<br> ( Max: ".$row["velibs_max"].")<br> (Max depuis 01/07: ".$row["velibs_max_072018"].")</TD>";	
									echo "<TD>".($row["VAE"]-$row["VAE_overflow"])." ( + Park+: ".$row["VAE_overflow"].")<br> ( Max: ".$row["VAE_Max"].")<br> (Max depuis 01/07: ".$row["VAE_Max_072018"].")</TD>"; 
									echo "</TR>";	
								}				
						}
				}
		?>
		</TABLE>
		<br>
		<TABLE class="table-compact">
		<TR>
		<TH>Nombre estimé de Velib disponibles</TH>
		</TR>	
		<?php
				if ($result = getEstimatedVelibCount($link)) 
				{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
								{									
									$estimatedVelibNumber3D =$row["estimatedVelibNumber"];
									$estimatedVelibNumberOverflow3D = $row["estimatedVelibNumberOverflow"];
								}				
						}
				}
				
				if ($result = getEstimatedVelibCount2D($link)) 
				{
						if (mysqli_num_rows($result)>0)
						{
							while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
								{									
									$estimatedVelibNumber2D =$row["estimatedVelibNumber"];
									$estimatedVelibNumberOverflow2D = $row["estimatedVelibNumberOverflow"];
								}				
						}
				}				
				
				echo "<TR>";	
				echo "
						<TD>
							Estimation sur 3J: ".$estimatedVelibNumber3D." ( + Park+: ".$estimatedVelibNumberOverflow3D.")<br>
							Estimation sur 2J: ".$estimatedVelibNumber2D." ( + Park+: ".$estimatedVelibNumberOverflow2D.")
						</TD>";	
				echo "</TR>";	
				
		?>		
		</TABLE>		
		<?php
				//fin mise en cache
				$newPage = ob_get_contents(); //recup contenu à cacher
				updatePageInCache('index.php.2', $newPage); //mise en cache
				ob_end_clean(); //ménage cache memoire
				echo $newPage;	//affichage live		
			}				
		?>

		<p class="notes">* les velib en cours d'utilisation ne sont pas comptés</p>		
		<p class="notes">* le nombre estimé de velib est obtenu en soustrayant le nombre min de velib enregistré par chaque station sur les 2 / 3 derniers jours</p>	
	</div>
	
	<div class="left-widget left360 col2">
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
	
		<div class="left-widget left360 col2">
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
			if($cacheValide1H == true)
				getPageFromCache('index.php.5');
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
				echo "  type: 'bar'},";
				
				//
				
				$nb=count($tablo);
				$nbstation = 0;
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo '{x: [';
					
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
				echo " yaxis: 'y2', type: 'scatter'}];";
				
				
				//
				
				echo "
					var layout = 
						{ 
							title: 'Ouvertures de stations (par semaine)', 
							yaxis: {
									tickfont: {color: 'rgb(31, 119, 180)'},
									showgrid: false
								  }, 
							yaxis2: {						
								overlaying: 'y', 
								tickfont: {color: 'rgb(255, 127, 14)'}, 
								side: 'right',
								showgrid: false
									},					
							paper_bgcolor: '#f8f9fa', 
							plot_bgcolor: '#f8f9fa',
							showlegend: false,
							margin: {
										l: 20,
										r: 40,
										b: 30,
										t: 30,
										pad: 4
									  }
						};
				";
				
				echo "Plotly.newPlot('GraphOuvStationSemaine', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "</div>";
				
				//
				$tablo=[];
				if ($result = getRentalByDate($link)) 
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
				
				echo "<div id='GraphEvolutionUtilisation' class='left-widget widgetGraph'>";
				echo "<script>";
				echo "var data = [{";
				
				$nb=count($tablo);
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['date'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					 ;
					echo '"'.$tablo[$i]['nbLocation'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				echo "  type: 'scatter'}];";
				
				echo "
				var layout = 
				{ 
					title: 'Nombre estimé d\'utilisations', 
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
				
				echo "Plotly.newPlot('GraphEvolutionUtilisation', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "<p class='notes'>* Nombre de retraits enregistrés. Les chiffres officiels de locations sont généralement supérieurs de +/- 10%</p>";
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
				
				echo "Plotly.newPlot('GraphStationActives', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "<p class='notes'>* Pourcentage moyen de stations ayant enregistré au moins un mouvement toutes les 1 / 3 /6 h</p>";
				echo "</div>";				
				
				// graph nb velib
				$tablo=[];
				if ($result = getVelibNbrStats($link)) 
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
				
				echo "<div id='GraphEvolutionNombreVelib' class='widgetGraph2' >";
				echo "<script>";
				echo "var data = [{";
				
				$nb=count($tablo);
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['date'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					 ;
					echo '"'.$tablo[$i]['avgVelib'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				
				echo ",
						error_y: {
						  type: 'data',
						  symmetric: false,
						  ";
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'array: [';
				
					 ;
					echo '"'.($tablo[$i]['maxVelib']-$tablo[$i]['avgVelib']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'arrayminus: [';
				
					 ;
					echo '"'.($tablo[$i]['avgVelib']-$tablo[$i]['minVelib']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " }, type: 'scatter', name : 'Officiel'},{";
				
				//serie 2
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['date'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					 ;
					echo '"'.$tablo[$i]['avgVelibOverflow'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				
				echo ",
						error_y: {
						  type: 'data',
						  symmetric: false,
						  ";
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'array: [';
				
					 ;
					echo '"'.($tablo[$i]['maxVelibOverflow']-$tablo[$i]['avgVelibOverflow']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'arrayminus: [';
				
					 ;
					echo '"'.($tablo[$i]['avgVelibOverflow']-$tablo[$i]['minVelibOverflow']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " }, type: 'scatter', visible: 'legendonly', name : 'Officiel,<br>En Overflow'},{";				
				//serie 3
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['date'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					 ;
					echo '"'.$tablo[$i]['avgEstimatedVelib'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				
				echo ",
						error_y: {
						  type: 'data',
						  symmetric: false,
						  ";
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'array: [';
				
					 ;
					echo '"'.($tablo[$i]['maxEstimatedVelib']-$tablo[$i]['avgEstimatedVelib']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'arrayminus: [';
				
					 ;
					echo '"'.($tablo[$i]['avgEstimatedVelib']-$tablo[$i]['minEstimatedVelib']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " }, type: 'scatter', name : 'Estimé'},{";	
				//serie 4
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['date'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					 ;
					echo '"'.$tablo[$i]['avgEstimatedVelibOverflow'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				
				echo ",
						error_y: {
						  type: 'data',
						  symmetric: false,
						  ";
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'array: [';
				
					 ;
					echo '"'.($tablo[$i]['maxEstimatedVelibOverflow']-$tablo[$i]['avgEstimatedVelibOverflow']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'arrayminus: [';
				
					 ;
					echo '"'.($tablo[$i]['avgEstimatedVelibOverflow']-$tablo[$i]['minEstimatedVelibOverflow']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " }, type: 'scatter', visible: 'legendonly', name : 'Estimé,<br>en Overflow '},{";				
				
				//serie 5
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
						echo 'x: [';
					
					echo '"'.$tablo[$i]['date'].'", ';

					if($i%$nbcol==($nbcol-1))
					echo '],';

				}		
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'y: [';
				
					 ;
					echo '"'.$tablo[$i]['avgEstimatedUnavailableVelib'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				
				echo ",
						error_y: {
						  type: 'data',
						  symmetric: false,
						  ";
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'array: [';
				
					 ;
					echo '"'.($tablo[$i]['maxEstimatedUnavailableVelib']-$tablo[$i]['avgEstimatedUnavailableVelib']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo '],';
				}
				for($i=0;$i<$nb;$i++)
				{
					if($i%$nbcol==0)
					echo 'arrayminus: [';
				
					 ;
					echo '"'.($tablo[$i]['avgEstimatedUnavailableVelib']-$tablo[$i]['minEstimatedUnavailableVelib']).'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " }, type: 'scatter', name : 'Estimé,<br>Indisponible '}"; 
				
				echo "];
				var layout = 
				{ 
					title: 'Nombre de Velib', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',					
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
					Ce graphique propose une représentation du nombre moyen, minimum et maximum de velib présents en station. <br>
					Les courbes officielles reprènent les données brutes de l'API Velib. Les courbes estimées essayent d'évaluer le nombre de velib réellements disponnibles/utilisables en soustrayant aux données officielles le nombre minimum de velib enregitré pour chaque station au cours des 3 derniers jours.<br>
					Ces courbes ne prennent pas en compte le nombre de velib en cours d'utilisation.
				</p>";				
				echo "</div>";


				//fin mise en cache
				$newPage = ob_get_contents(); //recup contenu à cacher
				updatePageInCache('index.php.5', $newPage); //mise en cache
				ob_end_clean(); //ménage cache memoire
				echo $newPage;	//affichage live	
				
			}						
		?>
		
	
	
	<div class="widget-short">	
		<h1 class="searchable-widget-title">Nouvelles stations Velib</h1>
		<input type="text" id="newStationsSearchInput" onkeyup="newStationsSearch()" placeholder="Search for names..">
		<TABLE id='newStations' class='table-compact sortable'>
			<TR>
			<TH>Code</TH>
			<TH>Nom</TH>
			<TH class='adapativeHide'>Adresse</TH>
			<TH class='thdate'>Date d'ajout</TH>
			<TH class='thstatus'>Status</TH>			
			<TH class='thdate'>Date d'activation</TH>				
			</TR>	
			<?php
				if($cacheValide == true)
					getPageFromCache('index.php.6');
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
								echo "<TD>".$row["stationInsertedInDb"]."</TD>";
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
								echo "<TD>".$row["stationOperativeDate"]."</TD>";									
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