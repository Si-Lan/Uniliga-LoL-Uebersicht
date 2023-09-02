<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../php-functions/ddragon-update.php');

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

// returns array with source, target_dir and target_name of images
if ($type == "get_image_data") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	$imagetype = $_SERVER["HTTP_IMAGETYPE"] ?? NULL;
	$only_missing = filter_var($_SERVER["HTTP_ONLYMISSING"] ?? TRUE, FILTER_VALIDATE_BOOLEAN);
	if ($patch == NULL || $imagetype == NULL) exit();
	$result = [];
	if ($imagetype === "all") {
		$result = get_ddragon_img_data($patch, "champions",$only_missing);
		array_push($result, ...get_ddragon_img_data($patch, "items",$only_missing));
		array_push($result, ...get_ddragon_img_data($patch, "summoners",$only_missing));
		array_push($result, ...get_ddragon_img_data($patch, "runes",$only_missing));
	} else {
		$result = get_ddragon_img_data($patch, $imagetype,$only_missing);
	}
	echo json_encode($result);
}

// downloads given image, converts to webp and saves it to given dir
if ($type == "download_dd_img") {
	$source = $_SERVER["HTTP_IMGSOURCE"] ?? NULL;
	$target_dir = $_SERVER["HTTP_TARGETDIR"] ?? NULL;
	$target_name = $_SERVER["HTTP_TARGETNAME"] ?? NULL;
	$force_overwrite = filter_var(($_SERVER["HTTP_FORCEDOWNLOAD"] ?? FALSE), FILTER_VALIDATE_BOOLEAN);
	if ($source == NULL || $target_dir == NULL || $target_name == NULL || str_contains("..",$target_dir) || str_contains("..",$target_name)) exit();
	$target_dir = realpath(dirname(__FILE__) . "/../ddragon"). "/" . $target_dir;
	//echo $target_dir;
	$saved_location = download_convert_dd_img($source, $target_dir, $target_name, $force_overwrite);
	echo $saved_location;
}

// syncs local patch directories with database
if ($type == "sync_patches_to_db") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$result = sync_local_patches_to_db($dbcn,$patch);
	echo json_encode($result);
	$dbcn->close();
}

// gets jsons for a patch
if ($type == "jsons_for_patch") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$result = get_jsons_for_patch($dbcn,$patch,TRUE);
	echo $result;
	$dbcn->close();
}

// adds directory and DB-entry for new patch
if ($type == "add_new_patch") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$result = add_new_patch($dbcn,$patch);
	echo $result;
	$dbcn->close();
}

if ($type == "delete_ddragon_pngs") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	delete_ddragon_pngs($patch);
	echo 1;
}

// gets html for add-patch-popup
if ($type == "add-patch-view") {
	$view = $_SERVER["HTTP_VIEW"] ?? NULL;
	$limit = $_SERVER["HTTP_LIMIT"] ?? NULL;
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$result = create_add_patch_view($dbcn,$view,$limit);
	echo $result;
	$dbcn->close();
}

//gets html for patch-rows
if ($type == "get-patch-rows") {
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	echo generate_patch_rows($dbcn);
	$dbcn->close();
}
if ($type == "get-patch-row") {
	$patch = $_SERVER["HTTP_PATCH"] ?? NULL;
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$patch_data = $dbcn->execute_query("SELECT * FROM local_patches WHERE Patch = ?", [$patch])->fetch_assoc();
	echo create_patch_row($patch_data);
	$dbcn->close();
}