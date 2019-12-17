<!DOCTYPE html> 
<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if	( 
			isCacheValid('networkBikeRatio.php.1') 
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
				getPageFromCache('networkBikeRatio.php.1');
			else
			{
				ob_start();			
				// debut mise en cache				
				
				// graph 
				$tablo=[];
				if ($result = getNetworkRationAtHour($link, 11)) 
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
				
				echo "<div id='GraphEvolutionNombreEVelib' class='widgetGraph2' > <button id='button_GraphEvolutionNombreEVelib' class='graphFullScreenButton'>+</button>";
				echo "<script>";
				echo "var data = [{";
				
				//serie 1.1
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
					echo '"'.$tablo[$i]['networkNbBike'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', name : 'nombre officiel de velib'},{";
				
				//serie 1.2
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
					echo '"'.$tablo[$i]['networkEstimatedNbBike'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', visible: 'legendonly', name : 'nombre estimé de velib'},{";		

				//serie 1.3
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
					echo '"'.$tablo[$i]['networkNbDock'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', name : 'nombre de bornes'},{";					
				
				//serie 2.1
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
					
					$percEstimVelib = $tablo[$i]['dockBikeRation'];

					echo '"'.$percEstimVelib.'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " , type: 'scatter', yaxis: 'y2', name : 'Ratio bornes / Velib<br>(officiel) '},{";
				
				//serie 2.2
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
					
					$percEstimVelib = $tablo[$i]['estimatedDockBikeRatio'];

					echo '"'.$percEstimVelib.'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " , type: 'scatter', visible: 'legendonly', yaxis: 'y2', name : 'Ratio bornes / Velib<br>(estimé) '}";				
				
				echo "];
				var layout = 
				{ 
					title: 'Nombre de Velib, bornes et ratio à 12h', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',
					yaxis: {
							tickfont: {},
						  }, 
					yaxis2: {						
								overlaying: 'y', 
								tickfont: {color: 'rgb(55, 34, 29)'}, 
								side: 'right',
								showgrid: false,
								range: [2, 5]
							},						
					showlegend: true,
					margin: {
								l: 45,
								r: 20,
								b: 40,
								t: 30,
								pad: 4
							  }
				};";
				
				echo "Plotly.newPlot('GraphEvolutionNombreEVelib', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "
				<p class='notes'>
					Ce graphique propose une représentation du nombre de Velib en station à midi, rapproché du nombre de ornes dans des stations actives. <br>
					Les courbes officielles reprènent les données brutes de l'API Velib. Les courbes estimées essayent d'évaluer le nombre de Velib réellements disponibles/utilisables en soustrayant aux données officielles le nombre minimum de Velib enregitré pour chaque station au cours des 3 derniers jours.<br>
					Ces courbes ne prennent pas en compte le nombre de Velib en cours d'utilisation et/ou de déplacement par les équipes de régulation.
				</p>";				
				echo "</div>";	
				
				
				
				// graph 2
				$tablo=[];
				if ($result = getNetworkRationAtHour($link, 5)) 
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
				
				echo "<div id='GraphEvolutionNombreEVelib2' class='widgetGraph2' > <button id='button_GraphEvolutionNombreEVelib' class='graphFullScreenButton'>+</button>";
				echo "<script>";
				echo "var data = [{";
				
				//serie 1.1
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
					echo '"'.$tablo[$i]['networkNbBike'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', name : 'nombre officiel de velib'},{";
				
				//serie 1.2
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
					echo '"'.$tablo[$i]['networkEstimatedNbBike'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', visible: 'legendonly', name : 'nombre estimé de velib'},{";		

				//serie 1.3
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
					echo '"'.$tablo[$i]['networkNbDock'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', name : 'nombre de bornes'},{";					
				
				//serie 2.1
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
					
					$percEstimVelib = $tablo[$i]['dockBikeRation'];

					echo '"'.$percEstimVelib.'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " , type: 'scatter', yaxis: 'y2', name : 'Ratio bornes / Velib<br>(officiel) '},{";
				
				//serie 2.2
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
					
					$percEstimVelib = $tablo[$i]['estimatedDockBikeRatio'];

					echo '"'.$percEstimVelib.'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " , type: 'scatter', visible: 'legendonly', yaxis: 'y2', name : 'Ratio bornes / Velib<br>(estimé) '}";				
				
				echo "];
				var layout = 
				{ 
					title: 'Nombre de Velib, bornes et ratio à 6h', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',
					yaxis: {
							tickfont: {},
						  }, 
					yaxis2: {						
								overlaying: 'y', 
								tickfont: {color: 'rgb(55, 34, 29)'}, 
								side: 'right',
								showgrid: false,
								range: [2, 5]
							},						
					showlegend: true,
					margin: {
								l: 45,
								r: 20,
								b: 40,
								t: 30,
								pad: 4
							  }
				};";
				
				echo "Plotly.newPlot('GraphEvolutionNombreEVelib2', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "
				<p class='notes'>
					Ce graphique propose une représentation du nombre de Velib en station à 6h, rapproché du nombre de ornes dans des stations actives. <br>
					Les courbes officielles reprènent les données brutes de l'API Velib. Les courbes estimées essayent d'évaluer le nombre de Velib réellements disponibles/utilisables en soustrayant aux données officielles le nombre minimum de Velib enregitré pour chaque station au cours des 3 derniers jours.<br>
					Ces courbes ne prennent pas en compte le nombre de Velib en cours d'utilisation et/ou de déplacement par les équipes de régulation.
				</p>";				
				echo "</div>";			

				// graph 3 - 18h00
				$tablo=[];
				if ($result = getNetworkRationAtHour($link, 17)) 
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
				
				echo "<div id='GraphEvolutionNombreEVelib3' class='widgetGraph2' > <button id='button_GraphEvolutionNombreEVelib' class='graphFullScreenButton'>+</button>";
				echo "<script>";
				echo "var data = [{";
				
				//serie 1.1
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
					echo '"'.$tablo[$i]['networkNbBike'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', name : 'nombre officiel de velib'},{";
				
				//serie 1.2
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
					echo '"'.$tablo[$i]['networkEstimatedNbBike'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', visible: 'legendonly', name : 'nombre estimé de velib'},{";		

				//serie 1.3
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
					echo '"'.$tablo[$i]['networkNbDock'].'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}
				echo " , type: 'scatter', name : 'nombre de bornes'},{";					
				
				//serie 2.1
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
					
					$percEstimVelib = $tablo[$i]['dockBikeRation'];

					echo '"'.$percEstimVelib.'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " , type: 'scatter', yaxis: 'y2', name : 'Ratio bornes / Velib<br>(officiel) '},{";
				
				//serie 2.2
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
					
					$percEstimVelib = $tablo[$i]['estimatedDockBikeRatio'];

					echo '"'.$percEstimVelib.'", ';

					if($i%$nbcol==($nbcol-1))
						echo ']';
				}				
				echo " , type: 'scatter', visible: 'legendonly', yaxis: 'y2', name : 'Ratio bornes / Velib<br>(estimé) '}";				
				
				echo "];
				var layout = 
				{ 
					title: 'Nombre de Velib, bornes et ratio à 18h', 
					paper_bgcolor: '#f8f9fa', 
					plot_bgcolor: '#f8f9fa',
					yaxis: {
							tickfont: {},
						  }, 
					yaxis2: {						
								overlaying: 'y', 
								tickfont: {color: 'rgb(55, 34, 29)'}, 
								side: 'right',
								showgrid: false,
								range: [4, 7]
							},						
					showlegend: true,
					margin: {
								l: 45,
								r: 20,
								b: 40,
								t: 30,
								pad: 4
							  }
				};";
				
				echo "Plotly.newPlot('GraphEvolutionNombreEVelib3', data, layout,{displayModeBar: false});";
				echo '</script>';
				echo "
				<p class='notes'>
					Ce graphique propose une représentation du nombre de Velib en station à 18h, rapproché du nombre de ornes dans des stations actives. <br>
					Les courbes officielles reprènent les données brutes de l'API Velib. Les courbes estimées essayent d'évaluer le nombre de Velib réellements disponibles/utilisables en soustrayant aux données officielles le nombre minimum de Velib enregitré pour chaque station au cours des 3 derniers jours.<br>
					Ces courbes ne prennent pas en compte le nombre de Velib en cours d'utilisation et/ou de déplacement par les équipes de régulation.
				</p>";				
				echo "</div>";					
		?>
 
 

		<?php
				//fin mise en cache
				$newPage = ob_get_contents(); //recup contenu à cacher
				updatePageInCache('networkBikeRatio.php.1', $newPage); //mise en cache
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

	<!-- graph to full screen -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	
	<script>

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