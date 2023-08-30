<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../summoner-card.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error)	exit("Database Connection failed");

$player_id = $_SERVER['HTTP_PLAYERID'] ?? $_GET['player'] ?? NULL;
if ($player_id != NULL) {
	$player = $dbcn->execute_query("SELECT * FROM players WHERE PlayerID = ?", [$player_id])->fetch_assoc();
	$dbcn->close();
	create_summonercard($player);
	exit;
}

$team_id = $_SERVER['HTTP_TEAMID'] ?? $_GET['team'] ?? NULL;
if ($team_id != NULL) {
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?", [$team_id])->fetch_all(MYSQLI_ASSOC);
	$dbcn->close();
	$cards = array();
	foreach ($players as $player) {
		$cards[] = create_summonercard($player, FALSE, FALSE);
	}
	echo json_encode($cards);
	exit;
}

$dbcn->close();