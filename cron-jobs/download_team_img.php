<?php
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
if (isset($_GET["t"])) {
	$tournament_id = $_GET['t'];
	if (isset($_GET["update"])) {
		$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID=$tournament_id AND imgID IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
	} else {
		$teams = $dbcn->query("SELECT * FROM teams WHERE TournamentID=$tournament_id AND imgID IS NOT NULL AND img_local = FALSE")->fetch_all(MYSQLI_ASSOC);
	}
} else {
	exit;
}

$toornament_img_path = "https://play.toornament.com/media/file/";
$imgfolder_path = dirname(__FILE__) . "/../img/team_logos";

foreach ($teams as $team) {
	echo "loading image for ".$team["TeamName"].":<br>";
	if (is_dir($imgfolder_path."/".$team["imgID"])) {
		echo "directory for Team already exists<br>";
	} else {
		echo "directory for Team doesnt exist, creating it<br>";
		mkdir($imgfolder_path."/".$team["imgID"]);
	}

	$local_tournament_directory_path = $imgfolder_path."/".$team["imgID"];
	$img = imagecreatefrompng($toornament_img_path.$team["imgID"]."/logo_small");
	imagepalettetotruecolor($img);
	imagealphablending($img, false);
	imagesavealpha($img, true);
	imagepng($img,$local_tournament_directory_path."/logo_small.png");
	imagewebp($img,$local_tournament_directory_path."/logo_small.webp",75);
	imagedestroy($img);
	echo "--saved logo_small<br>";

	$local_tournament_directory_path = $imgfolder_path."/".$team["imgID"];
	$img = imagecreatefrompng($toornament_img_path.$team["imgID"]."/logo_medium");
	imagepalettetotruecolor($img);
	imagealphablending($img, false);
	imagesavealpha($img, true);
	imagepng($img,$local_tournament_directory_path."/logo_medium.png");
	imagewebp($img,$local_tournament_directory_path."/logo_medium.webp",100);
	imagedestroy($img);
	echo "--saved logo_medium<br>";

	$dbcn->query("UPDATE teams SET img_local=TRUE WHERE TeamID=".$team["TeamID"]);
	echo "----added img_local to DB entry<br>";
}