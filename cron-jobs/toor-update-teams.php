<?php
include("functions.php");
include("../admin/scrapeToornament.php");

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

echo "<br>---- getting Teams from Toornament<br>";
$result = scrape_toornament_teams($tournament_id);
echo "[**********]<br>";
echo "-------- ".$result["writes"]." Teams written<br>";
echo "-------- ".$result["updates"]." Teams updated<br>";