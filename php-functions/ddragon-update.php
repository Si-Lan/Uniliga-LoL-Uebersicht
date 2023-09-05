<?php
$ddragon_dir = dirname(__FILE__) . "/../ddragon";

function get_patches():array {
	$patches = file_get_contents("https://ddragon.leagueoflegends.com/api/versions.json");
	return json_decode($patches);
}

function sync_local_patches_to_db(mysqli $dbcn, ?string $given_patch=NULL):array {
	$result = [
		"deleted" => 0,
		"updated" => [],
		"added" => 0,
	];
	global $ddragon_dir;
	$patchDBs = get_local_patches($dbcn);
	$patchDirs = get_local_patches();
	foreach ($patchDBs as $index=>$patch) {
		if (!in_array($patch,$patchDirs)) {
			$dbcn->execute_query("DELETE FROM local_patches WHERE Patch = ?", [$patch]);
			$result["deleted"]++;
		}
	}
	foreach ($patchDirs as $patch) {
		if ($given_patch != NULL && $given_patch != $patch) continue;
		$changed = FALSE;
		$added = FALSE;
		$patchresult = [
			"patch" => $patch,
			"data" => 0,
			"champions" => 0,
			"items" => 0,
			"spells" => 0,
			"runes" => 0,
		];
		//echo "checking Patch $patch </br>";
		$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE Patch = ?", [$patch])->fetch_assoc();
		if ($patch_data == NULL) {
			$dbcn->execute_query("INSERT INTO local_patches (Patch) VALUES (?)", [$patch]);
			$added = TRUE;
			$result["added"]++;
		}
		if (file_exists($ddragon_dir."/$patch/data/champion.json") && file_exists($ddragon_dir."/$patch/data/item.json") && file_exists($ddragon_dir."/$patch/data/runesReforged.json") && file_exists($ddragon_dir."/$patch/data/summoner.json")) {
			$dbcn->execute_query("UPDATE local_patches SET data = TRUE WHERE Patch = ?", [$patch]);
			if (!$added && !$patch_data["data"]) {
				$patchresult["data"] = 1;
				$changed = TRUE;
			}
			//echo "- all jsons exist <br>";
		} else {
			$dbcn->execute_query("UPDATE local_patches SET data = FALSE WHERE Patch = ?", [$patch]);
			if (!$added && $patch_data["data"]) {
				$patchresult["data"] = -1;
				$changed = TRUE;
				$result["updated"][] = $patchresult;
			}
			//echo "- some jsons missing <br>";
			continue;
		}

		$champs_webp = TRUE;
		$champions = json_decode(file_get_contents($ddragon_dir."/$patch/data/champion.json"),true);
		foreach ($champions["data"] as $champion) {
			if (!file_exists($ddragon_dir."/$patch/img/champion/".$champion["id"].".webp")) {
				$dbcn->execute_query("UPDATE local_patches SET champion_webp = FALSE WHERE Patch = ?", [$patch]);
				if (!$added && $patch_data["champion_webp"]) {
					$patchresult["champions"] = -1;
					$changed = TRUE;
				}
				$champs_webp = FALSE;
				break;
			}
		}
		if ($champs_webp) {
			$dbcn->execute_query("UPDATE local_patches SET champion_webp = TRUE WHERE Patch = ?", [$patch]);
			if (!$added && !$patch_data["champion_webp"]) {
				$patchresult["champions"] = 1;
				$changed = TRUE;
			}
			//echo "- all champion images exist<br>";
		}

		$item_webp = TRUE;
		$items = json_decode(file_get_contents($ddragon_dir."/$patch/data/item.json"),true);
		foreach ($items["data"] as $item_id=>$item) {
			if (!file_exists($ddragon_dir."/$patch/img/item/".$item_id.".webp")) {
				$dbcn->execute_query("UPDATE local_patches SET item_webp = FALSE WHERE Patch = ?", [$patch]);
				if (!$added && $patch_data["item_webp"]) {
					$patchresult["items"] = -1;
					$changed = TRUE;
				}
				$item_webp = FALSE;
				break;
			}
		}
		if ($item_webp) {
			$dbcn->execute_query("UPDATE local_patches SET item_webp = TRUE WHERE Patch = ?", [$patch]);
			if (!$added && !$patch_data["item_webp"]) {
				$patchresult["items"] = 1;
				$changed = TRUE;
			}
			//echo "- all item images exist<br>";
		}

		$summoner_webp = TRUE;
		$summoners = json_decode(file_get_contents($ddragon_dir."/$patch/data/summoner.json"),true);
		foreach ($summoners["data"] as $summoner_id=>$summoner) {
			if (!file_exists($ddragon_dir."/$patch/img/spell/".$summoner_id.".webp")) {
				$dbcn->execute_query("UPDATE local_patches SET spell_webp = FALSE WHERE Patch = ?", [$patch]);
				if (!$added && $patch_data["spell_webp"]) {
					$patchresult["spells"] = -1;
					$changed = TRUE;
				}
				$summoner_webp = FALSE;
				break;
			}
		}
		if ($summoner_webp) {
			$dbcn->execute_query("UPDATE local_patches SET spell_webp = TRUE WHERE Patch = ?", [$patch]);
			if (!$added && !$patch_data["spell_webp"]) {
				$patchresult["spells"] = 1;
				$changed = TRUE;
			}
			//echo "- all summoner spell images exist<br>";
		}

		$runes_webp = TRUE;
		$runes = json_decode(file_get_contents($ddragon_dir."/$patch/data/runesReforged.json"),true);
		foreach ($runes as $runetree) {
			$tree_icon = explode(".",$runetree["icon"])[0] . ".webp";
			if (!file_exists($ddragon_dir."/$patch/img/".$tree_icon)) {
				$dbcn->execute_query("UPDATE local_patches SET runes_webp = FALSE WHERE Patch = ?", [$patch]);
				if (!$added && $patch_data["runes_webp"]) {
					$patchresult["runes"] = -1;
					$changed = TRUE;
				}
				$runes_webp = FALSE;
				break;
			}
			foreach ($runetree["slots"][0]["runes"] as $keystone) {
				$rune_icon = explode(".",$keystone["icon"])[0] . ".webp";
				if (!file_exists($ddragon_dir."/$patch/img/".$rune_icon)) {
					$dbcn->execute_query("UPDATE local_patches SET runes_webp = FALSE WHERE Patch = ?", [$patch]);
					if (!$added && $patch_data["runes_webp"]) {
						$patchresult["runes"] = -1;
						$changed = TRUE;
					}
					$runes_webp = FALSE;
					break;
				}
			}
		}
		if ($runes_webp) {
			$dbcn->execute_query("UPDATE local_patches SET runes_webp = TRUE WHERE Patch = ?", [$patch]);
			if (!$added && !$patch_data["runes_webp"]) {
				$patchresult["runes"] = 1;
				$changed = TRUE;
			}
			//echo "- all rune images exist<br>";
		}
		if ($changed) {
			$result["updated"][] = $patchresult;
		}
	}
	return $result;
}

