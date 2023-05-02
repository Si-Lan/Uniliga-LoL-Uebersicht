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

echo "<br>---- PUUIDs and SummonerIDs for Players <br>";
$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$current_players_gotten = 0;
$ids_written = array("p"=>0,"s"=>0,"4"=>0);
$percentage = 0;
foreach ($teams as $tindex=>$team) {
	$team_id = $team['TeamID'];
	$players_from_team = $dbcn->query("SELECT * FROM players WHERE TeamID = {$team_id} AND (PUUID IS NULL OR SummonerID IS NULL)")->fetch_all(MYSQLI_ASSOC);
	if ($current_players_gotten + count($players_from_team) > 50) {
		$current_players_gotten = 0;
		sleep(10);
	}
	$current_players_gotten += count($players_from_team);
	$results = get_puuids_by_team($team_id);
	$ids_written["p"] += $results["writesP"];
	$ids_written["s"] += $results["writesS"];
	$ids_written["4"] += $results["404"];
	$percentage = count_percentages($tindex,count($teams),$percentage);
}
echo "-------- ".$ids_written["p"]." PUUIDS written<br>";
echo "-------- ".$ids_written["s"]." SummonerIDs written<br>";
echo "-------- ".$ids_written["4"]." Summoners not found<br>";