<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include_once(dirname(__FILE__).'/../fe-functions.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error) exit("Database Connection failed");

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

if ($type == "teams") {
    $tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$teams = $dbcn->execute_query("SELECT * FROM teams WHERE TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($teams);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "teams-by-div") {
	$divID = $_SERVER['HTTP_DIVID'] ?? $_REQUEST['divID'] ?? NULL;
	$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup t on teams.TeamID = t.TeamID JOIN `groups` g on t.GroupID = g.GroupID WHERE DivID = ?",[$divID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($teams);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "teams-by-group") {
     $groupID = $_SERVER['HTTP_GROUPID'] ?? $_REQUEST['groupID'] ?? NULL;
     $teams = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup t on teams.TeamID = t.TeamID WHERE GroupID = ?",[$groupID])->fetch_all(MYSQLI_ASSOC);
	 $result = json_encode($teams);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "teams-and-playercount-no-puuid") {
	$tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$teams = $dbcn->execute_query("SELECT teams.*, COUNT(players.PlayerID) FROM teams JOIN players ON teams.TeamID = players.TeamID WHERE (players.PUUID IS NULL OR players.SummonerID IS NULL) AND teams.TournamentID = ? GROUP BY teams.TeamID",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($teams);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "teams-and-playercount") {
	$tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$teams = $dbcn->execute_query("SELECT teams.*, COUNT(players.PlayerID) FROM teams JOIN players ON teams.TeamID = players.TeamID WHERE teams.TournamentID = ? GROUP BY teams.TeamID",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($teams);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "divisions") {
     $tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
     $divisions = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($divisions);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "groups") {
     $divID = $_SERVER["HTTP_DIVID"] ?? $_REQUEST["Did"] ?? NULL;
     $groups = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ? ORDER BY Number",[$divID])->fetch_all(MYSQLI_ASSOC);
     $result = json_encode($groups);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "playoffs") {
	$tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$playoffs = $dbcn->execute_query("SELECT * FROM playoffs WHERE TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($playoffs);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "playoffs-matches") {
	$tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$matches = $dbcn->execute_query("SELECT * FROM playoffmatches pm JOIN playoffs p on pm.PlayoffID = p.PlayoffID WHERE p.TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($matches);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "playoffs-matches-unplayed") {
	$tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$matches = $dbcn->execute_query("SELECT * FROM playoffmatches pm JOIN playoffs p on pm.PlayoffID = p.PlayoffID WHERE p.TournamentID = ? AND pm.played = FALSE",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($matches);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "match") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['Mid'] ?? NULL;
	$match = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?",[$matchID])->fetch_assoc();
	$result = json_encode($match);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "matches") {
     $tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
     $matches = $dbcn->execute_query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($matches);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
 } elseif ($type == "matches-unplayed") {
	$tournID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["Tid"] ?? NULL;
	$matches = $dbcn->execute_query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = ? AND m.played = FALSE",[$tournID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($matches);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "players-by-team") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST["team"] ?? NULL;
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$teamID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($players);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "players-by-team-with-PUUID") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST["team"] ?? NULL;
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ? AND PUUID IS NOT NULL",[$teamID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($players);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "players-by-team-with-SummonerID") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST["team"] ?? NULL;
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ? AND SummonerID IS NOT NULL",[$teamID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($players);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "players-by-tournament") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TournamentID = ?",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($players);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "players-by-tournament-with-SummonerID") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$players = $dbcn->execute_query("SELECT * FROM players WHERE TournamentID = ? AND SummonerID IS NOT NULL ",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($players);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "team-and-players") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST["team"] ?? NULL;
	$teamDB = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$teamID])->fetch_assoc();
	$playersDB = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$teamID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode(array("team" => $teamDB,"players" => $playersDB));
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "games") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$games = $dbcn->execute_query("SELECT * FROM games WHERE TournamentID = ?",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($games);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "games-without-data") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$games = $dbcn->execute_query("SELECT * FROM games WHERE TournamentID = ? AND MatchData IS NULL",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($games);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "games-unassigned") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$games = $dbcn->execute_query("SELECT * FROM games WHERE TournamentID = ? AND (MatchID IS NULL AND PLMatchID IS NULL ) AND (`UL-Game` IS NULL OR `UL-Game` = TRUE)",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($games);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "games-by-match") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'] ?? NULL;
	$games = $dbcn->execute_query("SELECT * FROM games WHERE MatchID = ? OR PLMatchID = ?",[$matchID,$matchID])->fetch_all(MYSQLI_ASSOC);
	$result = json_encode($games);
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "match-games-teams-by-matchid") {
	$matchID = $_SERVER["HTTP_MATCHID"] ?? $_REQUEST['match'] ?? NULL;
	$match = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?",[$matchID])->fetch_assoc();
	if ($match == NULL) {
		$match = $dbcn->execute_query("SELECT * FROM playoffmatches WHERE MatchID = ?",[$matchID])->fetch_assoc();
	}
	$games = $dbcn->execute_query("SELECT * FROM games WHERE MatchID = ? OR PLMatchID = ? ORDER BY RiotMatchID",[$matchID,$matchID])->fetch_all(MYSQLI_ASSOC);
	$team1 = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$match["Team1ID"]])->fetch_assoc();
	$team2 = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$match["Team2ID"]])->fetch_assoc();
	$result = json_encode(array("match"=>$match, "games"=>$games, "team1"=>$team1, "team2"=>$team2));
	$result = preg_replace("/:(\d{19,})([,}])/",':"$1"$2',$result);
	echo $result;
} elseif ($type == "local_patch_info") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	if ($patch == "all") {
		$patch_data = $dbcn->execute_query("SELECT * FROM local_patches")->fetch_all(MYSQLI_ASSOC);
	} else {
		$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE Patch = ?", [$patch])->fetch_all(MYSQLI_ASSOC);
	}
	echo json_encode($patch_data);
}

