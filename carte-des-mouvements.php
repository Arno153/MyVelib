<!DOCTYPE html> 
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

	<title>Velib Paris - Carte officieuse - Nombre de mouvement par station</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="Carte officieuse des stations du nouveau velib 2018: stations qui fonctionnent ou peut être pas, nombre de velos et VAE disponibles..." />
	<meta name="keywords" content="velib, velib 2018, velib2018, velib 2, cartes, geolocalisation, gps, autour de moi, station, vélo, paris, fonctionnent, disponibles, HS, en panne" />
	<meta name="viewport" content="initial-scale=1.0, width=device-width" />
	<meta name="robots" content="index, follow">
	<link rel="canonical" href="https://velib.philibert.info/carte-des-mouvements.php" />
	
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<meta name="theme-color" content="#ffffff">
	<link rel="stylesheet" media="all" href="./css/joujouVelib.css?<?php echo filemtime('./css/joujouVelib.css');?>">
	<script src="./inc/mapLeaflet.js?<?php echo filemtime('./inc/mapLeaflet.js');?>" type="text/javascript"></script>	
	
	
	<!-- Base MAP -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.4.0/dist/leaflet.css"
	   integrity="sha512-puBpdR0798OZvTTbP4A8Ix/l+A4dHDD0DGqYW6RQ+9jxkRFclaxxQb/SJAWZfWAkuyeQUytO7+7N4QKrDh+drA=="
	   crossorigin=""/>
	<!-- Make sure you put this AFTER Leaflet's CSS -->
	<script src="https://unpkg.com/leaflet@1.4.0/dist/leaflet.js"
	   integrity="sha512-QVftwZFqvtRNi0ZyCtsznlKSWOStnDORoefr1enyq5mVL4tmKB3S/EnC3rRJcxCPavG10IcrVGSmPh6Qw5lwrg=="
	   crossorigin=""></script>
	<!-- Base MAP END-->
	

	
	<!-- full screen-->
	<script src='https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/Leaflet.fullscreen.min.js'></script>
	<link href='https://api.mapbox.com/mapbox.js/plugins/leaflet-fullscreen/v1.0.1/leaflet.fullscreen.css' rel='stylesheet' />
	<!-- full screen END-->
	
	<!-- custom controle -- refresh and toggle button -->
	<!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">	-->
	<script src="./inc/Leaflet.Control.Custom.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
	
	<!-- custom controle -- END -->
	
  </head>
  <body>
	<?php	
	include "./inc/mysql.inc.php";

	$lofFile='./.maintenance';
	if(file_exists ($lofFile) )
	{
		echo 
			"
			<div class='maintenance'>
				<!-- !!! Mode maintenance actif !!! -->
					Mon processus de collecte des données Velib est actuellement perturbé.</br>
					Les statistiques d'utilisation affichées pour les 10, 11, 12 et 13 septembre sont erronées. </br>
					Les statistiques d'utilisation affichées depuis le 13 septembre sont plus ou moins lourdement sous estimées. </br>
			</div>	
			";
	}
		
	include "./inc/menu.inc.php";
	?>

    <div id="mapid"></div>
    <script type="text/javascript">		
		var locations = [];
		var marker, i, iconurl;
		var markers = [];

		var zoomp = 13;
		var latp = 48.86;
		var lonp = 2.34;
		
		var mvtDate = 0;

		
		// initiate leaflet map
		var mymap = L.map('mapid', {
			center: [latp, lonp],
			zoom: zoomp,
			zoomControl: false
		})
		// add zoomControl
		L.control.zoom({ position: 'topright' }).addTo(mymap);
		
		// create a cutom control to refresh data (display only in fullscreen mode)		
		var cc = L.control.custom({
							position: 'topleft',
							title: 'Rafraichir',
							content : '<a class="leaflet-bar-part leaflet-bar-part-single" id="ReloadData">'+
									  '    <i class="fa fa-refresh"></i> '+
									  '</a>',
							classes : 'leaflet-control-locate leaflet-bar leaflet-control',
							style   :
							{
								padding: '0px',
							},
							events:
								{
									click: function(data)
									{
										getMvtMapData(mvtDate);									
									},
								}
						})
						.addTo(mymap);
		
		// add full screen control
		mymap.addControl(new L.Control.Fullscreen());
		
		// set map area limits
		var southWest = L.latLng(48.74, 2.14),
		northEast = L.latLng( 48.98, 2.55),
		mybounds = L.latLngBounds(southWest, northEast);		
		mymap.setMaxBounds(mybounds);
		mymap.options.minZoom = 11;
		mymap.options.maxBoundsViscosity = 1.0;

		//Load tiles
		L.tileLayer('https://velib.philibert.info/tiles/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		}).addTo(mymap);

		
		//load stations to the map
		getMvtMapData(mvtDate);


		// slider management
		var cc2 = L.control.custom({
							position: 'bottomleft',
							title: 'switch',
							content : 
								'<div class="value">Aujourd\'hui</div><input type="range" min="0" max="10" step="1" value="0">',
							style   :
							{
								padding: '0px',
							}
						})
						.addTo(mymap);		
				
		var elem = document.querySelector('input[type="range"]');

		var rangeValue = debounce(function(){
		  var newValue = elem.value;
		  mvtDate = newValue;
		  getMvtMapData(mvtDate);
		  if(elem.value==0)
			  newValue = "Aujourd'hui";
		  else if(elem.value==1)
			  newValue = "Hier";
		  else newValue = "J-"+newValue;
		  var target = document.querySelector('.value');
		  target.innerHTML = newValue;
		},300);

		elem.addEventListener("input", rangeValue);		
    </script>
	
	
	<div class="disclaimer">
		* Stations Velib par nombre de mouvements enregistrés : 
		Aucun: <img src="./images/marker_grey0.png" alt="Gris" width="12">, 
		1 < <img src="./images/marker_yellow25.png" alt="Jaune" width="12"> <
		50 < <img src="./images/marker_orange75.png" alt="Orange" width="12"> <
		100 < <img src="./images/marker_green150.png" alt="Vert" width="12"> <
		200 < <img src="./images/marker_red300.png" alt="Rouge" width="12"> <
		350 < <img src="./images/marker_purple500.png" alt="Violet" width="12">
		<br>Station non opérationnelle selon Velib <img src="./images/marker_greenx10.png" alt="Croix" width="12"> 
		<br><b>Les valeurs > à 800 sont affichées comme 800</b>
		<br>
		<br><b> Données de la journée en cours quelque soit l'heure à laquelle vous consultez cette page!!!</b>
		<br><b>Ce site n'est pas un site officiel de vélib.</b> Les données utilisées proviennent de <a href="www.velib-metropole.fr">www.velib-metropole.fr</a> et appartiennent à leur propriétaire.
	</div>	
	
	<div id="mypub">
		<iframe id="gads" src="./inc/ads.inc.html" width="100%" height="600px" />
	</div>
	
  </body>
</html>