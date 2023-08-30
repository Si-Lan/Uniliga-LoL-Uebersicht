<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../game.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error) exit("Database Connection failed");

$gameID = $_SERVER['HTTP_GAMEID'] ?? $_GET['gameID'] ?? NULL;
if ($gameID === NULL) exit("no game found");
$teamID = $_SERVER['HTTP_TEAMID'] ?? $_GET['teamID'] ?? NULL;
create_game($dbcn, $gameID, $teamID);

$dbcn->close();