// IDs only
if ($type == "matchids-by-group") {
	$groupID =  $_SERVER["HTTP_GROUPID"] ?? $_REQUEST["group"] ?? NULL;
	$matches_nested = $dbcn->execute_query("SELECT MatchID FROM matches WHERE GroupID = ?",[$groupID])->fetch_all();
	$matches = array();
	foreach ($matches_nested as $match) {
		$matches[] = $match[0];
	}
	$result = json_encode($matches);
	$result = preg_replace("/(\d{19,})/",'"$1"',$result);
	echo $result;
}
if ($type == "matchids-by-team") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST["team"] ?? NULL;
	$matches_nested = $dbcn->execute_query("SELECT MatchID FROM matches WHERE Team1ID = ? OR Team2ID = ?",[$teamID,$teamID])->fetch_all();
	$matches = array();
	foreach ($matches_nested as $match) {
		$matches[] = $match[0];
	}
	$result = json_encode($matches);
	$result = preg_replace("/(\d{19,})/",'"$1"',$result);
	echo $result;
}
if ($type == "matchids-by-team-with-playoffs") {
	$teamID = $_SERVER["HTTP_TEAMID"] ?? $_REQUEST["team"] ?? NULL;
	$matches_nested = $dbcn->execute_query("SELECT MatchID FROM matches WHERE Team1ID = ? OR Team2ID = ?",[$teamID,$teamID])->fetch_all();
	$matches = array();
	foreach ($matches_nested as $match) {
		$matches[] = $match[0];
	}
	$plmatches_nested = $dbcn->execute_query("SELECT MatchID FROM playoffmatches WHERE Team1ID = ? OR Team2ID = ?",[$teamID,$teamID])->fetch_all();
	$plmatches = array();
	foreach ($plmatches_nested as $match) {
		$plmatches[] = $match[0];
	}
	$result = array(
		"groups" => $matches,
		"playoffs" => $plmatches
	);
	$result = preg_replace("/(\d{19,})/",'"$1"',json_encode($result));
	echo $result;
}

