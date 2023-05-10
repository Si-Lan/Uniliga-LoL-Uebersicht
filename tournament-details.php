<!DOCTYPE html>
<html lang="">
<head>
<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");

$logged_in = is_logged_in();

create_html_head_elements("tournament",$logged_in);

$tournamentID = $_GET['tournament'] ?? NULL;

$local_img_path = "img/team_logos/";
$toor_tourn_url = "https://play.toornament.com/de/tournaments/";

if (is_light_mode()) {
	$lightmode = " light";
} else {
	$lightmode = "";
}
if (!$logged_in || logged_in_buttons_hidden()) {
    $admin_buttons = FALSE;
    $adminbtnbody = "";
} else {
	$admin_buttons = TRUE;
	$adminbtnbody = " admin_li";
}

try {
    $dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

    if ($dbcn->connect_error) {
        echo "<title>Database Connection failed</title></head>Database Connection failed";
    } else {
	    if ($tournamentID == NULL) {
		    echo "<title>Kein Turnier gefunden | Uniliga LoL - Übersicht</title></head>";
		    echo "
                <body class='tournament$lightmode'>
                    <div class='header' style='margin-bottom: 20px'>
                        <a href='.' class='button'>
                            <div class='material-symbol'>". file_get_contents("icons/material/home.svg") ."</div>
                            Startseite
                        </a>
                    </div>
                    <div style='text-align: center'>
                        Kein Turnier unter der angegebenen ID gefunden!
                    </div>
                </body>";
		    exit;
	    }
        $tournamentDB = $dbcn->execute_query("SELECT * FROM tournaments WHERE TournamentID = ?",[$tournamentID])->fetch_assoc();
        $divisionsDB = $dbcn->execute_query("SELECT * FROM divisions WHERE TournamentID = ? ORDER BY Number",[$tournamentID])->fetch_all(MYSQLI_ASSOC);
        if ($tournamentDB == NULL) {
			echo "<title>Kein Turnier gefunden | Uniliga LoL - Übersicht</title></head>";
            echo "
                <body class='tournament$lightmode'>
                    <div class='header' style='margin-bottom: 20px'>
                        <a href='.' class='button'>
                            <div class='material-symbol'>". file_get_contents("icons/material/home.svg") ."</div>
                            Startseite
                        </a>
                    </div>
                    <div style='text-align: center'>
                        Kein Turnier unter der angegebenen ID gefunden!
                    </div>
                </body>";
            exit;
		}
        echo "<title>Uniliga {$tournamentDB['Split']} 20{$tournamentDB['Season']} | Uniliga LoL - Übersicht</title>";
?>
</head>
<body class="tournament<?php echo $lightmode.$adminbtnbody?>">
        <?php
        create_header($dbcn,"tournament",$tournamentID);
        create_tournament_overview_nav_buttons($dbcn,$tournamentID,"overview");
        echo "<h2 class='pagetitle'>Turnier-Details</h2>";
        echo "<div class='divisions-list-wrapper'>";
        echo "<div class='divisions-list'>";

            foreach ($divisionsDB as $division) {
                echo "<div class='division'>
                        <div class='group-title-wrapper'><h2>Liga {$division['Number']}</h2>";
				if ($logged_in) {
                    echo "<a class='button write games-div {$division['DivID']}' onclick='get_games_for_division(\"$tournamentID\",\"{$division['DivID']}\")'><div class='material-symbol'>". file_get_contents("icons/material/place_item.svg") ."</div>Lade Spiele</a>";
				}
                echo "</div>";
		        if ($logged_in) {
                    echo "<div class='result-wrapper no-res {$division['DivID']} {$tournamentID}'>
                            <div class='clear-button' onclick='clear_results(\"{$division['DivID']}\")'>Clear</div>
                            <div class='result-content'></div>
                          </div>";
		        }
                echo "<div class='divider'></div>";
                $groupsDB = $dbcn->execute_query("SELECT * FROM `groups` WHERE DivID = ? ORDER BY Number",[$division['DivID']])->fetch_all(MYSQLI_ASSOC);
                echo "<div class='groups'>";
                foreach ($groupsDB as $group) {
                    if ($division["format"] === "Swiss") {
                        $group_title = "Swiss-Gruppe";
					} else {
						$group_title = "Gruppe {$group['Number']}";
					}
                    echo "<div>";
                    echo "<div class='group'>
                            <a href='turnier/{$tournamentID}/gruppe/{$group['GroupID']}' class='button'>$group_title</a>
                            <a href='turnier/{$tournamentID}/teams?liga={$division['DivID']}&gruppe={$group['GroupID']}' class='button'><div class='material-symbol'>". file_get_contents("icons/material/group.svg") ."</div>Teams</a>";
                    echo "</div>"; // group
                    if ($logged_in) {
                        echo "<a class='button write games- {$group['GroupID']}' onclick='get_games_for_group(\"$tournamentID\",\"{$group['GroupID']}\")'><div class='material-symbol'>". file_get_contents("icons/material/place_item.svg") ."</div>Lade Spiele</a>";
                    }
                    echo "</div>";
                    if ($logged_in) {
                        echo "
                            <div class='result-wrapper no-res {$group['GroupID']} {$tournamentID}'>
                                <div class='clear-button' onclick='clear_results(\"{$group['GroupID']}\")'>Clear</div>
                                <div class='result-content'></div>
                            </div>";
                    }
				}
                echo "</div></div>"; // groups // division
			}
        echo "</div>";
        echo "</div>";
		if ($logged_in) {
			echo "
            <div class='writing-wrapper'>
                <div class='divider big-space'></div>
                <a class='button write gamedata {$tournamentID}' onclick='get_game_data(\"{$tournamentID}\")'>Lade Spiel-Daten für geladene Spiele</a>
                <a class='button write assign-una {$tournamentID}' onclick='assign_and_filter_games(\"{$tournamentID}\")'>sortiere unsortierte Spiele</a>
                <div class='result-wrapper no-res {$tournamentID}'>
                    <div class='clear-button' onclick='clear_results(\"$tournamentID\",1)'>Clear</div>
                    <div class='result-content'></div>
                </div>
                <div class='spacer'></div>
            </div>";
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