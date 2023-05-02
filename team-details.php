<!DOCTYPE html>
<html lang="de">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");
include("game.php");
include("summoner-card.php");

$logged_in = is_logged_in();

create_html_head_elements("team",$logged_in);

$pageurl = $_SERVER['REQUEST_URI'];

$teamID = $_GET['team'] ?? NULL;

$local_img_path = "img/team_logos/";
$toor_tourn_url = "https://play.toornament.com/de/tournaments/";
$opgg_logo_svg = file_get_contents("img/opgglogo.svg");
$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";

if (is_light_mode()) {
	$lightmode = " light";
} else {
	$lightmode = "";
}
if (!$logged_in || logged_in_buttons_hidden()) {
	$adminbtnbody = "";
} else {
	$adminbtnbody = " admin_li";
}

try {
    $dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

    if ($dbcn->connect_error) {
        echo "<title>Database Connection failed</title></head>Database Connection failed : " . $dbcn->connect_error;
    } else {
		if ($teamID == NULL) {
			echo "<title>Kein Team gefunden | Uniliga LoL - Übersicht</title></head>";
			echo "
                <body class='team'>
                    <div class='header' style='margin-bottom: 20px'>
                        <a href='.' class='button'>
                            <div class='material-symbol'>". file_get_contents("icons/material/home.svg") ."</div>
                            Startseite
                        </a>
                    </div>
                    <div style='text-align: center'>
                        Kein Team unter der angegebenen ID gefunden!
                    </div>
                </body>";
			exit;
		}
        $team = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$teamID}")->fetch_assoc();
        echo "<title>{$team['TeamName']} | Uniliga LoL - Übersicht</title>";
        ?>
</head>
<?php
		if (isset($_GET['match'])) {
			echo "<body class='team$lightmode$adminbtnbody' style='overflow: hidden'>";
		} else {
			echo "<body class='team$lightmode$adminbtnbody'>";
		}

        $tournament = $dbcn->query("SELECT * FROM tournaments WHERE TournamentID = {$team["TournamentID"]}")->fetch_assoc();
        $team_in_group = $dbcn->query("SELECT * FROM teamsingroup WHERE TeamID = {$teamID}")->fetch_assoc();

        if ($team_in_group === NULL) {
			create_header($dbcn,"team",$tournament["TournamentID"]);
			create_tournament_overview_nav_buttons($dbcn,$tournament['TournamentID'],"");
			create_team_nav_buttons($tournament["TournamentID"],$team,"details");
			$players = $dbcn->query("SELECT * FROM players WHERE TournamentID = {$tournament['TournamentID']} AND TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);
			$opgglink = $opgg_url;
			for ($i_opgg = 0; $i_opgg < count($players); $i_opgg++) {
				if ($i_opgg != 0) {
					$opgglink .= urlencode(",");
				}
				$opgglink .= urlencode($players[$i_opgg]["SummonerName"]);
			}
			$player_amount = count($players);
			$players_by_id = array();
			$players_gamecount_by_id = array();
			foreach ($players as $player) {
				$players_by_id[$player['PlayerID']] = $player;
				$played_games = 0;
				foreach (json_decode($player['roles'],true) as $role_played_amount) {
					$played_games += $role_played_amount;
				}
				$players_gamecount_by_id[$player['PlayerID']] = $played_games;
			}
			arsort($players_gamecount_by_id);
			echo "
            <div class='main-content'>
                <div class='player-cards opgg-cards'>
                    <div class='title'>
                        <h3>Spieler</h3>
                        <a href='$opgglink' class='button op-gg' target='_blank'><div class='svg-wrapper op-gg'>$opgg_logo_svg</div><span class='player-amount'>({$player_amount} Spieler)</span></a>";
			if (isset($_COOKIE["preference_sccollapsed"])) {
				if ($_COOKIE["preference_sccollapsed"] === "1") {
					$collapsed = TRUE;
					echo "<a href='$pageurl' class='button exp_coll_sc'><div class='svg-wrapper'>".file_get_contents("icons/material/unfold_more.svg")."</div>Stats ein</a>";
				} else {
					$collapsed = FALSE;
					echo "<a href='$pageurl' class='button exp_coll_sc'><div class='svg-wrapper'>".file_get_contents("icons/material/unfold_less.svg")."</div>Stats aus</a>";
				}
			} else {
				$collapsed = TRUE;
				echo "<a href='$pageurl' class='button exp_coll_sc'><div class='svg-wrapper'>".file_get_contents("icons/material/unfold_more.svg")."</div>Stats ein</a>";
			}
			echo "
                     </div>";
			if ($team['avg_rank_tier'] != NULL) {
				$avg_rank = strtolower($team['avg_rank_tier']);
				$avg_rank_cap = ucfirst($avg_rank);
				echo "
                    <div class='team-avg-rank'>
                        Team-Rang: 
                        <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$avg_rank}.webp' alt='$avg_rank_cap'>
                        <span>{$avg_rank_cap} {$team['avg_rank_div']}</span>
                    </div>";
			}
			echo "
                    <div class='summoner-card-container'>";
			foreach ($players_gamecount_by_id as $player_id=>$player_gamecount) {
				$player = $players_by_id[$player_id];
				create_summonercard($player,$collapsed);
			}
			echo "
                    </div> 
                </div>"; //summoner-card-container -then- player-cards
            echo "<span>Dieses Team ist noch keiner Gruppe zugewiesen</span></div>
        </body>";
            exit;
        }


        $group = $dbcn->query("SELECT * FROM `groups` WHERE GroupID = {$team_in_group['GroupID']}")->fetch_assoc();
        $div = $dbcn->query("SELECT * FROM divisions WHERE DivID = {$group['DivID']}")->fetch_assoc();
        $players = $dbcn->query("SELECT * FROM players WHERE TournamentID = {$tournament['TournamentID']} AND TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);
        $matches = $dbcn->query("SELECT * FROM matches WHERE GroupID = {$group['GroupID']} AND (Team1ID = {$teamID} OR Team2ID = {$teamID})")->fetch_all(MYSQLI_ASSOC);
        $teams_from_groupDB = $dbcn->query("SELECT * FROM teams JOIN teamsingroup ON teams.TeamID = teamsingroup.TeamID WHERE teamsingroup.GroupID = {$group['GroupID']} ORDER BY `Rank`")->fetch_all(MYSQLI_ASSOC);
        $teams_from_group = [];
        foreach ($teams_from_groupDB as $i=>$team_from_group) {
            $teams_from_group[$team_from_group['TeamID']] = array("TeamName"=>$team_from_group['TeamName'], "imgID"=>$team_from_group['imgID']);
        }
        $opgglink = $opgg_url;
        for ($i_opgg = 0; $i_opgg < count($players); $i_opgg++) {
            if ($i_opgg != 0) {
                $opgglink .= urlencode(",");
            }
            $opgglink .= urlencode($players[$i_opgg]["SummonerName"]);
        }
        $player_amount = count($players);
        $players_by_id = array();
        $players_gamecount_by_id = array();
		foreach ($players as $player) {
			$players_by_id[$player['PlayerID']] = $player;
			$played_games = 0;
			foreach (json_decode($player['roles'],true) as $role_played_amount) {
				$played_games += $role_played_amount;
			}
			$players_gamecount_by_id[$player['PlayerID']] = $played_games;
        }
        arsort($players_gamecount_by_id);

        create_header($dbcn,"team",$tournament["TournamentID"],$group["GroupID"],$teamID);
        create_tournament_overview_nav_buttons($dbcn,$tournament['TournamentID'],"",$div['DivID'],$group['GroupID']);
		create_team_nav_buttons($tournament["TournamentID"],$team,"details");

        echo "<div class='main-content'>";
        echo "
                <div class='player-cards opgg-cards'>
                    <div class='title'>
                        <h3>Spieler</h3>
                        <a href='$opgglink' class='button op-gg' target='_blank'><div class='svg-wrapper op-gg'>$opgg_logo_svg</div><span class='player-amount'>({$player_amount} Spieler)</span></a>";
		if (isset($_COOKIE["preference_sccollapsed"])) {
			if ($_COOKIE["preference_sccollapsed"] === "1") {
				$collapsed = TRUE;
				echo "<a href='$pageurl' class='button exp_coll_sc'><div class='svg-wrapper'>".file_get_contents("icons/material/unfold_more.svg")."</div>Stats ein</a>";
			} else {
				$collapsed = FALSE;
				echo "<a href='$pageurl' class='button exp_coll_sc'><div class='svg-wrapper'>".file_get_contents("icons/material/unfold_less.svg")."</div>Stats aus</a>";
			}
		} else {
			$collapsed = TRUE;
			echo "<a href='$pageurl' class='button exp_coll_sc'><div class='svg-wrapper'>".file_get_contents("icons/material/unfold_more.svg")."</div>Stats ein</a>";
		}
        echo "
                     </div>";
        if ($team['avg_rank_tier'] != NULL) {
			$avg_rank = strtolower($team['avg_rank_tier']);
            $avg_rank_cap = ucfirst($avg_rank);
			echo "
                    <div class='team-avg-rank'>
                        Team-Rang: 
                        <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$avg_rank}.webp' alt='$avg_rank_cap'>
                        <span>{$avg_rank_cap} {$team['avg_rank_div']}</span>
                    </div>";
		}
        echo "
                    <div class='summoner-card-container'>";
		foreach ($players_gamecount_by_id as $player_id=>$player_gamecount) {
			$player = $players_by_id[$player_id];
			create_summonercard($player,$collapsed);
		}
        echo "
                    </div> 
                </div>"; //summoner-card-container -then- player-cards

		echo "<div class='inner-content'>";

		create_standings($dbcn,$tournament['TournamentID'],$group['GroupID'],$teamID);

		echo "<div class='matches'>
                     <div class='title'><h3>Spiele</h3></div>";
		$curr_matchID = $_GET['match'] ?? NULL;
		if ($curr_matchID != NULL) {
			$curr_matchData = $dbcn->query("SELECT * FROM matches WHERE MatchID = $curr_matchID")->fetch_assoc();
			$curr_games = $dbcn->query("SELECT * FROM games WHERE MatchID = $curr_matchID ORDER BY RiotMatchID")->fetch_all(MYSQLI_ASSOC);
			$curr_team1 = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$curr_matchData['Team1ID']}")->fetch_assoc();
			$curr_team2 = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$curr_matchData['Team2ID']}")->fetch_assoc();
			echo "
                    <div class='mh-popup-bg' onclick='close_popup_match(event)' style='display: block; opacity: 1;'>
                        <div class='mh-popup'>
                            <div class='close-button' onclick='closex_popup_match()'><div class='material-symbol'>". file_get_contents("icons/material/close.svg") ."</div></div>
                            <div class='close-button-space'></div>
                            <a class='button' href='team/$teamID/matchhistory#{$curr_matchID}'><div class='material-symbol'>". file_get_contents("icons/material/manage_search.svg") ."</div>in Matchhistory ansehen</a>";
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
				create_game($dbcn,$gameID,$teamID);
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
        foreach ($matches as $match) {
            create_matchbutton($dbcn,$tournament['TournamentID'],$group['GroupID'],$match['MatchID'],$teamID);
        }
        echo "</div></div>";
        echo "</div>"; // inner-content
        echo "</div>"; // main-content

        if ($logged_in) {
			echo "<div class='writing-wrapper'>";
			    echo "<div class='divider big-space'></div>";
			    echo "<a class='button write games-team $teamID {$tournament['TournamentID']}' onclick='get_games_for_team(\"{$tournament['TournamentID']}\",\"$teamID\")'>Lade Spiele für {$team['TeamName']}</a>";
                echo "<a class='button write gamedata {$tournament['TournamentID']}' onclick='get_game_data(\"{$tournament['TournamentID']}\",\"$teamID\")'>Lade Spiel-Daten für geladene Spiele</a>";
                echo "<a class='button write assign-una {$tournament['TournamentID']}' onclick='assign_and_filter_games(\"{$tournament['TournamentID']}\",\"$teamID\")'>sortiere unsortierte Spiele</a>";
                echo "<div class='result-wrapper no-res $teamID {$tournament['TournamentID']}'>
                        <div class='clear-button' onclick='clear_results(\"$teamID\")'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
            echo "</div>"; // writing-wrapper
        }

        echo "</body>";
    }
} catch (Exception $e) {
    echo "<title>Error</title></head>";
}
?>
</html>