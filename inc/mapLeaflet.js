function refresh(estimatedVelibNumber, bloqueTF, VAEFlag, placeVelib )
{
	bloqueTF = typeof bloqueTF !== 'undefined' ?  bloqueTF : 0; //si 1 : vélib bloqué (min 2J ou 3J), si 0 velib dispo
	VAEFlag =  typeof VAEFlag !== 'undefined' ?  VAEFlag : 0; //0 = tous, 1 = Meca, 2 = VAE 
	placeVelib = typeof placeVelib !== 'undefined' ?  placeVelib : 0; //0 = Velib, 1 = places 
	//var varUrl = 'carte-des-stations.php?lat='+map.getCenter().lat()+'&lon='+map.getCenter().lng()+'&zoom='+map.getZoom();		
	//window.location.href=varUrl;
	removeMarkersToMap();
	//addMarkersToMap();
	getStations(estimatedVelibNumber, bloqueTF, VAEFlag, placeVelib );
	document.getElementById('gads').contentDocument.location.reload(true);
}

function getUrlParam(param){
	var vars = {};
	window.location.href.replace( location.hash, '' ).replace( 
		/[?&]+([^=&]+)=?([^&]*)?/gi, // regexp
		function( m, key, value ) { // callback
			vars[key] = value !== undefined ? value : '';
		}
	);

	if ( param ) {
		return vars[param] ? vars[param] : null;	
	}
	return vars;
}
 
 function addYourLocationButton(map, marker) 
{
	var controlDiv = document.createElement('div');

	var firstChild = document.createElement('button');
	firstChild.style.backgroundColor = '#fff';
	firstChild.style.border = 'none';
	firstChild.style.outline = 'none';
	firstChild.style.width = '28px';
	firstChild.style.height = '28px';
	firstChild.style.borderRadius = '2px';
	firstChild.style.boxShadow = '0 1px 4px rgba(0,0,0,0.3)';
	firstChild.style.cursor = 'pointer';
	firstChild.style.marginRight = '10px';
	firstChild.style.padding = '0px';
	firstChild.title = 'Your Location';
	controlDiv.appendChild(firstChild);

	var secondChild = document.createElement('div');
	secondChild.style.margin = '5px';
	secondChild.style.width = '18px';
	secondChild.style.height = '18px';
	secondChild.style.backgroundImage = 'url(https://maps.gstatic.com/tactile/mylocation/mylocation-sprite-1x.png)';
	secondChild.style.backgroundSize = '180px 18px';
	secondChild.style.backgroundPosition = '0px 0px';
	secondChild.style.backgroundRepeat = 'no-repeat';
	secondChild.id = 'you_location_img';
	firstChild.appendChild(secondChild);

	google.maps.event.addListener(map, 'dragend', function() {
		//$('#you_location_img').css('background-position', '0px 0px');
	});

	firstChild.addEventListener('click', function() {
		var imgX = '0';
		var animationInterval = setInterval(function(){
			if(imgX == '-18') imgX = '0';
			else imgX = '-18';
			//$('#you_location_img').css('background-position', imgX+'px 0px');
		}, 500);
		if(navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(position) {
				var latlng = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
				marker.setPosition(latlng);					
				map.setCenter(latlng);
				userMarker.setVisible(true);
				map.setZoom(15);
				clearInterval(animationInterval);
				//$('#you_location_img').css('background-position', '-144px 0px');
			}, function() {
		handleLocationError(true, infoWindow2, map.getCenter());
	  });
		}
		else{
			clearInterval(animationInterval);
			//$('#you_location_img').css('background-position', '0px 0px');
			handleLocationError(false, infoWindow2, map.getCenter());
		}
	});

	controlDiv.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(controlDiv);
}
 
function handleLocationError(browserHasGeolocation, infoWindow2, pos) {
	infoWindow2.setPosition(pos);
	infoWindow2.setContent(browserHasGeolocation ?
						  'Error: The Geolocation service failed. <a href=https://velib.philibert.info/carte-des-stations.php>Did you try https?</a>' :
						  'Error: Your browser doesn\'t support geolocation.');
	infoWindow2.open(map);
  }

