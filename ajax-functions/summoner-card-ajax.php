<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../summoner-card.php');
include(dirname(__FILE__).'/../fe-functions.php');

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
	$players_by_id = array();
	$players_gamecount_by_id = array();
	foreach ($players as $player) {
		$players_by_id[$player['PlayerID']] = $player;
		$played_games = 0;
		foreach (json_decode($player['roles'],true) as $role_played_amount) {
			$played_games += $role_played_amount;
		}
		$players_gamecount_by_id[$player['PlayerID']] = $played_games;
	}
	arsort($players_gamecount_by_id);
	$cards = array();
	foreach ($players_gamecount_by_id as $player_id=>$player_gamecount) {
		$player = $players_by_id[$player_id];
		$cards[] = create_summonercard($player,summonercards_collapsed(), FALSE);
	}
	echo json_encode($cards);
	exit;
}

$dbcn->close();