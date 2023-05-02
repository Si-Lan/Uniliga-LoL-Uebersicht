<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../fe-functions.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	$tournamentID = $_GET['tournament'] ?? NULL;
	if ($tournamentID == NULL) {
		exit;
	}
	$type = $_GET['type'] ?? NULL;
	if ($type == NULL) {
		exit;
	}

	$divisionsDB = $dbcn->query("SELECT * FROM divisions WHERE TournamentID = {$tournamentID} ORDER BY Number")->fetch_all(MYSQLI_ASSOC);
	$teams = $dbcn->query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID=$tournamentID ORDER BY avg_rank_num DESC")->fetch_all(MYSQLI_ASSOC);

	if ($type == "all") {
		generate_elo_list($dbcn,$type,$teams,$tournamentID,NULL,NULL);
	} elseif ($type == "div") {
		foreach ($divisionsDB as $division) {
			$teams_of_div = $dbcn->query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID=$tournamentID AND d.DivID = {$division['DivID']} ORDER BY avg_rank_num DESC")->fetch_all(MYSQLI_ASSOC);
			generate_elo_list($dbcn,$type,$teams_of_div,$tournamentID,$division,NULL);
		}
	} elseif ($type == "group") {
		foreach ($divisionsDB as $division) {
			$groups_of_div = $dbcn->query("SELECT * FROM `groups` WHERE DivID = {$division['DivID']} ORDER BY Number")->fetch_all(MYSQLI_ASSOC);
			foreach ($groups_of_div as $group) {
				$teams_of_group = $dbcn->query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID=$tournamentID AND g.GroupID = {$group['GroupID']} ORDER BY avg_rank_num DESC")->fetch_all(MYSQLI_ASSOC);
				generate_elo_list($dbcn,$type,$teams_of_group,$tournamentID,$division,$group);
			}
		}
	}
}