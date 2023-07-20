<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__DIR__)."/../DB-info.php");
$RGAPI_Key = "";
include(dirname(__DIR__).'/riot-api-access/RGAPI-info.php');

//error_reporting(0);

// sendet X Anfragen an Riot API (Summoner-V4)  (X = Anzahl Spieler im Team)
function get_puuids_by_team($teamID, $all = FALSE) {
	$returnArr = array("return"=>0, "echo"=>"", "writesP"=>0, "writesS"=>0, "changes"=>[0,[]], "RGAPI-Calls"=>0,"404"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
	$teamDB = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$teamID}")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing PUUIDS for Players from {$teamDB['TeamName']} :<br></span>";

	if ($all){
		$playersDB = $dbcn->query("SELECT * FROM players WHERE TeamID = {$teamID}")->fetch_all(MYSQLI_ASSOC);
	} else {
		$playersDB = $dbcn->query("SELECT * FROM players WHERE TeamID = {$teamID} AND (PUUID IS NULL OR SummonerID IS NULL)")->fetch_all(MYSQLI_ASSOC);
	}

	foreach ($playersDB as $player) {
		$SummonerName_safe = urlencode($player['SummonerName']);
		$returnArr['echo'] .= "<span style='color: lightskyblue'>-writing PUUID for {$player['SummonerName']} :<br></span>";

		$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
		$context = stream_context_create($options);

		$content = file_get_contents("https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/{$SummonerName_safe}", false, $context);
		$returnArr["RGAPI-Calls"] += 1;
		if ($content === FALSE) {
			$returnArr["echo"] .= "<span style='color: orangered'>--could not get PUUID, request failed: {$http_response_header[0]}<br></span>";
			if (str_contains($http_response_header[0], "404")) {
				$dbcn->query("UPDATE players SET PUUID404 = 1 WHERE PlayerID = {$player['PlayerID']}");
				$returnArr["404"]++;
			}
			continue;
		}
		if (str_contains($http_response_header[0], "200")) {
			$data = json_decode($content, true);
			$returnArr["echo"] .= "<span style='color: limegreen'>--got PUUID: {$data['puuid']}<br></span>";

			$playerinDB = $dbcn->query("SELECT * FROM players WHERE PlayerID = {$player['PlayerID']}")->fetch_assoc();
			if ($playerinDB['PUUID'] == NULL) {
				$returnArr["echo"] .= "<span style='color: lawngreen'>---write PUUID to DB<br></span>";
				$returnArr["writesP"]++;
				$dbcn->query("UPDATE players SET PUUID = '{$data['puuid']}' WHERE PlayerID = {$player['PlayerID']}");
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>---Player already has a PUUID in DB<br></span>";
				if ($playerinDB['PUUID'] == $data['puuid']) {
					$returnArr["echo"] .= "<span style='color: yellow'>----PUUID unchanged<br></span>";
				} else {
					$returnArr["echo"] .= "<span style='color: lawngreen'>----PUUID changed, update DB<br></span>";
					$dbcn->query("UPDATE players SET PUUID = '{$data['puuid']}' WHERE PlayerID = {$player['PlayerID']}");
				}
			}
			if ($playerinDB['SummonerID'] == NULL) {
				$returnArr["echo"] .= "<span style='color: lawngreen'>---write SummonerID to DB<br></span>";
				$returnArr["writesS"]++;
				$dbcn->query("UPDATE players SET SummonerID = '{$data['id']}' WHERE PlayerID = {$player['PlayerID']}");
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>---Player already has a SummonerID in DB<br></span>";
				if ($playerinDB['SummonerID'] == $data['id']) {
					$returnArr["echo"] .= "<span style='color: yellow'>----SummonerID unchanged<br></span>";
				} else {
					$returnArr["echo"] .= "<span style='color: lawngreen'>----SummonerID changed, update DB<br></span>";
					$dbcn->query("UPDATE players SET SummonerID = '{$data['id']}' WHERE PlayerID = {$player['PlayerID']}");
				}
			}
		} else {
			$response = explode(" ", $http_response_header[0])[1];
			$returnArr["echo"] .= "<span style='color: orangered'>--could not get PUUID, response-code: $response<br></span>";
		}
	}

	return $returnArr;
}

