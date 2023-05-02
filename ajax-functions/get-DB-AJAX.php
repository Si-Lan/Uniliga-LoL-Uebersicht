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
        $teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID = {$tournID}")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($teams);
    }
	if ($type == "teams-by-div") {
		$divID = $_REQUEST['divID'];
		$teams = $dbcn->query("SELECT * FROM teams JOIN teamsingroup t on teams.TeamID = t.TeamID JOIN `groups` g on t.GroupID = g.GroupID WHERE DivID = {$divID}")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($teams);
	}
    if ($type == "teams-by-group") {
        $groupID = $_REQUEST['groupID'];
        $teams = $dbcn->query("SELECT * FROM teams JOIN teamsingroup t on teams.TeamID = t.TeamID WHERE GroupID = {$groupID}")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($teams);
    }
	if ($type == "teams-and-playercount-no-puuid") {
		$tournID = $_REQUEST["Tid"];
		$teams = $dbcn->query("SELECT teams.*, COUNT(players.PlayerID) FROM teams JOIN players ON teams.TeamID = players.TeamID WHERE (players.PUUID IS NULL OR players.SummonerID IS NULL) AND teams.TournamentID = {$tournID} GROUP BY teams.TeamID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($teams);
	}
	if ($type == "teams-and-playercount") {
		$tournID = $_REQUEST["Tid"];
		$teams = $dbcn->query("SELECT teams.*, COUNT(players.PlayerID) FROM teams JOIN players ON teams.TeamID = players.TeamID WHERE teams.TournamentID = {$tournID} GROUP BY teams.TeamID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($teams);
	}

    if ($type == "divisions") {
        $tournID = $_REQUEST["Tid"];
        $divisions = $dbcn->query("SELECT * FROM divisions WHERE TournamentID = {$tournID} ORDER BY Number")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($divisions);
    }

    if ($type == "groups") {
        $divID = $_REQUEST["Did"];
        $groups = $dbcn->query("SELECT * FROM `groups` WHERE DivID = {$divID} ORDER BY Number")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($groups);
    }

	if ($type == "match") {
		$matchID =$_REQUEST['Mid'];
		$match = $dbcn->query("SELECT * FROM matches WHERE MatchID = {$matchID}")->fetch_assoc();
		echo json_encode($match);
	}

    if ($type == "matches") {
        $tournID = $_REQUEST["Tid"];
        $matches = $dbcn->query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = {$tournID}")->fetch_all(MYSQLI_ASSOC);
        echo json_encode($matches);
    }
	if ($type == "matches-unplayed") {
		$tournID = $_REQUEST["Tid"];
		$matches = $dbcn->query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = {$tournID} AND m.played = FALSE")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($matches);
	}

	if ($type == "players-by-team") {
		$teamID = $_REQUEST["team"];
		$players = $dbcn->query("SELECT * FROM players WHERE TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($players);
	}
	if ($type == "players-by-team-with-PUUID") {
		$teamID = $_REQUEST["team"];
		$players = $dbcn->query("SELECT * FROM players WHERE TeamID = $teamID AND PUUID IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($players);
	}
	if ($type == "players-by-tournament") {
		$tournamentID = $_REQUEST["tournament"];
		$players = $dbcn->query("SELECT * FROM players WHERE TournamentID = $tournamentID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($players);
	}

	if ($type == "team-and-players") {
		$teamID = $_REQUEST["team"];
		$teamDB = $dbcn->query("SELECT * FROM teams WHERE TeamID = $teamID")->fetch_assoc();
		$playersDB = $dbcn->query("SELECT * FROM players WHERE TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode(array("team" => $teamDB,"players" => $playersDB));
	}

	if ($type == "games") {
		$tournamentID = $_REQUEST["tournament"];
		$games = $dbcn->query("SELECT * FROM games WHERE TournamentID = $tournamentID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($games);
	}
	if ($type == "games-without-data") {
		$tournamentID = $_REQUEST["tournament"];
		$games = $dbcn->query("SELECT * FROM games WHERE TournamentID = $tournamentID AND MatchData IS NULL")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($games);
	}
	if ($type == "games-unassigned") {
		$tournamentID = $_REQUEST["tournament"];
		$games = $dbcn->query("SELECT * FROM games WHERE TournamentID = $tournamentID AND MatchID IS NULL AND (`UL-Game` IS NULL OR `UL-Game` = TRUE)")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($games);
	}
	if ($type == "games-by-match") {
		$matchID = $_REQUEST['match'];
		$games = $dbcn->query("SELECT * FROM games WHERE MatchID = $matchID")->fetch_all(MYSQLI_ASSOC);
		echo json_encode($games);
	}
	if ($type == "match-games-teams-by-matchid") {
		$matchID = $_REQUEST['match'];
		$match = $dbcn->query("SELECT * FROM matches WHERE MatchID = $matchID")->fetch_assoc();
		$games = $dbcn->query("SELECT * FROM games WHERE MatchID = $matchID ORDER BY RiotMatchID")->fetch_all(MYSQLI_ASSOC);
		$team1 = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$match['Team1ID']}")->fetch_assoc();
		$team2 = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$match['Team2ID']}")->fetch_assoc();
		echo json_encode(array("match"=>$match, "games"=>$games, "team1"=>$team1, "team2"=>$team2));
	}

	// counters
	if ($type == "number-teams") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->query("SELECT COUNT(TeamID) FROM teams WHERE TournamentID = $tournamentID")->fetch_row()[0];
		echo $Num;
	}
	if ($type == "number-players") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->query("SELECT COUNT(PlayerID) FROM players WHERE TournamentID = $tournamentID")->fetch_row()[0];
		echo $Num;
	}
	if ($type == "number-divs") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->query("SELECT COUNT(DivID) FROM divisions WHERE TournamentID = $tournamentID")->fetch_row()[0];
		echo $Num;
	}
	if ($type == "number-groups") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->query("SELECT COUNT(`groups`.GroupID) FROM `groups`,divisions WHERE divisions.TournamentID = $tournamentID AND `groups`.DivID = divisions.DivID")->fetch_row()[0];
		echo $Num;
	}
	if ($type == "number-teamsingroup") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->query("SELECT COUNT(teamsingroup.TeamID) FROM teamsingroup,`groups`,divisions WHERE divisions.TournamentID = $tournamentID AND `groups`.DivID = divisions.DivID AND teamsingroup.GroupID = `groups`.GroupID")->fetch_row()[0];
		echo $Num;
	}
	if ($type == "number-matches") {
		$tournamentID = $_REQUEST["tournament"];
		$Num = $dbcn->query("SELECT COUNT(matches.MatchID) FROM matches,`groups`,divisions WHERE divisions.TournamentID = $tournamentID AND `groups`.DivID = divisions.DivID AND matches.GroupID = `groups`.GroupID")->fetch_row()[0];
		echo $Num;
	}
}