function recursive_png_delete($path):void {
	$dir = new DirectoryIterator($path);
	foreach ($dir as $file) {
		if ($file->isDot()) {
			continue;
		}
		if($file->isFile()) {
			if ($file->getExtension() == "png") {
				unlink($file->getPathname());
			}
		}
		if ($file->isDir()) {
			recursive_png_delete($file->getPathname());
		}
	}
}

function delete_ddragon_pngs(string $patch):void {
	global $ddragon_dir;
	recursive_png_delete($ddragon_dir."/$patch/img");
}

function get_local_patches(mysqli $dbcn = NULL):array {
	if ($dbcn == NULL) {
		$patches = array();
		$dir = new DirectoryIterator(dirname(__FILE__) . "/../ddragon");
		foreach ($dir as $fileinfo) {
			if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
				$patches[] = $fileinfo->getFilename();
			}
		}
	} else {
		$patches_nested = $dbcn->execute_query("SELECT Patch FROM local_patches")->fetch_all();
		$patches = array();
		foreach ($patches_nested as $patch) {
			$patches[] = $patch[0];
		}
	}
	usort($patches, "version_compare");
	return array_reverse($patches);
}

function get_new_patches(mysqli $dbcn = NULL):array {
	$last_local_patch = get_local_patches($dbcn)[0] ?? NULL;
	$all_patches = get_patches();
	$lP_index = array_search($last_local_patch,$all_patches);
	return array_slice($all_patches,0,$lP_index);
}

