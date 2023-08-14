<?php
function create_html_head_elements($type,$loggedin=FALSE) {
	echo "<base href='/uniliga/'>";
	echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    echo "<link rel='icon' href='https://silence.lol/favicon-dark.ico' media='(prefers-color-scheme: dark)'/>";
    echo "<link rel='icon' href='https://silence.lol/favicon-light.ico' media='(prefers-color-scheme: light)'/>";
	echo "<link rel='stylesheet' href='style.css'>";
	if ($type==="elo") {
		echo "<link rel='stylesheet' href='elo-rank-colors.css'>";
	}
	if ($type==="group" || $type==="team" || $type==="matchhistory") {
		echo "<link rel='stylesheet' href='game.css'>";
	}
	echo "<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>";
	echo "<script src='main.js'></script>";
	if (($type==="team" || $type==="tournament") && $loggedin) {
		echo "<script src='admin/riot-api-access/rgapi.js'></script>";
	}
	if ($type==="home") {
		echo "<title>Uniliga LoL - Übersicht</title>";
	}
}

function generate_elo_list($dbcn,$view,$teams,$tournamentID,$division,$group) {
	$local_team_img = "img/team_logos/";
	$opgg_logo_svg = file_get_contents(dirname(__FILE__)."/img/opgglogo.svg");
	$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
	$view_class = "";
	if ($view != NULL) {
		$view_class = " " . $view . "-teams";
	}
	echo "
                <div class='teams-elo-list content$view_class'>";
	if ($view == "all") {
		echo "
                    <h3>Alle Ligen</h3>";
	} elseif ($view == "div") {
		echo "
                    <h3 class='liga{$division['Number']}'>Liga {$division['Number']}</h3>";
	} elseif ($view == "group") {
		if ($division["format"] === "Swiss") {
			echo "
                    <h3 class='liga{$division['Number']}'>Liga {$division['Number']} - Swiss-Gruppe</h3>";
		} else {
			echo "
                    <h3 class='liga{$division['Number']}'>Liga {$division['Number']} - Gruppe {$group['Number']}</h3>";
		}
	}
	echo "
                    <div class='elo-list-row elo-list-header'>
                        <div class='elo-list-pre-header league'>Liga #</div>
                        <a class='elo-list-item-wrapper-header'>
                        <div class='elo-list-item team'>Team</div>
                        <div class='elo-list-item rank'>avg. Rang</div>
                        </a>
                        <div class='elo-list-after-header elo-nr'>Elo</div>
                        <a class='elo-list-after-header op-gg'><div class='svg-wrapper op-gg'></div></a>
                    </div>";
	foreach ($teams as $team) {
		$curr_players = $dbcn->execute_query("SELECT * FROM players WHERE TournamentID = ? AND TeamID = ?",[$tournamentID,$team['TeamID']])->fetch_all(MYSQLI_ASSOC);
		$curr_opgglink = $opgg_url;
		$color_class = "";
		if ($view == "all") {
			$color_class = " liga".$team['Div_Num'];
		} elseif ($view == "div" || $view == "group") {
			$color_class = " rank".floor($team['avg_rank_num']);
		}
		foreach ($curr_players as $i_cop => $curr_player) {
			if ($i_cop != 0) {
				$curr_opgglink .= urlencode(",");
			}
			$curr_opgglink .= urlencode($curr_player["SummonerName"]);
		}
		echo "
                    <div class='elo-list-row elo-list-team {$team['TeamID']}$color_class'>
                        <div class='elo-list-pre league'>Liga {$team['Div_Num']}</div>
                        <a href='/uniliga/team/".$team['TeamID']."' onclick='popup_team(\"{$team['TeamID']}\")' class='elo-list-item-wrapper'>
                            <div class='elo-list-item team'>";
		if ($team['imgID'] != NULL) {
			echo "
                                <img src='$local_team_img{$team['imgID']}/logo_small.webp' alt='Teamlogo'>";
		}
		echo "
                                <span>{$team['TeamName']}</span>
                            </div>
                            <div class='elo-list-item rank'>";
		if ($team['avg_rank_tier'] != NULL) {
			$avg_rank = strtolower($team['avg_rank_tier']);
			$avg_rank_cap = ucfirst($avg_rank);
			$avg_rank_num = round($team['avg_rank_num'], 2);
			echo "
                                <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$avg_rank}.svg' alt='$avg_rank_cap'>
                                <span>{$avg_rank_cap} {$team['avg_rank_div']}</span>";
		} else {
			$avg_rank_num = 0.00;
		}
		echo "
                            </div>
                        </a>
                        <div class='elo-list-after elo-nr'>
                            <span>({$avg_rank_num})</span>
                        </div>
                        <a href='$curr_opgglink' target='_blank' class='elo-list-after op-gg'>
                            <div class='svg-wrapper op-gg'>$opgg_logo_svg</div>
                        </a>
                        
                    </div>";
	}
	echo "
                </div>"; // teams-elo-list
}

