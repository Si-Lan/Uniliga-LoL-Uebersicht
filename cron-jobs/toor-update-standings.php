<?php
include("functions.php");
include("../admin/scrapeToornament.php");

$dbservername = "";
$dbdatabase = "";
$dbusername = "";
$dbpassword = "";
$dbport = NULL;
include('../DB-info.php');

$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

if ($dbcn->connect_error) {
	echo "Database Connection failed";
	exit;
}
if (!(isset($_GET['t']))) {
	exit;
}
$tournament_id = $_GET['t'];

echo "<br>---- getting Standings from Toornament<br>";
$divisions = $dbcn->query("SELECT DivID, `format` FROM divisions WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$standings_gotten = array("new"=>0,"updated"=>0);
$divisions_regular = [];
$divisions_swiss = [];
for ($i = 0; $i < count($divisions); $i++) {
	if ($divisions[$i]['format'] == "Groups") {
		$divisions_regular[] = $divisions[$i]['DivID'];
	} else if ($divisions[$i]['format'] == "Swiss") {
		$divisions_swiss[] = $divisions[$i]['DivID'];
	}
}
echo "-------- ".count($divisions_regular)." regular group divisions<br>";
echo "-------- ".count($divisions_swiss)." swiss divisions<br>";
for ($i = 0; $i < count($divisions_regular); $i++) {
	$groups = $dbcn->query("SELECT GroupID FROM `groups` WHERE DivID = {$divisions_regular[$i]}")->fetch_all(MYSQLI_ASSOC);
	for ($j = 0; $j < count($groups); $j++) {
		// alle 5 Abfragen 2 sekunden warten, um nicht zu viele Anfragen gleichzeitig an Toornament zu schicken
		if (($j) % 5 === 0 && $j != 0) {
			sleep(2);
		}
		$result = scrape_toornaments_teams_in_groups($tournament_id, $divisions_regular[$i], $groups[$j]['GroupID']);
		$standings_gotten["new"] += $result["writes"];
		$standings_gotten["updated"] += $result["updates"];
	}
}
for ($i = 0; $i < count($divisions_swiss); $i++) {
	$groups = $dbcn->query("SELECT GroupID FROM `groups` WHERE DivID = {$divisions_swiss[$i]}")->fetch_all(MYSQLI_ASSOC);
	for ($j = 0; $j < count($groups); $j++) {
		// alle 5 Abfragen 2 sekunden warten, um nicht zu viele Anfragen gleichzeitig an Toornament zu schicken
		if (($j) % 5 === 0 && $j != 0) {
			sleep(2);
		}
		$result = scrape_toornaments_teams_in_groups_swiss($tournament_id, $divisions_swiss[$i], $groups[$j]['GroupID']);
		$standings_gotten["new"] += $result["writes"];
		$standings_gotten["updated"] += $result["updates"];
	}
}
echo "[**********]<br>";
echo "-------- Standings for ".$standings_gotten["new"]." new Teams written<br>";
echo "-------- Standings for ".$standings_gotten["updated"]." Teams updated<br>";