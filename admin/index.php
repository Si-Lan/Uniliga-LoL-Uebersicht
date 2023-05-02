<?php
include("admin-pass.php");
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
            <script src="main.js"></script>
            <title>Uniliga DB Write</title>
        </head>
        <body>
        <div class="nav-menu-login logged-in">
            <a href="../" style="color: grey;">zur Startseite</a>
            <a href="riot-api-access" style="color: grey">Daten von Riot API holen</a>
            <a href="?logout" style="color: grey;">ausloggen</a>
        </div>

        <h1>Toornament -> Database</h1>

        <div id="main-selection">
            <label for="input-tournament-id"></label><input id="input-tournament-id" name="id" placeholder="Tournament ID" type="number">
            <div class="turnier-button-get" onclick="get_toor_tournaments()">Add Tournament to Database</div>
            <div class="turnier-get-result no-res get-result"></div>
        </div>

        <div>
            <a class="turnier-button-get" href="../cron-jobs/download_tournament_img.php">download Tournament Logos</a>
        </div>

        <h2>Turniere in Datenbank:</h2>
        <div class="turnier-select"></div>

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
    <link rel="stylesheet" href="../style-login.css">
</head>
<body style="background-color: #202020; color: #fff; font-family: 'Arial','sans-serif';">
<div style="height: 85vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 40px">
<a href="../" class="button" style="text-decoration: none">zurück zur Startseite</a>
<form action="<?php echo strtok($_SERVER['REQUEST_URI'],'?'); ?>?p=login" method="post" style="display: flex; flex-direction: column; align-items: center;">
    <label><input type="password" name="keypass" id="keypass" placeholder="Password" /></label><br />
    <input type="submit" id="submit" value="Login" />
</form>
</div>
</body>