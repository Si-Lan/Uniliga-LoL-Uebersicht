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
if (isset($_GET['t'])) {
	$tournament_id = $_GET['t'];
	$time = date('Y-m-d H:i:s');
	$lastupdate = $dbcn->execute_query("SELECT last_update FROM cron_updates WHERE TournamentID = ?", [$tournament_id])->fetch_column();
	if ($lastupdate == NULL) {
		$dbcn->execute_query("INSERT INTO cron_updates VALUES (?, '$time')", [$tournament_id]);
	} else {
		$dbcn->execute_query("UPDATE cron_updates SET last_update = '$time' WHERE TournamentID = ?", [$tournament_id]);
	}
}