function getStations(estimatedVelibNumber,bloqueTF,VAEFlag, placeVelib)
{
   bloqueTF = typeof bloqueTF !== 'undefined' ?  bloqueTF : 0; //si 1 : vélib bloqué (min 2J ou 3J), si 0 velib dispo
   VAEFlag =  typeof VAEFlag !== 'undefined' ?  VAEFlag : 0; //0 = tous, 1 = Meca, 2 = VAE 
   placeVelib = typeof placeVelib !== 'undefined' ?  placeVelib : 0; //0 = Velib, 1 = places 
   
   var xmlhttp;
	// compatible with IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
			callback(xmlhttp.responseText);
		}
	}
	
	xmlhttp.onreadystatechange = function(event) {
		// XMLHttpRequest.DONE === 4
		if (this.readyState === XMLHttpRequest.DONE) {
			if (this.status === 200) {
				console.log("Réponse reçue: %s", this.responseText);
				locations = JSON.parse(	this.responseText);
				addMarkersToMap(estimatedVelibNumber, bloqueTF, VAEFlag, placeVelib);
				
			} else {
				console.log("Status de la réponse: %d (%s)", this.status, this.statusText);

			}
		}
	};
	
	url='./api/stationList.api.php?v=web&d='+estimatedVelibNumber;
	xmlhttp.open("POST", url, true);
	xmlhttp.send();			
}	
  
 function signaler(stationCode, YesNo)
 {
	   var xmlhttp;
		// compatible with IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function(){
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
				callback(xmlhttp.responseText);
			}
		}
		
		xmlhttp.onreadystatechange = function(event) {
			// XMLHttpRequest.DONE === 4
			if (this.readyState === XMLHttpRequest.DONE) {
				if (this.status === 200) {
					console.log("Réponse reçue: %s", this.responseText);
					var respArray;
					respArray = this.responseText.split("&");						
					document.getElementById("button-"+respArray[2]+"-"+respArray[1]).value = respArray[3];
					if(respArray[0]=="ok")
					{
					document.getElementById("button-"+respArray[2]+"-"+respArray[1]).disabled = true;
					sleep(2000);
					refresh();
					}						
				} else {
					console.log("Status de la réponse: %d (%s)", this.status, this.statusText);
				}
			}
		};
		
		url='./api/signalerStationHS.api.php?stationCode='+stationCode+'&HS='+YesNo;
		xmlhttp.open("POST", url, true);
		xmlhttp.send();
		
 }	
 

 
 function signalerAlimentee(stationCode, electrified)
 {
	 
	var nbrPopupDisplayed= 0;
	nbrPopupDisplayed = getCookie("popupdisplayed");
	if (nbrPopupDisplayed >= 1) 
	{
		confirmationCheck = true;
		setCookie("popupdisplayed",nbrPopupDisplayed, 2);
	} 
	else 
	{
		confirmationCheck = confirm("Par alimentée, on entend raccordée au réseau électrique et non sur batterie!\nLe sommet de la borne est allumé et s'ils existent l'écran, le lecteur CB, etc... fonctionnent ");
		if (confirmationCheck) 
		{
			setCookie("popupdisplayed",nbrPopupDisplayed*1+1, 1);
		}
	}
	 
	 
	 
	 if(confirmationCheck)
	 {
	   var xmlhttp;
		// compatible with IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function(){
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
				callback(xmlhttp.responseText);
			}
		}
		
		xmlhttp.onreadystatechange = function(event) {
			// XMLHttpRequest.DONE === 4
			if (this.readyState === XMLHttpRequest.DONE) {
				if (this.status === 200) {
					console.log("Réponse reçue: %s", this.responseText);
					var respArray;
					respArray = this.responseText.split("&");						
					document.getElementById("button-elec-"+respArray[1]).value = respArray[2];
					if(respArray[0]=="ok")
					{
					document.getElementById("button-elec-"+respArray[1]).disabled = true;
					sleep(1000);
					refresh();
					}						
				} else {
					console.log("Status de la réponse: %d (%s)", this.status, this.statusText);
				}
			}
		};
		
		url='./api/stationRaccordee.api.php?stationCode='+stationCode+'&electrified='+electrified;
		xmlhttp.open("POST", url, true);
		xmlhttp.send();
	 }
		
 }	
 
 function sleep(milliseconds) {
	  var start = new Date().getTime();
	  for (var i = 0; i < 1e7; i++) {
		if ((new Date().getTime() - start) > milliseconds){
		  break;
		}
	  }
	}
	

