<!DOCTYPE html>
<html lang="de">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");

create_html_head_elements("teams-list");

$local_img_path = "img/team_logos/";
$toor_tourn_url = "https://play.toornament.com/de/tournaments/";


$tournID = $_GET['tournament'];

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
		$tournamentRes = $dbcn->execute_query("SELECT name, split, season, imgID, TournamentID FROM tournaments where TournamentID = ?",[$tournID])->fetch_assoc();

		$divRes = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournID])->fetch_all(MYSQLI_ASSOC);
		$divs = [];
		$groups = [];
		foreach ($divRes as $i=>$div) {
			$divs[$div['DivID']] = $div['Number'];
			$groupRes = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ?",[$div['DivID']])->fetch_all(MYSQLI_ASSOC);
			foreach ($groupRes as $j=>$group) {
				$groups[$group['GroupID']] = $group['Number'];
			}
		}

        echo "<title>Team-Liste - Uniliga {$tournamentRes["split"]} 20{$tournamentRes["season"]} | Uniliga LoL - Übersicht</title>";
        ?>
</head>
<?php
		echo "<body class='teamlist$lightmode'>";
		create_header($dbcn,"tournament",$tournID);
        create_tournament_overview_nav_buttons($dbcn,$tournID,"list");
		echo "<h2 class='pagetitle'>Team-Liste</h2>";
        echo "<div class='search-wrapper'>
                <span class='searchbar'>
                    <input class=\"search-teams $tournID deletable-search\" onkeyup='search_teams(\"$tournID\")' placeholder=\"Teams durchsuchen\" type=\"text\">
                    <a class='material-symbol' href='#' onclick='clear_searchbar()'>". file_get_contents("icons/material/close.svg") ."</a>
                </span>
              </div>";

        if (isset($_GET["liga"])) {
            $filteredDivID = $_GET["liga"];
            $divallClass = "";
        } else {
            $divallClass = "selected='selected'";
        }
	    $toGroupButtonClass = "";
	    $toGroupButtonLink = "";
	    if (isset($filteredDivID) && isset($_GET["gruppe"])) {
	    	$filteredGroupID = $_GET["gruppe"];
		    $groupallClass = "";
		    $toGroupButtonClass = " shown";
		    $toGroupButtonLink = " href='turnier/".$tournID."/gruppe/".$filteredGroupID."'";
	    } else {
			$groupallClass = "selected='selected'";
		}
        echo "<div class='team-filter-wrap'><h3>Filter:</h3>";
        echo "<div class='slct div-select-wrap'>
                <select name='Ligen' class='divisions' onchange='filter_teams_list_division(this.value)'>
                    <option value='all' $divallClass>Alle Ligen</option>";
        foreach ($divRes as $div) {
            if (isset($filteredDivID) && $filteredDivID == $div['DivID']) {
                $divClass = " selected='selected'";
            } else {
                $divClass = "";
            }
            echo "<option value='".$div["DivID"]."'$divClass>Liga ".$div["Number"]."</option>";
        }
        echo "
                </select>
                <span class='material-symbol'>".file_get_contents("icons/material/arrow_drop_down.svg")."</span>
              </div>
                <div class='slct groups-select-wrap'>
                    <select name='Gruppen' class='groups' onchange='filter_teams_list_group(this.value)'>
                        <option value='all' $groupallClass>Alle Gruppen</option>";
        if (isset($filteredDivID)) {
            $groups_filteredDiv = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ? ORDER BY Number",[$filteredDivID])->fetch_all(MYSQLI_ASSOC);
            foreach ($groups_filteredDiv as $group) {
                if (isset($filteredGroupID) && $filteredGroupID == $group['GroupID']) {
                    $groupClass = " selected='selected'";
                } else {
                    $groupClass = "";
                }
                echo "<option value='".$group["GroupID"]."'$groupClass>Gruppe ".$group["Number"]."</option>";
            }
        }
        echo "
                    </select>
                    <span class='material-symbol'>".file_get_contents("icons/material/arrow_drop_down.svg")."</span>
                </div>";
		echo "<a class='button b-group$toGroupButtonClass'$toGroupButtonLink>zur Gruppe</a>";
		echo "</div>";

        echo "
            <div class='team-popup-bg' onclick='close_popup_team(event)'>
                <div class='team-popup'></div>
            </div>";
        echo "<div class='team-list $tournID'>";
        echo "<div class='no-search-res-text $tournID' style='display: none'>Kein Team gefunden!</div>";
        $teams = $dbcn->execute_query("SELECT T.TeamName, T.imgID, T.TeamID, TG.GroupID, G.DivID, T.org, T.avg_rank_tier, T.avg_rank_div FROM teams T LEFT JOIN teamsingroup TG ON TG.TeamID = T.TeamID LEFT JOIN `groups` G ON G.GroupID = TG.GroupID WHERE TournamentID = ? ORDER BY TeamName",[$tournID])->fetch_all();
        for ($i_teams = 0; $i_teams < count($teams); $i_teams++) {
            $currTeam = $teams[$i_teams][0];
            $currTeamID = $teams[$i_teams][2];
            $currTeamGroupID = $teams[$i_teams][3];
            $currTeamDivID = $teams[$i_teams][4];
            $currTeamImgID = $teams[$i_teams][1];
            $teaminfolink1 = "https://play.toornament.com/de/tournaments/";
            $teaminfolink2 = "/participants/";
            $teaminfolink3 = "/info";
            $opgglink = "https://www.op.gg/multisearch/euw?summoners=";

            $players = $dbcn->execute_query("SELECT SummonerName FROM players WHERE TournamentID = ? AND TeamID = ?",[$tournID,$currTeamID])->fetch_all();
            for ($i_opgg = 0; $i_opgg < count($players); $i_opgg++) {
                if ($i_opgg != 0) {
                    $opgglink .= urlencode(",");
                }
                $opgglink .= urlencode($players[$i_opgg][0]);
            }

            $team_rank = "";
            if (array_key_exists($currTeamDivID, $divs)) {
                $team_rank .= "Liga {$divs[$currTeamDivID]}";
                if (array_key_exists($currTeamGroupID, $groups)) {
                    if ($groups[$currTeamGroupID] == 0) {
						$team_rank .= "";
					} else {
						$team_rank .= " Gruppe {$groups[$currTeamGroupID]}";
					}
                }
            }

            if ($currTeamImgID == NULL || !file_exists("$local_img_path{$teams[$i_teams][1]}/logo_small.webp")) {
				$currTeamImgID = "";
                $img_url = "";
            } else {
                $img_url = $local_img_path . $teams[$i_teams][1] . "/logo_small.webp";
            }

            if (isset($filteredDivID) && $filteredDivID != $currTeamDivID) {
                $filterDClass = " filterD-off";
			} else {
				$filterDClass = "";
			}
			if (isset($filteredGroupID) && $filteredGroupID != $currTeamGroupID) {
				$filterGClass = " filterG-off";
			} else {
				$filterGClass = "";
			}

			echo "
                <div class=\"team-button $tournID $currTeamID $currTeamGroupID $currTeamDivID$filterDClass$filterGClass\" onclick='popup_team(\"$currTeamID\")'>
                    <div class='team-name'>";
            if ($img_url != NULL) {
                echo "
                        <img alt src='$img_url'>";
            }
            echo "
                        <span>$currTeam</span>
                    </div>
                    <div class='team-group'>
                        $team_rank
                    </div>
                </div>";
        }
        echo "</div>"; //Team-List
	echo "</body>";
    }
} catch (Exception $e) {
	echo "<title>Error</title></head>";
}
?>
</html>