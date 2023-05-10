<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('DB-info.php');
include("fe-functions.php");
include("admin/admin-pass.php");

$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

$local_img_path = "img/tournament_logos/";
$local_img_path_end = "/logo_small.webp";
$toor_tourn_url = "https://play.toornament.com/de/tournaments/";


if (is_light_mode()) {
	$lightmode = " light";
} else {
	$lightmode = "";
}

$password = get_admin_pass();
if (isset($_GET['p']) && $_GET['p'] == "login") {
	if ($_POST['keypass'] != $password) {
		echo "Sorry, that password does not match.";
		exit;
	} else {
		setcookie('write-login', password_hash($password, PASSWORD_BCRYPT),time()+31536000,'/');
        $pageurl = strtok($_SERVER['REQUEST_URI'],'?');
		header("Location: $pageurl");
	}
}
if (isset($_GET['logout'])) {
	if (isset($_COOKIE['write-login'])) {
		unset($_COOKIE['write-login']);
		setcookie('write-login','',time()-3600,'/');
	}
	if (isset($_COOKIE['admin_btns'])) {
		unset($_COOKIE['admin_btns']);
		setcookie('admin_btns','',time()-3600,'/');
	}
	header("Refresh:0; url=.");
}
if (isset($_GET['login'])) {
	include "login-page.php";
    exit;
}
?>

<!DOCTYPE html>
<html lang="">
<head>
    <?php
    create_html_head_elements("home");
    ?>
</head>

<body class="home<?php echo $lightmode?>">

<?php
create_header($dbcn,"home");
?>

<div class="home-content">
    <div id="turnier-select">
        <h2>Turniere:</h2>

    <?php

    try {
        if ($dbcn->connect_error) {
            echo "Database Connection failed : " . $dbcn->connect_error;
        } else {
            $toornamentsRes = $dbcn->execute_query("SELECT name, split, season, imgID, TournamentID FROM tournaments ORDER BY TournamentID DESC");
            $toornaments = $toornamentsRes->fetch_all(MYSQLI_ASSOC);

            echo "
            <span class='searchbar'>
                <input class=\"search-tournaments deletable-search\" onkeyup=\"search_tourns()\" placeholder='Suche' type='text'>
                <div class='material-symbol' onclick='clear_searchbar()'>". file_get_contents("icons/material/close.svg") ."</div>
            </span>";

            for ($i = 0; $i < count($toornaments); $i++) {
                $currTourn = $toornaments[$i]['name'];
                $tournID = $toornaments[$i]['TournamentID'];
                if ($toornaments[$i]['imgID'] == NULL) {
                    $tournimg_url = "";
                } else {
                    $tournimg_url = $local_img_path . $toornaments[$i]['imgID'] . $local_img_path_end;
                }
				$t_name_clean = explode("League of Legends",$toornaments[$i]['name']);
				if (count($t_name_clean)>1) {
					$tournament_name = $t_name_clean[0].$t_name_clean[1];
				} else {
					$tournament_name = $toornaments[$i]['name'];
				}
				echo "<a href='turnier/$tournID' class=\"turnier-button $tournID\">
                        <img alt src='$tournimg_url'>
                        <span>$tournament_name</span>
                      </a>";
            }

            $dbcn->close();
        }
    } catch (Exception $e) {
        echo "Database Connection failed";
    }
    ?>

    </div>
</div>
</body>
</html>
