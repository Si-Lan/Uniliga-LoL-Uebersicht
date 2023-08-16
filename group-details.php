<!DOCTYPE html>
<html lang="de">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");
include('game.php');

create_html_head_elements("group");

$pageurl = $_SERVER['REQUEST_URI'];

$tournamentID = $_GET['tournament'];
$groupID = $_GET['group'];

$local_team_img = "img/team_logos/";
$toor_tourn_url = "https://play.toornament.com/de/tournaments/";
$opgg_logo_svg = file_get_contents("img/opgglogo.svg");
$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";

try {
    $dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

    if ($dbcn->connect_error) {
        echo "<title>Database Connection failed</title></head>Database Connection failed";
    } else {
        $tournamentDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE TournamentID = ?",[$tournamentID])->fetch_assoc();
        $all_divsDB = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
        $groupDB = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ? ORDER BY Number",[$groupID])->fetch_assoc();
        $divisionsDB = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?",[$groupDB['DivID']])->fetch_assoc();
        $matches = $dbcn->execute_query("SELECT * FROM matches WHERE GroupID = ? ORDER BY round",[$groupDB['GroupID']])->fetch_all(MYSQLI_ASSOC);
        $matches_grouped = [];
        foreach ($matches as $match) {
            $matches_grouped[$match['round']][] = $match;
        }
        $teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup ON teams.TeamID = teamsingroup.TeamID WHERE teamsingroup.GroupID = ? ORDER BY `Rank`",[$groupDB['GroupID']])->fetch_all(MYSQLI_ASSOC);
        $teams_from_group = [];
        foreach ($teams_from_groupDB as $i=>$team_from_group) {
            $teams_from_group[$team_from_group['TeamID']] = array("TeamName"=>$team_from_group['TeamName'], "imgID"=>$team_from_group['imgID']);
        }
		if ($divisionsDB["format"] === "Swiss") {
			$group_title = "Swiss-Gruppe";
		} else {
			$group_title = "Gruppe {$groupDB['Number']}";
		}
        echo "<title>Liga {$divisionsDB['Number']} - $group_title | Uniliga LoL - Übersicht</title>";
        ?>
</head>
<?php
		if (is_light_mode()) {
			$lightmode = " light";
		} else {
			$lightmode = "";
		}
		if (isset($_GET['match'])) {
			echo "<body class='group$lightmode' style='overflow: hidden'>";
		} else {
			echo "<body class='group$lightmode'>";
		}
        create_header($dbcn,"group",$tournamentID,$groupID);
        create_tournament_overview_nav_buttons($dbcn,$tournamentID,"group",$divisionsDB['DivID'],$groupID);

        echo "<div class='pagetitlewrapper'><h2 class='pagetitle'>Liga {$divisionsDB['Number']} - $group_title</h2>
                <a href='$toor_tourn_url$tournamentID/stages/{$divisionsDB['DivID']}/groups/{$groupID}/' target='_blank' class='toorlink'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/open_in_new.svg")."</div></a>
              	<button type='button' class='icononly user_update_group' data-group='$groupID'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/sync.svg")."</div></button>
              </div>";
        //echo "<div class='divider bot-space'></div>";
        /*
              echo "<div class='navbar group-nav'>";
              foreach ($all_divsDB as $i_div=>$division) {
                  if ($i_div > 0) {
                      echo "<div class='divider-vert light space'></div>";
				  }
                  echo "<div class='navbar-item nav-division";
                  if ($division['DivID'] == $divisionsDB['DivID']) {
                      echo " current";
                  }
                  echo "' onclick=\"toggle_group_nav('{$division['Number']}')\"><h2>Liga {$division['Number']}</h2></div>";
                  echo "<div class='navbar-item nav-group {$division['Number']}";
                    if ($division['DivID'] != $divisionsDB['DivID']) {
						echo " hidden";
					}
                  echo "'>";
                    echo "<div class='divider-vert light space'></div>
                          <div class='nav-group-wrapper'>";
                    $groupsDB = $dbcn->query("SELECT * FROM `groups` WHERE DivID = {$division['DivID']}")->fetch_all(MYSQLI_ASSOC);
                    foreach ($groupsDB as $group) {
                        echo "<a href='turnier/{$tournamentID}/gruppe/{$group['GroupID']}'";
                        if ($groupID == $group['GroupID']) {
                            echo " class='current'";
                        }
                        echo "><h3>Gruppe {$group['Number']}</h3></a>";
					}
                    echo "</div>"; // nav-group-wrapper
                  echo "</div>"; // nav-group
			  }
              echo "</div>"; // group-nav
        */
        echo "<div class='main-content'>";

        create_standings($dbcn,$tournamentID,$groupID);

        echo "<div class='matches'>
                <div class='title'><h3>Spiele</h3></div>";
		$curr_matchID = $_GET['match'] ?? NULL;
		if ($curr_matchID != NULL) {
			$curr_matchData = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?",[$curr_matchID])->fetch_assoc();
			$curr_games = $dbcn->execute_query("SELECT * FROM games WHERE MatchID = ? ORDER BY RiotMatchID",[$curr_matchID])->fetch_all(MYSQLI_ASSOC);
			$curr_team1 = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$curr_matchData['Team1ID']])->fetch_assoc();
			$curr_team2 = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$curr_matchData['Team2ID']])->fetch_assoc();
			echo "
                    <div class='mh-popup-bg' onclick='close_popup_match(event)' style='display: block; opacity: 1;'>
                        <div class='mh-popup'>
                            <div class='close-button' onclick='closex_popup_match()'><div class='material-symbol'>". file_get_contents("icons/material/close.svg") ."</div></div>
                            <div class='close-button-space'></div>";
			if ($curr_matchData['Winner'] == 1) {
				$team1score = "win";
				$team2score = "loss";
			} elseif ($curr_matchData['Winner'] == 2) {
				$team1score = "loss";
				$team2score = "win";
			} else {
				$team1score = "draw";
				$team2score = "draw";
			}
			echo "
                <h2 class='round-title'>
                    <span class='round'>Runde {$curr_matchData['round']}: &nbsp</span>
                    <span class='team $team1score'>{$curr_team1['TeamName']}</span>
                    <span class='score'><span class='$team1score'>{$curr_matchData['Team1Score']}</span>:<span class='$team2score'>{$curr_matchData['Team2Score']}</span></span>
                    <span class='team $team2score'>{$curr_team2['TeamName']}</span>
                </h2>";
			foreach ($curr_games as $game_i=>$curr_game) {
				echo "<div class='game game$game_i'>";
				$gameID = $curr_game['RiotMatchID'];
				create_game($dbcn,$gameID);
				echo "</div>";
			}
			echo "
                        </div>
                    </div>";
		} else {
			echo "   <div class='mh-popup-bg' onclick='close_popup_match(event)'>
                            <div class='mh-popup'></div>
                     </div>";
		}
        echo "<div class='match-content content'>";
        foreach ($matches_grouped as $roundNum=>$round) {
            echo "<div class='match-round'>
                    <h4>Runde $roundNum</h4>
                    <div class='divider'></div>
                    <div class='match-wrapper'>";
            foreach ($round as $match) {
                create_matchbutton($dbcn,$tournamentID,$match['MatchID'],"groups");
            }
            echo "</div>";
            echo "</div>"; // match-round
        }
        echo "</div>"; // match-content
        echo "</div>"; // matches
        echo "</div>"; // main-content
        echo "</body>";
    }
} catch (Exception $e) {
    echo "<title>Error</title></head>";
}
?>
</html>
