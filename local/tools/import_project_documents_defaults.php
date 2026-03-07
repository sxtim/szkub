<?php
/**
 * Наполняет ИБ project_documents базовыми карточками документов для каждого ЖК.
 *
 * Запуск:
 *   php local/tools/import_project_documents_defaults.php --dry-run=1
 *   php local/tools/import_project_documents_defaults.php --dry-run=0
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
	$options = getopt("", array("dry-run::", "help::"));
	if (isset($options["help"])) {
		echo "Usage: php local/tools/import_project_documents_defaults.php [--dry-run=1]" . PHP_EOL;
		exit(0);
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

if (!class_exists("\\Bitrix\\Main\\Loader") || !\Bitrix\Main\Loader::includeModule("iblock")) {
	echo "Failed to load iblock module" . PHP_EOL;
	exit(1);
}

function iblockIdByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return (int)$row["ID"];
	}
	return 0;
}

function upsertProjectDocument($iblockId, $projectId, $code, $name, $subtitle, $sort, $dryRun)
{
	$existingId = 0;
	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => $iblockId, "=CODE" => $code),
		false,
		false,
		array("ID")
	);
	if ($row = $res->Fetch()) {
		$existingId = (int)$row["ID"];
	}

	$fields = array(
		"IBLOCK_ID" => $iblockId,
		"ACTIVE" => "Y",
		"NAME" => $name,
		"CODE" => $code,
		"SORT" => (int)$sort,
		"PREVIEW_TEXT" => $subtitle,
		"PREVIEW_TEXT_TYPE" => "text",
	);

	$properties = array(
		"PROJECT" => (int)$projectId,
		"LINK_TARGET" => "_self",
	);

	$action = $existingId > 0 ? "UPDATE" : "CREATE";
	echo "  [{$action}] {$code} :: {$subtitle}" . PHP_EOL;

	if ($dryRun) {
		return true;
	}

	$el = new CIBlockElement();
	if ($existingId > 0) {
		if (!$el->Update($existingId, $fields)) {
			echo "    [ERR] " . $el->LAST_ERROR . PHP_EOL;
			return false;
		}
		CIBlockElement::SetPropertyValuesEx($existingId, $iblockId, $properties);
		return true;
	}

	$newId = $el->Add($fields);
	if (!$newId) {
		echo "    [ERR] " . $el->LAST_ERROR . PHP_EOL;
		return false;
	}
	CIBlockElement::SetPropertyValuesEx((int)$newId, $iblockId, $properties);
	return true;
}

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1";

$projectsIblockId = iblockIdByCode("projects");
$documentsIblockId = iblockIdByCode("project_documents");
if ($projectsIblockId <= 0 || $documentsIblockId <= 0) {
	echo "Required iblocks not found. Run create_projects_iblock.php and create_project_detail_dynamic_iblocks.php first." . PHP_EOL;
	echo "projects={$projectsIblockId}, project_documents={$documentsIblockId}" . PHP_EOL;
	exit(2);
}

echo "Projects IBlock ID: {$projectsIblockId}" . PHP_EOL;
echo "Documents IBlock ID: {$documentsIblockId}" . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$defaults = array(
	array("slug" => "land", "name" => "Документы", "subtitle" => "Земельный участок", "sort" => 100),
	array("slug" => "project", "name" => "Документы", "subtitle" => "Проектные", "sort" => 200),
	array("slug" => "permit", "name" => "Документы", "subtitle" => "Разрешительные", "sort" => 300),
);

$created = 0;
$updated = 0;
$skipped = 0;

$projectsRes = CIBlockElement::GetList(
	array("SORT" => "ASC", "ID" => "ASC"),
	array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
	false,
	false,
	array("ID", "NAME", "CODE")
);
while ($project = $projectsRes->Fetch()) {
	$projectId = (int)$project["ID"];
	$projectCode = trim((string)$project["CODE"]);
	if ($projectId <= 0 || $projectCode === "") {
		$skipped++;
		continue;
	}

	echo "[PROJECT] {$projectCode} :: " . trim((string)$project["NAME"]) . PHP_EOL;

	foreach ($defaults as $row) {
		$code = $projectCode . "-doc-" . $row["slug"];
		$existingRes = CIBlockElement::GetList(array(), array("IBLOCK_ID" => $documentsIblockId, "=CODE" => $code), false, false, array("ID"));
		$exists = (bool)$existingRes->Fetch();

		$ok = upsertProjectDocument(
			$documentsIblockId,
			$projectId,
			$code,
			$row["name"],
			$row["subtitle"],
			(int)$row["sort"],
			$dryRun
		);
		if (!$ok) {
			continue;
		}
		if ($exists) {
			$updated++;
		} else {
			$created++;
		}
	}
}

echo PHP_EOL . "Done. Created: {$created}, Updated: {$updated}, Skipped projects: {$skipped}" . PHP_EOL;
exit(0);
