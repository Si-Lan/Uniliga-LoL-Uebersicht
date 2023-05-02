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

echo "<br>---- getting all Matchresults from Toornament<br>";
$matches = $dbcn->query("SELECT * FROM matches AS m INNER JOIN `groups` AS g ON g.GroupID = m.GroupID INNER JOIN divisions AS d ON d.DivID = g.DivID WHERE TournamentID = {$tournament_id}")->fetch_all(MYSQLI_ASSOC);
$matchresults_gotten = 0;
$percentage = 0;
foreach ($matches as $mindex=>$match) {
	// alle 5 Abfragen 2 sekunden warten, um nicht zu viele Anfragen gleichzeitig an Toornament zu schicken
	if (($mindex) % 5 === 0 && $mindex != 0) {
		sleep(2);
	}
	$result = scrape_toornament_matches($tournament_id,$match['MatchID'], FALSE);
	$matchresults_gotten += $result["changes"][0];
	$percentage = count_percentages($mindex,count($matches),$percentage);
}
echo "-------- ".$matchresults_gotten." Matchresults written<br>";