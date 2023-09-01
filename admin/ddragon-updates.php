<?php
$dbservername = $dbdatabase = $dbusername = $dbpassword = $dbport = NULL;
include("../DB-info.php");
include("../fe-functions.php");
include("../php-functions/ddragon-update.php");

$dbcn = new mysqli($dbservername, $dbusername, $dbpassword, $dbdatabase, $dbport);
?>

<!DOCTYPE html>
<html lang="de">
<head>
	<?php
	create_html_head_elements("admin_dd");
	?>
</head>
<body>

<?php
create_header($dbcn, "admin_dd");

if ($dbcn->connect_error) exit();

$patches = $dbcn->execute_query("SELECT * FROM local_patches")->fetch_all(MYSQLI_ASSOC);
usort($patches, function ($a,$b) {
	return version_compare($b["Patch"],$a["Patch"]);
});
?>
<div class="patch-display">
	<dialog class='patch-result-popup dismissable-popup'>
		<div class='dialog-content'>

		</div>
	</dialog>
	<div class="patch-table">
		<div class="patch-header">
			<div>Patches</div>
			<button type="button" class="open_add_patch_popup">Patch hinzufügen</button>
			<button type="button" class="sync_patches">Patches synchronisieren</button>
			<dialog class="add-patch-popup dismissable-popup">
				<div class="dialog-content">
					<?php
					echo create_dropdown("get-patches",["new"=>"neue Patches","missing"=>"fehlende Patches","old"=>"alte Patches"]);
					?>
					<div class='popup-loading-indicator' style="display: none"></div>
					<div class='add-patches-display'>
						<?php
						echo create_add_patch_view($dbcn, "new");
						?>
					</div>
				</div>
			</dialog>
		</div>
		<?php
		foreach ($patches as $patch) {
			$json_status = ($patch["data"]) ? "true" : "false";
			$image_status = ($patch["champion_webp"] && $patch["item_webp"] && $patch["spell_webp"] && $patch["runes_webp"]) ? "true" : "false";
			$champion_status = ($patch["champion_webp"]) ? "true" : "false";
			$item_status = ($patch["item_webp"]) ? "true" : "false";
			$spell_status = ($patch["spell_webp"]) ? "true" : "false";
			$runes_status = ($patch["runes_webp"]) ? "true" : "false";
			echo "
		<div class='patch-row'>
			<span class='patch-name'>{$patch["Patch"]}</span>
			<div class='patch-updatebutton-wrapper'>
				<div class='patchdata-status json' data-status='$json_status' data-patch='{$patch["Patch"]}'></div>
				<button type='button' class='patch-update json' data-patch='{$patch["Patch"]}'>JSONs</button>
			</div>
			<div class='patch-updatebutton-wrapper'>
				<div class='patchdata-status all-img' data-status='$image_status' data-patch='{$patch["Patch"]}'></div>
				<button type='button' class='patch-update all-img' data-patch='{$patch["Patch"]}'>Bilder</button>
			</div>
			<button type='button' class='patch-more-options' data-patch='{$patch["Patch"]}'>einzelne Bilder</button>
			<dialog class='patch-more-popup dismissable-popup' data-patch='{$patch["Patch"]}'>
				<div class='dialog-content'>
					<span class='patch-name'>Patch {$patch["Patch"]}</span>
					<div class='patch-row'>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status champion-img' data-status='$champion_status' data-patch='{$patch["Patch"]}'></div>
							<button type='button' class='patch-update'>Champions</button>
						</div>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status item-img' data-status='$item_status' data-patch='{$patch["Patch"]}'></div>
							<button type='button' class='patch-update'>Items</button>
						</div>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status spell-img' data-status='$spell_status' data-patch='{$patch["Patch"]}'></div>
							<button type='button' class='patch-update'>Summoners</button>
						</div>
						<div class='patch-updatebutton-wrapper'>
							<div class='patchdata-status runes-img' data-status='$runes_status' data-patch='{$patch["Patch"]}'></div>
							<button type='button' class='patch-update'>Runes</button>
						</div>
						<button type='button' class='patch-remove-pngs'>alte PNGs löschen</button>
					</div>
				</div>
			</dialog>
		</div>";
		}
		?>
	</div>
</div>

</body>