function setCookie(cname,cvalue,exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires=" + d.toGMTString();
	document.cookie = cname + "=" + cvalue.toString() + ";" + expires + ";path=/";
}

function getCookie(cname) {
	var name = cname + "=";
	var decodedCookie = decodeURIComponent(document.cookie);
	var ca = decodedCookie.split(';');
	for(var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) == 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

function removeMarkersToMap()
{
	for (var i = 0; i < markers.length; i++) 
	{
	  mymap.removeLayer(markers[i]);			  
	}
	markers = [];

}

function addMarkersToMap(estimatedVelibNumber, bloqueTF, VAEFlag, placeVelib )
{
	for (i = 0; i < locations.length; i++) 
	{ 
		if(locations[i]['stationSignaleHS']=='1' )
		{
			HS = 'x';
		}		
		else 
		{
			HS = '';
		}
		
		/* 
		// Ne plus afficher l'électrification sur la carte
		if(locations[i]['stationConnected']=='1' )
		{
			pow = 'p_';
		}	
		else if(locations[i]['stationConnected']=='2' )
		{
			pow = 'u_';
		}	
		else 
		{
			pow = '';
		}*/
		
		pow = '';
		
		if(placeVelib ==1)
		{
			
			nbBikeMarker = (parseInt(locations[i]['nbFreeEDock']) +  parseInt(locations[i]['nbFreeDock'])).toString();
		}
		else
		{
			// détermination de la valeur nbr velib du marker en fonction du mode
			if(estimatedVelibNumber==0) // nombre de velib officiel
			{
				//0 = tous, 1 = Meca, 2 = VAE 
				if(VAEFlag == 0)
					nbBikeMarker = (parseInt(locations[i]['stationNbBike'])+parseInt(locations[i]['stationNbEBike'])).toString();
				else if (VAEFlag ==1)
					nbBikeMarker = parseInt(locations[i]['stationNbBike']).toString();
				else if (VAEFlag ==2)
					nbBikeMarker = parseInt(locations[i]['stationNbEBike']).toString();
			}
			else
			{
				if(bloqueTF ==1)
					nbBikeMarker = Math.max(0,parseInt(locations[i]['stationMinVelibNDay'])).toString();
				else
				{				
					//0 = tous, 1 = Meca, 2 = VAE 
					if(VAEFlag == 0)
						nbBikeMarker = Math.max(0,parseInt(locations[i]['stationNbBike'])+parseInt(locations[i]['stationNbEBike'])-parseInt(locations[i]['stationMinVelibNDay'])).toString();
					else if (VAEFlag ==1)
						nbBikeMarker = Math.max(0,parseInt(locations[i]['stationNbBike'])-parseInt(locations[i]['stationMinVelibNDay'])+parseInt(locations[i]['stationMinEVelibNDay'])).toString();
					else if (VAEFlag ==2)					
						nbBikeMarker = Math.max(0,parseInt(locations[i]['stationNbEBike'])-parseInt(locations[i]['stationMinEVelibNDay'])).toString();
				}
			}
		}
		
		if(bloqueTF ==1) // selection du market pour la carte velib bloqué
		{
			if(nbBikeMarker<1)
			{
				iconurl = './images/marker_'+pow+'green'+HS+nbBikeMarker+'.png'				
			} 
			else if(nbBikeMarker < 4)
			{
				iconurl = './images/marker_'+pow+'yellow'+HS+nbBikeMarker+'.png'			
			}
			else if(nbBikeMarker < 8)
			{
				iconurl = './images/marker_'+pow+'orange'+HS+nbBikeMarker+'.png'
			}
			else
			{
				iconurl = './images/marker_'+pow+'red'+HS+nbBikeMarker+'.png'
			}			
		}		
		else
		{
			if( locations[i]['stationState']!='Operative')
			{
				iconurl = './images/marker_'+pow+'grey'+nbBikeMarker+'.png'				
			} 			
			else if(locations[i]['hourLastExistDiff']<1)
			{
				iconurl = './images/marker_'+pow+'green'+HS+nbBikeMarker+'.png'				
			} 
			else if(locations[i]['hourLastExistDiff']<3||(locations[i]['hourLastExistDiff']<4&&locations[i]['hourdiff']<2))
			{
				iconurl = './images/marker_'+pow+'yellow'+HS+nbBikeMarker+'.png'			
			}
			else if(locations[i]['hourLastExistDiff']<12||(locations[i]['hourLastExistDiff']<16&&locations[i]['hourdiff']<8))
			{
				iconurl = './images/marker_'+pow+'orange'+HS+nbBikeMarker+'.png'
			}
			else if(locations[i]['hourLastExistDiff']<24||(locations[i]['hourLastExistDiff']<32&&locations[i]['hourdiff']<16))
			{
				iconurl = './images/marker_'+pow+'red'+HS+nbBikeMarker+'.png'
			}			
			else
			{
				iconurl = './images/marker_'+pow+'purple'+HS+nbBikeMarker+'.png'
			}	
		}		
		

			
		marker = L.marker([locations[i]['stationLat'], locations[i]['stationLon']], 
		{
			icon: L.icon({
					iconUrl: iconurl,
					iconAnchor: [11, 40],
					popupAnchor:  [0, -41]
				})
		}).addTo(mymap);	

		
		var infoWindowContent = '<div id="content">'+
		'<h3>'+locations[i]['station'];

		if(locations[i]['stationConnected']=='1')
		{
			infoWindowContent = infoWindowContent + '   <img src="./images/electified.png" alt="'+locations[i]['stationConnectionDate']+'" width="20">';
		}
		
		infoWindowContent = infoWindowContent +'</h3>'
			+ locations[i]['stationAdress'] + '<br> Cette station est officiellement ';
		
		if(locations[i]['stationState']=='Operative')
		{
			infoWindowContent = infoWindowContent + 'en service.';
		}
		else if(locations[i]['stationState']=='Close')
		{
			infoWindowContent = infoWindowContent + 'Fermée.';
		}
		else
		{
			infoWindowContent = infoWindowContent + 'en travaux.';
		}
		
		infoWindowContent = infoWindowContent + 
		'<br><br> Nombre de velib: ' +locations[i]['stationNbBike'] +' ( et "Park+": ' +locations[i]['stationNbBikeOverflow'] +')';
		
		tmp=0;
		tmp= parseInt(locations[i]['nbFreeEDock']) +  parseInt(locations[i]['nbFreeDock']);
		
		infoWindowContent = infoWindowContent +
		'<br> Nombre de VAE: ' +locations[i]['stationNbEBike'] +' (et "Park+": ' +locations[i]['stationNbEBikeOverflow'] +')'+
		'<br> Places libres: ' +  tmp;
		
		
		
		if(parseInt(locations[i]['stationMinVelibNDay'])+parseInt(locations[i]['stationVelibMinVelibOverflow']) > 0 )
		{
			if(estimatedVelibNumber == 0)
				estimatedVelibNumberDisplayedIW= 3;
			else	
				estimatedVelibNumberDisplayedIW = estimatedVelibNumber;
			
			infoWindowContent = infoWindowContent +
			'<br> Sur les '+estimatedVelibNumberDisplayedIW+' derniers jours il n\'y a jamais eu moins de ' + locations[i]['stationMinVelibNDay'] + ' velib ( dont ' + locations[i]['stationMinEVelibNDay'] + ' VAE) (et '+locations[i]['stationVelibMinVelibOverflow']+' en park+)' ; 
			
			if( (parseInt(locations[i]['stationMinVelibNDay'])+parseInt(locations[i]['stationVelibMinVelibOverflow'])) == locations[i]['tot_station_nb_bike'] )
			{			
				infoWindowContent = infoWindowContent + '   <img src="./images/warning.png" width="15">';
			}		
		}	

		infoWindowContent = infoWindowContent + '<br><br>Dernier Mouvement il y a : ' +locations[i]['timediff']+'';
		infoWindowContent = infoWindowContent + '<br>Dernier retrait il y a : ' +locations[i]['lastExistDiff']+'';
		if(locations[i]['hourdiff']<1&&locations[i]['hourLastExistDiff']<1)
		{
			
		}
		else if(locations[i]['hourdiff']<1)
		{
			infoWindowContent = infoWindowContent + '<br>Ca bouge mais les derniers mouvements sont des retours ';
		}				
		else if(locations[i]['hourdiff']<4)
		{
			infoWindowContent = infoWindowContent + '<br>Ca bouge pas beaucoup officiellement... Faut voir... ';
		}
		else if(locations[i]['hourdiff']<24)
		{
			infoWindowContent = infoWindowContent + '<br>Il y a longtemps que rien n\'a été enregistré ici... <br>Prudence!!!';
		}				
		else
		{
			infoWindowContent = infoWindowContent + '<br>Il y a très très longtemps que rien n\'a été enregistré ici... <br>Prudence!!!';
		}
		
		infoWindowContent = infoWindowContent + '<br><br>Informations communautaire: La station ';
		if(locations[i]['stationSignaleHS']=='1')
		{
			infoWindowContent = infoWindowContent + '<br> - a été signalée comme étant HS le ' + locations[i]['stationSignaleHSDate'] +' à '+locations[i]['stationSignaleHSHeure']  ;
			if(locations[i]['nrRetraitDepuisSignalement']>0)
				infoWindowContent = infoWindowContent + '<br>   ** '+locations[i]['nrRetraitDepuisSignalement']+ ' retrait(s) depuis le signalement.' ;
			infoWindowContent = infoWindowContent + '<br>   ** fonctionne à nouveau? <input type="button" id="button-false-'+locations[i]['stationCode']+'" value="Signaler" onclick="signaler('+locations[i]['stationCode']+',false)" />';
		}
		else
		{
			infoWindowContent = infoWindowContent + '<br> - ne marche pas? <input type="button" id="button-true-'+locations[i]['stationCode']+'" value="Signaler" onclick="signaler('+locations[i]['stationCode']+',true)" />';
		}
		
		if(locations[i]['stationConnected']=='1')
		{
			infoWindowContent = infoWindowContent + '<br> - est signalée comme alimentée! <input type="button" id="button-elec-'+locations[i]['stationCode']+'" value="Signaler une erreur" onclick="signalerAlimentee('+locations[i]['stationCode']+',false)" />';
		}
		else if (locations[i]['stationConnected']=='0')
		{
			infoWindowContent = infoWindowContent + '<br> - est signalée comme non alimentée! <input type="button" id="button-elec-'+locations[i]['stationCode']+'" value="Signaler une erreur" onclick="signalerAlimentee('+locations[i]['stationCode']+',true)" />';
		}
		else 
		{
			infoWindowContent = infoWindowContent + '<br> - est elle alimentée? <input type="button" id="button-elec-'+locations[i]['stationCode']+'" value="Oui" onclick="signalerAlimentee('+locations[i]['stationCode']+',true)" />';
			infoWindowContent = infoWindowContent + ' <input type="button" id="button-elec-'+locations[i]['stationCode']+'" value="Non" onclick="signalerAlimentee('+locations[i]['stationCode']+',false)" />';
		}
		
		infoWindowContent = infoWindowContent + '<p>Plus d\'infos: <a href="https://velib.nocle.fr/station.php?code='+locations[i]['stationCode']+'" target="_blank">velib.nocle.fr</a> ';
		infoWindowContent = infoWindowContent + '</div>';			
		
		marker.bindPopup(infoWindowContent);			
		
		
		markers.push(marker);	
	}
} 





//js for mouvements heatmap
function getHeatmapData(mvtJmN)
{
   var xmlhttp;
	// compatible with IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
			callback(xmlhttp.responseText);
		}
	}
	
	xmlhttp.onreadystatechange = function(event) {
		// XMLHttpRequest.DONE === 4
		if (this.readyState === XMLHttpRequest.DONE) {
			if (this.status === 200) {
				console.log("Réponse reçue: %s", this.responseText);
				//var jsonDataArray = JSON.parse(	this.responseText);
				locations = JSON.parse(	this.responseText);;
				displayHeatMap();
				
			} else {
				console.log("Status de la réponse: %d (%s)", this.status, this.statusText);

			}
		}
	};
	
	url='./api/stationList.api.php?v=heatmap&d='+mvtJmN;
	xmlhttp.open("POST", url, true);
	xmlhttp.send();			
}	


