<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../admin/scrapeToornament.php');
include(dirname(__FILE__).'/../admin/riot-api-access/get-RGAPI-data.php');

$type = $_SERVER['HTTP_TYPE'] ?? $_REQUEST["type"] ?? NULL;

if ($type == "update_start_time") {
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$item_ID = $_SERVER['HTTP_ITEMID'] ?? $_REQUEST['id'] ?? NULL;
	if ($item_ID == NULL) exit;
	$update_type = $_SERVER['HTTP_UPDATETYPE'] ?? $_REQUEST['utype'] ?? 0;
	$lastupdate = $dbcn->execute_query("SELECT * FROM userupdates WHERE ItemID = ? AND update_type = ?", [$item_ID, $update_type])->fetch_assoc();
	$t = date('Y-m-d H:i:s');
	if ($lastupdate == NULL) {
		$dbcn->execute_query("INSERT INTO userupdates VALUES (?, ?, '$t')", [$item_ID, $update_type]);
	} else {
		$dbcn->execute_query("UPDATE userupdates SET last_update = '$t' WHERE ItemID = ? AND update_type = ?", [$item_ID, $update_type]);
	}
	exit("1");
}
if ($type == "teams_in_group") {
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST['teamid'] ?? NULL;
	if ($team_ID != NULL) {
		$groupID = $dbcn->execute_query("SELECT GroupID FROM teamsingroup WHERE TeamID = ?", [$team_ID])->fetch_column();
	} else {
		$groupID = $_SERVER['HTTP_GROUPID'] ?? $_REQUEST['id'] ?? NULL;
	}
	$delete = FALSE;
	if (isset($_SERVER['HTTP_DELETETEAMS']) || isset($_REQUEST["delete"])) {
		$delete = TRUE;
	}

	$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?", [$groupID])->fetch_assoc();
	$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?", [$group["DivID"]])->fetch_assoc();
	if ($div["format"] == "Groups") {
		$scrape_result = scrape_toornaments_teams_in_groups($div["TournamentID"], $div["DivID"], $groupID, FALSE, $delete);
		echo($scrape_result["writes"] + $scrape_result["updates"]);
	} elseif ($div["format"] == "Swiss") {
		$scrape_result = scrape_toornaments_teams_in_groups_swiss($div["TournamentID"], $div["DivID"], $groupID);
		echo($scrape_result["writes"] + $scrape_result["updates"]);
	}
}

if ($type == "matches_from_group") {
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST['teamid'] ?? NULL;
	if ($team_ID != NULL) {
		$groupID = $dbcn->execute_query("SELECT GroupID FROM teamsingroup WHERE TeamID = ?", [$team_ID])->fetch_column();
	} else {
		$groupID = $_SERVER['HTTP_GROUPID'] ?? $_REQUEST['id'] ?? NULL;
	}

	$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?", [$groupID])->fetch_assoc();
	$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?", [$group["DivID"]])->fetch_assoc();
	if ($div["format"] == "Groups") {
		$scrape_result = scrape_toornament_matches_from_group($div["TournamentID"], $div["DivID"], $groupID);
		echo($scrape_result["writes"] + $scrape_result["changes"][0]);
	} elseif ($div["format"] == "Swiss") {
		$scrape_result = scrape_toornament_matches_from_swiss($div["TournamentID"], $div["DivID"], $groupID);
		echo($scrape_result["writes"] + $scrape_result["changes"][0]);
	}
}
if ($type == "matches_from_playoff") {
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST['teamid'] ?? NULL;
	if ($team_ID != NULL) {
		$tournamentID = $dbcn->execute_query("SELECT TournamentID FROM teams WHERE TeamID = ?", [$team_ID])->fetch_column();
		$divNum = $dbcn->execute_query("SELECT Number FROM divisions WHERE DivID = (SELECT DivID FROM `groups` WHERE GroupID = (SELECT GroupID FROM teamsingroup WHERE TeamID = ?))", [$team_ID])->fetch_column();
		$playoffIDs = $dbcn->execute_query("SELECT PlayoffID FROM playoffs WHERE TournamentID = ? AND (Number1 = ? OR Number2 = ?)", [$tournamentID, $divNum, $divNum])->fetch_all();
		$playoffID = array();
		foreach ($playoffIDs as $id_nested) {
			$playoffID[] = $id_nested[0];
		}
	} else {
		$playoffID = $_SERVER['HTTP_PLAYOFFID'] ?? $_REQUEST['id'] ?? NULL;
		$tournamentID = $dbcn->execute_query("SELECT TournamentID FROM playoffs WHERE PlayoffID = ?", [$playoffID])->fetch_column();
		$playoffID = [$playoffID];
	}
	foreach ($playoffID as $id) {
		$scrape_result = scrape_toornament_matchups_from_playoffs($tournamentID,$id);
		echo($scrape_result["writes"] + $scrape_result["changes"][0]);
	}
}

if ($type == "matchresult") {
	$match_ID = $_SERVER['HTTP_MATCHID'] ?? $_REQUEST['id'] ?? NULL;
	$format = $_SERVER['HTTP_FORMAT'] ?? $_REQUEST['format'] ?? "groups";

	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		echo -1;
	} else {
		if ($format == "groups") {
			$tournamentID = $dbcn->execute_query("SELECT TournamentID FROM divisions WHERE DivID = (SELECT DivID FROM `groups` WHERE GroupID = (SELECT GroupID FROM matches WHERE MatchID = ?))", [$match_ID])->fetch_column();
			$playoffs = FALSE;
		} elseif ($format == "playoffs") {
			$tournamentID = $dbcn->execute_query("SELECT TournamentID FROM playoffs WHERE PlayoffID = (SELECT PlayoffID FROM playoffmatches WHERE MatchID = ?)", [$match_ID])->fetch_column();
			$playoffs = TRUE;
		} else {
			exit();
		}
		$scrape_result = scrape_toornament_matches($tournamentID, $match_ID, $playoffs);
		echo ($scrape_result["changes"][0]);
	}
}