function create_standings(mysqli $dbcn,$tournament_id,$group_id,$team_id=NULL) {
	$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";
	$local_img_path = "img/team_logos/";
	$opgg_logo_svg = file_get_contents(dirname(__FILE__)."/img/opgglogo.svg");
	$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?",[$group_id])->fetch_assoc();
	$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?",[$group['DivID']])->fetch_assoc();
	$teams_from_groupDB = $dbcn->execute_query("SELECT * FROM teams JOIN teamsingroup ON teams.TeamID = teamsingroup.TeamID WHERE teamsingroup.GroupID = ? ORDER BY CASE WHEN `Rank`=0 THEN 1 else 0 end, `Rank`",[$group['GroupID']])->fetch_all(MYSQLI_ASSOC);

	echo "<div class='standings'>";
	if ($team_id == NULL) {
		echo "<div class='title'><h3>Standings</h3></div>";
	} else {
		echo "<div class='title'><h3>Standings Liga {$div['Number']} / Gruppe {$group['Number']}</h3></div>";
	}
	echo "<div class='standings-table content'>
			<div class='standing-row standing-header'>
				<div class='standing-pre-header rank'>#</div>
				<a class='standing-item-wrapper-header'>
					<div class='standing-item team'>Team</div>
					<div class='standing-item played'>Pl</div>
					<div class='standing-item score'>W - D - L</div>
					<div class='standing-item points'>Pt</div>
					<a class='standing-after-header op-gg'><div class='svg-wrapper op-gg'></div></a>
                </a>
            </div>";
	$last_rank = -1;
	foreach ($teams_from_groupDB as $currteam) {
		$curr_players = $dbcn->execute_query("SELECT * FROM players WHERE TournamentID = ? AND TeamID = ?",[$tournament_id,$currteam['TeamID']])->fetch_all(MYSQLI_ASSOC);
		$curr_opgglink = $opgg_url;
		foreach ($curr_players as $i_cop=>$curr_player) {
			if ($i_cop != 0) {
				$curr_opgglink .= urlencode(",");
			}
			$curr_opgglink .= urlencode($curr_player["SummonerName"]);
		}
		if ($team_id != NULL) {
			$current = ($currteam['TeamID'] == $team_id)? " current" : "";
		} else {
			$current = "";
		}
		$same_rank_class = "";
		if ($last_rank == $currteam['Rank']) {
			$same_rank_class = " no-bg";
		}
		echo "<div class='standing-row standing-team$current'>
				<div class='standing-pre rank$same_rank_class'>{$currteam['Rank']}</div>
				<a href='team/{$currteam['TeamID']}' class='standing-item-wrapper'>
				<div class='standing-item team'>";
		if ($currteam['imgID'] != NULL) {
			echo "<img src='$local_img_path{$currteam['imgID']}/logo_small.webp' alt=\"Teamlogo\">";
		}
		if ($currteam['avg_rank_tier'] != NULL) {
			$team_tier = strtolower($currteam['avg_rank_tier']);
			$team_tier_cap = ucfirst($team_tier);
			echo "<div class='team-name-rank'>
                        <span>{$currteam['TeamName']}</span>
                        <span class='rank'>
                            <img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$team_tier.svg' alt='$team_tier_cap'>
                            $team_tier_cap ".$currteam['avg_rank_div']."
                        </span>
                      </div>
                  </div>";
		} else {
			echo "<span>{$currteam['TeamName']}</span></div>";
		}
		echo "
                    <div class='standing-item played'>{$currteam['played']}</div>
                    <div class='standing-item score'>{$currteam['Wins']} - {$currteam['Draws']} - {$currteam['Losses']}</div>
                    <div class='standing-item points'>{$currteam['Points']}</div>
                    <a href='$curr_opgglink' target='_blank' class='standing-after op-gg'><div class='svg-wrapper op-gg'>$opgg_logo_svg</div></a>
                </a>
            </div>";
		$last_rank = $currteam['Rank'];
	}
	echo "</div></div>";
}

