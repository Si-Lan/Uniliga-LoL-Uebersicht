<?php
include("get-RGAPI-data.php");
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include('../../DB-info.php');

$dbcn = new mysqli($dbservername,$dbusername,$dbpassword,$dbdatabase,$dbport);

if ($dbcn -> connect_error){
	echo "<span style='color: orangered'>Database Connection failed</span>";
} else {
	$type = $_REQUEST["type"];

	if ($type == "calculate-write-avg-rank") {
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
			"DIAMOND IV" => 21,
			"DIAMOND III" => 22,
			"DIAMOND II" => 23,
			"DIAMOND I" => 24,
			"MASTER" => 28,
			"GRANDMASTER" => 32,
			"CHALLENGER" => 35
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
			21 => ["DIAMOND", " IV"],
			22 => ["DIAMOND", " III"],
			23 => ["DIAMOND", " II"],
			24 => ["DIAMOND", " I"],
			25 => ["MASTER",""],
			26 => ["MASTER",""],
			27 => ["MASTER",""],
			28 => ["MASTER",""],
			29 => ["MASTER",""],
			30 => ["MASTER",""],
			31 => ["GRANDMASTER",""],
			32 => ["GRANDMASTER",""],
			33 => ["GRANDMASTER",""],
			34 => ["CHALLENGER",""],
			35 => ["CHALLENGER",""]
		);

		$teamID = $_REQUEST["team"];
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
			$dbcn->query("UPDATE teams SET avg_rank_tier = NULL, avg_rank_div = NULL WHERE TeamID = {$teamID}");
			echo "";
		} else {
			$rank = 0;
			foreach ($rank_arr as $player) {
				$rank += $player;
			}
			$rank_num = $rank / count($rank_arr);
			$rank = floor($rank_num);
			$dbcn->query("UPDATE teams SET avg_rank_tier = '{$ranks_rev[$rank][0]}', avg_rank_div = '{$ranks_rev[$rank][1]}', avg_rank_num = {$rank_num} WHERE TeamID = {$teamID}");
			echo $ranks_rev[$rank][0] . $ranks_rev[$rank][1] . " " . $rank_num;
		}
	}


	if ($type == "get-played-positions-for-players") {
		$teamID = $_REQUEST["team"];
		$result = get_played_positions_for_players($teamID);
		echo $result["echo"];
	}

	if ($type == "get-played-champions-for-players") {
		$teamID = $_REQUEST["team"];
		$result = get_played_champions_for_players($teamID);
		echo $result["echo"];
	}
	if ($type == "calculate-teamstats") {
		$teamID = $_REQUEST["team"];
		$result = calculate_teamstats($dbcn,$teamID);
		echo $result["echo"];
	}
}