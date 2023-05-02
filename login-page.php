<?php
include_once("fe-functions.php");
if (is_light_mode()) {
	$lightmode = " light";
} else {
	$lightmode = "";
}
?>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" href="https://silence.lol/favicon-dark.ico" media="(prefers-color-scheme: dark)"/>
	<link rel="icon" href="https://silence.lol/favicon-light.ico" media="(prefers-color-scheme: light)"/>
	<title>Uniliga LoL - Übersicht</title>
	<link rel="stylesheet" href="style-login.css">
</head>
<body class="<?php echo $lightmode?>" style="background-color: #202020; color: #fff; font-family: 'Arial','sans-serif';">
<div style="height: 85vh; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 40px">
	<a href="." class="button" style="text-decoration: none">zurück zur Startseite</a>
	<form action="<?php echo strtok($_SERVER['REQUEST_URI'],'?'); ?>?p=login" method="post" style="display: flex; flex-direction: column; align-items: center; gap: 1em;">
		<label><input type="password" name="keypass" id="keypass" placeholder="Password" /></label>
		<input type="submit" id="submit" value="Login" />
	</form>
</div>
</body>