function create_matchbutton(mysqli $dbcn,$tournament_id,$match_id,$type,$team_id=NULL) {
	$pageurl = $_SERVER['REQUEST_URI'];
	$toor_tourn_url = "https://play.toornament.com/de/tournaments/";
	if ($type == "groups") {
		$match = $dbcn->execute_query("SELECT * FROM matches WHERE MatchID = ?",[$match_id])->fetch_assoc();
	} elseif ($type == "playoffs") {
		$match = $dbcn->execute_query("SELECT * FROM playoffmatches WHERE MatchID = ?",[$match_id])->fetch_assoc();
	} else {
		return;
	}
	$teams_from_DB = $dbcn->execute_query("SELECT * FROM teams")->fetch_all(MYSQLI_ASSOC);
	$teams = [];
	foreach ($teams_from_DB as $i=>$team) {
		$teams[$team['TeamID']] = array("TeamName"=>$team['TeamName'], "imgID"=>$team['imgID']);
	}

	$current1 = "";
	$current2 = "";
	if ($team_id != NULL) {
		if ($match['Team1ID'] == $team_id) {
			$current1 = " current";
		} elseif ($match['Team2ID'] == $team_id) {
			$current2 = " current";
		}
	}

	if ($match['played'] == 0) {
		$datetime = date_create($match['plannedDate']);
		$date = date_format($datetime, 'd M');
		$time = date_format($datetime, 'H:i');
		echo "<div class='match-button-wrapper'>
                            <a class='button match nolink sideext-right'>
                                <div class='teams'>
                                    <div class='team 1$current1'><div class='name'>{$teams[$match['Team1ID']]['TeamName']}</div></div>
                                    <div class='team 2$current2'><div class='name'>{$teams[$match['Team2ID']]['TeamName']}</div></div>
                                </div>";
		if ($match['plannedDate'] != NULL) {
			echo "<div class='date'>{$date}<br>{$time}</div>";
		} else {
			echo "<div>vs.</div>";
		}
		echo "</a>
                          <a class='sidebutton-match' href='$toor_tourn_url{$tournament_id}/matches/{$match['MatchID']}' target='_blank'>
                            <div class='material-symbol'>". file_get_contents("icons/material/open_in_new.svg") ."</div>
                          </a>
                        </div>";
	} else {
		$t1score = $match['Team1Score'];
		$t2score = $match['Team2Score'];
		if ($t1score == -1 || $t2score == -1) {
			$t1score = ($t1score == -1) ? "L" : "W";
			$t2score = ($t2score == -1) ? "L" : "W";
		}
		if ($match['Winner'] == 1) {
			$state1 = "win";
			$state2 = "loss";
		} else if ($match['Winner'] == 2) {
			$state1 = "loss";
			$state2 = "win";
		} else {
			$state1 = "draw";
			$state2 = "draw";
		}
		echo "<div class='match-button-wrapper'>";
		if ($team_id != NULL) {
			echo "<a class='button match sideext-right' href='$pageurl' onclick='popup_match(\"{$match['MatchID']}\",\"{$team_id}\")'>";
		} else {
			echo "<a class='button match sideext-right' href='$pageurl' onclick='popup_match(\"{$match['MatchID']}\")'>";
		}
		echo "<div class='teams score'>
				<div class='team 1 $state1$current1'><div class='name'>{$teams[$match['Team1ID']]['TeamName']}</div><div class='score'>{$t1score}</div></div>
				<div class='team 2 $state2$current2'><div class='name'>{$teams[$match['Team2ID']]['TeamName']}</div><div class='score'>{$t2score}</div></div>
			  </div>
			</a>
			<a class='sidebutton-match' href='$toor_tourn_url{$tournament_id}/matches/{$match['MatchID']}' target='_blank'>
				<div class='material-symbol'>". file_get_contents("icons/material/open_in_new.svg") ."</div>
			</a>
		</div>";
	}
}

