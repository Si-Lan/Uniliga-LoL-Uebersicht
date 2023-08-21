<?php
include_once(dirname(__FILE__).'/../admin/scrapeToornament.php');
$type = $_REQUEST["type"] ?? NULL;

$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('../DB-info.php');

//error_reporting(0);

if ($type == "update_start_time") {
	$item_ID = $_REQUEST['id'];
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$lastupdate = $dbcn->execute_query("SELECT * FROM userupdates WHERE ItemID = ? AND update_type = 0", [$item_ID])->fetch_assoc();
	$t = date('Y-m-d H:i:s');
	if ($lastupdate == NULL) {
		$dbcn->execute_query("INSERT INTO userupdates VALUES (?, 0, '$t')", [$item_ID]);
	} else {
		$dbcn->execute_query("UPDATE userupdates SET last_update = '$t' WHERE ItemID = ? AND update_type = 0", [$item_ID]);
	}
}


if ($type == "teams_in_group") {
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if (isset($_REQUEST['teamid'])) {
		$team_ID = $_REQUEST['teamid'];
		$groupID = $dbcn->execute_query("SELECT GroupID FROM teamsingroup WHERE TeamID = ?", [$team_ID])->fetch_column();
	} else {
		$groupID = $_REQUEST['id'];
	}
	$delete = FALSE;
	if (isset($_REQUEST["delete"])) {
		$delete = TRUE;
	}
	if ($dbcn -> connect_error){
		echo -1;
	} else {
		$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?", [$groupID])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?", [$group["DivID"]])->fetch_assoc();
		if ($div["format"] == "Groups") {
			$scrape_result = scrape_toornaments_teams_in_groups($div["TournamentID"], $div["DivID"], $groupID, FALSE, $delete);
			echo ($scrape_result["writes"] + $scrape_result["updates"]);
		} elseif ($div["format"] == "Swiss") {
			$scrape_result = scrape_toornaments_teams_in_groups_swiss($div["TournamentID"], $div["DivID"], $groupID);
			echo ($scrape_result["writes"] + $scrape_result["updates"]);
		}
	}
}

if ($type == "matches_from_group") {
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if (isset($_REQUEST['teamid'])) {
		$team_ID = $_REQUEST['teamid'];
		$groupID = $dbcn->execute_query("SELECT GroupID FROM teamsingroup WHERE TeamID = ?", [$team_ID])->fetch_column();
	} else {
		$groupID = $_REQUEST['id'];
	}

	if ($dbcn -> connect_error){
		echo -1;
	} else {
		$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?", [$groupID])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?", [$group["DivID"]])->fetch_assoc();
		if ($div["format"] == "Groups") {
			$scrape_result = scrape_toornament_matches_from_group($div["TournamentID"], $div["DivID"], $groupID);
			echo ($scrape_result["writes"] + $scrape_result["changes"]);
		} elseif ($div["format"] == "Swiss") {
			$scrape_result = scrape_toornament_matches_from_swiss($div["TournamentID"],$div["DivID"], $groupID);
			echo ($scrape_result["writes"] + $scrape_result["changes"]);
		}
	}
}

if ($type == "matchresult") {
	$match_ID = $_REQUEST['id'] ?? NULL;
	$format = $_REQUEST['format'] ?? "groups";

	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		echo -1;
	} else {
		if ($format == "groups") {
			$match = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?", [$match_ID])->fetch_assoc();
			$playoffs = FALSE;
		} elseif ($format == "playoffs") {
			$match = $dbcn->execute_query("SELECT * FROM playoffmatches WHERE MatchID = ?", [$match_ID])->fetch_assoc();
			$playoffs = TRUE;
		} else {
			exit();
		}
		var_dump($match);
		$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?", [$match['GroupID']])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?", [$group["DivID"]])->fetch_assoc();
		$scrape_result = scrape_toornament_matches($div["TournamentID"], $match_ID, $playoffs);
		echo ($scrape_result["changes"][0]);
	}
}