function get_missing_patches(mysqli $dbcn = NULL):array {
	// implementation hier ist nicht effizient, aber funktioniert
	$local_patches = get_local_patches($dbcn);
	$first_local_patch = end($local_patches);
	$last_local_patch = $local_patches[0] ?? NULL;
	$all_patches = get_patches();
	$fP_index = array_search($first_local_patch,$all_patches);
	$lP_index = array_search($last_local_patch,$all_patches);
	$all_patches = array_slice($all_patches,$lP_index,$fP_index-$lP_index+1);
	$missing_patches = [];
	$patches_between = [0];
	foreach ($all_patches as $patch) {
		if (!in_array($patch,$local_patches)) {
			$missing_patches[] = $patch;
			$patches_between[] = 0;
		} else {
			$patches_between[count($patches_between)-1]++;
		}
	}
	return [$missing_patches, $patches_between];
}

function get_old_patches(int $limit = 10, mysqli $dbcn = NULL):array {
	$local_patches = get_local_patches($dbcn);
	$first_local_patch = end($local_patches);
	$all_patches = get_patches();
	$fP_index = array_search($first_local_patch,$all_patches);
	return array_slice($all_patches,$fP_index+1,$limit);
}

function add_new_patch(mysqli $dbcn, string $patch):int {
	$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE Patch = ?", [$patch])->fetch_assoc();
	if ($patch_data != NULL) {
		return 0;
	}
	if (!file_exists(dirname(__FILE__)."/../ddragon/".$patch)) mkdir(dirname(__FILE__)."/../ddragon/".$patch);
	$dbcn->execute_query("INSERT INTO local_patches (Patch) VALUES (?)", [$patch]);
	return 1;
}

function get_jsons_for_patch(mysqli $dbcn, string $patch, bool $force = FALSE):int {
	global $ddragon_dir;
	$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE Patch = ?", [$patch])->fetch_assoc();
	if ($patch_data == NULL) {
		add_new_patch($dbcn, $patch);
	}
	$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE Patch = ?", [$patch])->fetch_assoc();
	if (!$patch_data["data"] || $force) {
		if (!file_exists("$ddragon_dir/$patch/data")) {
			mkdir("$ddragon_dir/$patch/data");
		}
		$champion = file_get_contents("https://ddragon.leagueoflegends.com/cdn/".$patch."/data/en_US/champion.json");
		$item = file_get_contents("https://ddragon.leagueoflegends.com/cdn/".$patch."/data/en_US/item.json");
		$summoner = file_get_contents("https://ddragon.leagueoflegends.com/cdn/".$patch."/data/en_US/summoner.json");
		$runesReforged = file_get_contents("https://ddragon.leagueoflegends.com/cdn/".$patch."/data/en_US/runesReforged.json");
		file_put_contents("$ddragon_dir/$patch/data/champion.json",$champion);
		file_put_contents("$ddragon_dir/$patch/data/item.json",$item);
		file_put_contents("$ddragon_dir/$patch/data/summoner.json",$summoner);
		file_put_contents("$ddragon_dir/$patch/data/runesReforged.json",$runesReforged);
		$dbcn->execute_query("UPDATE local_patches SET data = TRUE WHERE Patch = ?", [$patch]);
		return 1;
	} else {
		return 0;
	}
}