function is_logged_in() {
	include_once(dirname(__FILE__)."/admin/admin-pass.php");
	$admin_pass = get_admin_pass();
	if (isset($_COOKIE['write-login'])) {
		if (password_verify($admin_pass, $_COOKIE['write-login'])) {
			return TRUE;
		}
	}
	return FALSE;
}
function logged_in_buttons_hidden() {
	if (isset($_COOKIE['admin_btns']) && $_COOKIE['admin_btns'] === "0") {
		return TRUE;
	}
	return FALSE;
}
function is_light_mode() {
	if (isset($_COOKIE['lightmode']) && $_COOKIE['lightmode'] === "1") {
		return TRUE;
	}
	return FALSE;
}

function create_header($dbcn,$type,$tournament_id=NULL,$group_id=NULL,$team_id=NULL) {
	$pageurl = $_SERVER['REQUEST_URI'];
	$toor_tourn_url = "https://play.toornament.com/de/tournaments/";
	$outlinkicon = file_get_contents(dirname(__FILE__)."/icons/material/open_in_new.svg");
	if ($tournament_id != NULL) {
		$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE TournamentID = ?",[$tournament_id])->fetch_assoc();
		$t_name_clean = explode("League of Legends",$tournament['Name']);
		if (count($t_name_clean)>1) {
			$tournament_name = $t_name_clean[0].$t_name_clean[1];
		} else {
			$tournament_name = $tournament['Name'];
		}
	}
	$loggedin = is_logged_in();
	if (is_light_mode()) {
		$colormode = "light";
	} else {
		$colormode = "dark";
	}

	echo "<header class='$type'>";
	if ($type != "home") {
		echo "
	<a href='/uniliga' class='homelink'>
		<div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/home.svg")."</div>
	</a>";
	}
	echo "<div class='title'>";
	if ($type == "home") {
		echo "<h1>Uniliga LoL - Übersicht</h1>";
	}
	if ($type == "players") {
		echo "<h1>Uniliga LoL - Spieler</h1>";
	}
	if ($type == "tournament" || $type == "group" || $type == "team") {
		echo "<h1>$tournament_name</h1>";
		echo "<a href='$toor_tourn_url$tournament_id' target='_blank' class='toorlink'><div class='material-symbol'>$outlinkicon</div></a>";
	}
	echo "</div>";
	echo "<a class='settings-button' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/tune.svg") ."</div></a>";
	if ($loggedin) {
		if (logged_in_buttons_hidden()) {
			$admin_button_state = "";
		} else {
			$admin_button_state = "_off";
		}
		echo "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option toggle-admin-b-vis' href='$pageurl'>Buttons<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/visibility$admin_button_state.svg") ."</div></a>
				<a class='settings-option toor-write' href='/uniliga/admin'>Admin<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/edit_square.svg") ."</div></a>
				<a class='settings-option rgapi-write' href='/uniliga/admin/riot-api-access'>RGAPI<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/videogame_asset.svg") ."</div></a>
				<a class='settings-option logout' href='?logout'>Logout<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/logout.svg") ."</div></a>
			</div>";
	} else {
		echo "
			<div class='settings-menu'>
				<a class='settings-option toggle-mode' href='$pageurl'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/{$colormode}_mode.svg") ."</div></a>
				<a class='settings-option github-link' href='https://github.com/Si-Lan/Uniliga-LoL-Uebersicht' target='_blank'>GitHub<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/img/github-mark-white.svg") ."</div></a>
				<a class='settings-option' href='https://paypal.me/SimonlLang' target='_blank'>Spenden<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/payments.svg") ."</div></a>
				<a class='settings-option login' href='?login'>Login<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/login.svg") ."</div></a>
			</div>";
	}
	echo "</header>";
}

