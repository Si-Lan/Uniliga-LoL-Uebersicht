<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../summoner-card.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	if (isset($_GET['player'])) {
		$player_id = $_GET['player'];
		$player = $dbcn->query("SELECT * FROM players WHERE PlayerID=$player_id")->fetch_assoc();
		create_summonercard($player);
	}
}