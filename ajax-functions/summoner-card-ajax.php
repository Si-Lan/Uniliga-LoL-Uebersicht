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
		$player = $dbcn->execute_query("SELECT * FROM players WHERE PlayerID = ?",[$player_id])->fetch_assoc();
		$dbcn->close();
		create_summonercard($player);
	} elseif (isset($_GET['team'])) {
		$team_id = $_GET['team'];
		$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$team_id])->fetch_all(MYSQLI_ASSOC);
		$dbcn->close();
		$cards = array();
		foreach ($players as $player) {
			$cards[] = create_summonercard($player,FALSE,FALSE);
		}
		echo json_encode($cards);
	}
}