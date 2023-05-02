<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../game.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	if (isset($_GET['gameID'])) {
		$gameID = $_GET['gameID'];
		$teamID = $_GET['teamID'] ?? NULL;
		create_game($dbcn,$gameID,$teamID);
	}
}