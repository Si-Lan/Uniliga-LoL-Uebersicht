<?php
$patches = [];
$dir = new DirectoryIterator(dirname(__FILE__) . "/../ddragon");
foreach ($dir as $fileinfo) {
	if (!$fileinfo->isDot() && $fileinfo->getFilename() != "img") {
		$patches[] = $fileinfo->getFilename();
	}
}
sort($patches);
foreach ($patches as $patch) {
	echo "Patch ". $patch .":<br>";
	convert_ddragon_webp($patch,"champion");
	convert_ddragon_webp($patch,"item");
	convert_ddragon_webp($patch,"runes");
	convert_ddragon_webp($patch,"spell");
	//convert_ddragon_webp($patch,"ranks");
	echo "<br>";
}

function convert_ddragon_webp($patch,$type) {
	if ($type == "champion") {
		$dirpath = dirname(__FILE__)."/../ddragon/$patch/img/champion";
	} elseif ($type == "item") {
		$dirpath = dirname(__FILE__)."/../ddragon/$patch/img/item";
	} elseif ($type == "runes") {
		$dirpath = dirname(__FILE__)."/../ddragon/$patch/img/perk-images";
	} elseif ($type == "spell") {
		$dirpath = dirname(__FILE__)."/../ddragon/$patch/img/spell";
	} elseif ($type == "ranks") {
		$dirpath = dirname(__FILE__) . "/../ddragon/img/ranks";
	} else {
		return;
	}
	$dir = new DirectoryIterator($dirpath);
	$webp_names = [];
	$pngs = [];
	$converted_in_sub = 0;
	foreach ($dir as $file) {
		if ($file->isDot()) {
			continue;
		}
		if ($file->isDir()) {
			$sub_path = $file->getPathname();
			$sub_name = $file->getFilename();
			$converted_in_sub += sub_dir_convert($sub_path,$sub_name);
			continue;
		}
		if($file->isFile()) {
			if ($file->getExtension() == "png") {
				$pngs[] = array("Name"=>$file->getBasename(".png"),"Path"=>$file->getRealPath());
			} elseif ($file->getExtension() == "webp") {
				$webp_names[] = $file->getBasename(".webp");
			}
		}
	}
	$pngs_to_convert = [];
	foreach ($pngs as $png) {
		if (!in_array($png["Name"],$webp_names)) {
			$pngs_to_convert[] = $png;
		}
	}
	echo $type. ": ". count($pngs_to_convert)." PNG images to convert<br>";
	echo "----- ".$converted_in_sub." PNG images to convert in subdirectories<br>";

	foreach ($pngs_to_convert as $png) {
		echo $png["Path"]."<br>";
		$img = imagecreatefrompng($png["Path"]);
		imagepalettetotruecolor($img);
		imagealphablending($img, true);
		imagesavealpha($img, true);
		imagewebp($img, $dirpath."/".$png["Name"].".webp", 50);
		imagedestroy($img);
	}
}

function sub_dir_convert($path,$name) {
	$dir = new DirectoryIterator($path);
	$webp_names = [];
	$pngs = [];
	$converted_in_sub = 0;
	foreach ($dir as $file) {
		if ($file->isDot()) {
			continue;
		}
		if ($file->isDir()) {
			$sub_path = $file->getPathname();
			$sub_name = $name."/".$file->getFilename();
			$converted_in_sub += sub_dir_convert($sub_path,$sub_name);
			continue;
		}
		if($file->isFile()) {
			if ($file->getExtension() == "png") {
				$pngs[] = array("Name"=>$file->getBasename(".png"),"Path"=>$file->getRealPath());
			} elseif ($file->getExtension() == "webp") {
				$webp_names[] = $file->getBasename(".webp");
			}
		}
	}
	$pngs_to_convert = [];
	foreach ($pngs as $png) {
		if (!in_array($png["Name"],$webp_names)) {
			$pngs_to_convert[] = $png;
		}
	}
	$num_to_convert = count($pngs_to_convert);

	foreach ($pngs_to_convert as $png) {
		echo $png["Path"]."<br>";
		$img = imagecreatefrompng($png["Path"]);
		imagepalettetotruecolor($img);
		imagealphablending($img, true);
		imagesavealpha($img, true);
		imagewebp($img, $path."/".$png["Name"].".webp", 50);
		imagedestroy($img);
	}

	return $num_to_convert + $converted_in_sub;
}

/*
$image_url = "https://silence.lol/uniliga/ddragon/img/ranks/emblems/diamond.png";
$img = imagecreatefrompng($image_url);
imagepalettetotruecolor($img);
imagealphablending($img, true);
imagesavealpha($img, true);
imagewebp($img, "img/test.webp", 100);
imagedestroy($img);

$img = file_get_contents($image_url);
file_put_contents("img/test.png",file_get_contents($image_url));
*/