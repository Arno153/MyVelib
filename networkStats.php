<?php	
	include "./inc/mysql.inc.php";
	$cacheValide = False;
	$link = mysqlConnect();

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
			!!! Mode maintenance actif !!!
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
				echo getLastUpdate($link);
		?>	
	Contact: 	
		<a href="https://twitter.com/arno152153">
			<img border="0" alt="Twitter" src="https://abs.twimg.com/favicons/favicon.ico" width="15px" height="15px">
		</a>
	</div>
    </nav>

	
		
		<?php
			
				if ($result = getActivStationPercentage2($link))  
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
				else echo mysqli_error($link);
	
					
				echo "<div id='GraphStationActives' class='widgetGraph2'>";
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
				echo "</div>";				
							
		?>
		
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