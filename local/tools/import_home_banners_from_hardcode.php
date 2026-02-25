<?php
/**
 * Одноразовый импорт hero-баннеров главной из local/tools/data/home-banners-hardcode-source.php в инфоблок.
 *
 * CLI:
 *   php local/tools/import_home_banners_from_hardcode.php --iblock-id=123 --dry-run=1
 *   php local/tools/import_home_banners_from_hardcode.php --iblock-id=123 --dry-run=0
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
	$options = getopt("", array("iblock-id:", "dry-run::", "help::"));
	if (isset($options["help"])) {
		echo "Usage: php local/tools/import_home_banners_from_hardcode.php --iblock-id=123 [--dry-run=1]" . PHP_EOL;
		exit(0);
	}
	if (isset($options["iblock-id"])) {
		$_REQUEST["iblock_id"] = $options["iblock-id"];
	}
	if (isset($options["dry-run"])) {
		$_REQUEST["dry_run"] = $options["dry-run"];
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

$iblockId = isset($_REQUEST["iblock_id"]) ? (int)$_REQUEST["iblock_id"] : 0;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

if ($iblockId <= 0) {
	echo "Parameter iblock_id is required (target Home Banners iblock ID)" . PHP_EOL;
	exit(1);
}

if (!defined("SITE_TEMPLATE_PATH")) {
	define("SITE_TEMPLATE_PATH", "/local/templates/szcube");
}

$dataFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/home-banners-hardcode-source.php";
if (!is_file($dataFile)) {
	echo "Source file not found: " . $dataFile . PHP_EOL;
	exit(1);
}

$items = require $dataFile;
if (!is_array($items)) {
	echo "Source file returned unexpected data type" . PHP_EOL;
	exit(1);
}

function importHomeBannersDateToBitrixFormat($dateRaw)
{
	$dateRaw = trim((string)$dateRaw);
	if ($dateRaw === "") {
		return "";
	}

	foreach (array("Y-m-d H:i:s", "Y-m-d") as $format) {
		$dt = \DateTime::createFromFormat($format, $dateRaw);
		if ($dt instanceof \DateTime) {
			return $dt->format("d.m.Y H:i:s");
		}
	}

	$timestamp = strtotime($dateRaw);
	return $timestamp ? date("d.m.Y H:i:s", $timestamp) : "";
}

function importHomeBannersMakeFileArray($imagePath)
{
	$imagePath = trim((string)$imagePath);
	if ($imagePath === "") {
		return false;
	}

	$imagePath = str_replace("\\", "/", $imagePath);
	$absPath = $_SERVER["DOCUMENT_ROOT"] . $imagePath;
	if (!is_file($absPath)) {
		return false;
	}

	return CFile::MakeFileArray($absPath);
}

function importHomeBannersGetPropertyIdByCode($iblockId, $code)
{
	$res = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => (int)$iblockId, "CODE" => (string)$code));
	if ($row = $res->Fetch()) {
		return (int)$row["ID"];
	}
	return 0;
}

function importHomeBannersGetSlotEnumMap($slotPropertyId)
{
	$map = array();
	if ($slotPropertyId <= 0) {
		return $map;
	}

	$res = CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("PROPERTY_ID" => (int)$slotPropertyId));
	while ($row = $res->Fetch()) {
		$xmlId = strtoupper(trim((string)$row["XML_ID"]));
		$value = strtoupper(trim((string)$row["VALUE"]));
		$id = (int)$row["ID"];
		if ($xmlId !== "") {
			$map[$xmlId] = $id;
		}
		if ($value !== "" && !isset($map[$value])) {
			$map[$value] = $id;
		}
	}

	return $map;
}

$slotPropertyId = importHomeBannersGetPropertyIdByCode($iblockId, "SLOT");
$slotEnumMap = importHomeBannersGetSlotEnumMap($slotPropertyId);
if ($slotPropertyId <= 0 || empty($slotEnumMap)) {
	echo "SLOT property or enums not found. Run create_home_banners_iblock.php first." . PHP_EOL;
	exit(1);
}

$el = new CIBlockElement();
$created = 0;
$updated = 0;
$skipped = 0;
$errors = array();

echo "Import started. IBlock ID: " . $iblockId . ". dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

foreach ($items as $item) {
	if (!is_array($item)) {
		$skipped++;
		continue;
	}

	$code = isset($item["code"]) ? trim((string)$item["code"]) : "";
	$name = isset($item["title"]) ? trim((string)$item["title"]) : "";
	$slotCode = isset($item["slot"]) ? strtoupper(trim((string)$item["slot"])) : "";
	if ($code === "" || $name === "" || $slotCode === "") {
		$skipped++;
		$errors[] = "Skip item with empty code/title/slot";
		continue;
	}

	if (!isset($slotEnumMap[$slotCode])) {
		$skipped++;
		$errors[] = "Unknown SLOT enum for item " . $code . ": " . $slotCode;
		continue;
	}

	$fields = array(
		"IBLOCK_ID" => $iblockId,
		"ACTIVE" => "Y",
		"NAME" => $name,
		"CODE" => $code,
		"SORT" => isset($item["sort"]) ? (int)$item["sort"] : 500,
		"ACTIVE_FROM" => importHomeBannersDateToBitrixFormat(isset($item["active_from"]) ? $item["active_from"] : ""),
		"ACTIVE_TO" => importHomeBannersDateToBitrixFormat(isset($item["active_to"]) ? $item["active_to"] : ""),
		"PREVIEW_TEXT" => isset($item["text"]) ? trim((string)$item["text"]) : "",
		"PREVIEW_TEXT_TYPE" => "text",
	);

	$fileArray = importHomeBannersMakeFileArray(isset($item["image"]) ? $item["image"] : "");
	if ($fileArray !== false) {
		$fields["PREVIEW_PICTURE"] = $fileArray;
	}

	$propertyValues = array(
		"SLOT" => $slotEnumMap[$slotCode],
		"LINK_URL" => isset($item["link_url"]) ? trim((string)$item["link_url"]) : "",
		"LINK_TARGET" => isset($item["link_target"]) && trim((string)$item["link_target"]) !== "" ? trim((string)$item["link_target"]) : "_self",
	);

	$existingId = 0;
	$res = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $iblockId, "=CODE" => $code), false, false, array("ID"));
	if ($row = $res->Fetch()) {
		$existingId = (int)$row["ID"];
	}

	echo ($existingId > 0 ? "[UPDATE]" : "[CREATE]") . " " . $slotCode . " :: " . $code . " :: " . $name . PHP_EOL;

	if ($dryRun) {
		if ($existingId > 0) {
			$updated++;
		} else {
			$created++;
		}
		continue;
	}

	$fields["PROPERTY_VALUES"] = $propertyValues;

	if ($existingId > 0) {
		if (!$el->Update($existingId, $fields)) {
			$errors[] = "Update failed for " . $code . ": " . $el->LAST_ERROR;
			continue;
		}
		$updated++;
	} else {
		$newId = $el->Add($fields);
		if (!$newId) {
			$errors[] = "Create failed for " . $code . ": " . $el->LAST_ERROR;
			continue;
		}
		$created++;
	}
}

echo PHP_EOL . "Done. Created: " . $created . ", Updated: " . $updated . ", Skipped: " . $skipped . PHP_EOL;
if (!empty($errors)) {
	echo "Errors:" . PHP_EOL;
	foreach ($errors as $error) {
		echo " - " . $error . PHP_EOL;
	}
	exit(2);
}

exit(0);
