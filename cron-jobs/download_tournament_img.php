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

if (isset($_GET["update"])) {
	$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE imgID IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
} else {
	$tournaments = $dbcn->query("SELECT * FROM tournaments WHERE imgID IS NOT NULL AND img_local = FALSE")->fetch_all(MYSQLI_ASSOC);
}

$toornament_img_path = "https://play.toornament.com/media/file/";
$imgfolder_path = dirname(__FILE__) . "/../img/tournament_logos";

foreach ($tournaments as $tournament) {
	echo "loading image for ".$tournament["Name"].":<br>";
	if (is_dir($imgfolder_path."/".$tournament["imgID"])) {
		echo "directory for Tournament already exists<br>";
	} else {
		echo "directory for Tournament doesnt exist, creating it<br>";
		mkdir($imgfolder_path."/".$tournament["imgID"]);
	}

	$local_tournament_directory_path = $imgfolder_path."/".$tournament["imgID"];
	$img = imagecreatefrompng($toornament_img_path.$tournament["imgID"]."/logo_small");
	imagepalettetotruecolor($img);
	imagealphablending($img, false);
	imagesavealpha($img, true);
	imagepng($img,$local_tournament_directory_path."/logo_small.png");
	imagewebp($img,$local_tournament_directory_path."/logo_small.webp",75);
	imagedestroy($img);
	echo "--saved logo_small<br>";

	$local_tournament_directory_path = $imgfolder_path."/".$tournament["imgID"];
	$img = imagecreatefrompng($toornament_img_path.$tournament["imgID"]."/logo_medium");
	imagepalettetotruecolor($img);
	imagealphablending($img, false);
	imagesavealpha($img, true);
	imagepng($img,$local_tournament_directory_path."/logo_medium.png");
	imagewebp($img,$local_tournament_directory_path."/logo_medium.webp",100);
	imagedestroy($img);
	echo "--saved logo_medium<br>";

	$dbcn->query("UPDATE tournaments SET img_local=TRUE WHERE TournamentID=".$tournament["TournamentID"]);
	echo "----added img_local to DB entry<br>";
}