<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include(dirname(__FILE__).'/../DB-info.php');
include(dirname(__FILE__).'/../php-functions/ddragon-update.php');

$type = $_SERVER["HTTP_TYPE"] ?? $_REQUEST["type"] ?? NULL;
if ($type == NULL) exit;

// returns array with source, target_dir and target_name of images
if ($type == "get_image_data") {
	$patch = $_SERVER["HTTP_PATCH"] ?? $_REQUEST["patch"] ?? NULL;
	$imagetype = $_SERVER["HTTP_IMAGETYPE"] ?? $_REQUEST["imagetype"] ?? NULL;
	if ($patch == NULL || $imagetype == NULL) exit();
	$result = [];
	if ($imagetype === "all") {
		$result = get_ddragon_img_data($patch, "champions");
		array_push($result, ...get_ddragon_img_data($patch, "items"));
		array_push($result, ...get_ddragon_img_data($patch, "summoners"));
		array_push($result, ...get_ddragon_img_data($patch, "runes"));
	}
	echo json_encode($result);
}

// downloads given image, converts to webp and saves it to given dir
if ($type == "download_dd_img") {
	$source = $_SERVER["HTTP_IMGSOURCE"] ?? NULL;
	$target_dir = $_SERVER["HTTP_TARGETDIR"] ?? NULL;
	$target_name = $_SERVER["HTTP_TARGETNAME"] ?? NULL;
	if ($source == NULL || $target_dir == NULL || $target_name == NULL || str_contains("..",$target_dir) || str_contains("..",$target_name)) exit();
	$target_dir = realpath(dirname(__FILE__) . "/../ddragon"). "/" . $target_dir;
	echo $target_dir;
	//$saved_location = download_convert_dd_img($source, $target_dir, $target_name);
	//echo $saved_location;
}

// syncs local patch directories with database
if ($type == "sync_patches_to_db") {
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$result = sync_local_patches_to_db($dbcn);
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

// gets html for add-patch-popup
if ($type == "add-patch-view") {
	$view = $_SERVER["HTTP_VIEW"] ?? NULL;
	$limit = $_SERVER["HTTP_LIMIT"] ?? NULL;
	$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
	$result = create_add_patch_view($dbcn,$view,$limit);
	echo $result;
	$dbcn->close();
}