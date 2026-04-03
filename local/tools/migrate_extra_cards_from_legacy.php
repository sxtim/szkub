<?php
/**
 * Переносит карточки "Кроме квартир" в новый ИБ extra_cards.
 *
 * Источники:
 * - главная: локальный data/extra-cards-home-source.php
 * - проекты: legacy-свойства EXTRA1/2/3_* из ИБ projects
 *
 * CLI:
 *   php local/tools/migrate_extra_cards_from_legacy.php --dry-run=1
 *   php local/tools/migrate_extra_cards_from_legacy.php --dry-run=0
 *   php local/tools/migrate_extra_cards_from_legacy.php --dry-run=0 --overwrite=1
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
		"extra-cards-code::",
		"projects-code::",
		"dry-run::",
		"overwrite::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/migrate_extra_cards_from_legacy.php [--extra-cards-code=extra_cards] [--projects-code=projects] [--dry-run=1] [--overwrite=0]" . PHP_EOL;
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

if (!defined("SITE_TEMPLATE_PATH")) {
	define("SITE_TEMPLATE_PATH", "/local/templates/szcube");
}

$extraCardsCode = isset($_REQUEST["extra_cards_code"]) && $_REQUEST["extra_cards_code"] !== "" ? (string)$_REQUEST["extra_cards_code"] : "extra_cards";
$projectsCode = isset($_REQUEST["projects_code"]) && $_REQUEST["projects_code"] !== "" ? (string)$_REQUEST["projects_code"] : "projects";
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
$overwrite = isset($_REQUEST["overwrite"]) && ((string)$_REQUEST["overwrite"] === "1" || strtolower((string)$_REQUEST["overwrite"]) === "y");

echo "Extra cards iblock code: " . $extraCardsCode . PHP_EOL;
echo "Projects iblock code: " . $projectsCode . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;
echo "overwrite: " . ($overwrite ? "Y" : "N") . PHP_EOL;

$homeSourceFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/extra-cards-home-source.php";
if (!is_file($homeSourceFile)) {
	echo "[ERROR] Home source file not found: " . $homeSourceFile . PHP_EOL;
	exit(2);
}

$homeCards = require $homeSourceFile;
if (!is_array($homeCards)) {
	echo "[ERROR] Home source returned unexpected data" . PHP_EOL;
	exit(2);
}

function migrateExtraCardsFindIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
	return $res ? $res->Fetch() : false;
}

function migrateExtraCardsFindSectionId($iblockId, array $codePath)
{
	$parentSectionId = false;

	foreach ($codePath as $code) {
		$filter = array(
			"IBLOCK_ID" => (int)$iblockId,
			"=CODE" => (string)$code,
		);
		$filter["SECTION_ID"] = $parentSectionId === false ? false : (int)$parentSectionId;

		$res = CIBlockSection::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			$filter,
			false,
			array("ID")
		);
		$row = $res ? $res->Fetch() : false;
		if (!is_array($row) || (int)$row["ID"] <= 0) {
			return 0;
		}

		$parentSectionId = (int)$row["ID"];
	}

	return (int)$parentSectionId;
}

function migrateExtraCardsLoadProperties($iblockId, $elementId)
{
	$result = array();
	$res = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("SORT" => "ASC", "ID" => "ASC"),
		array()
	);
	while ($row = $res->Fetch()) {
		$code = trim((string)$row["CODE"]);
		if ($code === "") {
			continue;
		}

		if (!isset($result[$code])) {
			$result[$code] = array(
				"VALUE" => $row["MULTIPLE"] === "Y" ? array() : "",
			);
		}

		if ($row["MULTIPLE"] === "Y") {
			$result[$code]["VALUE"][] = $row["VALUE"];
		} else {
			$result[$code]["VALUE"] = $row["VALUE"];
		}
	}

	return $result;
}

function migrateExtraCardsScalar(array $properties, $code)
{
	if (!isset($properties[$code])) {
		return "";
	}

	$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : "";
	if (is_array($value)) {
		$value = reset($value);
	}

	return trim((string)$value);
}

function migrateExtraCardsFileId(array $properties, $code)
{
	if (!isset($properties[$code])) {
		return 0;
	}

	$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : 0;
	if (is_array($value)) {
		$value = reset($value);
	}

	return (int)$value;
}

function migrateExtraCardsFindElementId($iblockId, $code)
{
	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => (int)$iblockId, "=CODE" => (string)$code),
		false,
		false,
		array("ID")
	);
	if ($row = $res->Fetch()) {
		return (int)$row["ID"];
	}

	return 0;
}

function migrateExtraCardsMakeFileArray($source)
{
	if (is_int($source) || ctype_digit((string)$source)) {
		$fileId = (int)$source;
		return $fileId > 0 ? CFile::MakeFileArray($fileId) : false;
	}

	$path = trim((string)$source);
	if ($path === "") {
		return false;
	}

	$absolutePath = $_SERVER["DOCUMENT_ROOT"] . str_replace("\\", "/", $path);
	if (!is_file($absolutePath)) {
		return false;
	}

	return CFile::MakeFileArray($absolutePath);
}

function migrateExtraCardsUpsert($iblockId, $sectionId, $code, $title, $linkUrl, $imageSource, $sort, $dryRun, $overwrite)
{
	$elementId = migrateExtraCardsFindElementId($iblockId, $code);
	$fileArray = migrateExtraCardsMakeFileArray($imageSource);

	$fields = array(
		"IBLOCK_ID" => (int)$iblockId,
		"IBLOCK_SECTION_ID" => (int)$sectionId,
		"ACTIVE" => "Y",
		"NAME" => (string)$title,
		"CODE" => (string)$code,
		"SORT" => (int)$sort > 0 ? (int)$sort : 500,
	);

	if ($fileArray !== false) {
		$fields["PREVIEW_PICTURE"] = $fileArray;
	}

	if ($elementId > 0) {
		if (!$overwrite) {
			echo "[SKIP] Element exists: " . $code . PHP_EOL;
			return true;
		}

		echo "[UPDATE] Element: " . $code . PHP_EOL;
		if ($dryRun) {
			return true;
		}

		$element = new CIBlockElement();
		$ok = $element->Update($elementId, $fields);
		if (!$ok) {
			echo "[ERROR] Failed to update " . $code . ": " . $element->LAST_ERROR . PHP_EOL;
			return false;
		}

		CIBlockElement::SetPropertyValuesEx($elementId, (int)$iblockId, array(
			"LINK_URL" => (string)$linkUrl,
		));

		return true;
	}

	echo "[CREATE] Element: " . $code . PHP_EOL;
	if ($dryRun) {
		return true;
	}

	$fields["PROPERTY_VALUES"] = array(
		"LINK_URL" => (string)$linkUrl,
	);

	$element = new CIBlockElement();
	$newId = (int)$element->Add($fields);
	if ($newId <= 0) {
		echo "[ERROR] Failed to create " . $code . ": " . $element->LAST_ERROR . PHP_EOL;
		return false;
	}

	return true;
}

$extraCardsIblock = migrateExtraCardsFindIblock($extraCardsCode);
if (!is_array($extraCardsIblock) || (int)$extraCardsIblock["ID"] <= 0) {
	echo "[ERROR] Extra cards iblock not found by code: " . $extraCardsCode . PHP_EOL;
	exit(2);
}

$projectsIblock = migrateExtraCardsFindIblock($projectsCode);
if (!is_array($projectsIblock) || (int)$projectsIblock["ID"] <= 0) {
	echo "[ERROR] Projects iblock not found by code: " . $projectsCode . PHP_EOL;
	exit(2);
}

$extraCardsIblockId = (int)$extraCardsIblock["ID"];
$projectsIblockId = (int)$projectsIblock["ID"];

$homeSectionId = migrateExtraCardsFindSectionId($extraCardsIblockId, array("home"));
$projectsRootSectionId = migrateExtraCardsFindSectionId($extraCardsIblockId, array("projects"));

if ($homeSectionId <= 0 || $projectsRootSectionId <= 0) {
	echo "[ERROR] Required sections are missing. Run sync_extra_cards_sections.php first." . PHP_EOL;
	exit(3);
}

foreach ($homeCards as $card) {
	$title = isset($card["title"]) ? trim((string)$card["title"]) : "";
	$image = isset($card["image"]) ? trim((string)$card["image"]) : "";
	$code = isset($card["code"]) ? trim((string)$card["code"]) : "";
	if ($title === "" || $image === "" || $code === "") {
		continue;
	}

	if (!migrateExtraCardsUpsert(
		$extraCardsIblockId,
		$homeSectionId,
		$code,
		$title,
		isset($card["url"]) ? trim((string)$card["url"]) : "",
		$image,
		isset($card["sort"]) ? (int)$card["sort"] : 500,
		$dryRun,
		$overwrite
	)) {
		exit(4);
	}
}

$projectRes = CIBlockElement::GetList(
	array("SORT" => "ASC", "ID" => "ASC"),
	array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
	false,
	false,
	array("ID", "NAME", "CODE")
);

while ($project = $projectRes->Fetch()) {
	$projectId = (int)$project["ID"];
	$projectCode = trim((string)$project["CODE"]);
	if ($projectId <= 0 || $projectCode === "") {
		continue;
	}

	$projectSectionId = migrateExtraCardsFindSectionId($extraCardsIblockId, array("projects", $projectCode));
	if ($projectSectionId <= 0) {
		echo "[SKIP] Project section missing: " . $projectCode . PHP_EOL;
		continue;
	}

	$projectProperties = migrateExtraCardsLoadProperties($projectsIblockId, $projectId);
	for ($i = 1; $i <= 3; $i++) {
		$title = migrateExtraCardsScalar($projectProperties, "EXTRA" . $i . "_TITLE");
		$imageFileId = migrateExtraCardsFileId($projectProperties, "EXTRA" . $i . "_IMAGE");
		$linkUrl = migrateExtraCardsScalar($projectProperties, "EXTRA" . $i . "_URL");

		if ($title === "" || $imageFileId <= 0) {
			continue;
		}

		if (!migrateExtraCardsUpsert(
			$extraCardsIblockId,
			$projectSectionId,
			$projectCode . "-extra-" . $i,
			$title,
			$linkUrl,
			$imageFileId,
			$i * 100,
			$dryRun,
			$overwrite
		)) {
			exit(5);
		}
	}
}

echo PHP_EOL;
echo "Done." . PHP_EOL;

exit(0);