// sendet 1 Anfrage an Riot API (Match-V5)
function get_games_by_player($playerID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0, "already"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$player = $dbcn->query("SELECT * FROM players WHERE players.PlayerID = {$playerID}")->fetch_assoc();
	$tournament = $dbcn->query("SELECT * FROM tournaments WHERE TournamentID = {$player['TournamentID']}")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing Matches for {$player['PlayerName']} :<br></span>";

	$tournament_start = strtotime($tournament['DateStart'])-(86400*7); // eine woche puffer
	$tournament_end = strtotime($tournament['DateEnd'])+86400; // ein Tag Puffer

	$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
	$context = stream_context_create($options);
	$content = file_get_contents("https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/{$player['PUUID']}/ids?startTime={$tournament_start}&endTime={$tournament_end}&type=tourney&start=0&count=40", false,$context);

	if ($content === FALSE) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get Games, request failed: {$http_response_header[0]}<br></span>";
		return $returnArr;
	}
	if (str_contains($http_response_header[0], "200")) {
		$data = json_decode($content, true);
		$game_count = count($data);
		$returnArr["echo"] .= "<span style='color: limegreen'>-got Games: $game_count<br></span>";
		foreach ($data as $game) {
			$game_in_DB = $dbcn->query("SELECT * FROM games WHERE RiotMatchID = '{$game}'")->fetch_assoc();
			if ($game_in_DB == NULL) {
				$returnArr["echo"] .= "<span style='color: lawngreen'>--write Game $game to DB<br></span>";
				$returnArr["writes"]++;
				$dbcn->query("INSERT INTO games (RiotMatchID,TournamentID) VALUES ('$game',{$tournament['TournamentID']})");
			} else {
				$returnArr["echo"] .= "<span style='color: orange'>--Game $game already in DB<br></span>";
				$returnArr["already"]++;
			}
		}
	} else {
		$response = explode(" ", $http_response_header[0])[1];
		$returnArr["echo"] .= "<span style='color: orangered'>-could not get Games, response-code: $response<br></span>";
	}

	return $returnArr;
}

