<?php	
	include "./inc/mysql.inc.php";
	include "./inc/cacheMgt.inc.php";	
	
	if( isCacheValid('lastUpdateText') )
	{
		//echo "valide";
		$cacheValide = true;		
	}
	else
	{
		//echo "rebuild";
		$cacheValide = False;
		$link = mysqlConnect();
	}	
?>	
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

  <title>Velib Paris - Carte officieuse des stations et velib disponibles</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="description" content="Carte officieuse des stations du nouveau velib 2018: stations qui fonctionnent ou peut être pas, nombre de velos et VAE disponibles..." />
  <meta name="keywords" content="velib, velib 2018, velib2018, velib 2, cartes, geolocalisation, gps, autour de moi, station, vélo, paris, fonctionnent, disponibles, HS, en panne" />
  <meta name="viewport" content="initial-scale=1.0, width=device-width" />

	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
	<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
	<meta name="msapplication-TileColor" content="#00a300">
	<meta name="msapplication-TileImage" content="/mstile-144x144.png">
	<meta name="theme-color" content="#ffffff">
  
  <link rel="stylesheet" media="all" href="./css/joujouVelib.css?<?php echo filemtime('./css/joujouVelib.css');?>">
  <script src="./inc/map.js?<?php echo filemtime('./inc/map.js');?>" type="text/javascript"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=GMAPS-API-KEY" type="text/javascript"></script>
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
		
	?>
	<nav class="navbar bg-light"><b>
      <a class="nav-link" href="./">Accueil</a>
	  <a class="nav-link" href="javascript:refresh();">Rafraichir</a>
	  <a class="nav-link" href="./liste-des-stations.php">Liste des stations</a>
    </b>
	</nav>
	
	<div id="map" style="height: 85%; width: 100%;"></div>
	<script type="text/javascript">

	var locations;
	var marker, i, iconurl;
	var markers = [];
	var HS;		
	
		var zoomp = getUrlParam('zoom');
		if(zoomp == undefined){
				var zoomp = 13;
		}
		
		var latp = getUrlParam('lat');
		if(latp == undefined){
				var latp = 48.86;
		}
		
		var lonp  = getUrlParam('lon');
		if(lonp == undefined){
				var lonp = 2.34;
		}

		var userPos = { lat: parseFloat(latp), lng: parseFloat(lonp)};
		
		var myStyles =[
		  {
			"featureType": "poi",
			"stylers": [
			  {
				"visibility": "off"
			  }
			]
		  }
		];

		var map = new google.maps.Map(document.getElementById('map'), {
		  zoom: parseInt(zoomp),
		  center: new google.maps.LatLng(parseFloat(latp), parseFloat(lonp)),
		  mapTypeId: google.maps.MapTypeId.ROADMAP,
		  styles: myStyles, 
		  streetViewControl: false,
		  FullscreenControl: false,
		  gestureHandling: 'greedy',
		  mapTypeControl: false

		});
		

		var infowindow = new google.maps.InfoWindow();
		var infoWindow2 = new google.maps.InfoWindow({map: map});
		infoWindow2.close();
		
		var im = 'https://i.stack.imgur.com/VpVF8.png';
		var userMarker = new google.maps.Marker({
					position: userPos,
					map: map,
					icon: im 
				});
		userMarker.setVisible(false);

		addYourLocationButton(map, userMarker);
		
		getStations();

	</script>	
	
	<div class="disclaimer">
		Stations Velib suivant les derniers mouvements : <img src="./images/marker_green0.png" alt="Vert" width="12"><1h
		<<img src="./images/marker_yellow0.png" alt="Jaune" width="12"><3h
		<<img src="./images/marker_orange0.png" alt="Orange" width="12"><12h
		<<img src="./images/marker_red0.png" alt="Rouge" width="12"><24h
		<<img src="./images/marker_purple0.png" alt="Violet" width="12">  --
		<img src="./images/marker_grey0.png" alt="Gris" width="12"> : En travaux / Fermée -- NB: Malus pour les stations sans retraits
		<br>Données communautaire: <img src="./images/marker_greenx10.png" alt="Croix" width="12"> Signalée HS
		*** Alimentation Electrique: 
					<img src="./images/marker_p_green0.png" alt="Enedis Powered" width="12"> Enedis 
					- <img src="./images/marker_green0.png" alt="batterie" width="12"> Sur batterie
					- <img src="./images/marker_u_green0.png" alt="inconnue" width="12"> Inconnue
		
		

		<br>L'absence de mouvement ne présume pas du dysfonctionnement d'une station, l'inverse est également vrai... 
		<b>Tous les symboles de la V-BOX <a href="http://blog.velib-metropole.fr/wp-content/uploads/2018/02/PICTOS_LISTE_VELIB-.pdf" target="_blank">chez velib metropole</a> </b>
		<br><b>Ce site n'est pas un site officiel de vélib.</b> Les données utilisées proviennent de <a href="www.velib-metropole.fr">www.velib-metropole.fr</a> et appartiennent à leur propriétaire.
		<br>Contact: <a href="https://twitter.com/arno152153"><img border="0" alt="Twitter" src="https://abs.twimg.com/favicons/favicon.ico" width="15px" height="15px"></a>
		<?php 
			echo " (Dernière collecte: ";
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
				mysqlClose($link);				
			}
			echo ")</h3>";		

		?>		
	</div>	
	
	<div id="mypub">
		<iframe id="gads" src="./inc/ads.inc.html" width="100%" height="600px" />
	</div>

</body>
</html>