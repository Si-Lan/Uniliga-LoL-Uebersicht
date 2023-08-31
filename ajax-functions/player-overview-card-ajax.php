<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../fe-functions.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error)	exit("Database Connection failed");

$search = $_SERVER['HTTP_SEARCH'] ?? $_GET['search'] ?? NULL;
if ($search != NULL) {
	create_player_overview_cards_from_search($dbcn, $search);
	exit;
}

$puuids = $_SERVER['HTTP_PUUIDS'] ?? $_GET['puuids'] ?? NULL;
if ($puuids != NULL) {
	$puuids = json_decode($puuids);
	create_player_overview_cards($dbcn, $puuids,true);
	exit;
}

$dbcn->close();