<?php
$dbservername = "";
$dbdatabase = "";
$dbusername = "";
$dbpassword = "";
$dbport = NULL;
include('../DB-info.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "Database Connection failed";
	exit;
}
if (isset($_GET['r']) && $_GET['r'] === '1') {
	echo "running general Update: <br>";
	$start_time = time();
	echo "Calltime: ".date("d.m.Y H:i:s",$start_time)."<br>";

	echo "starting Script<br>";
	echo "<br>checking Tournaments<br>";
	$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE finished = false")->fetch_all(MYSQLI_ASSOC);
	$tournaments_num = count($tournaments);
	echo "---- $tournaments_num ongoing tournament found <br>";
}
if ((isset($_GET['r']) && $_GET['r'] === '2') && isset($_GET['t']) && isset($_GET['tn'])) {
	$tournament_id = $_GET['t'];
	$tn = $_GET['tn'];
	$tournament = $dbcn->query("SELECT * FROM tournaments WHERE TournamentID = '$tournament_id'")->fetch_assoc();
	echo "<br>Tournament " . ($tn+1) . ": {$tournament['Name']} <br>";
	$date_today = date("Y-m-d");
	$last_run = FALSE;
	if ($date_today > $tournament['DateEnd']) {
		echo "---- Tournament is over, running for the last time <br>";
		$last_run = TRUE;
		$dbcn->query("UPDATE tournaments SET finished = true WHERE TournamentID = $tournament_id");
	}
}