function get_ddragon_img_data(string $patch, string $type, bool $only_missing = TRUE):array {
	global $ddragon_dir;
	$res = array();

	if ($type == "champions") {
		if (!file_exists("$ddragon_dir/$patch/data/champion.json")) return $res;
		$champions = json_decode(file_get_contents("$ddragon_dir/$patch/data/champion.json"),true);
		foreach ($champions["data"] as $champion) {
			$image_name = $champion["id"];
			if (file_exists("$ddragon_dir/$patch/img/champion/$image_name.webp") && $only_missing) continue;
			$res[] = array(
				"source" => "https://ddragon.leagueoflegends.com/cdn/$patch/img/champion/".$champion["image"]["full"],
				"target_dir" => "$patch/img/champion",
				"target_name" => $image_name,
			);
		}
	}
	if ($type == "items") {
		if (!file_exists("$ddragon_dir/$patch/data/item.json")) return $res;
		$items = json_decode(file_get_contents($ddragon_dir."/$patch/data/item.json"),true);
		foreach ($items["data"] as $item_id=>$item) {
			$image_name = $item_id;
			if (file_exists("$ddragon_dir/$patch/img/item/$image_name.webp") && $only_missing) continue;
			$res[] = array(
				"source" => "https://ddragon.leagueoflegends.com/cdn/$patch/img/item/".$item["image"]["full"],
				"target_dir" => "$patch/img/item",
				"target_name" => $image_name,
			);
		}
	}
	if ($type == "summoners") {
		if (!file_exists("$ddragon_dir/$patch/data/summoner.json")) return $res;
		$summoners = json_decode(file_get_contents($ddragon_dir."/$patch/data/summoner.json"),true);
		foreach ($summoners["data"] as $summoner_id=>$summoner) {
			$image_name = $summoner_id;
			if (file_exists("$ddragon_dir/$patch/img/spell/$image_name.webp") && $only_missing) continue;
			$res[] = array(
				"source" => "https://ddragon.leagueoflegends.com/cdn/$patch/img/spell/".$summoner["image"]["full"],
				"target_dir" => "$patch/img/spell",
				"target_name" => $image_name,
			);
		}
	}
	if ($type == "runes") {
		if (!file_exists("$ddragon_dir/$patch/data/runesReforged.json")) return $res;
		$runes = json_decode(file_get_contents($ddragon_dir."/$patch/data/runesReforged.json"),true);
		foreach ($runes as $runetree) {
			$runetree_subdir = implode("/",explode("/",$runetree["icon"],-1));
			$image_name = explode("/", $runetree["icon"]);
			$image_name = explode(".", end($image_name))[0];
			if (!file_exists("$ddragon_dir/$patch/img/$runetree_subdir/$image_name.webp") || !$only_missing) {
				$res[] = array(
					"source" => "https://ddragon.leagueoflegends.com/cdn/img/".$runetree["icon"],
					"target_dir" => "$patch/img/$runetree_subdir",
					"target_name" => $image_name,
				);
			}
			foreach ($runetree["slots"][0]["runes"] as $keystone){
				$keystone_subdir = implode("/",explode("/",$keystone["icon"],-1));
				$image_name = explode("/", $keystone["icon"]);
				$image_name = explode(".", end($image_name))[0];
				if (file_exists("$ddragon_dir/$patch/img/$keystone_subdir/$image_name.webp") && $only_missing) continue;
				$res[] = array(
					"source" => "https://ddragon.leagueoflegends.com/cdn/img/".$keystone["icon"],
					"target_dir" => "$patch/img/$keystone_subdir",
					"target_name" => $image_name,
				);
			}
		}
	}
	return $res;
}

function download_convert_dd_img(string $source, string $target_dir, string $target_name, bool $overwrite=FALSE):string {
	$target = "$target_dir/$target_name.webp";
	if (file_exists($target) && !$overwrite) return "exists $target";
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}
	$img = imagecreatefrompng($source);
	imagepalettetotruecolor($img);
	imagealphablending($img, true);
	imagesavealpha($img, true);
	imagewebp($img, $target, 50);
	imagedestroy($img);
	return "get $target";
}


