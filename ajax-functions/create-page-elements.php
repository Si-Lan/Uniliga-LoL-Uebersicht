<?php
include_once(dirname(__FILE__).'/../fe-functions.php');
include_once(dirname(__FILE__).'/../summoner-card.php');
$type = $_REQUEST["type"] ?? NULL;

$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('../DB-info.php');

if ($type == "standings") {
	$group_ID = $_REQUEST['group'] ?? NULL;
	$team_ID = $_REQUEST['team'] ?? NULL;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($group_ID == NULL && $team_ID != NULL) {
		$group_ID = $dbcn->execute_query("SELECT GroupID FROM teamsingroup WHERE TeamID = ?", [$team_ID])->fetch_column();
	}
	$div_ID = $dbcn->execute_query("SELECT DivID FROM `groups` WHERE GroupID = ?", [$group_ID])->fetch_column();
	$tourn_ID = $dbcn->execute_query("SELECT TournamentID FROM divisions WHERE DivID = ?", [$div_ID])->fetch_column();
	create_standings($dbcn,$tourn_ID,$group_ID,$team_ID);
}
if ($type == "matchbutton") {
	$match_ID = $_REQUEST['match'] ?? NULL;
	$team_ID = $_REQUEST['team'] ?? NULL;
	$matchtype = $_REQUEST['mtype'] ?? 'groups';
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$group_ID = $dbcn->execute_query("SELECT GroupID FROM matches WHERE MatchID = ?", [$match_ID])->fetch_column();
	$div_ID = $dbcn->execute_query("SELECT DivID FROM `groups` WHERE GroupID = ?", [$group_ID])->fetch_column();
	$tourn_ID = $dbcn->execute_query("SELECT TournamentID FROM divisions WHERE DivID = ?", [$div_ID])->fetch_column();
	create_matchbutton($dbcn,$tourn_ID,$match_ID,$matchtype,$team_ID);
}
if ($type == "summoner-card-container") {
	$team_ID = $_SERVER['HTTP_DATA_TEAMID'] ?? NULL;
	if ($team_ID == NULL) die();
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$team_ID])->fetch_all(MYSQLI_ASSOC);

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
	$collapsed = summonercards_collapsed();
	echo "<div class='summoner-card-container'>";
	foreach ($players_gamecount_by_id as $player_id=>$player_gamecount) {
		$player = $players_by_id[$player_id];
		create_summonercard($player,$collapsed);
	}
	echo "</div>";
}