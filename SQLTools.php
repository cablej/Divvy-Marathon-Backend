<?php



function getmysqli() {
	$mysqli = new mysqli("localhost", "tiphzjin", "Password changed to protect the innocent", "tiphzjin_divvy_marathon");
	return $mysqli;
}


function cryptPass($input, $rounds = 12){ //Sequence - cryptPass, save hash in db, crypt(input, hash) == hash
	$salt = "";
	$saltChars = array_merge(range('A','Z'), range('a','z'), range(0,9));
	for($i = 0; $i < 22; $i++){
		$salt .= $saltChars[array_rand($saltChars)];
	}
	return crypt($input, sprintf('$2y$%02d$', $rounds) . $salt);
}


function signIn($username, $password, $mysqli) {
	$sql = "SELECT `username`, `password` FROM `Users` WHERE username = '$username'";
	$row = query_one($sql, $mysqli);
	$hashedPass = $row["password"];
	if(crypt($password, $hashedPass) == $hashedPass) {
		$key = uniqid();
		$sql = "UPDATE `Users` SET `key` = '$key' WHERE `username` = '$username'";
		if(!$mysqli->query($sql)) {
			error("could not log in");
		}
		
		return $key;
		
	} else {
		error("wrong username/password");
	}
}

function createUser($username, $newPass, $mysqli) {
	$hashedPass = cryptPass($newPass);
	$sql = "SELECT `username` FROM `Users` WHERE username = '$username'";
	if($result = $mysqli->query($sql)) {
		if($result->num_rows == 0) {
			$sql = "INSERT INTO `Users`(`username`, `password`) VALUES ('$username', '$hashedPass')";
			if(!$mysqli->query($sql)) {
				error("could not create user");
			}
			return signIn($username, $newPass, $mysqli);
		} else {
			error("username already used");
		}
	} else {
		error("could not create user");
	}
}

function hitStation($key, $miles, $mysqli) {

	$sql = "SELECT `totalStations`, `totalMiles` FROM `Users` WHERE `key` = '$key'";
	$row = query_one($sql, $mysqli);
	$totalStations = $row["totalStations"];
	$totalMiles = $row["totalMiles"];
	
	$newStations = $totalStations + 1;
	$newMiles = $totalMiles + $miles;
	
	$sql = "UPDATE `Users` SET `totalStations` = '$newStations', `totalMiles` = '$newMiles' WHERE `key` = '$key'";
	if(!$mysqli->query($sql)) {
		error("could not hit station");
	}
	
	return ["success" => "true"];
}

function userStats($key, $mysqli) {
	$sql = "SELECT `totalStations`, `totalMiles` FROM `Users` WHERE `key` = '$key'";
	$row = query_one($sql, $mysqli);
	$totalStations = $row["totalStations"];
	$totalMiles = $row["totalMiles"];
	
	return ["totalMiles" => $totalMiles, "totalStations" => $totalStations];
}

function leaderboard($type, $mysqli) {
	$sql = "SELECT `username`, `$type` FROM `Users` ORDER BY `$type` DESC";
	$result = query($sql, $mysqli);
	
	return $result;
}


function query($sql, $mysqli) {
	$resultArray = [];
	if($result = $mysqli->query($sql)) {
		while($row = $result->fetch_array(MYSQLI_ASSOC)) {
			$resultArray[] = $row;
		}
	} else {
		error("could not query");
	}
	return $resultArray;
}

function query_one($sql, $mysqli) {
	if($result = $mysqli->query($sql)) {
	    if($result->num_rows == 1) {
	        $row = $result->fetch_array(MYSQLI_ASSOC);
	        return $row;
		} else {
			error("could not query");
		}
	} else {
		error("could not query");
	}
}



function error($message) {
	die(json_encode(["error" => $message], JSON_UNESCAPED_SLASHES));
}

?>