function create_add_patch_view(mysqli $dbcn, string $view="new", int $limit=10):string {
	$result = "";
	$patches = [];
	if ($view == "new") {
		$patches = get_new_patches($dbcn);
	} elseif ($view == "missing") {
		$patches_and_between = get_missing_patches($dbcn);
		$patches = $patches_and_between[0];
		$patches_between = $patches_and_between[1];
	} elseif ($view == "old") {
		$patches = get_old_patches($limit,$dbcn);
	}

	foreach ($patches as $i=>$patch) {
		if ($view == "missing" && $patches_between[$i] != 0) {
			$text = ($i == 0) ? "neuere lokale Patches" : "lokale Patches dazwischen";
			$result .= "<span>{$patches_between[$i]} $text</span>";
		}
		$result .= "<div class='add-patches-row'>
						<span class='patch-name'>{$patch}</span>
						<button type='button' class='add_patch' data-patch='$patch'><span>Hinzufügen</span></button>
					</div>";
	}
	if ($view == "missing" && $patches_between[count($patches_between)-1] != 0 && count($patches) != 0) $result .= "<span>{$patches_between[count($patches_between)-1]} ältere lokale Patches</span>";

	return $result;
}

function generate_patch_rows(mysqli $dbcn):string {
	$patches = $dbcn->execute_query("SELECT * FROM local_patches")->fetch_all(MYSQLI_ASSOC);
	usort($patches, function ($a,$b) {
		return version_compare($b["Patch"],$a["Patch"]);
	});
	$result = "";
	foreach ($patches as $patch) {
		$result .= create_patch_row($patch);
	}
	return $result;
}

function create_patch_row(array $patch_data):string {
	$json_status = ($patch_data["data"]) ? "true" : "false";
	$image_status = ($patch_data["champion_webp"] && $patch_data["item_webp"] && $patch_data["spell_webp"] && $patch_data["runes_webp"]) ? "true" : "false";
	$champion_status = ($patch_data["champion_webp"]) ? "true" : "false";
	$item_status = ($patch_data["item_webp"]) ? "true" : "false";
	$spell_status = ($patch_data["spell_webp"]) ? "true" : "false";
	$runes_status = ($patch_data["runes_webp"]) ? "true" : "false";
	return "
		<div class='patch-row' data-patch='{$patch_data["Patch"]}'>
			<span class='patch-name'>{$patch_data["Patch"]}</span>
			<div class='patch-updatebutton-wrapper'>
				<div class='patchdata-status json' data-status='$json_status' data-patch='{$patch_data["Patch"]}'></div>
				<button type='button' class='patch-update json' data-patch='{$patch_data["Patch"]}'><span>JSONs</span></button>
			</div>
			<div class='patch-updatebutton-wrapper'>
				<div class='patchdata-status all-img' data-status='$image_status' data-patch='{$patch_data["Patch"]}'></div>
				<button type='button' class='patch-update all-img' data-getimg='all' data-patch='{$patch_data["Patch"]}'><span>Bilder</span></button>
			</div>
			<button type='button' class='patch-more-options' data-patch='{$patch_data["Patch"]}'><span>einzelne Bilder</span></button>
			<dialog class='patch-more-popup dismissable-popup' data-patch='{$patch_data["Patch"]}'>
				<div class='dialog-content'>
					<span class='patch-name'>Patch {$patch_data["Patch"]}</span>
					<div class='patch-row'>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status champion-img' data-status='$champion_status' data-patch='{$patch_data["Patch"]}'></div>
							<button type='button' class='patch-update' data-getimg='champions' data-patch='{$patch_data["Patch"]}'><span>Champions</span></button>
						</div>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status item-img' data-status='$item_status' data-patch='{$patch_data["Patch"]}'></div>
							<button type='button' class='patch-update' data-getimg='items' data-patch='{$patch_data["Patch"]}'><span>Items</span></button>
						</div>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status spell-img' data-status='$spell_status' data-patch='{$patch_data["Patch"]}'></div>
							<button type='button' class='patch-update' data-getimg='summoners' data-patch='{$patch_data["Patch"]}'><span>Summoners</span></button>
						</div>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status runes-img' data-status='$runes_status' data-patch='{$patch_data["Patch"]}'></div>
							<button type='button' class='patch-update' data-getimg='runes' data-patch='{$patch_data["Patch"]}'><span>Runes</span></button>
						</div>
						<button type='button' class='patch-remove-pngs'><span>alte PNGs löschen</span></button>
					</div>
				</div>
			</dialog>
		</div>";
}