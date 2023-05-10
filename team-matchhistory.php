<!DOCTYPE html>
<html lang="de">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");
include("game.php");

create_html_head_elements("matchhistory");

$pageurl = $_SERVER['REQUEST_URI'];

$teamID = $_GET['team'];

$toor_tourn_url = "https://play.toornament.com/de/tournaments/";

if (is_light_mode()) {
	$lightmode = " light";
} else {
	$lightmode = "";
}

try {
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

	if ($dbcn->connect_error) {
		echo "<title>Database Connection failed</title></head>Database Connection failed";
	} else {
		$team = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$teamID])->fetch_assoc();
        ?>
    <title><?php echo "{$team['TeamName']}" ?> - Matchhistory | Uniliga LoL - Übersicht</title>
</head>
<body class="match-history<?php echo $lightmode?>" style="gap: 30px;">
		<?php
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE TournamentID = ?",[$team["TournamentID"]])->fetch_assoc();
		$team_in_group = $dbcn->execute_query("SELECT * FROM teamsingroup WHERE TeamID = ?",[$teamID])->fetch_assoc();
		$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?",[$team_in_group['GroupID']])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?",[$group['DivID']])->fetch_assoc();
		$matches = $dbcn->execute_query("SELECT * FROM matches WHERE Team1ID = ? OR Team2ID = ? ORDER BY round",[$teamID,$teamID])->fetch_all(MYSQLI_ASSOC);
		$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup ON teams.TeamID = teamsingroup.TeamID WHERE teamsingroup.GroupID = ? ORDER BY `Rank`",[$group['GroupID']])->fetch_all(MYSQLI_ASSOC);
		$teams_from_group = [];
        foreach ($teams_from_groupDB as $i=>$team_from_group) {
			$teams_from_group[$team_from_group['TeamID']] = array("TeamName"=>$team_from_group['TeamName'], "imgID"=>$team_from_group['imgID']);
		}

		create_header($dbcn,"team",$tournament["TournamentID"],$group["GroupID"],$teamID);
		create_tournament_overview_nav_buttons($dbcn,$tournament['TournamentID'],"",$div['DivID'],$group['GroupID']);
		create_team_nav_buttons($tournament["TournamentID"],$team,"matchhistory");

		foreach ($matches as $m=>$match) {
			$games = $dbcn->execute_query("SELECT * FROM games WHERE MatchID = ? ORDER BY RiotMatchID",[$match['MatchID']])->fetch_all(MYSQLI_ASSOC);
            $team1 = $teams_from_group[$match['Team1ID']];
			$team2 = $teams_from_group[$match['Team2ID']];
            if ($match['Winner'] == 1) {
                $team1score = "win";
                $team2score = "loss";
			} elseif ($match['Winner'] == 2) {
				$team1score = "loss";
				$team2score = "win";
			} else {
				$team1score = "draw";
				$team2score = "draw";
			}
            if ($m != 0) {
                echo "<div class='divider rounds'></div>";
			}
			echo "<div id='{$match['MatchID']}' class='round-wrapper'>";
            echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$match['round']}: &nbsp</span>
                    <span class='team $team1score'>{$team1['TeamName']}</span>
                    <span class='score'><span class='$team1score'>{$match['Team1Score']}</span>:<span class='$team2score'>{$match['Team2Score']}</span></span>
                    <span class='team $team2score'>{$team2['TeamName']}</span>
                </h2>";
			if ($games == NULL) {
                echo "</div>";
				continue;
			}
			foreach ($games as $game) {
				$gameID = $game['RiotMatchID'];
				create_game($dbcn,$gameID,$teamID);
			}
			echo "</div>";
		}

		?>
</body>
<?php
	}
} catch (Exception $e) {
	echo "<title>Error</title></head>";
}
?>
</html>