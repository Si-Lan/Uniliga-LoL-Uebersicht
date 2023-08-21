<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../fe-functions.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	if (isset($_GET['search'])) {
		$search = $_GET['search'];
		create_player_overview_cards_from_search($dbcn,$search);
	} elseif (isset($_SERVER['HTTP_DATA_PUUIDS'])) {
		$puuids = json_decode($_SERVER['HTTP_DATA_PUUIDS']);
		create_player_overview_cards($dbcn,$puuids);
	}
}