<?php
include("functions.php");
include("../admin/riot-api-access/get-RGAPI-data.php");

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

echo "<br>---- calculate Champions of Players <br>";
$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$percentage = 0;
$players_updated = 0;
foreach ($teams as $tindex=>$team) {
	$result = get_played_champions_for_players($team['TeamID']);
	$players_updated += $result['writes'];
	$percentage = count_percentages($tindex,count($teams),$percentage);
}
echo "-------- Champions für ".$players_updated." Spieler aktualisiert<br>";