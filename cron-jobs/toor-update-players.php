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

echo "<br>---- getting Players from Toornament <br>";
$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$players_gotten = array("new"=>0,"NC"=>0,"SNC"=>0,"nT"=>0);
$percentage = 0;
foreach ($teams as $tindex=>$team) {
	// alle 5 Abfragen 2 sekunden warten, um nicht zu viele Anfragen gleichzeitig an Toornament zu schicken
	if (($tindex) % 5 === 0 && $tindex != 0) {
		sleep(2);
	}
	$result = scrape_toornaments_players($tournament_id,$team['TeamID'],FALSE);
	$players_gotten["new"] += $result["writes"];
	$players_gotten["NC"] += $result["NameUpdate"];
	$players_gotten["SNC"] += $result["SNameUpdate"];
	$players_gotten["nT"] += $result["notInToor"];
	$percentage = count_percentages($tindex,count($teams),$percentage);
}
echo "[**********]<br>";
echo "-------- ".$players_gotten["new"]." new Players written<br>";
echo "-------- ".$players_gotten["NC"]." changed Playernames updated<br>";
echo "-------- ".$players_gotten["SNC"]." changed Summonernames updated<br>";
echo "-------- ".$players_gotten["nT"]." Players not in Toornament anymore<br>";