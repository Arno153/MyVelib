# Velib2018 Tools
Code source du site velib.philibert.info: analyse et interprétation des données du site Velib Métropole www.velib-metropole.fr/map#/


## Installation: 
	--> créer  la base de donnée MySQL à l'aide du script sql suivant: ./setup/CreateVelibToolDatabase.sql 
 	--> Renseigner le paramétrage Mysql dans ./inc/config.inc.php ($server, $user, $password, $db)
 	--> Remplacer "API-Key" par une clé d'api google map valide dans ./cron/stationLocationHasMoved.php
	--> Remplacer l'identifiant google analystics ou supprimer le js des pages
 	--> Adapter le .htaccess si le site n'est pas en https (NB: https requis pour la géolocalisation  sur la carte)
	--> les scripts du dossier cron sont prévu être appelés périodiquement 
		- velibAPIParser.php : collecte des données depuis Velib-metropole
		- stationLocationHasMoved.php: reverse geocoding: mise à jour des adresses des stations dont les coordonnées ont changé
		- mysqlBackup.php: sauvegarde mysql
 	--> renommer le fichier no.maintenance en .maintenance active les bandeaux de maintenance

## Ressources Open street Map et Leaflet 
	* Leaflet: https://leafletjs.com/
	* Leaflet plugins: https://leafletjs.com/plugins.html
	En particulier:
		*** Controles: Leaflet.Control.Custom.js --> fork de https://github.com/yigityuce/Leaflet.Control.Custom
		*** geolocalisation: Leaflet.Locate : https://github.com/domoritz/leaflet-locatecontrol
		*** Basculer en fulscreen: Leaflet.fullscreen: https://github.com/Leaflet/Leaflet.fullscreen
		*** Recherche de lieu: leaflet-control-geocoder: https://github.com/perliedman/leaflet-control-geocoder 

	* Le script tiles.php et les dossiers tiles associés sont inspirés du proxy php proposé par le wiki OSM
	https://wiki.openstreetmap.org/wiki/ProxySimplePHP afin de réduire la charge sur les server openstreet map


## Autres ressources 
	* Trie des tableaux: --> fork de sortable.js http://www.kryogenix.org/code/browser/sorttable/
	* Les markers sont générés à partir d'un fork bourrin de Google-Maps-Markers by Concept211
	https://github.com/Concept211/Google-Maps-Markers disponible ici https://github.com/Arno153/Google-Maps-Markers
	* graphiques réalisés avec plotly.js: https://plot.ly/javascript/