function displayHeatMap()
{
	var heatMapData = [];
	var maxValue = 0;
	for(var i = 0; i< locations.length;i++)
	{	
		if(parseInt(locations[i]['stationVelibExit'])>maxValue)
			maxValue = parseInt(locations[i]['stationVelibExit']);	
	}
	
	
	for(var i = 0; i< locations.length;i++)
	{
		if(parseInt(locations[i]['stationVelibExit'])>0)
		{	
			heatMapData.push([locations[i]['stationLat'], locations[i]['stationLon'],(locations[i]['stationVelibExit'])/maxValue]);
		}
	}	
	
//heatMapData = heatMapData.map(function (p) { return [p[0], p[1]]; });
var heat = L.heatLayer(heatMapData, {radius: 30, maxZoom: 10, minOpacity:0.05 }).addTo(mymap);
}

//js for mouvements map  
function getMvtMapData(mvtJmN)
{
   var xmlhttp;
	// compatible with IE7+, Firefox, Chrome, Opera, Safari
	xmlhttp = new XMLHttpRequest();
	xmlhttp.onreadystatechange = function(){
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200){
			callback(xmlhttp.responseText);
		}
	}
	
	xmlhttp.onreadystatechange = function(event) {
		// XMLHttpRequest.DONE === 4
		if (this.readyState === XMLHttpRequest.DONE) {
			if (this.status === 200) {
				console.log("Réponse reçue: %s", this.responseText);
				locations = JSON.parse(	this.responseText);
				displayMvtMap();
				
			} else {
				console.log("Status de la réponse: %d (%s)", this.status, this.statusText);

			}
		}
	};
	
	url='./api/stationList.api.php?v=heatmap&d='+mvtJmN;
	xmlhttp.open("POST", url, true);
	xmlhttp.send();			
}	


