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

echo "<br>---- get Gamedata for Games without Data <br>";
$games = $dbcn->query("SELECT * FROM games WHERE TournamentID = $tournament_id AND MatchData IS NULL")->fetch_all(MYSQLI_ASSOC);
$gamedata_gotten = 0;
$percentage = 0;
foreach ($games as $gindex=>$game) {
	if (($gindex) % 50 === 0 && $gindex != 0) {
		sleep(10);
	}
	$result = add_match_data($game["RiotMatchID"],$tournament_id);
	$gamedata_gotten += $result["writes"];
	$percentage = count_percentages($gindex,count($games),$percentage);
}
echo "-------- Gamedata for ".$gamedata_gotten." Games written<br>";