function create_tournament_overview_nav_buttons($dbcn,$tournament_id,$active="",$division_id=NULL,$group_id=NULL) {
	$overview = $list = $elo = $group_a = "";
	if ($active == "overview") {
		$overview = " active";
	} elseif ($active == "list") {
		$list = " active";
	} elseif ($active == "elo") {
		$elo = " active";
	} elseif ($active == "group") {
		$group_a = " active";
	}
	$teamlink_addition = "";
	if ($division_id != NULL) {
		$teamlink_addition = "?liga=$division_id";
		if ($group_id != NULL) {
			$teamlink_addition .= "&gruppe=$group_id";
		}
	}
	echo "
		<div class='turnier-bonus-buttons'>
			<div class='turnier-nav-buttons'>
				<a href='turnier/{$tournament_id}' class='button$overview'>
    	        	<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/sports_esports.svg") ."</div>
        		    Turnier
            	</a>
	            <a href='turnier/{$tournament_id}/teams$teamlink_addition' class='button$list'>
    	        	<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/group.svg") ."</div>
        	        Teams
            	</a>
	            <a href='turnier/{$tournament_id}/elo' class='button$elo'>
    	            <div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/stars.svg") ."</div>
        	        Eloverteilung
            	</a>
            </div>";
	if ($group_id != NULL && $active != "group") {
		$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?",[$group_id])->fetch_assoc();
		$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?",[$group['DivID']])->fetch_assoc();
		if ($div["format"] === "Swiss") {
			$group_title = "Swiss-Gruppe";
		} else {
			$group_title = "Gruppe {$group['Number']}";
		}
		echo "
			<div class='divider-vert'></div>
			<a href='turnier/{$tournament_id}/gruppe/$group_id' class='button$group_a'>
                <div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/table_rows.svg") ."</div>
                Liga ".$div['Number']." - $group_title
            </a>";
	}
	echo "</div>";
	echo "<div class='divider bot-space'></div>";
}

function create_team_nav_buttons($tournament_id,$team,$active) {
	$details_a = $matchhistory_a = $stats_a = "";
	if ($active == "details") {
		$details_a = " active";
	} elseif ($active == "matchhistory") {
		$matchhistory_a = " active";
	} elseif ($active == "stats") {
		$stats_a = " active";
	}
	$local_team_img = "img/team_logos/";
	$toor_tourn_url = "https://play.toornament.com/de/tournaments/";
	$team_id = $team['TeamID'];
	echo "<div class='team title'>
			<div class='team-name'>";
	if ($team['imgID'] != NULL) {
		echo "<img alt src='$local_team_img{$team['imgID']}/logo_medium.webp'>";
	}
	echo "
			<div>
				<h2>{$team['TeamName']}</h2>
				<a href=\"$toor_tourn_url$tournament_id/participants/$team_id/info\" class='toorlink'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/open_in_new.svg") ."</div></a>
			</div>
        </div>
        <div class='team-titlebutton-wrapper'>
           	<a href='team/$team_id' class='button$details_a'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/info.svg") ."</div>Team-Übersicht</a>
           	<a href='team/$team_id/matchhistory' class='button$matchhistory_a'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/manage_search.svg") ."</div>Match-History</a>
            <a href='team/$team_id/stats' class='button$stats_a'><div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/monitoring.svg") ."</div>Statistiken</a>
        </div>";
	echo "</div>";
}

function populate_th($maintext,$tooltiptext,$init=false) {
	if ($init) {
		$svg_code = file_get_contents(dirname(__FILE__)."/icons/material/expand_more.svg");
	} else {
		$svg_code = file_get_contents(dirname(__FILE__)."/icons/material/check_indeterminate_small.svg");
	}
	return "<span class='tooltip'>$maintext<span class='tooltiptext'>$tooltiptext</span><div class='material-symbol sort-direction'>".$svg_code."</div></span>";
}

