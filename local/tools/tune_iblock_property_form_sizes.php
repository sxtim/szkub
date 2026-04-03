<?php
/**
 * Идемпотентно настраивает размеры полей свойств ИБ в админке Bitrix.
 *
 * Задача:
 * - длинные текстовые свойства сделать шире/выше
 * - короткие строковые свойства сделать просто шире
 *
 * CLI:
 *   php local/tools/tune_iblock_property_form_sizes.php --dry-run=1
 *   php local/tools/tune_iblock_property_form_sizes.php --dry-run=0
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
		"codes::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
			echo "Usage: php local/tools/tune_iblock_property_form_sizes.php [--codes=apartments] [--dry-run=1]\n";
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

if (!class_exists("\\Bitrix\\Main\\Loader")) {
	echo "Bitrix Loader class is unavailable" . PHP_EOL;
	exit(1);
}

if (!\Bitrix\Main\Loader::includeModule("iblock")) {
	echo "Failed to load iblock module" . PHP_EOL;
	exit(1);
}

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
$codesRaw = isset($_REQUEST["codes"]) && $_REQUEST["codes"] !== "" ? (string)$_REQUEST["codes"] : "apartments";
$targetCodes = array_values(array_filter(array_map("trim", explode(",", $codesRaw)), static function ($item) {
	return $item !== "";
}));

echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;
echo "codes: " . implode(", ", $targetCodes) . PHP_EOL;

function findIblockByCodeForTune($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function findPropertyByCodeForTune($iblockId, $code)
{
	$res = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $iblockId, "CODE" => $code)
	);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function buildPropertyUpdateFields(array $current, array $override)
{
	$fields = array(
		"NAME" => (string)$current["NAME"],
		"ACTIVE" => (string)$current["ACTIVE"] !== "" ? (string)$current["ACTIVE"] : "Y",
		"SORT" => (int)$current["SORT"],
		"CODE" => (string)$current["CODE"],
		"PROPERTY_TYPE" => (string)$current["PROPERTY_TYPE"],
		"MULTIPLE" => (string)$current["MULTIPLE"] !== "" ? (string)$current["MULTIPLE"] : "N",
		"IS_REQUIRED" => (string)$current["IS_REQUIRED"] !== "" ? (string)$current["IS_REQUIRED"] : "N",
		"FILTRABLE" => (string)$current["FILTRABLE"] !== "" ? (string)$current["FILTRABLE"] : "N",
		"SEARCHABLE" => (string)$current["SEARCHABLE"] !== "" ? (string)$current["SEARCHABLE"] : "N",
		"MULTIPLE_CNT" => (string)$current["MULTIPLE_CNT"] !== "" ? (string)$current["MULTIPLE_CNT"] : "5",
		"WITH_DESCRIPTION" => (string)$current["WITH_DESCRIPTION"] !== "" ? (string)$current["WITH_DESCRIPTION"] : "N",
		"ROW_COUNT" => isset($override["ROW_COUNT"]) ? (int)$override["ROW_COUNT"] : (int)$current["ROW_COUNT"],
		"COL_COUNT" => isset($override["COL_COUNT"]) ? (int)$override["COL_COUNT"] : (int)$current["COL_COUNT"],
		"HINT" => isset($current["HINT"]) ? (string)$current["HINT"] : "",
	);

	$optionalFields = array(
		"DEFAULT_VALUE",
		"LINK_IBLOCK_ID",
		"FILE_TYPE",
		"LIST_TYPE",
		"USER_TYPE",
		"USER_TYPE_SETTINGS",
		"SMART_FILTER",
		"DISPLAY_TYPE",
		"DISPLAY_EXPANDED",
		"FILTER_HINT",
		"IBLOCK_ID",
	);

	foreach ($optionalFields as $fieldName) {
		if (array_key_exists($fieldName, $current)) {
			$fields[$fieldName] = $current[$fieldName];
		}
	}

	return $fields;
}

function syncPropertyFormSize($iblockCode, $propertyCode, array $sizeDef, $dryRun)
{
	$iblock = findIblockByCodeForTune($iblockCode);
	if (!is_array($iblock)) {
		echo "[WARN] IBlock not found: " . $iblockCode . PHP_EOL;
		return true;
	}

	$property = findPropertyByCodeForTune((int)$iblock["ID"], $propertyCode);
	if (!is_array($property)) {
		echo "[WARN] Property not found: " . $iblockCode . "." . $propertyCode . PHP_EOL;
		return true;
	}

	$currentRow = (int)$property["ROW_COUNT"];
	$currentCol = (int)$property["COL_COUNT"];
	$targetRow = isset($sizeDef["ROW_COUNT"]) ? (int)$sizeDef["ROW_COUNT"] : $currentRow;
	$targetCol = isset($sizeDef["COL_COUNT"]) ? (int)$sizeDef["COL_COUNT"] : $currentCol;

	if ($currentRow === $targetRow && $currentCol === $targetCol) {
		echo "[OK] " . $iblockCode . "." . $propertyCode . " row=" . $currentRow . " col=" . $currentCol . PHP_EOL;
		return true;
	}

	echo "[SYNC] " . $iblockCode . "." . $propertyCode . " row " . $currentRow . " -> " . $targetRow . ", col " . $currentCol . " -> " . $targetCol . PHP_EOL;
	if ($dryRun) {
		return true;
	}

	$updateFields = buildPropertyUpdateFields($property, array(
		"ROW_COUNT" => $targetRow,
		"COL_COUNT" => $targetCol,
	));

	$propertyApi = new CIBlockProperty();
	$ok = $propertyApi->Update((int)$property["ID"], $updateFields);
	if (!$ok) {
		echo "[ERROR] Failed to update " . $iblockCode . "." . $propertyCode . ": " . $propertyApi->LAST_ERROR . PHP_EOL;
		return false;
	}

	return true;
}

$matrix = array(
	"projects" => array(
		"CLASS_LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 30),
		"TAG_LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 40),
		"ADDRESS" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"DELIVERY_TEXT" => array("ROW_COUNT" => 1, "COL_COUNT" => 40),
		"SALE_COUNT_TEXT" => array("ROW_COUNT" => 1, "COL_COUNT" => 40),
		"PRICE_FROM_TEXT" => array("ROW_COUNT" => 1, "COL_COUNT" => 40),
		"ABOUT_TITLE_SUFFIX" => array("ROW_COUNT" => 1, "COL_COUNT" => 70),
		"ABOUT_TEXT_1" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"ABOUT_TEXT_2" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"ABOUT_TEXT_3" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"ABOUT_COMPANY_TEXT_1" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"ABOUT_COMPANY_TEXT_2" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"ABOUT_F1_LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F1_VALUE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F2_LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F2_VALUE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F3_LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F3_VALUE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F4_LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"ABOUT_F4_VALUE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"CONSTRUCTION_SUBTITLE" => array("ROW_COUNT" => 2, "COL_COUNT" => 80),
	),
	"apartments" => array(
		"CORPUS" => array("ROW_COUNT" => 1, "COL_COUNT" => 20),
		"ENTRANCE" => array("ROW_COUNT" => 1, "COL_COUNT" => 20),
		"APARTMENT_NUMBER" => array("ROW_COUNT" => 1, "COL_COUNT" => 20),
		"DISCOUNT_PERCENT" => array("ROW_COUNT" => 1, "COL_COUNT" => 20),
		"DISCOUNT_AMOUNT" => array("ROW_COUNT" => 1, "COL_COUNT" => 20),
		"BADGES" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"VIEW_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"WINDOW_SIDES" => array("ROW_COUNT" => 1, "COL_COUNT" => 50),
		"BALCONY_TYPE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"FEATURE_TAGS" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"PLAN_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"PLAN_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"PLAN_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"FLOOR_SLIDE_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"FLOOR_SLIDE_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"FLOOR_SLIDE_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"BUILDING_SLIDE_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"BUILDING_SLIDE_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"BUILDING_SLIDE_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"VIEW_SLIDE_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"VIEW_SLIDE_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"VIEW_SLIDE_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"RENDER_SLIDE_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"RENDER_SLIDE_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"RENDER_SLIDE_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
	),
	"about_company_page" => array(
		"HERO_TEXT_1" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"HERO_TEXT_2" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"AWARD_1_LOGO" => array("ROW_COUNT" => 1, "COL_COUNT" => 30),
		"AWARD_1_CAPTION" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"AWARD_2_LOGO" => array("ROW_COUNT" => 1, "COL_COUNT" => 30),
		"AWARD_2_CAPTION" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"AWARD_3_LOGO" => array("ROW_COUNT" => 1, "COL_COUNT" => 30),
		"AWARD_3_CAPTION" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"SOCIAL_INTRO_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"SOCIAL_INTRO_TEXT" => array("ROW_COUNT" => 6, "COL_COUNT" => 90),
		"SOCIAL_METRIC_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"SOCIAL_METRIC_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"SOCIAL_METRIC_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"SOCIAL_MATERIAL_TITLE" => array("ROW_COUNT" => 2, "COL_COUNT" => 80),
		"SOCIAL_MATERIAL_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"SOCIAL_MATERIAL_ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"SOCIAL_PROGRESS_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"SOCIAL_PROGRESS_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"PROJECTS_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"SALE_TITLE" => array("ROW_COUNT" => 1, "COL_COUNT" => 60),
		"SALE_DESCRIPTION" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
		"SALE_CONTACT_TITLE" => array("ROW_COUNT" => 2, "COL_COUNT" => 80),
		"SALE_CONTACT_TEXT" => array("ROW_COUNT" => 4, "COL_COUNT" => 90),
	),
	"about_company_social_gallery" => array(
		"LABEL" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"ALT" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
		"ITEM_HEIGHT" => array("ROW_COUNT" => 1, "COL_COUNT" => 20),
	),
	"extra_cards" => array(
		"LINK_URL" => array("ROW_COUNT" => 1, "COL_COUNT" => 80),
	),
);

foreach ($matrix as $iblockCode => $properties) {
	if (!in_array($iblockCode, $targetCodes, true)) {
		continue;
	}

	echo PHP_EOL . "[IBLOCK] " . $iblockCode . PHP_EOL;
	foreach ($properties as $propertyCode => $sizeDef) {
		if (!syncPropertyFormSize($iblockCode, $propertyCode, $sizeDef, $dryRun)) {
			exit(2);
		}
	}
}

echo PHP_EOL . "Done." . PHP_EOL;

exit(0);
