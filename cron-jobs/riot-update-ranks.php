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

echo "<br>---- Ranks for Players <br>";
$players = $dbcn->query("SELECT * FROM players WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$players_updated = 0;
$percentage = 0;
foreach ($players as $pindex=>$player) {
	if ($pindex % 50 === 0 && $pindex != 0) {
		sleep(10);
	}
	$result = get_Rank_by_SummonerId($player['PlayerID']);
	$players_updated += $result["writes"];
	$percentage = count_percentages($pindex,count($players),$percentage);
}
echo "-------- ".$players_updated." Ranks for Players updated<br>";

echo "<br>---- avg Ranks for Teams <br>";
$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$teams_updated = 0;
$percentage = 0;
foreach ($teams as $tindex=>$team) {
	$result = calculate_avg_team_rank($team['TeamID']);
	$teams_updated += $result['writes'];
	$percentage = count_percentages($tindex,count($teams),$percentage);
}
echo "-------- ".$teams_updated." avg Ranks for Teams updated<br>";