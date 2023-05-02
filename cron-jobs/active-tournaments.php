<?php
$dbservername = "";
$dbdatabase = "";
$dbusername = "";
$dbpassword = "";
$dbport = NULL;
include('../DB-info.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE finished = false")->fetch_all(MYSQLI_ASSOC);
$tids = [];
foreach ($tournaments as $tournament) {
	$tids[] = $tournament['TournamentID'];
}
echo json_encode($tids);