function create_player_overview_cards($dbcn,$search) {
	$puuids = $dbcn->execute_query("SELECT DISTINCT PUUID FROM players WHERE PUUID IN (SELECT PUUID FROM players WHERE SummonerName LIKE ? OR PlayerName LIKE ?)",["%".$search."%","%".$search."%"])->fetch_all(MYSQLI_ASSOC);
	$unique_players = array();
	foreach ($puuids AS $puuid) {
		$player_stats = $dbcn->execute_query("SELECT PlayerName, SummonerName, TeamName, PUUID, `Name` FROM players JOIN teams ON players.TeamID=teams.TeamID JOIN tournaments ON tournaments.TournamentID=teams.TournamentID WHERE PUUID = ? ORDER BY tournaments.DateStart DESC",[$puuid["PUUID"]])->fetch_all(MYSQLI_ASSOC);
		$unique_players[] = $player_stats;
	}
	$player_cards = "";

	foreach ($unique_players as $player) {
		$player_cards .= "<a class='player-ov-card' href='/uniliga/spieler' onclick='popup_player(\"".$player[0]["PUUID"]."\")'>";
		$player_names = array();
		$summoner_names = array();
		foreach ($player as $tourn_player) {
			if (!in_array($tourn_player["PlayerName"],$player_names)) {
				$player_names[] = $tourn_player["PlayerName"];
			}
			if (!in_array($tourn_player["SummonerName"],$summoner_names)) {
				$summoner_names[] = $tourn_player["SummonerName"];
			}
		}
		$player_cards .= "<span>".$player_names[0]."</span>";
		if (count($player_names)>1) {
			$player_cards .= "<span>(";
			for ($i=1; $i < count($player_names); $i++) {
				$player_cards .= $player_names[$i];
				if (!($i == count($player_names)-1)) {
					$player_cards .= ", ";
				}
			}
			$player_cards .= ")</span>";
		}
		$player_cards .= "<div class='divider'></div>";
		$player_cards .= "<span>".$summoner_names[0]."</span>";
		if (count($summoner_names)>1) {
			$player_cards .= "<span>(";
			for ($i=1; $i < count($summoner_names); $i++) {
				$player_cards .= $summoner_names[$i];
				if (!($i == count($summoner_names)-1)) {
					$player_cards .= ", ";
				}
			}
			$player_cards .= ")</span>";
		}
		$player_cards .= "</a>";
	}

	echo $player_cards;
}

function create_player_overview($dbcn,$puuid) {
	$player_stats = $dbcn->execute_query("SELECT players.PlayerID, players.PlayerName, players.SummonerName, teams.TeamName, teams.TeamID, players.PUUID, tournaments.`Name`, teams.imgID, tournaments.Season, tournaments.Split, tournaments.TournamentID, tournaments.imgID as TimgID FROM players JOIN teams ON players.TeamID=teams.TeamID JOIN tournaments ON tournaments.TournamentID=teams.TournamentID WHERE PUUID = ? ORDER BY tournaments.DateStart DESC",[$puuid])->fetch_all(MYSQLI_ASSOC);
	if (count($player_stats) >= 2) {
		echo "<div class='player-ov-buttons'>";
		echo "<a href='#' class='button expand-pcards' title='Ausklappen' onclick='expand_all_playercards()'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/unfold_more.svg")."</div></a>";
		echo "<a href='#' class='button expand-pcards' title='Einklappen' onclick='expand_all_playercards(true)'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/unfold_less.svg")."</div></a>";
		echo "</div>";
	}
	echo "<div class='player-popup-content'>";
	foreach ($player_stats as $player) {
		$details = $dbcn->execute_query("SELECT rank_div, rank_tier, leaguePoints, roles, champions FROM players WHERE PlayerID=?", [$player["PlayerID"]])->fetch_assoc();
		echo create_playercard($player, $details);
	}
	echo "</div>";
}

