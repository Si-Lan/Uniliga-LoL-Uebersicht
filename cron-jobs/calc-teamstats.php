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

echo "<br>---- calculate Teamstats <br>";
$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID = $tournament_id")->fetch_all(MYSQLI_ASSOC);
$teamstats_gotten = array("writes"=>0,"updates"=>0,"not"=>0);
$percentage = 0;
foreach ($teams as $tindex=>$team) {
    $result = calculate_teamstats($dbcn,$team["TeamID"]);
    $teamstats_gotten["writes"] += $result["writes"];
    $teamstats_gotten["updates"] += $result["updates"];
    $teamstats_gotten["not"] += $result["without"];
    $percentage = count_percentages($tindex,count($teams),$percentage);
}
echo "-------- written Stats for ".$teamstats_gotten["writes"]." Teams<br>";
echo "-------- updated Stats for ".$teamstats_gotten["updates"]." Teams<br>";
echo "-------- no Games played for ".$teamstats_gotten["not"]." Teams<br>";