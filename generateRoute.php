<?php

require_once('SQLTools.php');

if(!isSet($_POST["seconds"]) || !isSet($_POST["startingStation"])) {
	die("invalid parameters");
}

$seconds = $_POST["seconds"];
$startingStation = $_POST["startingStation"];
$minTime = $_POST["minTime"];

$json_data = file_get_contents("http://www.divvybikes.com/stations/json");

$stations_json = json_decode($json_data, true);

$stations = [];

foreach($stations_json["stationBeanList"] as $stationObject) {
	
	$id = $stationObject["id"];
	$availableDocks = $stationObject["availableDocks"];
	$statusKey = $stationObject["statusKey"];
	$availableBikes = $stationObject["availableBikes"];
	
	$operational = $statusKey == 1 && $availableDocks >= 1 && $availableBikes >= 1;
	
	if($id == $startingStation) $operational = false;
	
	$stations[$id] = ["operational" => $operational, "availableDocks" => $availableDocks, "availableBikes" => $availableBikes];
}

$mysqli = getmysqli();

$route = generateRoute($startingStation, [], $seconds/2, $mysqli, $stations, $minTime);

if($route == NULL) {
	error("Please specify a longer route.");
}

$routeWithAdditionalValues = [];

for($i=0; $i<count($route); $i++) {
	$section = $route[$i];
	
	$station = $section["station"];
	
	$stationArray = [];
	
	$sql = "SELECT * FROM `Stations` WHERE `id` = '$station'";
	if($result = $mysqli->query($sql)) {
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$station_from_dictionary = $stations[$station];
		$availableDocks = $station_from_dictionary["availableDocks"];
		$availableBikes = $station_from_dictionary["availableBikes"];
		
		$stationArray = ["name" => $row["name"], "id" => $station, "latitude" => $row["latitude"], "longitude" => $row["longitude"], "availableDocks" => $availableDocks, "availableBikes" => $availableBikes];
	}
	
	$routeWithAdditionalValues[] = ["station" => $stationArray, "estTime" => $section["estTime"]];
}

/*$reversed = [];

for($i=count($route) - 2; $i>=0; $i--) {
	$reversed[] = ["station" => $route[$i]["station"], "estTime" => $route[$i+1]["estTime"]];
}*/

$reversed = array_reverse($routeWithAdditionalValues);

unset($reversed[0]);

$sql = "SELECT * FROM `Stations` WHERE `id` = '$startingStation'";
if($result = $mysqli->query($sql)) {
	$row = $result->fetch_array(MYSQLI_ASSOC);
	$reversed[] = ["station" => ["name" => $row["name"], "id" => $startingStation, "latitude" => $row["latitude"], "longitude" => $row["longitude"]], "estTime" => $route[0]["estTime"]];
}

echo(json_encode(array_merge($routeWithAdditionalValues, $reversed)));


function generateRoute($lastStation, $route, $time, $mysqli, $stations, $minTime) {
	
	$sql = "SELECT * FROM `RideLengths` WHERE `fromStation` = '$lastStation' ORDER BY `avgTime` ASC";
	
	if($result = $mysqli->query($sql)) {
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
		
			if(rand(1, 3) != 1) continue;
		
			$nextTime = $row["avgTime"];
			$toStation = $row["toStation"];
			$numRides = $row["numRides"];
			
			if($nextTime < $minTime) continue;
			
			if(!$stations[$toStation]["operational"]) continue; //station isn't operational
			
			$shouldContinue = false;
			
			foreach($route as $station) {
				$stationID = $station["station"];
				if($stationID == $toStation) {
					$shouldContinue = true;
					break;
				}
			}
			
			if($shouldContinue) continue;
			
			if($nextTime >= 25*60 || $numRides < 5) { //more than 25 mins, too risky
				continue;
			}
			
			$timeLeft = $time - $nextTime;
			
			if($timeLeft <= 1*60) { //cutting it too close to being late
				continue;
			}
			
			if($timeLeft >= 5*60) { //can break it up with more than 10 mins left
				
				$route[] = ["station" => $toStation, "estTime" => $nextTime];
				
				$generatedRoute = generateRoute($toStation, $route, $timeLeft, $mysqli, $stations, $minTime);
				
				if($generatedRoute == NULL) {
					if($timeLeft <= 10*60) {
						return $route;
					} else {
						continue;
					}
				}
				
				$route = $generatedRoute;
				return $route;
			} else {
				$route[] = ["station" => $toStation, "estTime" => $nextTime];
				return $route;
			}
		}
	} else {
		echo("Could not query");
	}
	
	return NULL;
}

?>