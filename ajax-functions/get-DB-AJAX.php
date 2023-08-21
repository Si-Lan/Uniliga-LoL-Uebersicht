<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
    echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	if (!isset($_REQUEST["type"])) {
		exit;
	}
    $type = $_REQUEST["type"];

    if ($type == "teams") {
        $tournID = $_REQUEST["Tid"];
        $teams = $dbcn->execute_query("SELECT * FROM teams WHERE TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($teams);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
    } elseif ($type == "teams-by-div") {
		$divID = $_REQUEST['divID'];
		$teams = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup t on teams.TeamID = t.TeamID JOIN `groups` g on t.GroupID = g.GroupID WHERE DivID = ?",[$divID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($teams);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "teams-by-group") {
        $groupID = $_REQUEST['groupID'];
        $teams = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup t on teams.TeamID = t.TeamID WHERE GroupID = ?",[$groupID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($teams);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
    } elseif ($type == "teams-and-playercount-no-puuid") {
		$tournID = $_REQUEST["Tid"];
		$teams = $dbcn->execute_query("SELECT teams.*, COUNT(players.PlayerID) FROM teams JOIN players ON teams.TeamID = players.TeamID WHERE (players.PUUID IS NULL OR players.SummonerID IS NULL) AND teams.TournamentID = ? GROUP BY teams.TeamID",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($teams);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "teams-and-playercount") {
		$tournID = $_REQUEST["Tid"];
		$teams = $dbcn->execute_query("SELECT teams.*, COUNT(players.PlayerID) FROM teams JOIN players ON teams.TeamID = players.TeamID WHERE teams.TournamentID = ? GROUP BY teams.TeamID",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($teams);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "divisions") {
        $tournID = $_REQUEST["Tid"];
        $divisions = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($divisions);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
    } elseif ($type == "groups") {
        $divID = $_REQUEST["Did"];
        $groups = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ? ORDER BY Number",[$divID])->fetch_all(MYSQLI_ASSOC);
        $result = json_encode($groups);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
    } elseif ($type == "playoffs") {
		$tournID = $_REQUEST["Tid"];
		$playoffs = $dbcn->execute_query("SELECT * FROM playoffs WHERE TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($playoffs);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "playoffs-matches") {
		$tournID = $_REQUEST["Tid"];
		$matches = $dbcn->execute_query("SELECT * FROM playoffmatches pm JOIN playoffs p on pm.PlayoffID = p.PlayoffID WHERE p.TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($matches);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "playoffs-matches-unplayed") {
		$tournID = $_REQUEST["Tid"];
		$matches = $dbcn->execute_query("SELECT * FROM playoffmatches pm JOIN playoffs p on pm.PlayoffID = p.PlayoffID WHERE p.TournamentID = ? AND pm.played = FALSE",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($matches);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "match") {
		$matchID =$_REQUEST['Mid'];
		$match = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?",[$matchID])->fetch_assoc();
		$result = json_encode($match);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "matches") {
        $tournID = $_REQUEST["Tid"];
        $matches = $dbcn->execute_query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = ?",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($matches);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
    } elseif ($type == "matches-unplayed") {
		$tournID = $_REQUEST["Tid"];
		$matches = $dbcn->execute_query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = ? AND m.played = FALSE",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($matches);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "players-by-team") {
		$teamID = $_REQUEST["team"];
		$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$teamID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($players);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "players-by-team-with-PUUID") {
		$teamID = $_REQUEST["team"];
		$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ? AND PUUID IS NOT NULL",[$teamID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($players);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "players-by-team-with-SummonerID") {
		$teamID = $_REQUEST["team"];
		$players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ? AND SummonerID IS NOT NULL",[$teamID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($players);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "players-by-tournament") {
		$tournamentID = $_REQUEST["tournament"];
		$players = $dbcn->execute_query("SELECT * FROM players WHERE TournamentID = ?",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($players);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "players-by-tournament-with-SummonerID") {
		$tournamentID = $_REQUEST["tournament"];
		$players = $dbcn->execute_query("SELECT * FROM players WHERE TournamentID = ? AND SummonerID IS NOT NULL ",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($players);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "team-and-players") {
		$teamID = $_REQUEST["team"];
		$teamDB = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$teamID])->fetch_assoc();
		$playersDB = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$teamID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode(array("team" => $teamDB,"players" => $playersDB));
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "games") {
		$tournamentID = $_REQUEST["tournament"];
		$games = $dbcn->execute_query("SELECT * FROM games WHERE TournamentID = ?",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($games);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "games-without-data") {
		$tournamentID = $_REQUEST["tournament"];
		$games = $dbcn->execute_query("SELECT * FROM games WHERE TournamentID = ? AND MatchData IS NULL",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($games);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "games-unassigned") {
		$tournamentID = $_REQUEST["tournament"];
		$games = $dbcn->execute_query("SELECT * FROM games WHERE TournamentID = ? AND (MatchID IS NULL AND PLMatchID IS NULL ) AND (`UL-Game` IS NULL OR `UL-Game` = TRUE)",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($games);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "games-by-match") {
		$matchID = $_REQUEST['match'];
		$games = $dbcn->execute_query("SELECT * FROM games WHERE MatchID = ? OR PLMatchID = ?",[$matchID,$matchID])->fetch_all(MYSQLI_ASSOC);
		$result = json_encode($games);
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	} elseif ($type == "match-games-teams-by-matchid") {
		$matchID = $_REQUEST['match'];
		$match = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?",[$matchID])->fetch_assoc();
		if ($match == NULL) {
			$match = $dbcn->execute_query("SELECT * FROM playoffmatches WHERE MatchID = ?",[$matchID])->fetch_assoc();
		}
		$games = $dbcn->execute_query("SELECT * FROM games WHERE MatchID = ? OR PLMatchID = ? ORDER BY RiotMatchID",[$matchID,$matchID])->fetch_all(MYSQLI_ASSOC);
		$team1 = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$match["Team1ID"]])->fetch_assoc();
		$team2 = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$match["Team2ID"]])->fetch_assoc();
		$result = json_encode(array("match"=>$match, "games"=>$games, "team1"=>$team1, "team2"=>$team2));
		$result = preg_replace("/:(\d{19,})([,\}])/",':"$1"$2',$result);
		echo $result;
	}

	// counters
	if ($type == "number-teams") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(TeamID) FROM teams WHERE TournamentID = ?",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-players") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(PlayerID) FROM players WHERE TournamentID = ?",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-divs") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(DivID) FROM divisions WHERE TournamentID = ?",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-groups") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(`groups`.GroupID) FROM `groups`,divisions WHERE divisions.TournamentID = ? AND `groups`.DivID = divisions.DivID",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-teamsingroup") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(teamsingroup.TeamID) FROM teamsingroup,`groups`,divisions WHERE divisions.TournamentID = ? AND `groups`.DivID = divisions.DivID AND teamsingroup.GroupID = `groups`.GroupID",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-matches") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(matches.MatchID) FROM matches,`groups`,divisions WHERE divisions.TournamentID = ? AND `groups`.DivID = divisions.DivID AND matches.GroupID = `groups`.GroupID",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-playoffs") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(playoffs.PlayoffID) FROM playoffs WHERE playoffs.TournamentID = ?",[$tournamentID])->fetch_row()[0];
		echo $Num;
	} elseif ($type == "number-playoff-matches") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->execute_query("SELECT COUNT(playoffmatches.PlayoffID) FROM playoffmatches,playoffs WHERE playoffs.TournamentID = ? AND playoffmatches.PlayoffID = playoffs.PlayoffID",[$tournamentID])->fetch_row()[0];
		echo $Num;
	}
}
$dbcn->close();