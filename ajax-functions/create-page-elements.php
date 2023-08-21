<?php
include_once(dirname(__FILE__).'/../fe-functions.php');
$type = $_REQUEST["type"] ?? NULL;

$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('../DB-info.php');

error_reporting(0);

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