<?php

require_once('SQLTools.php');

$ck_key = '/^[A-Za-z0-9]{13,13}$/';
$ck_username = '/^[A-Za-z0-9_]{2,20}$/';
$ck_password =  '/^[A-Za-z0-9!@#$%^&*()_]{2,20}$/';

if(!isSet($_POST["action"])) {
	error("no action specified");
}

$action = $_POST["action"];

$mysqli = getmysqli();

switch ($action) {

	case "SignIn":
		$username = $_POST['username'];
		$newPass = $_POST['password'];
		if(!preg_match($ck_username, $username) || !preg_match($ck_password, $newPass)) {
		   error("username/password contains illegal characters");
		}
		$key = signIn($username, $newPass, $mysqli);
		$returnValue = ["key" => $key, "username" => $username];
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
	case "SignUp":
		$username = $_POST['username'];
		$newPass = $_POST['password'];
		if(!preg_match($ck_username, $username) || !preg_match($ck_password, $newPass)) {
			error("username/password contains illegal characters");
		}
		$key = createUser($username, $newPass, $mysqli);
		$returnValue = ["key" => $key, "username" => $username];

		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
	case "HitStation":
		
		$miles = $_POST['miles'];
		$key = $_POST['key'];
		
		$returnValue = hitStation($key, $miles, $mysqli);
		
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		
		break;
	case "UserStats":
		$key = $_POST['key'];
		
		$returnValue = userStats($key, $mysqli);
		
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		
		break;
	case "Leaderboard":
	
		$type = $_POST['type'];
		
		$selectType = "totalStations";
		
		if($type == "totalStations" || $type == "totalMiles") $selectType = $type;
	
		$returnValue = leaderboard($selectType, $mysqli);
		echo(json_encode($returnValue, JSON_UNESCAPED_SLASHES));
		break;
}

?>