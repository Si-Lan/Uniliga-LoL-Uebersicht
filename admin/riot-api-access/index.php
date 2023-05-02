<?php
include("../admin-pass.php");
$password = get_admin_pass();
$pwoptions   = ['cost' => 8,];
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
if (isset($_COOKIE['write-login'])) {
	if (password_verify($password,$_COOKIE['write-login'])) {
		?>
		<!DOCTYPE html>
		<html lang="">
		<head>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="icon" href="https://silence.lol/favicon-dark.ico" media="(prefers-color-scheme: dark)"/>
            <link rel="icon" href="https://silence.lol/favicon-light.ico" media="(prefers-color-scheme: light)"/>
			<link rel="stylesheet" href="style.css">
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
			<script src="rgapi.js"></script>
            <title>Uniliga DB Riot-API-Access</title>
		</head>
		<body>
		<div class="nav-menu-login logged-in">
			<a href="../../" style="color: grey">zur Startseite</a>
            <a href="../" style="color: grey;">Daten von Toornament holen</a>
            <a href="?logout" style="color: grey;">ausloggen</a>
		</div>

		<h1>Riot Games API Access</h1>

        <?php
		$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
		include('../../DB-info.php');
		$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);

		if ($dbcn->connect_error) {
			echo "Database Connection failed : " . $dbcn->connect_error;
		} else {
            $tournaments = $dbcn->query("SELECT * FROM tournaments ORDER BY TournamentID DESC")->fetch_all(MYSQLI_ASSOC);
            echo "<div class='main-content'>";
            echo "<select onchange='change_tournament(this.value)'>";
            foreach ($tournaments as $tournament) {
                echo "<option value='".$tournament['TournamentID']."'>Uniliga {$tournament['Split']}season {$tournament['Season']}</option>";
			}
            echo "</select>";
            foreach ($tournaments as $index=>$tournament) {
                if ($index == 0) {
                    $hiddenclass = "";
				} else {
                    $hiddenclass = " hidden";
				}
                echo "<div class='writing-wrapper ".$tournament['TournamentID'].$hiddenclass."'>";
                echo "<h2>Uniliga {$tournament['Split']}season {$tournament['Season']}</h2>";
                echo "<a class='button write puuids {$tournament['TournamentID']}' onclick='get_puuids(\"{$tournament['TournamentID']}\")'>get PUUIDs for Players without ID</a>";
                echo "<a class='button write puuids-all {$tournament['TournamentID']}' onclick='get_puuids(\"{$tournament['TournamentID']}\",false)'>get PUUIDs for all Players</a>";
				echo "<a class='button write get-ranks {$tournament['TournamentID']}' onclick='get_ranks(\"{$tournament['TournamentID']}\")'>get Ranks for Players</a>";
				echo "<a class='button write calc-team-rank {$tournament['TournamentID']}' onclick='get_average_team_ranks(\"{$tournament['TournamentID']}\")'>calculate average Ranks for Teams</a>";
				echo "<a class='button write games {$tournament['TournamentID']}' onclick='' style='color: #ff6161'>get all Games (API-Calls)</a>";
				echo "<a class='button write gamedata {$tournament['TournamentID']}' onclick='get_game_data(\"{$tournament['TournamentID']}\")'>get Gamedata for Games without Data</a>";
				echo "<a class='button write gamedata-all {$tournament['TournamentID']}' onclick='get_game_data(\"{$tournament['TournamentID']}\",0,1)'>get Gamedata for all Games</a>";
				echo "<a class='button write assign-una {$tournament['TournamentID']}' onclick='assign_and_filter_games(\"{$tournament['TournamentID']}\")'>sort all unsorted Games</a>";
				echo "<a class='button write assign-all {$tournament['TournamentID']}' onclick='assign_and_filter_games(\"{$tournament['TournamentID']}\",0,1)'>sort all Games</a>";
				echo "<a class='button write get-pos {$tournament['TournamentID']}' onclick='get_positions_for_players(\"{$tournament['TournamentID']}\")'>calculate Positions of Players</a>";
				echo "<a class='button write get-champs {$tournament['TournamentID']}' onclick='get_champions_for_players(\"{$tournament['TournamentID']}\")'>calculate Champions of Players</a>";
				echo "<a class='button write teamstats {$tournament['TournamentID']}' onclick='get_teamstats(\"{$tournament['TournamentID']}\")'>calculate Teamstats</a>";
                echo "<div class='result-wrapper no-res {$tournament['TournamentID']}'>
                        <div class='clear-button' onclick='clear_results(\"{$tournament['TournamentID']}\")'>Clear</div>
                        <div class='result-content'></div>
                      </div>";
                echo "</div>";
			}
            echo "</div>";
		}
        ?>

		</body>
		</html>
		<?php
		exit;
	} else {
		echo "Bad Cookie.";
		exit;
	}
}

if (isset($_GET['p']) && $_GET['p'] == "login") {
	if ($_POST['keypass'] != $password) {
		echo "Sorry, that password does not match.";
		exit;
	} else if ($_POST['keypass'] == $password) {
		setcookie('write-login', password_hash($password, PASSWORD_BCRYPT),time()+31536000,'/');
		$pageurl = strtok($_SERVER['REQUEST_URI'],'?');
		header("Location: $pageurl");
	} else {
		echo "Sorry, you could not be logged in at this time.";
	}
}
?>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="https://silence.lol/favicon-dark.ico" media="(prefers-color-scheme: dark)"/>
    <link rel="icon" href="https://silence.lol/favicon-light.ico" media="(prefers-color-scheme: light)"/>
	<title>Uniliga DB Write</title>
    <link rel="stylesheet" href="../../style-login.css">
</head>
<body style="background-color: #202020; color: #fff; font-family: 'Arial','sans-serif';">
<div style="height: 85vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 40px">
	<a href="../../" class="button" style="text-decoration: none">zurück zur datenbank</a>
	<form action="<?php echo strtok($_SERVER['REQUEST_URI'],'?'); ?>?p=login" method="post" style="display: flex; flex-direction: column; align-items: center;">
		<label><input type="password" name="keypass" id="keypass" placeholder="Password" /></label><br />
		<input type="submit" id="submit" value="Login" />
	</form>
</div>
</body>