<?php

require_once('SQLTools.php');

$mysqli = getmysqli();

$json_data = file_get_contents("http://www.divvybikes.com/stations/json");

$stations = json_decode($json_data, true);

foreach($stations["stationBeanList"] as $stationObject) {
	
	$id = $stationObject["id"];
	$name = $stationObject["stationName"];
	$latitude = $stationObject["latitude"];
	$longitude = $stationObject["longitude"];
	
	
	$sql = "INSERT INTO `Stations`(`id`, `name`, `latitude`, `longitude`) VALUES ('$id','$name','$latitude','$longitude')";
	/*if(!$mysqli->query($sql)) {
			error("could not update");
	}*/
}

?>