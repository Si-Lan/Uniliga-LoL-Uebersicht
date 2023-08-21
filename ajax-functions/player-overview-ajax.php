<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../fe-functions.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	if (isset($_GET['puuid'])) {
		$puuid = $_GET['puuid'];
		create_player_overview($dbcn,$puuid);
	}
}