<?php
	include "./inc/config.inc.php";
    $ttl = 86400; //cache timeout in seconds
  
    $x = intval($_GET['x']);
    $y = intval($_GET['y']);
    $z = intval($_GET['z']);
    if (isset($_GET['r'])) {
		$r = strip_tags($_GET['r']);
	} else {
		$r = 'hot';
	}

    switch ($r)
    {
      case 'mapnik':
        $r = 'mapnik';
		$file = "tiles/$r/${z}_${x}_$y.png";
        break;
      case 'hot':
        $r = 'hot';
		$file = "tiles/$r/${z}_${x}_$y.png";
        break;	
      case 'gp':
        $r = 'gp';
		$file = "tiles/$r/${z}_${x}_$y.jpg";
		$ttl = 864000; // surcharge du cache timeout pour geoportail (x10)
        break;	
      case 'gp2':
        $r = 'gp2';
		$file = "tiles/$r/${z}_${x}_$y.jpg";
		$ttl = 864000; // surcharge du cache timeout pour geoportail (x10)
        break;			
      case 'osma':
      default:
        $r = 'osma';
		$file = "tiles/$r/${z}_${x}_$y.png";
        break;
    }

    
    if (!is_file($file) || filemtime($file)<time()-($ttl*30))
    {
      $server = array();
      switch ($r)
      {
      	case 'mapnik':
          $server[] = 'a.tile.openstreetmap.org';
          $server[] = 'b.tile.openstreetmap.org';
          $server[] = 'c.tile.openstreetmap.org';

          $url = 'http://'.$server[array_rand($server)];
          $url .= "/".$z."/".$x."/".$y.".png";
          break;
      	
		case 'gp':
          $url = 'https://wxs.ign.fr/'.$geoportailAPIKey.'/geoportail/wmts?service=WMTS&request=GetTile&version=1.0.0&tilematrixset=PM&';
          $url .= "tilematrix=".$z."&tilecol=".$x."&tilerow=".$y."&layer=GEOGRAPHICALGRIDSYSTEMS.MAPS&format=image/jpeg&style=normal";
          break;

		case 'gp2':
          $url = 'https://wxs.ign.fr/'.$geoportailAPIKey.'/geoportail/wmts?service=WMTS&request=GetTile&version=1.0.0&tilematrixset=PM&';
          $url .= "tilematrix=".$z."&tilecol=".$x."&tilerow=".$y."&layer=ORTHOIMAGERY.ORTHOPHOTOS&format=image/jpeg&style=normal";
          break;		  
		  
		case 'hot':
          $server[] = 'a.tile.openstreetmap.fr';
          $server[] = 'b.tile.openstreetmap.fr';
          $server[] = 'c.tile.openstreetmap.fr';
  
          $url = 'http://'.$server[array_rand($server)];
          $url .= "/".$r."/".$z."/".$x."/".$y.".png";
          break;
		  
      	case 'osma':
      	default:
          $server[] = 'a.tah.openstreetmap.org';
          $server[] = 'b.tah.openstreetmap.org';
          $server[] = 'c.tah.openstreetmap.org';

          $url = 'http://'.$server[array_rand($server)].'/Tiles/tile.php';
          $url .= "/".$z."/".$x."/".$y.".png";
          break;
      }

	  //error_log( date("Y-m-d H:i:s")." Get Tile from server");
      $ch = curl_init($url);	 
      $fp = fopen($file, "w");
      curl_setopt($ch, CURLOPT_FILE, $fp);
	  //curl_setopt($ch, CURLOPT_REFERER, 'https://velib.philibert.info');
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $res = curl_exec($ch);
      curl_close($ch);
      $res2 = fflush($fp);    // need to insert this line for proper output when tile is first requestet
      $res3=fclose($fp);
	  //error_log("tile url: ".$url." curl session: ".$ch." curul get status: ".$res." file write status: ".$res2.$res3);	  
    }

    $exp_gmt = gmdate("D, d M Y H:i:s", time() + $ttl * 60) ." GMT";
    $mod_gmt = gmdate("D, d M Y H:i:s", filemtime($file)) ." GMT";
    header("Expires: " . $exp_gmt);
    header("Last-Modified: " . $mod_gmt);
    header("Cache-Control: public, max-age=" . $ttl * 60);
    // for MSIE 5
    header("Cache-Control: pre-check=" . $ttl * 60, FALSE);  
    header ('Content-Type: image/png');
    readfile($file);
  ?>