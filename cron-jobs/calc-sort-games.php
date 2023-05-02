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

echo "<br>---- sort Games to Tournament-Matches <br>";
$games = $dbcn->query("SELECT * FROM games WHERE TournamentID = $tournament_id AND MatchData IS NULL")->fetch_all(MYSQLI_ASSOC);
$games_sorted = array("not"=>0,"is"=>0,"sorted"=>0,"nsorted"=>0);
$percentage = 0;
foreach ($games as $gindex=>$game) {
	$result = assign_and_filter_game($game["RiotMatchID"],$tournament_id);
	$games_sorted["not"] += $result["notUL"];
	$games_sorted["is"] += $result["isUL"];
	$games_sorted["sorted"] += $result["sorted"];
	$games_sorted["nsorted"] += $result["notsorted"];
	$percentage = count_percentages($gindex,count($games),$percentage);
}
echo "-------- ".$games_sorted["not"]." Games not from Tournament<br>";
echo "-------- ".$games_sorted["is"]." Games from the Tournament<br>";
echo "-------- ".$games_sorted["sorted"]." Games matched with Tournament-Games<br>";
echo "-------- ".$games_sorted["nsorted"]." Games found no match<br>";