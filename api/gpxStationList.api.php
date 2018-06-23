<?php 

include "./../inc/mysql.inc.php";
$link = mysqlConnect();
if (!$link) {
	echo "Error: Unable to connect to MySQL." . PHP_EOL;
	echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
	echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
	exit;
}

$query_rsMap = 
	"	
		SELECT 
			concat(`stationCode`,'-',`stationName`) as Title,
			`stationLat` as Lat,
			`stationLon` as 'Long',
			stationState
		FROM `velib_station` 
		where 
			`stationNbEDock`+
			  `stationNbBike`+
			  `stationNbEBike`+
			  `nbFreeDock`+
			  `nbFreeEDock` > 0 
			and stationHidden = 0
	";
$rsMap = mysqli_query($link, $query_rsMap) or die(mysqli_error($link));
$row_rsMap = mysqli_fetch_array($rsMap, MYSQLI_ASSOC);
$totalRows_rsMap = mysqli_num_rows($rsMap);

// Send the headers
# This line will stream the file to the user rather than spray it across the screen
header("Content-type: application/octet-stream");

# replace excelfile.xls with whatever you want the filename to default to
header("Content-Disposition: attachment; filename=velibStation.gpx");
header("Pragma: no-cache");
header("Expires: 0");
?>
<?php echo('<?xml version="1.0" encoding="UTF-8"?>
<gpx version="1.1" creator="ArnoP@Twitter" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.topografix.com/GPX/1/1" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
<metadata>
	<name>velib.philibert.info</name>
	<desc>velib.philibert.info</desc>
	<author>ArnoP@twitter</author>
</metadata>
'); ?>
<?php if ($totalRows_rsMap > 0) { // Show if recordset not empty ?>
<?php do { ?>
<wpt lat="<?php echo $row_rsMap['Lat']; ?>" lon="<?php echo
$row_rsMap['Long']; ?>">
  <name><?php echo $row_rsMap['Title']; ?></name>
  <cmt><?php echo $row_rsMap['stationState']; ?></cmt>
  <sym>Waypoint</sym>
</wpt>
<?php } while ($row_rsMap = mysqli_fetch_array($rsMap, MYSQLI_ASSOC)); ?>
</gpx>
<?php } // Show if recordset not empty ?>


<?php
	mysqlClose($link);
?> 