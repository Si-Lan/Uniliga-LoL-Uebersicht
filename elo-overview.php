<!DOCTYPE html>
<html lang="de">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");

create_html_head_elements("elo");

$tournamentID = $_GET['tournament'] ?? NULL;

$pageurl = $_SERVER['REQUEST_URI'];

$toor_tourn_url = "https://play.toornament.com/de/tournaments/";
$opgg_url = "https://www.op.gg/multisearch/euw?summoners=";

try {
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

	if ($dbcn->connect_error) {
        echo "<title>Database Connection failed</title></head>Database Connection failed";
	} else {
        $tournamentDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE TournamentID = ?",[$tournamentID])->fetch_assoc();
		$divisionsDB = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
        $groups_by_div = [];
        foreach ($divisionsDB as $division) {
            $groups_by_div[$division['DivID']] = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ?",[$division['DivID']])->fetch_all(MYSQLI_ASSOC);
		}
        $teams = $dbcn->execute_query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID = ? ORDER BY avg_rank_num DESC",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
		echo "<title>Elo-Übersicht - Uniliga {$tournamentDB['Split']} 20{$tournamentDB['Season']} | Uniliga LoL - Übersicht</title>";
        ?>
</head>
    <?php
    if (is_light_mode()) {
	    $lightmode = " light";
    } else {
	    $lightmode = "";
    }
?>
<body class="elo-overview<?php echo $lightmode?>">
        <?php
		create_header($dbcn,"tournament",$tournamentID);
        create_tournament_overview_nav_buttons($dbcn,$tournamentID,"elo");
		echo "<h2 class='pagetitle'>Elo/Rang-Übersicht</h2>";
		echo "<div class='search-wrapper'>
                <span class='searchbar'>
                    <input class=\"search-teams-elo $tournamentID deletable-search\" oninput='search_teams_elo()' placeholder='Team suchen' type='text'>
                    <a class='material-symbol' href='#' onclick='clear_searchbar()'>". file_get_contents("icons/material/close.svg") ."</a>
                </span>
              </div>";
        $filtered = $_REQUEST['view'] ?? NULL;
        $active_all = "";
        $active_div = "";
        $active_group = "";
        if ($filtered === "liga") {
            $active_div = " active";
            $color_by = "Rang";
		} elseif ($filtered === "gruppe") {
			$active_group = " active";
			$color_by = "Rang";
		} else {
			$active_all = " active";
			$color_by = "Liga";
		}
        echo "
            <div class='filter-button-wrapper'>
                <a class='button filterb all-teams$active_all' onclick='switch_elo_view(\"{$tournamentID}\",\"all-teams\")' href='$pageurl'>Alle Ligen</a>
                <a class='button filterb div-teams$active_div' onclick='switch_elo_view(\"{$tournamentID}\",\"div-teams\")' href='$pageurl'>Pro Liga</a>
                <a class='button filterb group-teams$active_group' onclick='switch_elo_view(\"{$tournamentID}\",\"group-teams\")' href='$pageurl'>Pro Gruppe</a>
            </div>";
		if (isset($_GET['colored'])) {
			echo "
            <div class='settings-button-wrapper'>
                <a class='button' onclick='color_elo_list()' href='$pageurl'><input type='checkbox' name='coloring' checked class='color-checkbox'><span>Nach $color_by einfärben</span></a>
            </div>";
			$color = " colored-list";
		} else {
			echo "
            <div class='settings-button-wrapper'>
                <a class='button' onclick='color_elo_list()' href='$pageurl'><input type='checkbox' name='coloring' class='color-checkbox'><span>Nach $color_by einfärben</span></a>
            </div>";
			$color = "";
		}
		if ($filtered == "liga" || $filtered == "gruppe") {
            $jbutton_hide = "";
		} else {
			$jbutton_hide = " style=\"display: none;\"";
		}
        echo "
            <div class='jump-button-wrapper'$jbutton_hide>";
        foreach ($divisionsDB as $division) {
            $div_num = $division['Number'];
            echo "<a class='button' onclick='jump_to_league_elo(\"{$division['Number']}\")' href='$pageurl'>Zu Liga {$division['Number']}</a>";
		}
        echo "
            </div>";
        echo "
            <div class='team-popup-bg' onclick='close_popup_team(event)'>
                            <div class='team-popup'></div>
            </div>";
        echo "
            <div class='main-content$color'>";
		if ($filtered == "liga") {
			foreach ($divisionsDB as $division) {
				$teams_of_div = $dbcn->execute_query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID = ? AND d.DivID = ? ORDER BY avg_rank_num DESC",[$tournamentID,$division['DivID']])->fetch_all(MYSQLI_ASSOC);
				generate_elo_list($dbcn,"div",$teams_of_div,$tournamentID,$division,NULL);
			}
		} elseif ($filtered == "gruppe") {
			foreach ($divisionsDB as $division) {
				$groups_of_div = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ? ORDER BY Number",[$division['DivID']])->fetch_all(MYSQLI_ASSOC);
				foreach ($groups_of_div as $group) {
					$teams_of_group = $dbcn->execute_query("SELECT teams.*, g.GroupID AS GroupID, d.DivID AS DivID, g.Number AS Group_Num, d.Number as Div_Num FROM teams JOIN teamsingroup on teams.TeamID = teamsingroup.TeamID JOIN `groups` g on g.GroupID = teamsingroup.GroupID JOIN divisions d on g.DivID = d.DivID WHERE d.TournamentID = ? AND g.GroupID = ? ORDER BY avg_rank_num DESC",[$tournamentID,$group['GroupID']])->fetch_all(MYSQLI_ASSOC);
					generate_elo_list($dbcn,"group",$teams_of_group,$tournamentID,$division,$group);
				}
			}
		} else {
			generate_elo_list($dbcn,"all",$teams,$tournamentID,NULL,NULL);
		}
		echo "
            </div>"; // main-content
        echo "<a class='button totop' onclick='to_top()' style='opacity: 0; pointer-events: none;'><div class='material-symbol'>". file_get_contents("icons/material/expand_less.svg") ."</div></a>";
        ?>
</body>
    <?php
	}
} catch (Exception $e) {
	echo "<title>Error</title></head>";
}
?>
</html>
