<!DOCTYPE html>
<html lang="de">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");

create_html_head_elements("statistics");

$pageurl = $_SERVER['REQUEST_URI'];

$team_id = $_GET['team'];

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
	$team = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$team_id])->fetch_assoc();
?>
	<title><?php echo "{$team['TeamName']}" ?> - Statistiken | Uniliga LoL - Übersicht</title>
</head>
<body class="statistics<?php echo $lightmode?>">
	<?php
	$tournament = $dbcn->execute_query("SELECT * FROM tournaments WHERE TournamentID = ?",[$team["TournamentID"]])->fetch_assoc();
	$tournament_id = $tournament['TournamentID'];
	$team_in_group = $dbcn->execute_query("SELECT * FROM teamsingroup WHERE TeamID = ?",[$team_id])->fetch_assoc();
	$group = $dbcn->execute_query("SELECT * FROM `groups` WHERE GroupID = ?",[$team_in_group['GroupID']])->fetch_assoc();
	$group_id = $group['GroupID'];
	$div = $dbcn->execute_query("SELECT * FROM divisions WHERE DivID = ?",[$group['DivID']])->fetch_assoc();
	$div_id = $div['DivID'];
    $teamstats = $dbcn->execute_query("SELECT * FROM teamstats WHERE TeamID = ?",[$team_id])->fetch_assoc();
    $players = $dbcn->execute_query("SELECT * FROM players WHERE TeamID = ?",[$team_id])->fetch_all(MYSQLI_ASSOC);

    $ddragon_dir = new DirectoryIterator(dirname(__FILE__)."/ddragon");
    $patches = [];

    foreach ($ddragon_dir as $patch_dir) {
        if (!$patch_dir->isDot() && $patch_dir->getFilename() != "img") {
            $patches[] = $patch_dir->getFilename();
        }
    }
    rsort($patches);
    $latest_patch = $patches[0];

    $games_played = $teamstats['games_played'] ?? 0;

	create_header($dbcn,"team",$tournament_id,$group_id,$team_id);
	create_tournament_overview_nav_buttons($dbcn,$tournament_id,"",$div_id,$group_id);
    create_team_nav_buttons($tournament_id,$team,"stats");
    echo "<div class='main-content'>";
    if ($games_played == 0) {
        echo "<span>Dieses Team hat noch keine Spiele gespielt</span>";
	} else {
        echo "<span>Spiele: ".$games_played." | Siege: ".$teamstats['games_won']." (".round($teamstats['games_won']/$games_played*100,2)."%)</span>";
        echo "<span>durchschn. Zeit zum Sieg: ".date("i:s",$teamstats['avg_win_time'])."</span>";

		$playername_to_summoner = array();
        $players_by_name = array();
		$team_roles = array("top"=>array(),"jungle"=>array(),"middle"=>array(),"bottom"=>array(),"utility"=>array());
		foreach ($players as $player) {
			$roles = json_decode($player['roles'],true);
			foreach ($roles as $role=>$role_num) {
				if ($role_num > 0) {
					$team_roles[$role][$player['PlayerName']] = $role_num;
				}
			}
            $playername_to_summoner[$player['PlayerName']] = $player['SummonerName'];
            $players_by_name[$player['PlayerName']] = $player;
		}
		$players_to_show = array();
		$players_not_to_show = array();
		echo "<div class='teamroles'>";
        foreach ($team_roles as $role=>$role_players) {
            arsort($role_players);
            echo "<div class='role'>
                    <div class='svg-wrapper role'>".file_get_contents("ddragon/img/positions/position-$role-light.svg")."</div>";
            echo "<div class='roleplayers'>";
            $count_role_players = 0;
            foreach ($role_players as $role_player=>$role_player_num) {
                $selected = " selected-player-table";
                if ($count_role_players > 0){
                    echo "<div class='divider-vert'></div>";
                    $selected = "";
                    if (!in_array($role_player,$players_to_show) && !in_array($role_player,$players_not_to_show)) {
						$players_not_to_show[] = $role_player;
					}
				}
				if ($selected !== "") {
					if (!in_array($role_player,$players_to_show)) {
						$players_to_show[] = $role_player;
					}
                    if (in_array($role_player,$players_not_to_show)) {
                        if (($key = array_search($role_player,$players_not_to_show)) !== false) {
							array_splice($players_not_to_show,$key,1);
						}
					}
				}
				echo "<a href='$pageurl' class='role-playername$selected'>".$playername_to_summoner[$role_player]." ({$role_player_num}x)</a>";
                $count_role_players++;
			}
            echo "</div>";
            echo "</div>";
		}
        echo "</div>";

        $champs_played = json_decode($teamstats['champs_played'], true);
        arsort($champs_played);
        $champs_banned_against = json_decode($teamstats['champs_banned_against'], true);
        arsort($champs_banned_against);
        $champs_played_against = json_decode($teamstats['champs_played_against'],true);
        arsort($champs_played_against);
        $champs_banned = json_decode($teamstats['champs_banned'],true);
        arsort($champs_banned);
        $champs_presence = array();
        $champs_presence_only = array();
        foreach ($champs_played as $champ=>$champ_num) {
            $champs_presence[$champ] = array("played"=>$champ_num['games'],"banned_against"=>0,"played_against"=>0,"banned"=>0,"wins"=>$champ_num['wins'],"total"=>$champ_num['games']);
            $champs_presence_only[$champ] = $champ_num['games'];
        }
        foreach ($champs_banned_against as $champ=>$champ_num) {
            if (array_key_exists($champ,$champs_presence)) {
                $champs_presence[$champ]["banned_against"] += $champ_num;
                $champs_presence[$champ]["total"] += $champ_num;
                $champs_presence_only[$champ] += $champ_num;
            } else {
                $champs_presence[$champ] = array("played"=>0,"banned_against"=>$champ_num,"played_against"=>0,"banned"=>0,"wins"=>0,"total"=>$champ_num);
				$champs_presence_only[$champ] = $champ_num;
            }
        }
        foreach ($champs_played_against as $champ=>$champ_num) {
            if (array_key_exists($champ,$champs_presence)) {
                $champs_presence[$champ]["played_against"] += $champ_num;
                $champs_presence[$champ]["total"] += $champ_num;
				$champs_presence_only[$champ] += $champ_num;
            } else {
                $champs_presence[$champ] = array("played"=>0,"banned_against"=>0,"played_against"=>$champ_num,"banned"=>0,"wins"=>0,"total"=>$champ_num);
				$champs_presence_only[$champ] = $champ_num;
            }
        }
        foreach ($champs_banned as $champ=>$champ_num) {
            if (array_key_exists($champ,$champs_presence)) {
                $champs_presence[$champ]["banned"] += $champ_num;
                $champs_presence[$champ]["total"] += $champ_num;
				$champs_presence_only[$champ] += $champ_num;
            } else {
                $champs_presence[$champ] = array("played"=>0,"banned_against"=>0,"played_against"=>0,"banned"=>$champ_num,"wins"=>0,"total"=>$champ_num);
				$champs_presence_only[$champ] = $champ_num;
            }
        }
        arsort($champs_presence_only);

        echo "<div class='stattables'>";
        echo "<div class='playertable-header'>
		        <h3>Spieler</h3>
                <a class='button pt-expand-all'><div class='material-symbol'>".file_get_contents("icons/material/unfold_more.svg")."</div></a>
                <a class='button pt-collapse-all'><div class='material-symbol'>".file_get_contents("icons/material/unfold_less.svg")."</div></a>
              </div>";
        echo "<div class='table playerstable'>";
		for ($index=0; $index < count($players); $index++) {
            if ($index < count($players_to_show)) {
				$player_to_show = $players_to_show[$index];
				$player = $players_by_name[$player_to_show];
                $dontshow = "";
                $roleclass = " role".$index;
			} else {
				$new_index = $index - count($players_to_show);
				if ($new_index < count($players_not_to_show)) {
					$player_not_to_show = $players_not_to_show[$new_index];
					$player = $players_by_name[$player_not_to_show];
					$dontshow = " hidden-table";
					$roleclass = "";
				} else {
                    break;
				}
			}
			$player_champs = json_decode($player['champions'],true);
			if (count($player_champs) === 0) {
				continue;
			}
			echo "<div class='playertable$dontshow$roleclass'>";
			arsort($player_champs);
			echo "<h4>".$player['SummonerName']."</h4>";
			if (count($player_champs) > 5) {
				echo "<table class='collapsed'>";
			} else {
				echo "<table>";
			}
			echo "
                <tr>
                    <th></th>
                    <th class='sortable sortedby desc'>".populate_th("P","Picks",true)."</th>
                    <th class='sortable'>".populate_th("W","Wins")."</th>
                    <th class='sortable'>".populate_th("W%","Winrate")."</th>
                </tr>";
			foreach ($player_champs as $champ_name => $champ) {
				echo "
                <tr>
                    <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                    <td>".$champ['games']."</td>
                    <td>".$champ['wins']."</td>
                    <td>".round(($champ['wins'] / $champ['games']) * 100, 2)."%</td>
                </tr>";
			}
			if (count($player_champs) > 5) {
				echo "
                <tr class='expand-table'>
                    <td colspan='4'><div class='material-symbol'>".file_get_contents("icons/material/expand_less.svg")."</div></td>
                </tr>";
			}
			echo "</table>";
            echo "</div>";
		}
		echo "</div>"; // div.table.playerstable


        echo "<div class='table-wrapper'>";

        echo create_dropdown("stat-tables",["all"=>"Gesamt-Tabelle","single"=>"Einzel-Tabellen"]);

		echo "<div class='champstattables entire'>";
		echo "<div class='table pickstable'><h3>Championstatistiken</h3><table>";
		echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("P","eigene Picks",true)."</th>
                <th class='sortable'>".populate_th("P(g)","gegnerische Picks")."</th>
                <th class='sortable'>".populate_th("B","eigene Bans")."</th>
                <th class='sortable'>".populate_th("B(g)","gegnerische Bans")."</th>
                <th class='sortable'>".populate_th("W%","eigene Winrate")."</th>
                <th class='sortable'>".populate_th("PB%","Gesamte Pick/Banrate")."</th>
            </tr>";
		foreach ($champs_presence as $champ_name => $champ) {
            if ($champ['played'] === 0) {
                $winrate = "-";
			} else {
				$winrate = round(($champ['wins']/$champ['played'])*100,2)."% (".$champ['wins'].")";
			}
			echo "
            <tr>
                <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ['played']."</td>
                <td>".$champ['played_against']."</td>
                <td>".$champ['banned']."</td>
                <td>".$champ['banned_against']."</td>
                <td>".$winrate."</td>
                <td>".round(($champ['total']/$games_played)*100,2)."% (". $champ['total'].")</td>
            </tr>";
		}
		echo "</table></div>";
		echo "</div>"; //champstattables entire


		echo "<div class='champstattables singles' style='display: none'>";

		echo "<div class='table pickstable'><h3>Eigene Picks</h3><table>";
		echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("P","Picks",true)."</th>
                <th class='sortable'>".populate_th("W%","Winrate")."</th>
            </tr>";
		foreach ($champs_played as $champ_name => $champ) {
			echo "
            <tr>
                <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ['games']."</td>
                <td>".round(($champ['wins']/$champ['games'])*100,2)."% (".$champ['wins'].")</td>
            </tr>";
		}
		echo "</table></div>";
		echo "<div class='divider-vert'></div>";

		echo "<div class='table pickstable'><h3>Gegner Picks</h3><table>";
		echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("P","Picks",true)."</th>
            </tr>";
		foreach ($champs_played_against as $champ_name => $champ_num) {
			echo "
            <tr>
                <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ_num."</td>
            </tr>";
		}
		echo "</table></div>";
		echo "<div class='divider-vert'></div>";

		echo "<div class='table banstable'><h3>Gegner Bans</h3><table>";
		echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("B","Bans",true)."</th>
            </tr>";
		foreach ($champs_banned_against as $champ_name => $champ_num) {
			echo "
            <tr>
                <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ_num."</td>
            </tr>";
		}
		echo "</table></div>";
		echo "<div class='divider-vert'></div>";

		echo "<div class='table banstable'><h3>eigene Bans</h3><table>";
		echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("B","Bans",true)."</th>
            </tr>";
		foreach ($champs_banned as $champ_name => $champ_num) {
			echo "
            <tr>
                <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".$champ_num."</td>
            </tr>";
		}
		echo "</table></div>";

		echo "<div class='divider-vert'></div>";

		echo "<div class='table presencetable'><h3>Champ-Präsenz</h3><table>";
		echo "
            <tr>
                <th></th>
                <th class='sortable sortedby desc'>".populate_th("PB%","Gesamte Pick/Banrate",true)."</th>
            </tr>";
		foreach ($champs_presence_only as $champ_name => $champ_num) {
			echo "
            <tr>
                <td class='champion'><img src='/uniliga/ddragon/$latest_patch/img/champion/$champ_name.webp' alt='$champ_name'></td>
                <td>".round(($champ_num/$games_played)*100,2)."% (".$champ_num.")</td>
            </tr>";
		}
		echo "</table></div>";

		echo "</div>"; // div.champstattables singles
        echo "</div>"; // div.table-wrapper
        echo "</div>"; // div.stattables
	}
    echo "</div>";
	?>
</body>
<?php
}
} catch (Exception $e) {
	echo "<title>Database Connection failed</title></head>Database Connection failed";
}
?>
</html>