<?php

require_once('SQLTools.php');

if(!isSet($_POST["seconds"]) || !isSet($_POST["startingStation"])) {
	die("invalid parameters");
}

$seconds = $_POST["seconds"];
$startingStation = $_POST["startingStation"];

$mysqli = getmysqli();

$route = generateRoute($startingStation, [], $seconds/2, $mysqli);

if($route == NULL) {
	error("Please specify a longer route.");
}

$routeWithAdditionalValues = [];

foreach($route as $section) {

	$fromStation = $section["from"];
	$toStation = $section["to"];

	$fromArray = [];
	
	$sql = "SELECT * FROM `Stations` WHERE `id` = '$fromStation'";
	if($result = $mysqli->query($sql)) {
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$fromArray = ["name" => $row["name"], "latitude" => $row["latitude"], "longitude" => $row["longitude"]];
	}
	
	$toArray = [];
	
	$sql = "SELECT * FROM `Stations` WHERE `id` = '$toStation'";
	if($result = $mysqli->query($sql)) {
		$row = $result->fetch_array(MYSQLI_ASSOC);
		$toArray = ["name" => $row["name"], "latitude" => $row["latitude"], "longitude" => $row["longitude"]];
	}
	
	$routeWithAdditionalValues[] = ["from" => $fromArray, "to" => $toArray, "estTime" => $section["estTime"]];
}

$reversed = [];

foreach($routeWithAdditionalValues as $section) {
	$temp_section = $section;
	$from = $temp_section["from"];
	$temp_section["from"] = $temp_section["to"];
	$temp_section["to"] = $from;
	$reversed[] = $temp_section;
}

$reversed = array_reverse($reversed);

echo(json_encode(array_merge($routeWithAdditionalValues, $reversed)));


function generateRoute($fromStation, $route, $time, $mysqli) {
	
	$sql = "SELECT * FROM `RideLengths` WHERE `fromStation` = '$fromStation' ORDER BY `avgTime` DESC";
	
	if($result = $mysqli->query($sql)) {
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$nextTime = $row["avgTime"];
			$toStation = $row["toStation"];
			$numRides = $row["numRides"];
			
			if($nextTime >= 25*60 || $numRides < 5) { //more than 25 mins, too risky
				continue;
			}
			
			$timeLeft = $time - $nextTime;
			
			if($timeLeft <= 2.5*60) { //cutting it too close to being late
				continue;
			}
			
			if($timeLeft >= 10*60) { //can break it up with more than 10 mins left
				
				$generatedRoute = generateRoute($toStation, [], $timeLeft, $mysqli);
				
				if($generatedRoute == NULL) continue;
				
				$route[] = ["from" => $fromStation, "to" => $toStation, "estTime" => $nextTime];
				
				$route = array_merge($route, $generatedRoute);
				return $route;
			} else {
				$route[] = ["from" => $fromStation, "to" => $toStation, "estTime" => $nextTime];
				return $route;
			}
		}
	} else {
		echo("Could not query");
	}
	
	return NULL;
}

?>