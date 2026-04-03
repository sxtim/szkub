<?php
/**
 * Синхронизирует разделы ИБ "Кроме квартир":
 * - Главная
 * - Проекты
 *   - <ЖК>
 *
 * CLI:
 *   php local/tools/sync_extra_cards_sections.php --dry-run=1
 *   php local/tools/sync_extra_cards_sections.php --dry-run=0
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
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/sync_extra_cards_sections.php [--extra-cards-code=extra_cards] [--projects-code=projects] [--dry-run=1]" . PHP_EOL;
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

$extraCardsCode = isset($_REQUEST["extra_cards_code"]) && $_REQUEST["extra_cards_code"] !== "" ? (string)$_REQUEST["extra_cards_code"] : "extra_cards";
$projectsCode = isset($_REQUEST["projects_code"]) && $_REQUEST["projects_code"] !== "" ? (string)$_REQUEST["projects_code"] : "projects";
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "Extra cards iblock code: " . $extraCardsCode . PHP_EOL;
echo "Projects iblock code: " . $projectsCode . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function extraCardsSectionsFindIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
	return $res ? $res->Fetch() : false;
}

function extraCardsSectionsFindSection($iblockId, $code, $parentSectionId)
{
	$filter = array(
		"IBLOCK_ID" => (int)$iblockId,
		"=CODE" => (string)$code,
	);
	$filter["SECTION_ID"] = $parentSectionId === false ? false : (int)$parentSectionId;

	$res = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		$filter,
		false,
		array("ID", "NAME", "CODE", "SORT", "IBLOCK_SECTION_ID")
	);

	return $res ? $res->Fetch() : false;
}

function extraCardsSectionsFindSectionByCode($iblockId, $code)
{
	$res = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array(
			"IBLOCK_ID" => (int)$iblockId,
			"=CODE" => (string)$code,
		),
		false,
		array("ID", "NAME", "CODE", "SORT", "IBLOCK_SECTION_ID")
	);

	return $res ? $res->Fetch() : false;
}

function extraCardsEnsureSectionsMode($iblockId, $dryRun)
{
	$res = CIBlock::GetList(array(), array("ID" => (int)$iblockId), false);
	$row = $res ? $res->Fetch() : false;
	if (!is_array($row)) {
		return false;
	}

	$needsSync = (string)$row["SECTIONS"] !== "Y" || (string)$row["SECTION_CHOOSER"] !== "L";
	if (!$needsSync) {
		echo "[OK] Sections mode already enabled" . PHP_EOL;
		return true;
	}

	echo "[SYNC] Enable sections mode" . PHP_EOL;
	if ($dryRun) {
		return true;
	}

	$ib = new CIBlock();
	return (bool)$ib->Update((int)$iblockId, array(
		"NAME" => (string)$row["NAME"],
		"CODE" => (string)$row["CODE"],
		"IBLOCK_TYPE_ID" => (string)$row["IBLOCK_TYPE_ID"],
		"LID" => array((string)$row["LID"]),
		"SECTION_CHOOSER" => "L",
		"SECTIONS" => "Y",
		"RIGHTS_MODE" => (string)$row["RIGHTS_MODE"] !== "" ? (string)$row["RIGHTS_MODE"] : "S",
		"VERSION" => (int)$row["VERSION"] > 0 ? (int)$row["VERSION"] : 2,
		"INDEX_ELEMENT" => (string)$row["INDEX_ELEMENT"] !== "" ? (string)$row["INDEX_ELEMENT"] : "N",
		"INDEX_SECTION" => (string)$row["INDEX_SECTION"] !== "" ? (string)$row["INDEX_SECTION"] : "N",
	));
}

function extraCardsEnsureSection($iblockId, $name, $code, $sort, $parentSectionId, $dryRun)
{
	$current = extraCardsSectionsFindSection($iblockId, $code, $parentSectionId);
	if (!$current) {
		$current = extraCardsSectionsFindSectionByCode($iblockId, $code);
	}

	$fields = array(
		"IBLOCK_ID" => (int)$iblockId,
		"ACTIVE" => "Y",
		"NAME" => (string)$name,
		"CODE" => (string)$code,
		"SORT" => (int)$sort > 0 ? (int)$sort : 500,
		"IBLOCK_SECTION_ID" => $parentSectionId === false ? false : (int)$parentSectionId,
	);

	if (is_array($current) && (int)$current["ID"] > 0) {
		$currentParentId = isset($current["IBLOCK_SECTION_ID"]) ? (int)$current["IBLOCK_SECTION_ID"] : 0;
		$targetParentId = $parentSectionId === false ? 0 : (int)$parentSectionId;
		$needsUpdate =
			trim((string)$current["NAME"]) !== (string)$name
			|| (int)$current["SORT"] !== (int)$fields["SORT"]
			|| $currentParentId !== $targetParentId;

		if (!$needsUpdate) {
			echo "[OK] Section exists: " . $code . PHP_EOL;
			return (int)$current["ID"];
		}

		echo "[SYNC] Section update: " . $code . PHP_EOL;
		if ($dryRun) {
			return (int)$current["ID"];
		}

		$section = new CIBlockSection();
		$ok = $section->Update((int)$current["ID"], $fields);
		if (!$ok) {
			echo "[ERROR] Failed to update section " . $code . ": " . $section->LAST_ERROR . PHP_EOL;
			exit(4);
		}

		return (int)$current["ID"];
	}

	echo "[CREATE] Section: " . $code . PHP_EOL;
	if ($dryRun) {
		return 0;
	}

	$section = new CIBlockSection();
	$sectionId = (int)$section->Add($fields);
	if ($sectionId <= 0) {
		echo "[ERROR] Failed to create section " . $code . ": " . $section->LAST_ERROR . PHP_EOL;
		exit(4);
	}

	return $sectionId;
}

$extraCardsIblock = extraCardsSectionsFindIblock($extraCardsCode);
if (!is_array($extraCardsIblock) || (int)$extraCardsIblock["ID"] <= 0) {
	echo "[ERROR] Extra cards iblock not found by code: " . $extraCardsCode . PHP_EOL;
	exit(2);
}

$projectsIblock = extraCardsSectionsFindIblock($projectsCode);
if (!is_array($projectsIblock) || (int)$projectsIblock["ID"] <= 0) {
	echo "[ERROR] Projects iblock not found by code: " . $projectsCode . PHP_EOL;
	exit(2);
}

$extraCardsIblockId = (int)$extraCardsIblock["ID"];
$projectsIblockId = (int)$projectsIblock["ID"];

if (!extraCardsEnsureSectionsMode($extraCardsIblockId, $dryRun)) {
	echo "[ERROR] Failed to enable sections mode for extra_cards" . PHP_EOL;
	exit(3);
}

$homeSectionId = extraCardsEnsureSection($extraCardsIblockId, "Главная", "home", 100, false, $dryRun);
$projectsSectionId = extraCardsEnsureSection($extraCardsIblockId, "Проекты", "projects", 200, false, $dryRun);

$projectRes = CIBlockElement::GetList(
	array("SORT" => "ASC", "NAME" => "ASC"),
	array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
	false,
	false,
	array("ID", "NAME", "CODE", "SORT")
);

while ($project = $projectRes->Fetch()) {
	$projectCode = trim((string)$project["CODE"]);
	$projectName = trim((string)$project["NAME"]);
	$projectSort = (int)$project["SORT"] > 0 ? (int)$project["SORT"] : 500;

	if ($projectCode === "" || $projectName === "") {
		continue;
	}

	extraCardsEnsureSection($extraCardsIblockId, $projectName, $projectCode, $projectSort, $projectsSectionId, $dryRun);
}

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "- home section ID=" . (int)$homeSectionId . PHP_EOL;
echo "- projects section ID=" . (int)$projectsSectionId . PHP_EOL;

exit(0);
