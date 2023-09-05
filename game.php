<?php
function create_game($dbcn,$gameID,$curr_team=NULL) {
    $gameDB = $dbcn->execute_query("SELECT * FROM games WHERE RiotMatchID = ?",[$gameID])->fetch_assoc();
    $team_blue_ID = $gameDB['BlueTeamID'];
    $team_red_ID = $gameDB['RedTeamID'];
    $team_blue = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$team_blue_ID])->fetch_assoc();
    $team_red = $dbcn->execute_query("SELECT * FROM teams WHERE TeamID = ?",[$team_red_ID])->fetch_assoc();
    $players_blue_DB = $dbcn->execute_query("SELECT SummonerName, rank_tier, rank_div, PUUID FROM players WHERE TeamID = ?",[$team_blue['TeamID']])->fetch_all(MYSQLI_ASSOC);
	$players_red_DB = $dbcn->execute_query("SELECT SummonerName, rank_tier, rank_div, PUUID FROM players WHERE TeamID = ?",[$team_red['TeamID']])->fetch_all(MYSQLI_ASSOC);

    $players_PUUID = [];
    $players = [];
    for ($i = 0; $i < count($players_blue_DB); $i++)  {
        $players[$players_blue_DB[$i]["SummonerName"]] = $players_blue_DB[$i];
        $players_PUUID[$players_blue_DB[$i]['PUUID']] = $players_blue_DB[$i];
    }
	for ($i = 0; $i < count($players_red_DB); $i++)  {
		$players[$players_red_DB[$i]["SummonerName"]] = $players_red_DB[$i];
		$players_PUUID[$players_red_DB[$i]['PUUID']] = $players_red_DB[$i];
	}

    if ($curr_team == $team_blue_ID) {
        $blue_curr = "current";
		$red_curr = "";
	} elseif ($curr_team == $team_red_ID) {
		$blue_curr = "";
		$red_curr = "current";
	} else {
		$blue_curr = "";
		$red_curr = "";
	}
    if ($gameDB['winningTeam'] == 0) {
		$score_blue = "Victory";
		$score_red = "Defeat";
        $score_blue_class = " win";
		$score_red_class = " loss";
	} else {
		$score_blue = "Defeat";
		$score_red = "Victory";
		$score_blue_class = " loss";
		$score_red_class = " win";
	}

    //$obj_icon_url = "https://raw.communitydragon.org/12.1/plugins/rcp-fe-lol-match-history/global/default/";
	$obj_icon_url = "ddragon/img/";
    $kills_icon = $obj_icon_url."kills.png";
    $obj_icons = $obj_icon_url."right_icons.png";
    $gold_icons = $obj_icon_url."icon_gold.png";
    $cs_icons = $obj_icon_url."icon_minions.png";

    $data = json_decode($gameDB['MatchData'],true);
    $participants_PUUIDs = $data['metadata']['participants'];
    $info = $data['info'];
    $participants = $info['participants'];
    for ($team_index = 0; $team_index <= 1; $team_index++) {
        for ($player_index = $team_index*5; $player_index < $team_index*5+5; $player_index++) {
            $roles = ["TOP","JUNGLE","MIDDLE","BOTTOM","UTILITY"];
            $roles_check = array("TOP"=>0,"JUNGLE"=>1,"MIDDLE"=>2,"BOTTOM"=>3,"UTILITY"=>4);
            $role = $participants[$player_index]['teamPosition'];
            if ($role != $roles[$player_index-($team_index*5)]) {
				$player_2_index = $roles_check[$role] + $team_index*5;
				$helper = $participants[$player_index];
                $participants[$player_index] = $participants[$player_2_index];
                $participants[$player_2_index] = $helper;
            }
        }
    }
    $teams = $info['teams'];

    $towers_blue = $teams[0]['objectives']['tower']['kills'];
    $towers_red = $teams[1]['objectives']['tower']['kills'];
    $inhibs_blue = $teams[0]['objectives']['inhibitor']['kills'];
    $inhibs_red = $teams[1]['objectives']['inhibitor']['kills'];
    $heralds_blue = $teams[0]['objectives']['riftHerald']['kills'];
    $heralds_red = $teams[1]['objectives']['riftHerald']['kills'];
    $dragons_blue = $teams[0]['objectives']['dragon']['kills'];
    $dragons_red = $teams[1]['objectives']['dragon']['kills'];
    $barons_blue = $teams[0]['objectives']['baron']['kills'];
    $barons_red = $teams[1]['objectives']['baron']['kills'];

    $bans_blue = $teams[0]['bans'];
    $bans_red = $teams[1]['bans'];

    $gold_blue = 0;
    $kills_blue = 0;
    $deaths_blue = 0;
    $assists_blue = 0;
    $gold_red = 0;
    $kills_red = 0;
    $deaths_red = 0;
    $assists_red = 0;
    for ($t = 0; $t < 2; $t++) {
        for ($p = 0; $p < 5; $p++) {
            $curr_player = $participants[$t*5+$p];
            if ($t == 0) {
                $gold_blue += $curr_player['goldEarned'];
                $kills_blue += $curr_player['kills'];
                $deaths_blue += $curr_player['deaths'];
                $assists_blue += $curr_player['assists'];
            } else {
				$gold_red += $curr_player['goldEarned'];
				$kills_red += $curr_player['kills'];
				$deaths_red += $curr_player['deaths'];
				$assists_red += $curr_player['assists'];
			}
        }
    }
    $gold_blue_1 = floor($gold_blue / 1000);
    $gold_blue_2 = floor($gold_blue % 1000 / 100);
    $gold_red_1 = floor($gold_red / 1000);
    $gold_red_2 = floor($gold_red % 1000 / 100);

	$patch = NULL;
	$patches = [];
	$dir = new DirectoryIterator(dirname(__FILE__) . "/ddragon");
	foreach ($dir as $fileinfo) {
		if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
			$patches[] = $fileinfo->getFilename();
		}
	}
	usort($patches, "version_compare");
	$game_patch_1 = explode(".",$info['gameVersion'])[0];
	$game_patch_2 = explode(".",$info['gameVersion'])[1];
	foreach ($patches as $patch_from_arr) {
		$patch_from_arr_1 = explode(".",$patch_from_arr)[0];
		$patch_from_arr_2 = explode(".",$patch_from_arr)[1];
		// durchlaufe Patchnummern der lokalen Patchdaten von alt nach neu
		// ist der Patch des Spiels älter als dieser Patch, oder genau dieser, setze $patch auf diesen Patch
		if ($game_patch_1 < $patch_from_arr_1 || ($game_patch_1 == $patch_from_arr_1 && $game_patch_2 <= $patch_from_arr_2)) {
			$patch = $patch_from_arr;
			break;
		}
	}
	// wurde $patch noch nicht gesetzt, muss der Patch des Spiels neuer sein, setze $patch auf den neuesten lokalen Patch
	if ($patch === NULL) {
		$patch = end($patches);
	}

	$dd_img = "ddragon/$patch/img";
    $dd_data = dirname(__FILE__)."/ddragon/$patch/data";

    //$champion_dd = file_get_contents("https://ddragon.leagueoflegends.com/cdn/$patch/data/en_US/champion.json");
    $champion_dd = file_get_contents("$dd_data/champion.json");
    $champion_dd = json_decode($champion_dd,true);
    $champion_data = $champion_dd['data'];
    $champions_by_key = [];
    foreach ($champion_data as $champ) {
        $champions_by_key[$champ['key']] = $champ['id'];
    }

    //$runes_dd = json_decode(file_get_contents("https://ddragon.leagueoflegends.com/cdn/$patch/data/en_US/runesReforged.json"),true);
    $runes_dd = json_decode(file_get_contents("$dd_data/runesReforged.json"),true);
    $runes = [];
    for ($r = 0; $r < count($runes_dd); $r++) {
        $keystones = $runes_dd[$r]['slots'][0]['runes'];
        $keystones_new = [];
        for ($k = 0; $k < count($keystones); $k++) {
            $keystones_new[$keystones[$k]['id']] = $keystones[$k];
        }
        $runes[$runes_dd[$r]["id"]] = $runes_dd[$r];
        $runes[$runes_dd[$r]["id"]]["slots"][0]["runes"] = $keystones_new;
    }

    //$summs_cd = json_decode(file_get_contents("https://raw.communitydragon.org/latest/plugins/rcp-be-lol-game-data/global/default/v1/summoner-spells.json"),true);

    //$summs_dd = json_decode(file_get_contents("https://ddragon.leagueoflegends.com/cdn/$patch/data/en_US/summoner.json"),true);
    $summs_dd = json_decode(file_get_contents("$dd_data/summoner.json"),true);
    $summs = array_column($summs_dd['data'],"id","key");

    $game_duration = $info['gameDuration'];
    $game_duration_min = floor($game_duration / 60);
    $game_duration_sec = $game_duration % 60;
    if ($game_duration_sec < 10) {
        $game_duration_sec = "0".$game_duration_sec;
    }

	$local_team_img = "img/team_logos/";
    if ($team_blue['imgID'] == NULL || !file_exists("$local_team_img{$team_blue['imgID']}/logo_small.webp")) {
        $logo_blue = "";
    } else {
		$logo_blue = "<img alt='' src='$local_team_img{$team_blue['imgID']}/logo_small.webp'>";
	}
	if ($team_red['imgID'] == NULL || !file_exists("$local_team_img{$team_red['imgID']}/logo_small.webp")) {
		$logo_red = "";
	} else {
		$logo_red = "<img alt='' src='$local_team_img{$team_red['imgID']}/logo_small.webp'>";
	}

    echo "
    <div class='game-details'>
        <div class='game-row teams'>
            <a class='team 1 $blue_curr$score_blue_class' href='/uniliga/team/$team_blue_ID'>
                <div class='name'>$logo_blue{$team_blue['TeamName']}</div>
                <div class='score$score_blue_class'>$score_blue</div>
            </a>
            <div class='time'>
                <div>$game_duration_min:$game_duration_sec</div>
            </div>
            <a class='team 2 $red_curr$score_red_class' href='/uniliga/team/$team_red_ID'>
                <div class='score$score_red_class'>$score_red</div>
                <div class='name'>{$team_red['TeamName']}$logo_red</div>
            </a>
        </div>
        <div class='game-row team-stats'>
            <div class='stats-wrapper'>
                <span><img src='$kills_icon' class='stats kills' alt=''>$kills_blue / $deaths_blue / $assists_blue</span>
                <span><img src='$gold_icons' class='stats gold' alt=''>{$gold_blue_1}.{$gold_blue_2}k</span>
            </div>
            <div class='game-row-divider'></div>
            <div class='stats-wrapper'>
                <span><img src='$kills_icon' class='stats kills' alt=''>$kills_red / $deaths_red / $assists_red</span>
                <span><img src='$gold_icons' class='stats gold' alt=''>{$gold_red_1}.{$gold_red_2}k</span>
            </div>
        </div>
        <div class='game-row objectives'>
            <div class='obj-wrapper'>
                <span><img src='$obj_icons' class='obj obj-tower' alt=''>$towers_blue</span>
                <span><img src='$obj_icons' class='obj obj-inhib' alt=''>$inhibs_blue</span>
                <span><img src='$obj_icons' class='obj obj-herald' alt=''>$heralds_blue</span>
                <span><img src='$obj_icons' class='obj obj-dragon' alt=''>$dragons_blue</span>
                <span><img src='$obj_icons' class='obj obj-baron' alt=''>$barons_blue</span>
            </div>
            <div class='game-row-divider'></div>
            <div class='obj-wrapper'>
                <span><img src='$obj_icons' class='obj obj-tower' alt=''>$towers_red</span>
                <span><img src='$obj_icons' class='obj obj-inhib' alt=''>$inhibs_red</span>
                <span><img src='$obj_icons' class='obj obj-herald' alt=''>$heralds_red</span>
                <span><img src='$obj_icons' class='obj obj-dragon' alt=''>$dragons_red</span>
                <span><img src='$obj_icons' class='obj obj-baron' alt=''>$barons_red</span>
            </div>
        </div>";
    for ($i = 0; $i < 5; $i++) {
        echo "
        <div class='game-row summoners'>";
        for ($p = 0; $p < 2; $p++) {
            if ($p == 0) {
                $team_side = "blue";
            } else {
                $team_side = "red";
            }
			$player = $participants[$i+($p*5)];

			$runepage_pri = $player['perks']['styles'][0]['style'];
			$keystone = $player['perks']['styles'][0]['selections'][0]['perk'];
			$runepage_sec = $player['perks']['styles'][1]['style'];
			$keystone_img = $runes[$runepage_pri]['slots'][0]['runes'][$keystone]['icon'];
			$sec_rune_img = $runes[$runepage_sec]['icon'];
            $keystone_img = explode(".",$keystone_img)[0].".webp";
            $sec_rune_img = explode(".",$sec_rune_img)[0].".webp";

            $summ1_img = $summs[$player['summoner1Id']];
            $summ2_img = $summs[$player['summoner2Id']];

            $championId = $player['championName'];
            $champ_lvl = $player['champLevel'];

			$summoner_rank = "";
			$summoner_rank_div = "";
            $summoner_name = $player['summonerName'];
            $puuid = $player['puuid'];
			if (array_key_exists($puuid, $players_PUUID)) {
				$summoner_rank = strtolower($players_PUUID[$puuid]['rank_tier']);
				if ($summoner_rank != "master" && $summoner_rank != "grandmaster" && $summoner_rank != "challenger") {
					$summoner_rank_div = $players_PUUID[$puuid]['rank_div'];
				}
			}
            $summoner_rank_cap = ucfirst($summoner_rank);

            $kills = $player['kills'];
            $deaths = $player['deaths'];
            $assists = $player['assists'];
            $cs = $player['totalMinionsKilled'];
            $gold = $player['goldEarned'];
			$gold_1 = floor($gold / 1000);
			$gold_2 = floor($gold % 1000 / 100);

            if ($team_side == "blue") {
				$item0 = ($player['item0'] == 0)? 7050 : $player['item0'];
				$item2 = ($player['item2'] == 0)? 7050 : $player['item2'];
				$item3 = ($player['item3'] == 0)? 7050 : $player['item3'];
				$item5 = ($player['item5'] == 0)? 7050 : $player['item5'];
			} else {
				$item0 = ($player['item2'] == 0)? 7050 : $player['item2'];
				$item2 = ($player['item0'] == 0)? 7050 : $player['item0'];
				$item3 = ($player['item5'] == 0)? 7050 : $player['item5'];
				$item5 = ($player['item3'] == 0)? 7050 : $player['item3'];
            }
			$item1 = ($player['item1'] == 0)? 7050 : $player['item1'];
			$item4 = ($player['item4'] == 0)? 7050 : $player['item4'];
            $item6 = ($player['item6'] == 0)? 7050 : $player['item6'];

			echo "
            <div class='game-item summoner $team_side'>
                <div class='runes'>
                    <img loading='lazy' alt='' src='$dd_img/$keystone_img' class='keystone'>
                    <img loading='lazy' alt='' src='$dd_img/$sec_rune_img' class='sec-rune'>
                </div>
                <div class='summoner-spells'>
                    <img loading='lazy' alt='' src='$dd_img/spell/$summ1_img.webp' class='summ-spell'>
                    <img loading='lazy' alt='' src='$dd_img/spell/$summ2_img.webp' class='summ-spell'>
                </div>
                <div class='champion'>
                    <img loading='lazy' alt='' src='$dd_img/champion/{$championId}.webp' class='champ'>
                    <div class='champ-lvl'>$champ_lvl</div>
                </div>
                <div class='summoner-name'>
                    <div>$summoner_name</div>";
			if (array_key_exists($puuid, $players_PUUID)) {
                if ($summoner_rank != NULL) {
					echo "
                    <div class='summ-rank'><img loading='lazy' class='rank-emblem-mini' src='/uniliga/ddragon/img/ranks/mini-crests/{$summoner_rank}.svg' alt=''> $summoner_rank_cap $summoner_rank_div</div>";
				}
			}
            echo "
                </div>
                <div class='player-stats'>
                    <div class='player-stats-wrapper'>
                        <span class='kills'><img loading='lazy' src='$kills_icon' class='stats kills' alt=''>$kills / $deaths / $assists</span>
                        <span class='CS'><img loading='lazy' src='$cs_icons' class='stats cs' alt=''>$cs</span>
                        <span class='gold'><img loading='lazy' src='$gold_icons' class='stats gold' alt=''>{$gold_1}.{$gold_2}k Gold</span>
                    </div>
                </div>
                <div class='items'>
                    <div class='items-wrapper'>
                        <img loading='lazy' src='$dd_img/item/{$item0}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item1}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item2}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item3}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item4}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item5}.webp' alt=''>
                        <img loading='lazy' src='$dd_img/item/{$item6}.webp' alt=''>
                    </div>
                </div>
            </div>";
            if ($p == 0) {
				echo "<div class='game-row-divider'></div>";
			}
		}
        echo "
        </div>";
	}
    echo "
        <div class='game-row bans'>
            <div class='bans-wrapper'>";
    foreach ($bans_blue as $ban) {
        echo "
                <span>
                    <img loading='lazy' src='$dd_img/champion/{$champions_by_key[$ban['championId']]}.webp' alt=''>
                    <i class='gg-block'></i>
                </span>";
    }
    echo "
            </div>
            <div class='game-row-divider'></div>
            <div class='bans-wrapper'>";
	foreach ($bans_red as $ban) {
		echo "
                <span>
                    <img loading='lazy' src='$dd_img/champion/{$champions_by_key[$ban['championId']]}.webp' alt=''>
                    <i class='gg-block'></i>
                </span>";
	}
    echo "
            </div>
        </div>
    </div>
    ";
}