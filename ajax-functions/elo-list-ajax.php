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

	$divisionsDB = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	$teams = $dbcn->execute_query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID = ? ORDER BY avg_rank_num DESC",[$tournamentID])->fetch_all(MYSQLI_ASSOC);

	if ($type == "all") {
		generate_elo_list($dbcn,$type,$teams,$tournamentID,NULL,NULL);
	} elseif ($type == "div") {
		foreach ($divisionsDB as $division) {
			$teams_of_div = $dbcn->execute_query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID = ? AND d.DivID = ? ORDER BY avg_rank_num DESC",[$tournamentID,$division['DivID']])->fetch_all(MYSQLI_ASSOC);
			generate_elo_list($dbcn,$type,$teams_of_div,$tournamentID,$division,NULL);
		}
	} elseif ($type == "group") {
		foreach ($divisionsDB as $division) {
			$groups_of_div = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ? ORDER BY Number",[$division['DivID']])->fetch_all(MYSQLI_ASSOC);
			foreach ($groups_of_div as $group) {
				$teams_of_group = $dbcn->execute_query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID = ? AND g.GroupID = ? ORDER BY avg_rank_num DESC",[$tournamentID,$group['GroupID']])->fetch_all(MYSQLI_ASSOC);
				generate_elo_list($dbcn,$type,$teams_of_group,$tournamentID,$division,$group);
			}
		}
	}
}