// update timers
if ($type == "user-update-timer") {
	$ItemID =  $_SERVER["HTTP_ITEMID"] ?? $_REQUEST["id"] ?? NULL;
	$ud_type =  $_SERVER["HTTP_UPDATETYPE"] ?? $_REQUEST["utype"] ?? NULL;
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["t"] ?? NULL;
	if ($ud_type === "1") {
		$GroupID = $dbcn->execute_query("SELECT GroupID FROM matches WHERE MatchID = ?",[$ItemID])->fetch_column();
		if ($GroupID == NULL) {
			$PlayoffID = $dbcn->execute_query("SELECT PlayoffID FROM playoffmatches WHERE MatchID = ?",[$ItemID])->fetch_column();
			$tournamentID = $dbcn->execute_query("SELECT TournamentID FROM playoffs WHERE PlayoffID = ?", [$PlayoffID])->fetch_column();
		} else {
			$tournamentID = $dbcn->execute_query("SELECT TournamentID FROM divisions WHERE DivID = (SELECT DivID FROM `groups` WHERE GroupID = ?)", [$GroupID])->fetch_column();
		}
	}
	$last_update = $dbcn->execute_query("SELECT last_update FROM userupdates WHERE ItemID = ? AND update_type = ?", [$ItemID, $ud_type])->fetch_column();
	if ($tournamentID != NULL) {
		$last_cron_update = $dbcn->execute_query("SELECT last_update FROM cron_updates WHERE TournamentID = ?", [$tournamentID])->fetch_column();
		if ($ud_type == "0") {
			$manual_updates = $dbcn->execute_query("SELECT standings, matches, matchresults FROM manual_updates WHERE TournamentID = ?", [$tournamentID])->fetch_row();
		} elseif ($ud_type == "1") {
			$manual_updates = $dbcn->execute_query("SELECT matchresults, gamedata, gamesort FROM manual_updates WHERE TournamentID = ?", [$tournamentID])->fetch_row();
		} else {
			$manual_updates = NULL;
		}
		$last_update = latest_update($last_update,$last_cron_update,$manual_updates);
	}
	$return_relative_time_string = $_SERVER["HTTP_RELATIVETIME"] ?? $_REQUEST["reltime"] ?? FALSE;
	if ($return_relative_time_string) {
		if ($last_update == NULL) {
			echo "unbekannt";
		} else {
			echo max_time_from_timestamp(time() - strtotime($last_update));
		}
	} else {
		echo $last_update;
	}
}
if ($type == "cron-update-timer") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["id"] ?? NULL;
	$last_update = $dbcn->execute_query("SELECT last_update FROM cron_updates WHERE TournamentID = ?", [$tournamentID])->fetch_column();
	echo $last_update;
}

// counters
if ($type == "number-teams") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(TeamID) FROM teams WHERE TournamentID = ?",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-players") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(PlayerID) FROM players WHERE TournamentID = ?",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-divs") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(DivID) FROM divisions WHERE TournamentID = ?",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-groups") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(`groups`.GroupID) FROM `groups`,divisions WHERE divisions.TournamentID = ? AND `groups`.DivID = divisions.DivID",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-teamsingroup") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(teamsingroup.TeamID) FROM teamsingroup,`groups`,divisions WHERE divisions.TournamentID = ? AND `groups`.DivID = divisions.DivID AND teamsingroup.GroupID = `groups`.GroupID",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-matches") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(matches.MatchID) FROM matches,`groups`,divisions WHERE divisions.TournamentID = ? AND `groups`.DivID = divisions.DivID AND matches.GroupID = `groups`.GroupID",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-playoffs") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(playoffs.PlayoffID) FROM playoffs WHERE playoffs.TournamentID = ?",[$tournamentID])->fetch_row()[0];
	echo $Num;
} elseif ($type == "number-playoff-matches") {
	$tournamentID = $_SERVER["HTTP_TOURNAMENTID"] ?? $_REQUEST["tournament"] ?? NULL;
	$Num = $dbcn->execute_query("SELECT COUNT(playoffmatches.PlayoffID) FROM playoffmatches,playoffs WHERE playoffs.TournamentID = ? AND playoffmatches.PlayoffID = playoffs.PlayoffID",[$tournamentID])->fetch_row()[0];
	echo $Num;
}

$dbcn->close();