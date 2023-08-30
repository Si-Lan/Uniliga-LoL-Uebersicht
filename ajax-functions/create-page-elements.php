<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../fe-functions.php');
include(dirname(__FILE__).'/../summoner-card.php');

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "standings") {
	$group_ID = $_SERVER["HTTP_GROUPID"] ?? $_REQUEST['group'] ?? NULL;
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($group_ID == NULL && $team_ID != NULL) {
		$group_ID = $dbcn->execute_query("SELECT GroupID FROM teamsingroup WHERE TeamID = ?", [$team_ID])->fetch_column();
	}
	$div_ID = $dbcn->execute_query("SELECT DivID FROM `groups` WHERE GroupID = ?", [$group_ID])->fetch_column();
	$tourn_ID = $dbcn->execute_query("SELECT TournamentID FROM divisions WHERE DivID = ?", [$div_ID])->fetch_column();
	create_standings($dbcn,$tourn_ID,$group_ID,$team_ID);
}
if ($type == "matchbutton") {
	$match_ID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'] ?? NULL;
	$team_ID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST['team'] ?? NULL;
	$matchtype = $_SERVER["HTTP_MATCHTYPE"] ?? $_REQUEST['mtype'] ?? 'groups';
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$tourn_ID = NULL;
	if ($matchtype == "groups") {
		$group_ID = $dbcn->execute_query("SELECT GroupID FROM matches WHERE MatchID = ?", [$match_ID])->fetch_column();
		$div_ID = $dbcn->execute_query("SELECT DivID FROM `groups` WHERE GroupID = ?", [$group_ID])->fetch_column();
		$tourn_ID = $dbcn->execute_query("SELECT TournamentID FROM divisions WHERE DivID = ?", [$div_ID])->fetch_column();
	} elseif ($matchtype == "playoffs") {
		$playoff_ID = $dbcn->execute_query("SELECT PlayoffID FROM playoffmatches WHERE MatchID = ?", [$match_ID])->fetch_column();
		$tourn_ID = $dbcn->execute_query("SELECT TournamentID FROM playoffs WHERE PlayoffID = ?", [$playoff_ID])->fetch_column();
	}
	create_matchbutton($dbcn,$tourn_ID,$match_ID,$matchtype,$team_ID);
}
if ($type == "summoner-card-container") {
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST["team"] ?? NULL;
	if ($team_ID == NULL) exit();
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
$dbcn->close();