// sendet 1 Anfrage an Riot API (Match-V5)
function add_match_data($RiotMatchID,$tournamentID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$matchDB = $dbcn->query("SELECT * FROM games WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing Matchdata for $RiotMatchID :<br></span>";

	if ($matchDB['MatchData'] == NULL) {
		$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
		$context = stream_context_create($options);
		$content = file_get_contents("https://europe.api.riotgames.com/lol/match/v5/matches/{$RiotMatchID}", false,$context);

		if ($content === FALSE) {
			$returnArr["echo"] .= "<span style='color: orangered'>-could not get MatchData, request failed: {$http_response_header[0]}<br></span>";
			return $returnArr;
		}
		if (str_contains($http_response_header[0], "200")) {
			$data = json_decode($content, true);
			$returnArr["echo"] .= "<span style='color: limegreen'>-got MatchData<br></span>";
			$returnArr["echo"] .= "<span style='color: lawngreen'>--write MatchData to DB<br></span>";
            $returnArr["writes"]++;
			$dbcn->query("UPDATE games SET MatchData = '$content' WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
		} else {
			$response = explode(" ", $http_response_header[0])[1];
			$returnArr["echo"] .= "<span style='color: orangered'>-could not get MatchData, response-code: $response<br></span>";
		}
	} else {
		$returnArr["echo"] .= "<span style='color: orange'>-Matchdata is already in DB<br></span>";
	}

	return $returnArr;
}

// sendet keine Anfrage
function assign_and_filter_game($RiotMatchID,$tournamentID) {
	$returnArr = array("return"=>0, "echo"=>"", "notUL"=>0, "isUL"=>0, "sorted"=>0, "notsorted"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	// check for Data
	$returnArr["echo"] .= "<span style='color: royalblue'>Sorting $RiotMatchID<br></span>";
	$gameDB = $dbcn->query("SELECT * FROM games WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID")->fetch_assoc();
	$data = json_decode($gameDB['MatchData'], true);
	if ($data == NULL) {
		$returnArr["echo"] .= "<span style='color: orange'>-Game has no Data<br></span>";
		$returnArr["return"] = 1;
		return $returnArr;
	}

	// check to see if the players make a team in the tournament
	$puuids = $data['metadata']['participants'];
	$blueIDs = array_slice($puuids,0,5);
	$redIDs = array_slice($puuids,5,10);
	$BlueTeamID = get_teamID_by_puuids($blueIDs,$tournamentID);
	$RedTeamID = get_teamID_by_puuids($redIDs,$tournamentID);
	if ($BlueTeamID['r'] == 1 or $RedTeamID['r'] == 1) {
		$returnArr["echo"] .= "<span style='color: red'>-Database Connection Error<br></span>";
		return $returnArr;
	}
	if ($BlueTeamID['TeamID'] == NULL) {
		$returnArr["echo"] .= "<span style='color: orange'>-Blue Team is not a Team from Tournament<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write not UL-Game to DB<br></span>";
		$returnArr["notUL"]++;
		$dbcn->query("UPDATE games SET `UL-Game` = FALSE WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
		return $returnArr;
	} else {
		$BlueTeamName = $dbcn->query("SELECT TeamName FROM teams WHERE TeamID = {$BlueTeamID['TeamID']}")->fetch_assoc()['TeamName'];
		$returnArr["echo"] .= "<span style='color: lightblue'>-Blue Team is $BlueTeamName<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write BLueTeamID to DB<br></span>";
		$dbcn->query("UPDATE games SET BlueTeamID = {$BlueTeamID['TeamID']} WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
	}
	if ($RedTeamID['TeamID'] == NULL) {
		$returnArr["echo"] .= "<span style='color: orange'>-Red Team is not a Team from Tournament<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write not an UL Game to DB<br></span>";
		$returnArr["notUL"]++;
		$dbcn->query("UPDATE games SET `UL-Game` = FALSE WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
		return $returnArr;
	} else {
		$RedTeamName = $dbcn->query("SELECT TeamName FROM teams WHERE TeamID = {$RedTeamID['TeamID']}")->fetch_assoc()['TeamName'];
		$returnArr["echo"] .= "<span style='color: lightblue'>-Red Team is $RedTeamName<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write RedTeamID to DB<br></span>";
		$dbcn->query("UPDATE games SET RedTeamID = {$RedTeamID['TeamID']} WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
	}
	$dbcn->query("UPDATE games SET `UL-Game` = TRUE WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
	$returnArr["echo"] .= "<span style='color: limegreen'>-Game is from Tournament<br></span>";
	$returnArr["echo"] .= "<span style='color: lawngreen'>--write UL-Game to DB<br></span>";
	$returnArr["isUL"]++;

	// check from which match the game is
	$matchDB = $dbcn->query("SELECT * FROM matches WHERE (Team1ID = {$BlueTeamID['TeamID']} AND Team2ID = {$RedTeamID['TeamID']}) OR (Team1ID = {$RedTeamID['TeamID']} AND Team2ID = {$BlueTeamID['TeamID']})")->fetch_all(MYSQLI_ASSOC);
	$matchDBPlayoffs = $dbcn->query("SELECT * FROM playoffmatches WHERE (Team1ID = {$BlueTeamID['TeamID']} AND Team2ID = {$RedTeamID['TeamID']}) OR (Team1ID = {$RedTeamID['TeamID']} AND Team2ID = {$BlueTeamID['TeamID']})")->fetch_all(MYSQLI_ASSOC);
	if (count($matchDB) == 0 && count($matchDBPlayoffs) == 0) {
		$returnArr["echo"] .= "<span style='color: orange'>-!no fitting Match found<br></span>";
		$returnArr["notsorted"]++;
	} elseif (count($matchDB) > 0 && count($matchDBPlayoffs) > 0) {
		// TO-DO: wenn Teams in Groups und Playoffs treffen, prüfen an welchem Termin das Spiel näher war
		$returnArr["echo"] .= "<span style='color: orange'>-Game has matches in Groups and Playoffs<br></span>";
	} elseif (count($matchDB) > 0) {
		// TO-DO: mit gespielter Zeit abstimmen, falls sich mehrfach getroffen wird
		if (count($matchDB) > 1) {
			$returnArr["echo"] .= "<span style='color: orange'>-!more than one Match fits to Teams, taking the first one!<br></span>";
		}
		$matchID = $matchDB[0]['MatchID'];
		$returnArr["echo"] .= "<span style='color: lightblue'>-Game is from Match $matchID<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write MatchID to DB<br></span>";
		$returnArr["sorted"]++;
		$dbcn->query("UPDATE games SET MatchID = $matchID WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
	} else {
		// TO-DO: wenn Teams in Playoffs mehrfach treffen, prüfen an welchem Termin das Spiel näher war
		if (count($matchDBPlayoffs) > 1) {
			$returnArr["echo"] .= "<span style='color: orange'>-!more than one Match fits to Teams, taking the first one!<br></span>";
		}
		$matchID = $matchDBPlayoffs[0]['MatchID'];
		$returnArr["echo"] .= "<span style='color: lightblue'>-Game is from Playoff-Match $matchID<br></span>";
		$returnArr["echo"] .= "<span style='color: lawngreen'>--write MatchID to DB<br></span>";
		$returnArr["sorted"]++;
		$dbcn->query("UPDATE games SET PLMatchID = $matchID WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");
	}

	// check which team won
	if ($data['info']['teams'][0]['win']) {
		$winner = 0;
		$returnArr["echo"] .= "<span style='color: lightblue'>-Blue Team won<br></span>";
	} else {
		$winner = 1;
		$returnArr["echo"] .= "<span style='color: lightblue'>-Red Team won<br></span>";
	}
	$dbcn->query("UPDATE games SET winningTeam = $winner WHERE RiotMatchID = '$RiotMatchID' AND TournamentID = $tournamentID");


	return $returnArr;
}

// sendet keine Anfrage
function get_teamID_by_puuids($PUUIDs,$tournamentID) {
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		return array("r"=>1, "TeamID"=>"");
	}

	$teams = [];
	$team_counts = [];
	foreach ($PUUIDs as $player) {
		$player_data = $dbcn->query("SELECT * FROM players WHERE PUUID = '$player' AND TournamentID = $tournamentID")->fetch_assoc();
		if ($player_data == NULL) {
			continue;
		}
		$team = $dbcn->query("SELECT * FROM teams WHERE TeamID = {$player_data['TeamID']}")->fetch_assoc();
		if (in_array($team['TeamID'],$teams)) {
			$team_counts[$team['TeamID']] += 1;
		} else {
			$teams[] = $team['TeamID'];
			$team_counts[$team['TeamID']] = 1;
		}
	}
	if (count($teams) == 0) {
		return array("r"=>0, "TeamID"=>NULL);
	}
	if (max($team_counts) >= 3) {
		$TeamID = array_keys($team_counts, max($team_counts))[0];
	} else {
		$TeamID = NULL;
	}
	return array("r"=>0, "TeamID"=>$TeamID);
}

// sendet 1 Anfrage an Riot API (League-V4)
function get_Rank_by_SummonerId($playerID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$player = $dbcn->query("SELECT * FROM players WHERE players.PlayerID = {$playerID}")->fetch_assoc();
	$returnArr["echo"] .= "<span style='color: royalblue'>writing Rank for {$player['PlayerName']} :<br></span>";

	$options = ["http" => ["header" => "X-Riot-Token: $RGAPI_Key"]];
	$context = stream_context_create($options);
	$content = file_get_contents("https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/{$player['SummonerID']}", false,$context);

	if ($content === FALSE) {
		$returnArr["echo"] .= "<span style='color: orangered'>--could not get Rank, request failed: {$http_response_header[0]}<br></span>";
		return $returnArr;
	}
	if (str_contains($http_response_header[0], "200")) {
		$data = json_decode($content, true);
		$solo_ranked = false;
		for ($i=0; $i<count($data); $i++) {
			if ($data[$i]['queueType'] == "RANKED_SOLO_5x5") {
				$data = $data[$i];
				$solo_ranked = true;
			}
		}
		if ($solo_ranked) {
			$tier = $data['tier'];
			$div = $data['rank'];
			$league_points = $data['leaguePoints'];
		} else {
			$tier = "UNRANKED";
			$div = NULL;
			$league_points = NULL;
		}
		$returnArr["echo"] .= "<span style='color: limegreen'>--got Rank: $tier $div ($league_points LP)<br></span>";

		$dbcn->query("UPDATE players SET rank_tier = '{$tier}', rank_div = '{$div}', leaguePoints = '{$league_points}' WHERE PlayerID = {$playerID}");
		$returnArr["echo"] .= "<span style='color: lawngreen'>---write Rank to DB<br></span>";
		$returnArr["writes"]++;
	} else {
		$response = explode(" ", $http_response_header[0])[1];
		$returnArr["echo"] .= "<span style='color: orangered'>-could not get Rank, response-code: $response<br></span>";
	}
	return $returnArr;
}


function get_played_positions_for_players($teamID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$players = $dbcn->query("SELECT * FROM players WHERE TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);

	foreach ($players as $player) {
		$games = $dbcn->query("SELECT MatchData FROM games WHERE (BlueTeamID = '{$player['TeamID']}' OR RedTeamID = '{$player['TeamID']}') AND `UL-Game` = TRUE")->fetch_all(MYSQLI_ASSOC);
		$roles = array("top"=>0,"jungle"=>0,"middle"=>0,"bottom"=>0,"utility"=>0);
		foreach ($games as $game) {
			$game_data = json_decode($game['MatchData'],true);
			if (in_array($player['PUUID'],$game_data['metadata']['participants'])) {
				$index = array_search($player['PUUID'],$game_data['metadata']['participants']);
				$position = strtolower($game_data['info']['participants'][$index]['teamPosition']);
				$roles[$position]++;
			}
		}
		$returnArr["echo"] .= "-{$player['SummonerName']}:<br>
					--- TOP: {$roles['top']}<br>
					--- JGL: {$roles['jungle']}<br>
					--- MID: {$roles['middle']}<br>
					--- BOT: {$roles['bottom']}<br>
					--- SUP: {$roles['utility']}<br>";
		$roles = json_encode($roles);
		$dbcn->query("UPDATE players SET roles = '$roles' WHERE PlayerID = {$player['PlayerID']}");
		$returnArr["writes"]++;
	}
	return $returnArr;
}

function get_played_champions_for_players($teamID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}

	$players = $dbcn->query("SELECT * FROM players WHERE TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);

	foreach ($players as $player) {
		$games = $dbcn->query("SELECT MatchData FROM games WHERE (BlueTeamID = '{$player['TeamID']}' OR RedTeamID = '{$player['TeamID']}') AND `UL-Game` = TRUE")->fetch_all(MYSQLI_ASSOC);
		$champions = array();
		foreach ($games as $game) {
			$game_data = json_decode($game['MatchData'],true);
			if (in_array($player['PUUID'],$game_data['metadata']['participants'])) {
				$index = array_search($player['PUUID'],$game_data['metadata']['participants']);
				$champion = $game_data['info']['participants'][$index]['championName'];
				$win = $game_data['info']['participants'][$index]['win'] ? 1 : 0;
				if (array_key_exists($champion,$champions)) {
					$champions[$champion]["games"]++;
					$champions[$champion]["wins"] += $win;
				} else {
					$champions[$champion] = array("games"=>1,"wins"=>$win);
				}
			}
		}
		$uniq = count($champions);
		$returnArr["echo"] .= "-{$player['SummonerName']}: $uniq versch. Champs <br>";
		$champions = json_encode($champions);
		$returnArr["echo"] .= "--$champions<br>";
		$dbcn->query("UPDATE players SET champions = '$champions' WHERE PlayerID = {$player['PlayerID']}");
		$returnArr["writes"]++;
	}
	return $returnArr;
}

function calculate_avg_team_rank($teamID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0);
	global $dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport, $RGAPI_Key;
	$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
	$ranks = array(
		"IRON IV" => 1,
		"IRON III" => 2,
		"IRON II" => 3,
		"IRON I" => 4,
		"BRONZE IV" => 5,
		"BRONZE III" => 6,
		"BRONZE II" => 7,
		"BRONZE I" => 8,
		"SILVER IV" => 9,
		"SILVER III" => 10,
		"SILVER II" => 11,
		"SILVER I" => 12,
		"GOLD IV" => 13,
		"GOLD III" => 14,
		"GOLD II" => 15,
		"GOLD I" => 16,
		"PLATINUM IV" => 17,
		"PLATINUM III" => 18,
		"PLATINUM II" => 19,
		"PLATINUM I" => 20,
		"EMERALD IV" => 21,
		"EMERALD III" => 22,
		"EMERALD II" => 23,
		"EMERALD I" => 24,
		"DIAMOND IV" => 25,
		"DIAMOND III" => 26,
		"DIAMOND II" => 27,
		"DIAMOND I" => 28,
		"MASTER" => 32,
		"GRANDMASTER" => 36,
		"CHALLENGER" => 39
	);
	$ranks_rev = array(
		1 => ["IRON", " IV"],
		2 => ["IRON", " III"],
		3 => ["IRON", " II"],
		4 => ["IRON", " I"],
		5 => ["BRONZE", " IV"],
		6 => ["BRONZE", " III"],
		7 => ["BRONZE", " II"],
		8 => ["BRONZE", " I"],
		9 => ["SILVER", " IV"],
		10 => ["SILVER", " III"],
		11 => ["SILVER", " II"],
		12 => ["SILVER", " I"],
		13 => ["GOLD", " IV"],
		14 => ["GOLD", " III"],
		15 => ["GOLD", " II"],
		16 => ["GOLD", " I"],
		17 => ["PLATINUM", " IV"],
		18 => ["PLATINUM", " III"],
		19 => ["PLATINUM", " II"],
		20 => ["PLATINUM", " I"],
		21 => ["EMERALD", " IV"],
		22 => ["EMERALD", " III"],
		23 => ["EMERALD", " II"],
		24 => ["EMERALD", " I"],
		25 => ["DIAMOND", " IV"],
		26 => ["DIAMOND", " III"],
		27 => ["DIAMOND", " II"],
		28 => ["DIAMOND", " I"],
		29 => ["MASTER",""],
		30 => ["MASTER",""],
		31 => ["MASTER",""],
		32 => ["MASTER",""],
		33 => ["MASTER",""],
		34 => ["MASTER",""],
		35 => ["GRANDMASTER",""],
		36 => ["GRANDMASTER",""],
		37 => ["GRANDMASTER",""],
		38 => ["CHALLENGER",""],
		39 => ["CHALLENGER",""]
	);

	$players = $dbcn->query("SELECT * FROM players WHERE TeamID = $teamID")->fetch_all(MYSQLI_ASSOC);
	$rank_arr = [];
	foreach ($players as $player) {
		if ($player['rank_tier'] != NULL && $player['rank_tier'] != "UNRANKED") {
			$player_rank = 0;
			if ($player['rank_tier'] === "MASTER" || $player['rank_tier'] === "GRANDMASTER" || $player['rank_tier'] === "CHALLENGER") {
				$player_rank = $ranks[$player['rank_tier']];
			} else {
				$player_rank = $ranks[$player['rank_tier']." ".$player['rank_div']];
			}
			$rank_arr[] = $player_rank;
		}
	}
	if (count($rank_arr) == 0) {
		$dbcn->query("UPDATE teams SET avg_rank_tier = NULL, avg_rank_div = NULL, avg_rank_num = NULL WHERE TeamID = {$teamID}");
		$returnArr["echo"] .= "";
	} else {
		$rank = 0;
		foreach ($rank_arr as $player) {
			$rank += $player;
		}
		$rank_num = $rank / count($rank_arr);
		$rank = floor($rank_num);
		$dbcn->query("UPDATE teams SET avg_rank_tier = '{$ranks_rev[$rank][0]}', avg_rank_div = '{$ranks_rev[$rank][1]}', avg_rank_num = {$rank_num} WHERE TeamID = {$teamID}");
		$returnArr["writes"]++;
		$returnArr["echo"] .= $ranks_rev[$rank][0] . $ranks_rev[$rank][1] . " " . $rank_num;
	}
	return $returnArr;
}


function calculate_teamstats($dbcn,$teamID) {
	$returnArr = array("return"=>0, "echo"=>"", "writes"=>0, "updates"=>0, "without"=>0);
	if ($dbcn -> connect_error){
		$returnArr["return"] = 1;
		$returnArr["echo"] .= "<span style='color: red'>Database Connection failed : " . $dbcn->connect_error . "<br><br></span>";
		return $returnArr;
	}
	$games = $dbcn->query("SELECT MatchData, BlueTeamID, RedTeamID FROM games WHERE (BlueTeamID = '$teamID' OR RedTeamID = '$teamID') AND `UL-Game` = TRUE")->fetch_all(MYSQLI_ASSOC);
	$games_played = count($games);

	if ($games_played == 0) {
		$returnArr["echo"] .= "Team ".$teamID." has not played any Games<br>";
        $returnArr["without"]++;
		return $returnArr;
	}

	$champs_played = $champs_played_against = $bans = $bans_against = array();

	$wins = 0;
	$win_time = 0;

	$ddragon_dir = new DirectoryIterator(dirname(__FILE__)."/../../ddragon");
	$patches = [];

	foreach ($ddragon_dir as $patch_dir) {
		if (!$patch_dir->isDot() && $patch_dir->getFilename() != "img") {
			$patches[] = $patch_dir->getFilename();
		}
	}
	rsort($patches);
	$latest_patch = $patches[0];
	$champion_data = json_decode(file_get_contents(dirname(__FILE__)."/../../ddragon/$latest_patch/data/champion.json"),true)['data'];
	$champions_by_key = [];
	foreach ($champion_data as $champ) {
		$champions_by_key[$champ['key']] = $champ['id'];
	}

	foreach ($games as $gindex=>$game) {
		$game_data = json_decode($game['MatchData'],true);
		if ($game['BlueTeamID'] == $teamID) {
			$side = 0;
			$side_a = 1;
		} elseif ($game['RedTeamID'] == $teamID) {
			$side = 1;
			$side_a = 0;
		} else {
			continue;
		}
		// wins and win_time
		$game_win = 0;
		if ($game_data['info']['teams'][$side]['win']) {
			$wins++;
			$win_time += $game_data['info']['gameDuration'];
			$game_win = 1;
		}
		// played champs
		for ($i = $side*5; $i < $side*5+5; $i++) {
			$champ = $game_data['info']['participants'][$i]['championName'];
			if (array_key_exists($champ,$champs_played)) {
				$champs_played[$champ]["games"]++;
				$champs_played[$champ]["wins"] += $game_win;
			} else {
				$champs_played[$champ] = array("games"=>1,"wins"=>$game_win);
			}
		}
		for ($i = $side_a*5; $i < $side_a*5+5; $i++) {
			$champ = $game_data['info']['participants'][$i]['championName'];
			if (array_key_exists($champ,$champs_played_against)) {
				$champs_played_against[$champ]++;
			} else {
				$champs_played_against[$champ] = 1;
			}
		}
		// banned champs
		$game_bans = $game_data['info']['teams'][$side]['bans'];
		foreach ($game_bans as $game_ban) {
			$champ = $champions_by_key[$game_ban['championId']];
			if (array_key_exists($champ,$bans)) {
				$bans[$champ]++;
			} else {
				$bans[$champ] = 1;
			}
		}
		$game_bans_against = $game_data['info']['teams'][$side_a]['bans'];
		foreach ($game_bans_against as $game_ban) {
			$champ = $champions_by_key[$game_ban['championId']];
			if (array_key_exists($champ,$bans_against)) {
				$bans_against[$champ]++;
			} else {
				$bans_against[$champ] = 1;
			}
		}

	}
	if ($wins != 0) {
		$avg_win_time = $win_time / $wins;
	} else {
		$avg_win_time = 0;
	}

	$champs_played = json_encode($champs_played);
	$bans = json_encode($bans);
	$champs_played_against = json_encode($champs_played_against);
	$bans_against = json_encode($bans_against);
	
	$teamstats = $dbcn->query("SELECT * FROM teamstats WHERE TeamID = $teamID")->fetch_assoc();
	if ($teamstats == NULL) {
		$dbcn->query("INSERT INTO teamstats
    		(TeamID, champs_played, champs_banned, champs_played_against, champs_banned_against, games_played, games_won, avg_win_time)
			VALUES 
			($teamID, '$champs_played', '$bans', '$champs_played_against', '$bans_against', $games_played, $wins, $avg_win_time)");
		$returnArr["echo"] .= "writing Stats for Team ".$teamID."<br>";
		$returnArr["writes"]++;
	} else {
		$dbcn->query("UPDATE teamstats SET champs_played='$champs_played', champs_banned='$bans', champs_banned_against='$champs_played_against', champs_banned_against='$bans_against', games_played=$games_played, games_won=$wins, avg_win_time=$avg_win_time WHERE TeamID = $teamID");
		$returnArr["echo"] .= "updating Stats for Team ".$teamID."<br>";
		$returnArr["updates"]++;
	}

	return $returnArr;
}

/*
$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);
echo calculate_teamstats($dbcn,'6146821358003044352')["echo"];
*/