if ($type == "players_in_team") {
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST['id'] ?? NULL;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		echo -1;
		exit();
	}
	$tournament_id = $dbcn->execute_query("SELECT TournamentID FROM teams WHERE TeamID = ?",[$team_ID])->fetch_column();
	$scrape_result = scrape_toornaments_players($tournament_id,$team_ID);
	$puuids_result = get_puuids_by_team($team_ID, TRUE);
	echo ($scrape_result["writes"]+$scrape_result["NameUpdate"]+$scrape_result["SNameUpdate"]);
}

if ($type == "games_for_match") {
	$match_ID = $_SERVER['HTTP_MATCHID'] ?? $_REQUEST['id'] ?? NULL;
	$format = $_SERVER['HTTP_FORMAT'] ?? $_REQUEST['format'] ?? "groups";
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		echo -1;
		exit();
	}
	if ($format == "playoffs") {
		$teams = $dbcn->execute_query("SELECT Team1ID, Team2ID FROM playoffmatches WHERE MatchID = ?", [$match_ID])->fetch_row();
	} else {
		$teams = $dbcn->execute_query("SELECT Team1ID, Team2ID FROM matches WHERE MatchID = ?", [$match_ID])->fetch_row();
	}
	$players1 = $dbcn->execute_query("SELECT PlayerID FROM players WHERE TeamID = ? AND PUUID IS NOT NULL", [$teams[0]])->fetch_all(MYSQLI_ASSOC);
	$players2 = $dbcn->execute_query("SELECT PlayerID FROM players WHERE TeamID = ? AND PUUID IS NOT NULL", [$teams[1]])->fetch_all(MYSQLI_ASSOC);
	function cut_players(array $playerlist):array {
		$newlength = count($playerlist);
		if (count($playerlist) >= 5) {
			$newlength = ceil(count($playerlist) / 2)+1;
		}
		shuffle($playerlist);
		return array_slice($playerlist,0,$newlength);
	}
	$players1 = cut_players($players1);
	$players2 = cut_players($players2);
	$players = array_merge($players1,$players2);
	$games = array();
	foreach ($players as $player) {
		$result = get_games_by_player($player["PlayerID"]);
	}

}

if ($type == "gamedata_for_match") {
	$match_ID = $_SERVER['HTTP_MATCHID'] ?? $_REQUEST['id'] ?? NULL;
	$format = $_SERVER['HTTP_FORMAT'] ?? $_REQUEST['format'] ?? "groups";
	$sort = $_SERVER['HTTP_SORT'] ?? $_REQUEST['sort'] ?? "true";
	if ($sort == "true") {
		$sort = TRUE;
	} else {
		$sort = FALSE;
	}
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		echo -1;
		exit();
	}
	if ($format == "playoffs") {
		$teams = $dbcn->execute_query("SELECT Team1ID, Team2ID FROM playoffmatches WHERE MatchID = ?", [$match_ID])->fetch_row();
	} else {
		$teams = $dbcn->execute_query("SELECT Team1ID, Team2ID FROM matches WHERE MatchID = ?", [$match_ID])->fetch_row();
	}

	$players1 = $dbcn->execute_query("SELECT PlayerID FROM players WHERE TeamID = ?", [$teams[0]])->fetch_all(MYSQLI_ASSOC);
	$players2 = $dbcn->execute_query("SELECT PlayerID FROM players WHERE TeamID = ?", [$teams[1]])->fetch_all(MYSQLI_ASSOC);
	$players = array_merge($players1,$players2);
	$games = array();
	foreach ($players as $player) {
		$games_from_player = $dbcn->execute_query("SELECT matches_gotten FROM players WHERE PlayerID = ?", [$player["PlayerID"]])->fetch_column();
		$games_from_player = json_decode($games_from_player);
		foreach ($games_from_player as $game) {
			if (!in_array($game,$games)) {
				$games[] = $game;
			}
		}
	}
	$tournament_id = $dbcn->execute_query("SELECT TournamentID FROM teams WHERE TeamID = ? OR TeamID = ?",[$teams[0],$teams[1]])->fetch_column();
	foreach ($games as $game) {
		$matchdata = $dbcn->execute_query("SELECT MatchData FROM games WHERE RiotMatchID = ?", [$game])->fetch_column();
		if ($matchdata == NULL) {
			$result = add_match_data($game,$tournament_id);
			if ($result["response"] == 429) {
				break;
			}
		}
		if ($sort) {
			$sortresult = assign_and_filter_game($game,$tournament_id);
		}
	}
}

if ($type == "recalc_team_stats") {
	$team_ID = $_SERVER['HTTP_TEAMID'] ?? $_REQUEST['id'] ?? NULL;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		echo -1;
		exit();
	}
	get_played_champions_for_players($team_ID);
	get_played_positions_for_players($team_ID);
	calculate_teamstats($dbcn,$team_ID);
}

if (isset($dbcn)) $dbcn->close();