<?php

require_once('SQLTools.php');

$mysqli = getmysqli();

$times = [];

$row = 0;

$numSame = 0;

if (($handle = fopen("data/Q4.csv", "r")) !== FALSE) {
  while (($data = fgetcsv($handle, ",")) !== FALSE) {
    
    if($row == 0) {
    	$row++;
    	continue; //skip header
    }
    
    $time = $data[0];
    
    $from = $data[1];
    $to = $data[2];
    
    if($from == $to) continue; //don't want going to the same place
    
    if(array_key_exists("$from $to", $times)) {
    	$time_array = $times["$from $to"];
    	$times["$from $to"] = [$time_array[0] + $time, $time_array[1] + 1];
    	$numSame++;
    } else {
    	$times["$from $to"] = [$time, 1];
    }
    
    $row++;
    
  }
  fclose($handle);
}

$time_averages = [];

foreach($times as $key => $value) {
	$exploded = explode(" ", $key);
	$from = $exploded[0];
	$to = $exploded[1];
	$total = $value[1];
	$avg = $value[0] / $total; //(total seconds) / (num times)
	
	$sql = "SELECT `avgTime`, `numRides` FROM `RideLengths` WHERE `fromStation` = '$from' AND `toStation` = '$to'";
	if($result = $mysqli->query($sql)) {
		if($result->num_rows == 0) { //does not exist
			$sql = "INSERT INTO `RideLengths`(`fromStation`, `toStation`, `avgTime`, `numRides`) VALUES ('$from', '$to', '$avg', '$total')";
			/*if(!$mysqli->query($sql)) {
				die("could not insert");
			}*/
		} else { //does exist
		
			$row = $result->fetch_array(MYSQLI_ASSOC);
			
			$oldAvg = $row["avgTime"];
			$oldNumRides = $row["numRides"];
		
			$newNumRides = $oldNumRides + $total;
			$newAvg = (($oldAvg*$oldNumRides) + ($value[0])) / $newNumRides;
		
			$sql = "UPDATE `RideLengths` SET `avgTime` = $newAvg, `numRides` = $newNumRides WHERE `fromStation` = '$from' AND `toStation` = '$to'";
			/*if(!$mysqli->query($sql)) {
				die("could not update");
			}*/
		}
	} else {
		die("could not select");
	}
	
	//$time_averages[] = ["from" => $from, "to" => $to, "avgTime" => $avg];
}

echo("Q4: Out of $row rows, $numSame were the same.");

//echo(json_encode($time_averages));

?>