<?php
function create_summonercard($player,$collapsed=FALSE,$echo=TRUE){
	$return = "";
    if ($collapsed) {
		$sc_collapsed_state = "collapsed";
    } else {
		$sc_collapsed_state = "";
    }
	$enc_summoner = urlencode($player['SummonerName']);
	$player_tier = $player['rank_tier'];
	$player_div = $player['rank_div'];
	$player_LP = NULL;
	if ($player_tier == "CHALLENGER" || $player_tier == "GRANDMASTER" || $player_tier == "MASTER") {
		$player_div = "";
		$player_LP = $player["leaguePoints"];
	}
	$return .= "<div class='summoner-card-wrapper'>";
	$return .= "
	<div class='summoner-card {$player['PlayerID']} $sc_collapsed_state' onclick='player_to_opgg_link(\"{$player['PlayerID']}\",\"{$player['SummonerName']}\")'>";
	$return .= "<input type='checkbox' name='OPGG' checked class='opgg-checkbox'>";
	$return .= "
	<span class='card-player'>
		{$player['PlayerName']}
	</span>
	<div class='divider'></div>
	<div class='card-summoner'>
		<span>
			{$player['SummonerName']}
		</span>";

	if ($player_tier != NULL) {
		$player_tier = strtolower($player_tier);
        $player_tier_cap = ucfirst($player_tier);
		if ($player_LP != NULL) {
			$player_LP = "(".$player_LP." LP)";
		} else {
			$player_LP = "";
		}
		$return .= "
		<div class='card-rank'>
			<img class='rank-emblem-mini' src='ddragon/img/ranks/mini-crests/{$player_tier}.svg' alt='$player_tier_cap'>
			$player_tier_cap $player_div $player_LP
		</div>";
	}

	$return .= "
			<div class='played-positions'>";
	$roles = json_decode($player['roles']);
	foreach ($roles as $role=>$role_amount) {
		if ($role_amount != 0) {
			$return .= "
				<div class='role-single'>
					<div class='svg-wrapper role'>".file_get_contents(dirname(__FILE__)."/ddragon/img/positions/position-$role-light.svg")."</div>
					<span class='played-amount'>$role_amount</span>
				</div>";
		}
	}
	$return .= "
		</div>"; // played-positions

	$return .= "
		<div class='played-champions'>";
	$champions = json_decode($player['champions'],true);
	arsort($champions);
	$champs_cut = FALSE;
	if (count($champions) > 5) {
		$champions = array_slice($champions, 0, 5);
		$champs_cut = TRUE;
	}

	$patches = [];
	$dir = new DirectoryIterator(dirname(__FILE__) . "/ddragon");
	foreach ($dir as $fileinfo) {
		if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
			$patches[] = $fileinfo->getFilename();
		}
	}
	sort($patches);
	$patch = end($patches);

	foreach ($champions as $champion=>$champion_amount) {
		$return .= "
			<div class='champ-single'>
				<img src='/uniliga/ddragon/{$patch}/img/champion/{$champion}.webp' alt='$champion'>
				<span class='played-amount'>".$champion_amount['games']."</span>
			</div>";
	}
	if ($champs_cut) {
		$return .= "
		<div class='champ-single'>
			<div class='material-symbol'>". file_get_contents(dirname(__FILE__)."/icons/material/more_horiz.svg") ."</div>
		</div>";
	}
	$return .= "
		</div>"; // played-champions
	$return .= "
	</div>"; // card-summoner
	$return .= "
	</div>"; // summoner-card
	$return .= "<a href='https://www.op.gg/summoners/euw/$enc_summoner' target='_blank' class='op-gg-single'><div class='svg-wrapper op-gg'>".file_get_contents(dirname(__FILE__)."/img/opgglogo.svg")."</div></a>";
	$return .= "</div>"; // summoner-card-wrapper

	if ($echo) {
		echo $return;
	}
	return $return;
}
