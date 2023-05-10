<!DOCTYPE html>
<html lang="de">
<head>
	<base href="/uniliga/">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="https://silence.lol/favicon-dark.ico" media="(prefers-color-scheme: dark)"/>
	<link rel="icon" href="https://silence.lol/favicon-light.ico" media="(prefers-color-scheme: light)"/>
	<link rel="stylesheet" href="style.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	<script src="main.js"></script>
	<?php
	include "fe-functions.php";
	$dbservername = "";
	$dbdatabase = "";
	$dbusername = "";
	$dbpassword = "";
	$dbport = NULL;
	include('DB-info.php');

	$pageurl = $_SERVER['REQUEST_URI'];

	try {
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	if ($dbcn->connect_error) {
	?>
	<title>Database Connection failed</title></head>Database Connection failed"
<?php
} else {
echo "<title>Spieler | Uniliga LoL - Übersicht</title></head>";
if (is_light_mode()) {
	$lightmode = " light";
} else {
	$lightmode = "";
}
?>
<body class="players<?php echo $lightmode?>">
<?php
create_header($dbcn,"players");

echo "<div class='main-content'>";
echo "<div><h2>Spielersuche</h2>Suche nach Spielernamen oder Summonernamen</div>";
echo "<div class='search-wrapper'>
                <span class='searchbar'>
                    <input class=\"search-players deletable-search\" placeholder='Spieler suchen' type='text'>
                    <div class='material-symbol' onclick='clear_searchbar()'>". file_get_contents("icons/material/close.svg") ."</div>
                </span>
              </div>";
echo "</div>";

$players = $dbcn->query("SELECT DISTINCT PUUID FROM players")->fetch_all(MYSQLI_ASSOC);
/*
foreach ($players as $player) {
	$player_stats = $dbcn->query("SELECT SummonerName, TeamName, PUUID, `Name` FROM players JOIN teams ON players.TeamID=teams.TeamID JOIN tournaments ON tournaments.TournamentID=teams.TournamentID WHERE PUUID = '".$player["PUUID"]."' ORDER BY tournaments.DateStart")->fetch_all(MYSQLI_ASSOC);
	if (count($player_stats) == 0) {
		continue;
	}
	echo "<h3>{$player_stats[0]['SummonerName']}</h3><br>";
	foreach ($player_stats as $single_player) {
		echo "<div>{$single_player['SummonerName']} von {$single_player['TeamName']} ({$single_player['Name']})</div><br>";
	}
	echo "<br>";
}
*/
}
} catch (Exception $e) {
	echo "<title>Error</title></head>";
}
?>
</html>