function displayMvtMap()
{
	var HS;
	document.getElementById('gads').contentDocument.location.reload(true);
	removeMarkersToMap();
	
	for (i = 0; i < locations.length; i++) 
	{ 
		
		// détermination de la valeur du marker 
		nbBikeMarker = (parseInt(locations[i]['stationVelibExit'])).toString();
		if(nbBikeMarker>400)
			nbBikeMarker = 400;
		
		if(locations[i]['stationState'] =='Operative')
			HS = "";
		else
			HS = "x"
		
		if( nbBikeMarker == 0)
		{
			iconurl = './images/marker_'+'grey'+HS+nbBikeMarker+'.png';			
		} 			
		else if( nbBikeMarker < 20)
		{
			iconurl = './images/marker_'+'yellow'+HS+nbBikeMarker+'.png';
		} 
		else if( nbBikeMarker < 50)
		{
			iconurl = './images/marker_'+'orange'+HS+nbBikeMarker+'.png';	
		}
		else if( nbBikeMarker < 110)
		{
			iconurl = './images/marker_'+'green'+HS+nbBikeMarker+'.png';				
		}
		else if( nbBikeMarker < 200)
		{
			iconurl = './images/marker_'+'red'+HS+nbBikeMarker+'.png';				
		}			
		else
		{
			iconurl = './images/marker_'+'purple'+HS+nbBikeMarker+'.png';	
		}		
			
		marker = L.marker([locations[i]['stationLat'], locations[i]['stationLon']], 
		{
			icon: L.icon({
					iconUrl: iconurl,
					iconAnchor: [11, 40],
					popupAnchor:  [0, -41]
				})
		}).addTo(mymap);		
		markers.push(marker);	
	}

}

/**
 * Retourne une fonction qui, tant qu'elle continue à être invoquée,
 * ne sera pas exécutée. La fonction ne sera exécutée que lorsque
 * l'on cessera de l'appeler pendant plus de N millisecondes.
 * Si le paramètre `immediate` vaut vrai, alors la fonction 
 * sera exécutée au premier appel au lieu du dernier.
 * Paramètres :
 *  - func : la fonction à `debouncer`
 *  - wait : le nombre de millisecondes (N) à attendre avant 
 *           d'appeler func()
 *  - immediate (optionnel) : Appeler func() à la première invocation
 *                            au lieu de la dernière (Faux par défaut)
 *  - context (optionnel) : le contexte dans lequel appeler func()
 *                          (this par défaut)
 */
function debounce(func, wait, immediate, context) {
	var result;
	var timeout = null;
	return function() {
		var ctx = context || this, args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) result = func.apply(ctx, args);
		};
		var callNow = immediate && !timeout;
		// Tant que la fonction est appelée, on reset le timeout.
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) result = func.apply(ctx, args);
		return result;
	};
}