function create_playercard($player_data, $detail_stats=NULL) {
	if ($detail_stats != NULL) {
		$roles = json_decode($detail_stats['roles'], true);
		$champions = json_decode($detail_stats['champions'], true);
		$rendered_rows = 0;
		if (array_sum($roles)>0 && count($champions)>0) {
			$rendered_rows = 2;
		} elseif (array_sum($roles)>0 || count($champions)>0) {
			$rendered_rows = 1;
		}
		$result = "<div class='player-card' data-details='$rendered_rows'>";
	} else {
		$result = "<div class='player-card'>";
	}

	// Turnier-Titel
	$result .= "<a class='player-card-div player-card-tournament' href='turnier/{$player_data["TournamentID"]}'>";
	if ($player_data["TimgID"] != NULL) {
		$result .= "<img alt='Turnier Logo' src='img/tournament_logos/{$player_data["TimgID"]}/logo_medium.webp'>";
	}
	$result .= "{$player_data["Split"]} {$player_data["Season"]}";
	$result .= "</a>";
	// Spielername und Summonername
	$result .= "<div class='player-card-div player-card-name'>
					<span>{$player_data["PlayerName"]}</span>
					<a href='https://op.gg/summoners/euw/{$player_data["SummonerName"]}' target='_blank'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/person.svg")."</div>{$player_data["SummonerName"]}</a>
				</div>";
	// Teamname
	$result .= "<a class='player-card-div player-card-team' href='team/{$player_data["TeamID"]}'>";
	if ($player_data["imgID"] != NULL) {
		$result .= "<img alt='{$player_data["TeamName"]} Logo' src='img/team_logos/{$player_data["imgID"]}/logo_medium.webp'>";
		$result .= "<span>{$player_data["TeamName"]}</span>";
	} else {
		$result .= "<span class='player-card-nologo'>{$player_data["TeamName"]}</span>";

	}
	$result .= "</a>";
	// detailed Stats
	if ($detail_stats != NULL) {
		$rank_tier = strtolower($detail_stats["rank_tier"]);
		$rank_div = $detail_stats["rank_div"];
		$LP = NULL;
		if ($rank_tier == "CHALLENGER" || $rank_tier == "GRANDMASTER" || $rank_tier == "MASTER") {
			$rank_div = "";
			$LP = $detail_stats["leaguePoints"];
		}
		if ($LP != NULL) {
			$LP = "(".$LP." LP)";
		} else {
			$LP = "";
		}

		// Rang
		if ($detail_stats["rank_tier"] != NULL) {
			$result .= "<div class='player-card-div player-card-rank'><img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/$rank_tier.svg' alt='".ucfirst($rank_tier)."'>".ucfirst($rank_tier)." $rank_div $LP</div>";
		} else {
			$result .= "<div class='player-card-div player-card-rank'>kein Rang</div>";
		}

		// roles
		if (array_sum($roles) > 0) {
			$result .= "<div class='player-card-div player-card-roles'>";
			foreach ($roles as $role => $role_amount) {
				if ($role_amount != 0) {
					$result .= "
				<div class='role-single'>
					<div class='svg-wrapper role'>" . file_get_contents(dirname(__FILE__) . "/ddragon/img/positions/position-$role-light.svg") . "</div>
					<span class='played-amount'>$role_amount</span>
				</div>";
				}
			}
			$result .= "</div>";
		}

		// champs
		if (count($champions) > 0) {
			$result .= "<div class='player-card-div player-card-roles'>";
			arsort($champions);
			$champs_cut = FALSE;
			if (count($champions) > 5) {
				$champions = array_slice($champions, 0, 5);
				$champs_cut = TRUE;
			}

			$patches = [];
			$dir = new DirectoryIterator(dirname(__FILE__) . "/ddragon");
			foreach ($dir as $fileinfo) {
				if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
					$patches[] = $fileinfo->getFilename();
				}
			}
			usort($patches, "version_compare");
			$patch = end($patches);

			foreach ($champions as $champion => $champion_amount) {
				$result .= "
			<div class='champ-single'>
				<img src='/uniliga/ddragon/{$patch}/img/champion/{$champion}.webp' alt='$champion'>
				<span class='played-amount'>" . $champion_amount['games'] . "</span>
			</div>";
			}
			if ($champs_cut) {
				$result .= "
		<div class='champ-single'>
			<div class='material-symbol'>" . file_get_contents(dirname(__FILE__) . "/icons/material/more_horiz.svg") . "</div>
		</div>";
			}
			$result .= "</div>";
		}
		// erweiterungs button
		$result .= "<a class='player-card-div player-card-more' href='#' onclick='expand_playercard(this)'><div class='material-symbol'>".file_get_contents(dirname(__FILE__)."/icons/material/expand_more.svg")."</div> mehr Infos</a>";
	}

	$result .= "</div>";
	return $result;
}