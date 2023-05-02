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

echo "<br>---- getting Matches from Toornament<br>";
$groups = $dbcn->query("SELECT * FROM `groups` JOIN divisions d on d.DivID = `groups`.DivID WHERE TournamentID=$tournament_id")->fetch_all(MYSQLI_ASSOC);
$matches_gotten = array("writes"=>0,"updates"=>0);
$percentage = 0;
foreach ($groups as $gindex=>$group) {
	// alle 5 Abfragen 2 sekunden warten, um nicht zu viele Anfragen gleichzeitig an Toornament zu schicken
	if (($gindex) % 5 === 0 && $gindex != 0) {
		sleep(2);
	}
	$format = $group['format'];
	if ($format == "Groups") {
		$result = scrape_toornament_matches_from_group($tournament_id,$group['DivID'],$group['GroupID']);
		$matches_gotten["writes"] += $result["writes"];
		$matches_gotten["updates"] += $result["changes"][0];
	} elseif ($format == "Swiss") {
		$result = scrape_toornament_matches_from_swiss($tournament_id,$group['DivID'],$group['GroupID']);
		$matches_gotten["writes"] += $result["writes"];
		$matches_gotten["updates"] += $result["changes"][0];
	}
	$percentage = count_percentages($gindex,count($groups),$percentage);
}
echo "-------- ".$matches_gotten["writes"]." Matches written<br>";
echo "-------- ".$matches_gotten["updates"]." Matches updated<br>";