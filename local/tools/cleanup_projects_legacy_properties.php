<?php
/**
 * Удаляет legacy-свойства деталки из инфоблока "Проекты":
 * - BEN1..BEN8 (преимущества старой фиксированной схемы)
 * - CONS1..CONS6 (ход строительства старой фиксированной схемы)
 *
 * CLI:
 *   php local/tools/cleanup_projects_legacy_properties.php --iblock-id=10 --dry-run=1
 *   php local/tools/cleanup_projects_legacy_properties.php --iblock-id=10 --dry-run=0
 */

@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
	? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
	: rtrim(dirname(__DIR__, 2), "/");

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

if (PHP_SAPI === "cli") {
	$options = getopt("", array(
		"iblock-id::",
		"iblock-code::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/cleanup_projects_legacy_properties.php [--iblock-id=10] [--iblock-code=projects] [--dry-run=1]" . PHP_EOL;
		exit(0);
	}

	foreach ($options as $key => $value) {
		$_REQUEST[str_replace("-", "_", $key)] = $value;
	}
}

$prologPath = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!is_file($prologPath)) {
	echo "Bitrix bootstrap not found: " . $prologPath . PHP_EOL;
	exit(1);
}

require $prologPath;

if (!class_exists("\\Bitrix\\Main\\Loader") || !\Bitrix\Main\Loader::includeModule("iblock")) {
	echo "Failed to load iblock module" . PHP_EOL;
	exit(1);
}

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1";
$iblockId = isset($_REQUEST["iblock_id"]) ? (int)$_REQUEST["iblock_id"] : 0;
$iblockCode = isset($_REQUEST["iblock_code"]) && (string)$_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "projects";

if ($iblockId <= 0) {
	$iblockRes = CIBlock::GetList(array(), array("=CODE" => $iblockCode), false);
	if ($iblock = $iblockRes->Fetch()) {
		$iblockId = (int)$iblock["ID"];
	}
}

if ($iblockId <= 0) {
	echo "Projects iblock not found. Pass --iblock-id explicitly." . PHP_EOL;
	exit(2);
}

$legacyCodes = array();
for ($i = 1; $i <= 8; $i++) {
	$legacyCodes[] = "BEN" . $i . "_LABEL";
	$legacyCodes[] = "BEN" . $i . "_TITLE";
	$legacyCodes[] = "BEN" . $i . "_TEXT";
	$legacyCodes[] = "BEN" . $i . "_IMAGE";
}
for ($i = 1; $i <= 6; $i++) {
	$legacyCodes[] = "CONS" . $i . "_TITLE";
	$legacyCodes[] = "CONS" . $i . "_DATE";
	$legacyCodes[] = "CONS" . $i . "_TEXT";
	$legacyCodes[] = "CONS" . $i . "_IMAGE";
	$legacyCodes[] = "CONS" . $i . "_GALLERY";
}

echo "IBlock ID: " . $iblockId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$deleted = 0;
$missing = 0;
$errors = array();

foreach ($legacyCodes as $code) {
	$propRes = CIBlockProperty::GetList(
		array("ID" => "ASC"),
		array(
			"IBLOCK_ID" => $iblockId,
			"CODE" => $code,
		)
	);

	$property = $propRes->Fetch();
	if (!$property) {
		$missing++;
		echo "[MISS] " . $code . PHP_EOL;
		continue;
	}

	$propId = (int)$property["ID"];
	if ($dryRun) {
		$deleted++;
		echo "[DEL] " . $code . " (ID=" . $propId . ")" . PHP_EOL;
		continue;
	}

	$ibp = new CIBlockProperty();
	if (!$ibp->Delete($propId)) {
		$errors[] = $code . " (ID=" . $propId . ")";
		echo "[ERR] " . $code . " (ID=" . $propId . ")" . PHP_EOL;
		continue;
	}

	$deleted++;
	echo "[OK] Deleted " . $code . " (ID=" . $propId . ")" . PHP_EOL;
}

echo PHP_EOL . "Done. To delete: " . $deleted . ", Missing: " . $missing . PHP_EOL;
if (!empty($errors)) {
	echo "Failed to delete:" . PHP_EOL;
	foreach ($errors as $error) {
		echo " - " . $error . PHP_EOL;
	}
	exit(3);
}

exit(0);
