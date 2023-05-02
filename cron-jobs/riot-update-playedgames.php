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

echo "<br>---- all played Custom-Games from Players <br>";
$players = $dbcn->query("SELECT * FROM players p JOIN teams t on p.TeamID = t.TeamID WHERE p.TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$games_gotten = array("already"=>0,"new"=>0);
$percentage = 0;
foreach ($players as $pindex=>$player) {
	if (($pindex) % 50 === 0 && $pindex != 0) {
		sleep(10);
	}
	$results = get_games_by_player($player['PlayerID']);
	$games_gotten["new"] += $results["writes"];
	$games_gotten["already"] += $results["already"];
	$percentage = count_percentages($pindex,count($players),$percentage);
}
echo "-------- ".$games_gotten["new"]." new Games written<br>";
echo "-------- ".$games_gotten["already"]." found Games already in Database<br>";