<?php
include_once('scrapeToornament.php');
$type = $_REQUEST["type"];

$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('../DB-info.php');

error_reporting(0);

if ($type == "tournaments") {
    $tournID = $_REQUEST["id"];
    if(strlen($tournID) == 0){
        echo "";
    } else {
        echo "<div class='turnier-get-result-content'>";
        echo "<div class='clear-button' onclick=\"clear_tourn_res_info()\">clear</div>";
        scrape_toornament_tourn_inf($tournID);
        echo "</div>";
    }
}

if ($type == "teams") {
    $tournID = $_REQUEST['id'];
	$results = scrape_toornament_teams($tournID);
	$results["echo"] = "<div class='all-get-result-content'>
						<div class='clear-button' onclick=\"clear_all_res_info('$tournID')\">clear</div>".$results["echo"];
	$results["echo"] .= "</div>";
	echo $results["echo"];
}

if ($type == "players") {
    $tournID = $_REQUEST['id'];
    $teamID = $_REQUEST['teamid'];
    $returnArr = ["", 0];

    $scrape_result = scrape_toornaments_players($tournID,$teamID);

    $returnArr[0] = $scrape_result["echo"];
    $returnArr[1] = $scrape_result["writes"];

    echo json_encode($returnArr, JSON_UNESCAPED_SLASHES);
}

if ($type == "divisions") {
    $tournID = $_REQUEST['id'];
    echo "<div class='all-get-result-content'>";
    echo "<div class='clear-button' onclick=\"clear_all_res_info('$tournID')\">clear</div>";
    scrape_toornament_divs($tournID);
    echo "</div>";
}

if ($type == "groups") {
    $tournID = $_REQUEST['id'];
	$delete = FALSE;
	if (isset($_REQUEST["delete"])) {
		$delete = TRUE;
	}
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
    if ($dbcn -> connect_error){
        echo "<span style='color: orangered'>Database Connection failed</span>";
    } else {
        $divs = $dbcn->query("SELECT DivID FROM divisions WHERE TournamentID = {$tournID}")->fetch_all();
        $divsRes = [];
        for ($i = 0; $i < count($divs); $i++) {
            $divsRes[] = $divs[$i][0];
        }
        echo "<div class='all-get-result-content'>";
        echo "<div class='clear-button' onclick=\"clear_all_res_info('$tournID')\">clear</div>";
        for ($i = 0; $i < count($divsRes); $i++) {
            scrape_toornament_groups($tournID, $divsRes[$i], $delete);
        }
        echo "</div>";
    }
}

if ($type == "teams-in-groups") {
    $tournID = $_REQUEST['id'];
	$delete = FALSE;
	if (isset($_REQUEST["delete"])) {
		$delete = TRUE;
	}
    $dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
    if ($dbcn -> connect_error){
        echo "<span style='color: orangered'>Database Connection failed</span>";
    } else {
        $divs = $dbcn->query("SELECT DivID, `format` FROM divisions WHERE TournamentID = {$tournID}")->fetch_all();
        $divsRes = [];
        $divsSwissRes = [];
        for ($i = 0; $i < count($divs); $i++) {
            if ($divs[$i][1] == "Groups") {
                $divsRes[] = $divs[$i][0];
            } else if ($divs[$i][1] == "Swiss") {
                $divsSwissRes[] = $divs[$i][0];
            }
        }
        echo "<div class='all-get-result-content'>";
        echo "<div class='clear-button' onclick=\"clear_all_res_info('$tournID')\">clear</div>";
        for ($i = 0; $i < count($divsRes); $i++) {
            $groups = $dbcn->query("SELECT GroupID FROM `groups` WHERE DivID = {$divsRes[$i]}")->fetch_all();
            $groupsRes = [];
            for ($j = 0; $j < count($groups); $j++) {
                $groupsRes[] = $groups[$j][0];
            }
            for ($j = 0; $j < count($groupsRes); $j++) {
                $scrape_result = scrape_toornaments_teams_in_groups($tournID, $divsRes[$i], $groupsRes[$j], FALSE, $delete);
                echo $scrape_result["echo"];
            }
        }
        for ($i = 0; $i < count($divsSwissRes); $i++) {
            $groups = $dbcn->query("SELECT GroupID FROM `groups` WHERE DivID = {$divsSwissRes[$i]}")->fetch_all();
            $groupsRes = [];
            for ($j = 0; $j < count($groups); $j++) {
                $groupsRes[] = $groups[$j][0];
            }
            for ($j = 0; $j < count($groupsRes); $j++) {
                $scrape_result = scrape_toornaments_teams_in_groups_swiss($tournID, $divsSwissRes[$i], $groupsRes[$j]);
                echo $scrape_result["echo"];
            }
        }
        echo "</div>";
    }
}

