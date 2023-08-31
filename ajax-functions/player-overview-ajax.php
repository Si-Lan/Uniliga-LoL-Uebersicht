<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../fe-functions.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error)	exit("Database Connection failed");

$puuid = $_SERVER["HTTP_PUUID"] ?? $_GET['puuid'] ?? NULL;
if ($puuid === NULL) exit;

create_player_overview($dbcn, $puuid);
