<?php
include_once('get-RGAPI-data.php');
$type = $_REQUEST["type"];

if ($type == "puuids-by-team") {
	$teamID = $_REQUEST['team'];

	if (isset($_REQUEST["all"])) {
		$results = get_puuids_by_team($teamID,TRUE);
	} else {
		$results = get_puuids_by_team($teamID);
	}

	echo json_encode($results, JSON_UNESCAPED_SLASHES);
}

if ($type == "games-by-player") {
	$playerID = $_REQUEST['player'];

	$results = get_games_by_player($playerID);

	$returnArr = $results["echo"];
	echo $returnArr;
}

if ($type == "add-match-data") {
	$matchID = $_REQUEST['match'];
    $tournamentID = $_REQUEST['tournament'];

	$results = add_match_data($matchID, $tournamentID);

	$returnArr = $results["echo"];
	echo $returnArr;
}

if ($type == "assign-and-filter") {
	$matchID = $_REQUEST['match'];
    $tournamentID = $_REQUEST['tournament'];

	$results = assign_and_filter_game($matchID, $tournamentID);

	$returnArr = $results["echo"];
	echo $returnArr;
}

if ($type == "get-rank-for-player") {
	$id = $_REQUEST['player'];

	$results = get_Rank_by_SummonerId($id);

	$returnArr = $results['echo'];
	echo $returnArr;
}