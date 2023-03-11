<link rel="stylesheet" media="all" href="./css/newMenu.css?<?php echo filemtime('./css/newMenu.css');?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" 
	integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" 
	crossorigin="anonymous" 
	referrerpolicy="no-referrer" />

<div id="cssmenu"><div id="menu-button">Menu</div>
  <ul>
     <li><a href="./"><i class="fa-solid fa-bicycle"></i> Accueil</a></li>
     <li><a href="./carte-des-stations.php"><i class="fa-brands fa-leanpub"></i> Carte</a></li>
     <li><span class="submenu-button"></span><a href="#">+ de cartes</a>
        <ul>
           <li><a href="./carte-des-mouvements.php"><i class="fa-regular fa-map"></i> Carte des mouvements</a></li>
           <li><a href="./carte-heatmap.php"><i class="fa-regular fa-map"></i> Heat Map des mouvements</a></li>
		   <li><a href="./carte-heatmap-velib.php"><i class="fa-regular fa-map"></i> Heat Map des Velib</a></li>
		   <li><a href="./carte-des-velib-bloques.php"><i class="fa-regular fa-map"></i> Carte des velib bloqués</a></li>
        </ul>
     </li>
     <li><span class="submenu-button"></span><a href="#">+ de stats</a>
        <ul>
           <li><a href="./liste-des-stations.php"><i class="fa-solid fa-bars"></i> Liste des stations</a></li>
           <li><a href="./velib-par-commune.php"><i class="fa-solid fa-bars"></i> Velib disponibles par commune</a></li>		   
		   <li><a href="./top5Journee.php"><i class="fa-solid fa-bars"></i> Utilisations du jour et des 5 plus fortes journées</a></li>
		   <li><a href="./15DerniersJours.php"><i class="fa-solid fa-bars"></i> Utilisations des 15 derniers jours</a></li>
		   <li><a href="./networkBikeRatio.php"><i class="fa-solid fa-bars"></i> Ratio bornes/velib</a></li>
        </ul>
     </li>
     <li class="has-sub"><span class="submenu-button"></span><a href="#">Contact et autres</a>
        <ul>
           <li><a href="https://twitter.com/arno152153"><img alt="Twitter" src="https://abs.twimg.com/favicons/favicon.ico" width="12px" height="12px" border="0"> Me contacter </a></li>           
           <li><a href="https://github.com/Arno153/MyVelib"><img alt="Twitter" src="https://github.githubassets.com/favicon.ico" width="12px" height="12px" border="0"> Sources du projet</a></li>
        </ul>
     </li>	 
  </ul>
</div>