if ($type == "matches-from-group") {
    $tournID = $_GET["Tid"];
    $divID = $_GET["Did"];
    $groupID = $_GET["Gid"];
    $returnArr = ["", 0, [0,[]]];

	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr[0] = "<span style='color: orangered'>Database Connection failed</span>";
		echo json_encode($returnArr, JSON_UNESCAPED_SLASHES);
	} else {
		$format = $dbcn->query("SELECT format FROM divisions WHERE DivID = $divID")->fetch_assoc()['format'];

		if ($format == "Groups") {
			$scrape_result = scrape_toornament_matches_from_group($tournID, $divID, $groupID);
		} elseif ($format == "Swiss") {
			$scrape_result = scrape_toornament_matches_from_swiss($tournID,$divID,$groupID);
		} else {
			$scrape_result = array("echo"=>"Group has wrong format", "writes"=>0, "changes"=>[0,[]]);
		}

		$returnArr[0] = $scrape_result["echo"];
		$returnArr[1] = $scrape_result["writes"];
		$returnArr[2] = $scrape_result["changes"];

		echo json_encode($returnArr, JSON_UNESCAPED_SLASHES);
	}
}

if ($type == "matches") {
    $tournID = $_GET["Tid"];
    $matchID = $_GET["Mid"];
    $returnArr = ["", [0,[]]];
    $scrape_result = scrape_toornament_matches($tournID,$matchID);

    $returnArr[0] = $scrape_result["echo"];
    $returnArr[1] = $scrape_result["changes"];

    echo json_encode($returnArr, JSON_UNESCAPED_SLASHES);
}
if ($type == "playoffs-match-details") {
	$tournID = $_GET["Tid"];
	$matchID = $_GET["Mid"];
	$returnArr = ["", [0,[]]];
	$scrape_result = scrape_toornament_matches($tournID,$matchID,TRUE);

	$returnArr[0] = $scrape_result["echo"];
	$returnArr[1] = $scrape_result["changes"];

	echo json_encode($returnArr, JSON_UNESCAPED_SLASHES);
}

if ($type == "playoffs") {
	$tournID = $_GET["Tid"];
	$scrape_result = scrape_toornament_playoffs($tournID);

	//echo json_encode($scrape_result, JSON_UNESCAPED_SLASHES);
}

if ($type == "playoff-matchups") {
	$tournID = $_GET["Tid"];
	$playoffID = $_GET["Pid"];
	$scrape_result = scrape_toornament_matchups_from_playoffs($tournID,$playoffID);

	$returnArr = ["", 0, [0,[]]];
	$returnArr[0] = $scrape_result["echo"];
	$returnArr[1] = $scrape_result["writes"];
	$returnArr[2] = $scrape_result["changes"];

	echo json_encode($returnArr, JSON_UNESCAPED_SLASHES);
}


// update timers
if ($type == "update-timers") {
	$tournID = $_GET["Tid"];
	$table = $_GET["table"];
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	$lastupdate = $dbcn->execute_query("SELECT * FROM manual_updates WHERE TournamentID = ?", [$tournID])->fetch_assoc();
	$t = date('Y-m-d H:i:s');

	$allowed = ['teams', 'players', 'divisions', 'groups', 'standings','matches','matchresults'];
	if (!in_array($table, $allowed)) {
		exit();
	}

	if ($lastupdate == NULL) {
		/** @noinspection SqlInsertValues */
		$dbcn->execute_query("INSERT INTO manual_updates (TournamentID, $table) VALUES (?, '$t')", [$tournID]);
	} else {
		$dbcn->execute_query("UPDATE manual_updates SET $table = '$t' WHERE TournamentID = ?", [$tournID]);
	}
}
?>