<?php
/**
 * Раскладывает элементы динамических ИБ проектов по разделам ЖК (по проектам),
 * с возможностью отката.
 *
 * Поддержка:
 * - status   : показать текущее состояние
 * - apply    : создать/использовать разделы ЖК и разложить элементы
 * - rollback : вернуть секции элементов и режим ИБ из backup
 *
 * Примеры:
 *   php local/tools/migrate_project_dynamic_iblocks_to_sections.php --mode=status
 *   php local/tools/migrate_project_dynamic_iblocks_to_sections.php --mode=apply --dry-run=1
 *   php local/tools/migrate_project_dynamic_iblocks_to_sections.php --mode=apply --dry-run=0
 *   php local/tools/migrate_project_dynamic_iblocks_to_sections.php --mode=rollback --dry-run=1
 *   php local/tools/migrate_project_dynamic_iblocks_to_sections.php --mode=rollback --dry-run=0
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
		"mode::",
		"projects-code::",
		"codes::",
		"backup-file::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/migrate_project_dynamic_iblocks_to_sections.php [--mode=status|apply|rollback] [--projects-code=projects] [--codes=project_advantages,project_construction,project_documents] [--backup-file=/tmp/project_dynamic_sections_backup.json] [--dry-run=1]" . PHP_EOL;
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

$mode = isset($_REQUEST["mode"]) && $_REQUEST["mode"] !== "" ? strtolower((string)$_REQUEST["mode"]) : "status";
if (!in_array($mode, array("status", "apply", "rollback"), true)) {
	echo "Unknown mode: {$mode}" . PHP_EOL;
	exit(2);
}

$projectsCode = isset($_REQUEST["projects_code"]) && $_REQUEST["projects_code"] !== "" ? (string)$_REQUEST["projects_code"] : "projects";
$codesRaw = isset($_REQUEST["codes"]) && $_REQUEST["codes"] !== "" ? (string)$_REQUEST["codes"] : "project_advantages,project_construction,project_documents";
$iblockCodes = array_values(array_filter(array_map("trim", explode(",", $codesRaw)), static function ($v) {
	return $v !== "";
}));
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
$backupRel = isset($_REQUEST["backup_file"]) && $_REQUEST["backup_file"] !== ""
	? (string)$_REQUEST["backup_file"]
	: "/tmp/project_dynamic_sections_backup.json";
$backupFile = strpos($backupRel, "/") === 0
	? $backupRel
	: ($_SERVER["DOCUMENT_ROOT"] . "/" . ltrim($backupRel, "/"));

function iblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}
	return null;
}

function projectsMap($projectsIblockId)
{
	$map = array();
	$res = CIBlockElement::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => (int)$projectsIblockId, "ACTIVE" => "Y"),
		false,
		false,
		array("ID", "NAME", "CODE", "SORT")
	);
	while ($row = $res->Fetch()) {
		$id = (int)$row["ID"];
		$code = trim((string)$row["CODE"]);
		if ($id <= 0 || $code === "") {
			continue;
		}
		$map[$id] = array(
			"id" => $id,
			"name" => trim((string)$row["NAME"]),
			"code" => $code,
			"sort" => (int)$row["SORT"],
		);
	}
	return $map;
}

function topSectionsByCode($iblockId)
{
	$sections = array();
	$res = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => (int)$iblockId, "SECTION_ID" => false),
		false,
		array("ID", "NAME", "CODE", "SORT")
	);
	while ($row = $res->Fetch()) {
		$code = trim((string)$row["CODE"]);
		$key = mb_strtolower($code !== "" ? $code : ("id_" . (int)$row["ID"]));
		$sections[$key] = array(
			"id" => (int)$row["ID"],
			"name" => trim((string)$row["NAME"]),
			"code" => $code,
			"sort" => (int)$row["SORT"],
		);
	}
	return $sections;
}

function allSectionIds($iblockId)
{
	$ids = array();
	$res = CIBlockSection::GetList(
		array("ID" => "ASC"),
		array("IBLOCK_ID" => (int)$iblockId),
		false,
		array("ID")
	);
	while ($row = $res->Fetch()) {
		$ids[] = (int)$row["ID"];
	}
	return $ids;
}

function elementSectionIds($elementId)
{
	$ids = array();
	$res = CIBlockElement::GetElementGroups((int)$elementId, true, array("ID"));
	while ($row = $res->Fetch()) {
		$ids[] = (int)$row["ID"];
	}
	sort($ids);
	return array_values(array_unique(array_filter($ids)));
}

function saveJson($path, array $data)
{
	$dir = dirname($path);
	if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
		return false;
	}
	$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
	if ($json === false) {
		return false;
	}
	return file_put_contents($path, $json) !== false;
}

function loadJson($path)
{
	if (!is_file($path)) {
		return null;
	}
	$json = file_get_contents($path);
	if ($json === false) {
		return null;
	}
	$data = json_decode($json, true);
	return is_array($data) ? $data : null;
}

function updateIblockSectionsMode($iblockId, $enabled, $dryRun)
{
	$iblockId = (int)$iblockId;
	if ($iblockId <= 0) {
		return array("ok" => false, "error" => "invalid iblock id");
	}
	if ($dryRun) {
		return array("ok" => true, "dry" => true);
	}
	$ib = new CIBlock();
	$ok = $ib->Update($iblockId, array(
		"SECTIONS" => $enabled ? "Y" : "N",
		"SECTION_CHOOSER" => "L",
	));
	if (!$ok) {
		return array("ok" => false, "error" => $ib->LAST_ERROR);
	}
	return array("ok" => true);
}

function createSection($iblockId, $name, $code, $sort, $dryRun)
{
	if ($dryRun) {
		return array("ok" => true, "id" => 0, "dry" => true);
	}
	$section = new CIBlockSection();
	$newId = (int)$section->Add(array(
		"IBLOCK_ID" => (int)$iblockId,
		"ACTIVE" => "Y",
		"NAME" => (string)$name,
		"CODE" => (string)$code,
		"SORT" => (int)$sort > 0 ? (int)$sort : 500,
		"IBLOCK_SECTION_ID" => false,
	));
	if ($newId <= 0) {
		return array("ok" => false, "error" => $section->LAST_ERROR);
	}
	return array("ok" => true, "id" => $newId);
}

function deleteSection($sectionId, $dryRun)
{
	$sectionId = (int)$sectionId;
	if ($sectionId <= 0) {
		return array("ok" => true);
	}
	if ($dryRun) {
		return array("ok" => true, "dry" => true);
	}
	$section = new CIBlockSection();
	$ok = $section->Delete($sectionId);
	if (!$ok) {
		return array("ok" => false, "error" => "delete failed");
	}
	return array("ok" => true);
}

function setElementSections($elementId, array $sectionIds, $dryRun)
{
	$elementId = (int)$elementId;
	if ($elementId <= 0) {
		return array("ok" => false, "error" => "invalid element id");
	}
	if ($dryRun) {
		return array("ok" => true, "dry" => true);
	}
	$ok = CIBlockElement::SetElementSection($elementId, $sectionIds, false);
	if (!$ok) {
		return array("ok" => false, "error" => "SetElementSection failed");
	}
	return array("ok" => true);
}

function planIblock($iblockRow, array $projects)
{
	$iblockId = (int)$iblockRow["ID"];
	$existingSections = topSectionsByCode($iblockId);
	$sectionIdsBefore = allSectionIds($iblockId);

	$plan = array(
		"iblock_id" => $iblockId,
		"iblock_name" => (string)$iblockRow["NAME"],
		"iblock_code" => (string)$iblockRow["CODE"],
		"before_sections_mode" => (string)$iblockRow["SECTIONS"],
		"before_section_chooser" => (string)$iblockRow["SECTION_CHOOSER"],
		"before_section_ids" => $sectionIdsBefore,
		"sections_to_create" => array(),
		"elements_to_move" => array(),
		"touched_element_ids" => array(),
		"created_section_ids" => array(),
	);

	foreach ($projects as $projectId => $project) {
		$projectCode = (string)$project["code"];
		$sectionKey = mb_strtolower($projectCode);

		$targetSectionId = 0;
		if (isset($existingSections[$sectionKey])) {
			$targetSectionId = (int)$existingSections[$sectionKey]["id"];
		} else {
			$plan["sections_to_create"][] = array(
				"project_id" => (int)$projectId,
				"project_code" => $projectCode,
				"name" => (string)$project["name"],
				"code" => $projectCode,
				"sort" => (int)$project["sort"],
			);
		}

		$elementsRes = CIBlockElement::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			array(
				"IBLOCK_ID" => $iblockId,
				"PROPERTY_PROJECT" => (int)$projectId,
			),
			false,
			false,
			array("ID", "NAME", "CODE")
		);
		while ($element = $elementsRes->Fetch()) {
			$elementId = (int)$element["ID"];
			$currentSections = elementSectionIds($elementId);
			$targetId = $targetSectionId;
			$needsMove = true;
			if ($targetId > 0 && count($currentSections) === 1 && (int)$currentSections[0] === $targetId) {
				$needsMove = false;
			}

			$plan["touched_element_ids"][] = $elementId;
			$plan["elements_to_move"][] = array(
				"element_id" => $elementId,
				"element_name" => (string)$element["NAME"],
				"project_id" => (int)$projectId,
				"project_code" => $projectCode,
				"target_section_id" => $targetId,
				"target_section_code" => $projectCode,
				"before_sections" => $currentSections,
				"needs_move" => $needsMove || $targetId === 0,
			);
		}
	}

	$plan["touched_element_ids"] = array_values(array_unique($plan["touched_element_ids"]));
	return $plan;
}

echo "Mode: {$mode}" . PHP_EOL;
echo "Projects code: {$projectsCode}" . PHP_EOL;
echo "IBlocks: " . implode(", ", $iblockCodes) . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;
echo "Backup: {$backupRel}" . PHP_EOL;

$projectsIblock = iblockByCode($projectsCode);
if (!is_array($projectsIblock)) {
	echo "[ERROR] projects iblock not found by code '{$projectsCode}'" . PHP_EOL;
	exit(3);
}
$projects = projectsMap((int)$projectsIblock["ID"]);
if (empty($projects)) {
	echo "[ERROR] no active projects found in iblock '{$projectsCode}'" . PHP_EOL;
	exit(4);
}

if ($mode === "status") {
	foreach ($iblockCodes as $code) {
		$ib = iblockByCode($code);
		if (!is_array($ib)) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}

		$sections = topSectionsByCode((int)$ib["ID"]);
		$elementCount = (int)CIBlockElement::GetList(
			array(),
			array("IBLOCK_ID" => (int)$ib["ID"]),
			array(),
			false,
			array("ID")
		);
		echo "[OK] {$code}: ID=" . (int)$ib["ID"]
			. ", TYPE=" . (string)$ib["IBLOCK_TYPE_ID"]
			. ", SECTIONS=" . (string)$ib["SECTIONS"]
			. ", SECTIONS_COUNT=" . count($sections)
			. ", ELEMENTS=" . $elementCount . PHP_EOL;
	}
	exit(0);
}

if ($mode === "apply") {
	$plans = array();
	foreach ($iblockCodes as $code) {
		$ib = iblockByCode($code);
		if (!is_array($ib)) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}
		$plan = planIblock($ib, $projects);
		$plans[$code] = $plan;
		echo "[PLAN] {$code}: sections_to_create=" . count($plan["sections_to_create"])
			. ", elements_to_touch=" . count($plan["elements_to_move"])
			. ", current_sections_mode=" . (string)$plan["before_sections_mode"] . PHP_EOL;
	}

	if (empty($plans)) {
		echo "[ERROR] no target iblocks found" . PHP_EOL;
		exit(5);
	}

	if ($dryRun) {
		foreach ($plans as $code => $plan) {
			if (!empty($plan["sections_to_create"])) {
				foreach ($plan["sections_to_create"] as $sectionPlan) {
					echo "  [DRY][{$code}] create section: " . $sectionPlan["code"] . " (" . $sectionPlan["name"] . ")" . PHP_EOL;
				}
			}
			$previewMoves = 0;
			foreach ($plan["elements_to_move"] as $move) {
				if (!$move["needs_move"]) {
					continue;
				}
				echo "  [DRY][{$code}] move #" . (int)$move["element_id"] . " '" . $move["element_name"] . "' -> section " . $move["target_section_code"] . PHP_EOL;
				$previewMoves++;
				if ($previewMoves >= 8) {
					echo "  [DRY][{$code}] ... and more" . PHP_EOL;
					break;
				}
			}
		}
		exit(0);
	}

	$backup = array(
		"created_at" => date("c"),
		"projects_iblock_code" => $projectsCode,
		"projects_iblock_id" => (int)$projectsIblock["ID"],
		"iblock_codes" => array_values(array_keys($plans)),
		"iblocks" => array(),
	);
	foreach ($plans as $code => $plan) {
		$elementStates = array();
		foreach ($plan["elements_to_move"] as $move) {
			$elementStates[(string)$move["element_id"]] = $move["before_sections"];
		}
		$backup["iblocks"][$code] = array(
			"iblock_id" => (int)$plan["iblock_id"],
			"iblock_name" => (string)$plan["iblock_name"],
			"before_sections_mode" => (string)$plan["before_sections_mode"],
			"before_section_chooser" => (string)$plan["before_section_chooser"],
			"before_section_ids" => $plan["before_section_ids"],
			"element_sections_before" => $elementStates,
			"created_section_ids" => array(),
		);
	}

	if (!saveJson($backupFile, $backup)) {
		echo "[ERROR] failed to save backup file: {$backupFile}" . PHP_EOL;
		exit(6);
	}
	echo "[OK] backup saved" . PHP_EOL;

	foreach ($plans as $code => &$plan) {
		echo "[APPLY] {$code}" . PHP_EOL;
		$iblockId = (int)$plan["iblock_id"];

		if ((string)$plan["before_sections_mode"] !== "Y") {
			echo "  [SET] enable sections mode" . PHP_EOL;
			$res = updateIblockSectionsMode($iblockId, true, false);
			if (!$res["ok"]) {
				echo "  [ERROR] " . $res["error"] . PHP_EOL;
				exit(7);
			}
		}

		$sectionMap = topSectionsByCode($iblockId);
		foreach ($plan["sections_to_create"] as $sectionPlan) {
			$key = mb_strtolower((string)$sectionPlan["code"]);
			if (isset($sectionMap[$key])) {
				continue;
			}
			echo "  [CREATE] section " . $sectionPlan["code"] . " (" . $sectionPlan["name"] . ")" . PHP_EOL;
			$createRes = createSection(
				$iblockId,
				(string)$sectionPlan["name"],
				(string)$sectionPlan["code"],
				(int)$sectionPlan["sort"],
				false
			);
			if (!$createRes["ok"]) {
				echo "  [ERROR] " . $createRes["error"] . PHP_EOL;
				exit(8);
			}
			$newSectionId = (int)$createRes["id"];
			$plan["created_section_ids"][] = $newSectionId;
		}
		$sectionMap = topSectionsByCode($iblockId);

		foreach ($plan["elements_to_move"] as $move) {
			$targetKey = mb_strtolower((string)$move["target_section_code"]);
			$targetSectionId = 0;
			if (isset($sectionMap[$targetKey])) {
				$targetSectionId = (int)$sectionMap[$targetKey]["id"];
			}
			if ($targetSectionId <= 0) {
				continue;
			}

			$currentSections = elementSectionIds((int)$move["element_id"]);
			if (count($currentSections) === 1 && (int)$currentSections[0] === $targetSectionId) {
				continue;
			}

			echo "  [MOVE] #" . (int)$move["element_id"] . " -> section #" . $targetSectionId . PHP_EOL;
			$setRes = setElementSections((int)$move["element_id"], array($targetSectionId), false);
			if (!$setRes["ok"]) {
				echo "  [ERROR] " . $setRes["error"] . PHP_EOL;
				exit(9);
			}
		}

		$backup["iblocks"][$code]["created_section_ids"] = array_values(array_unique($plan["created_section_ids"]));
	}
	unset($plan);

	if (!saveJson($backupFile, $backup)) {
		echo "[ERROR] failed to update backup with created sections: {$backupFile}" . PHP_EOL;
		exit(10);
	}
	echo "[OK] backup updated with created section ids" . PHP_EOL;
	echo "Done." . PHP_EOL;
	exit(0);
}

if ($mode === "rollback") {
	$backup = loadJson($backupFile);
	if (!is_array($backup) || !isset($backup["iblocks"]) || !is_array($backup["iblocks"])) {
		echo "[ERROR] backup file not found or invalid: {$backupFile}" . PHP_EOL;
		exit(11);
	}

	foreach ($backup["iblocks"] as $code => $item) {
		$iblockId = isset($item["iblock_id"]) ? (int)$item["iblock_id"] : 0;
		if ($iblockId <= 0) {
			$ib = iblockByCode((string)$code);
			if (is_array($ib)) {
				$iblockId = (int)$ib["ID"];
			}
		}
		if ($iblockId <= 0) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}

		echo "[ROLLBACK] {$code}" . PHP_EOL;

		$elementStates = isset($item["element_sections_before"]) && is_array($item["element_sections_before"])
			? $item["element_sections_before"]
			: array();
		foreach ($elementStates as $elementIdRaw => $sectionIdsRaw) {
			$elementId = (int)$elementIdRaw;
			if ($elementId <= 0) {
				continue;
			}
			$sectionIds = array();
			if (is_array($sectionIdsRaw)) {
				foreach ($sectionIdsRaw as $idRaw) {
					$id = (int)$idRaw;
					if ($id > 0) {
						$sectionIds[] = $id;
					}
				}
			}
			$current = elementSectionIds($elementId);
			$normalizedCurrent = $current;
			$normalizedTarget = $sectionIds;
			sort($normalizedCurrent);
			sort($normalizedTarget);
			if ($normalizedCurrent === $normalizedTarget) {
				continue;
			}
			echo "  [RESTORE] element #{$elementId} sections" . PHP_EOL;
			$res = setElementSections($elementId, $sectionIds, $dryRun);
			if (!$res["ok"]) {
				echo "  [ERROR] " . $res["error"] . PHP_EOL;
				exit(12);
			}
		}

		$createdSectionIds = isset($item["created_section_ids"]) && is_array($item["created_section_ids"])
			? $item["created_section_ids"]
			: array();
		foreach ($createdSectionIds as $sidRaw) {
			$sid = (int)$sidRaw;
			if ($sid <= 0) {
				continue;
			}
			echo "  [DELETE] section #{$sid}" . PHP_EOL;
			$res = deleteSection($sid, $dryRun);
			if (!$res["ok"]) {
				echo "  [WARN] failed to delete section #{$sid}: " . $res["error"] . PHP_EOL;
			}
		}

		$beforeSectionsMode = isset($item["before_sections_mode"]) ? (string)$item["before_sections_mode"] : "N";
		$needSections = $beforeSectionsMode === "Y";
		echo "  [SET] sections mode -> {$beforeSectionsMode}" . PHP_EOL;
		$modeRes = updateIblockSectionsMode($iblockId, $needSections, $dryRun);
		if (!$modeRes["ok"]) {
			echo "  [ERROR] " . $modeRes["error"] . PHP_EOL;
			exit(13);
		}
	}

	echo "Done." . PHP_EOL;
	exit(0);
}

echo "Nothing to do." . PHP_EOL;
exit(0);
