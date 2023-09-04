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
			<button type="button" class="open_add_patch_popup"><span>Patch hinzufügen</span></button>
			<button type="button" class="sync_patches"><span>Patches synchronisieren</span></button>
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
		<div class="get-patch-options">
			<input type="checkbox" id="force-overwrite-patch-img" name="force-overwrite-patch-img">
			<label for="force-overwrite-patch-img">Alle Bilder herunterladen und überschreiben erzwingen</label>
		</div>
		<?php
		echo generate_patch_rows($dbcn);
		?>
	</div>
</div>

</body>