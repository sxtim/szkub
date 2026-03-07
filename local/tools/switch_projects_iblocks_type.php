<?php
/**
 * Переключение типа инфоблоков проектов (для группировки в админке в одну папку типа).
 *
 * Поддержка:
 * - status   : показать текущее состояние
 * - apply    : перевести ИБ в target-type и сохранить backup
 * - rollback : вернуть ИБ в типы из backup
 *
 * Примеры:
 *   php local/tools/switch_projects_iblocks_type.php --mode=status
 *   php local/tools/switch_projects_iblocks_type.php --mode=apply --target-type=realty --dry-run=1
 *   php local/tools/switch_projects_iblocks_type.php --mode=apply --target-type=realty --dry-run=0
 *   php local/tools/switch_projects_iblocks_type.php --mode=rollback --dry-run=1
 *   php local/tools/switch_projects_iblocks_type.php --mode=rollback --dry-run=0
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
		"target-type::",
		"codes::",
		"backup-file::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/switch_projects_iblocks_type.php [--mode=status|apply|rollback] [--target-type=realty] [--codes=projects,project_advantages,project_construction] [--dry-run=1]\n";
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

$mode = isset($_REQUEST["mode"]) && $_REQUEST["mode"] !== "" ? strtolower((string)$_REQUEST["mode"]) : "status";
if (!in_array($mode, array("status", "apply", "rollback"), true)) {
	echo "Unknown mode: {$mode}" . PHP_EOL;
	exit(2);
}

$targetType = isset($_REQUEST["target_type"]) && $_REQUEST["target_type"] !== "" ? (string)$_REQUEST["target_type"] : "realty";
$codesRaw = isset($_REQUEST["codes"]) && $_REQUEST["codes"] !== "" ? (string)$_REQUEST["codes"] : "projects,project_advantages,project_construction";
$codes = array_values(array_filter(array_map("trim", explode(",", $codesRaw)), static function ($v) { return $v !== ""; }));
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
$backupFileRel = isset($_REQUEST["backup_file"]) && $_REQUEST["backup_file"] !== ""
	? (string)$_REQUEST["backup_file"]
	: "local/tools/data/projects_iblocks_type_backup.json";
$backupFile = strpos($backupFileRel, "/") === 0
	? $backupFileRel
	: ($_SERVER["DOCUMENT_ROOT"] . "/" . ltrim($backupFileRel, "/"));

function findIblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}
	return null;
}

function ensureTypeExists($typeId, $dryRun)
{
	$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
	if ($typeRes->Fetch()) {
		return true;
	}

	if ($dryRun) {
		echo "  [DRY] would create type '{$typeId}'" . PHP_EOL;
		return true;
	}

	$type = new CIBlockType();
	$ok = $type->Add(array(
		"ID" => $typeId,
		"SORT" => 500,
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"LANG" => array(
			"ru" => array(
				"NAME" => "Недвижимость",
				"SECTION_NAME" => "Раздел",
				"ELEMENT_NAME" => "Элемент",
			),
			"en" => array(
				"NAME" => "Realty",
				"SECTION_NAME" => "Section",
				"ELEMENT_NAME" => "Element",
			),
		),
	));

	if (!$ok) {
		echo "  [ERROR] failed to create type '{$typeId}': " . $type->LAST_ERROR . PHP_EOL;
		return false;
	}

	echo "  [CREATE] type '{$typeId}' created" . PHP_EOL;
	return true;
}

function updateIblockType($iblockId, $newType, $dryRun)
{
	$iblockId = (int)$iblockId;
	if ($iblockId <= 0) {
		return array("ok" => false, "error" => "invalid iblock id");
	}

	if ($dryRun) {
		return array("ok" => true, "dry" => true);
	}

	$ib = new CIBlock();
	if ($ib->Update($iblockId, array("IBLOCK_TYPE_ID" => $newType))) {
		return array("ok" => true);
	}

	return array("ok" => false, "error" => $ib->LAST_ERROR);
}

function loadBackup($backupFile)
{
	if (!is_file($backupFile)) {
		return null;
	}
	$json = file_get_contents($backupFile);
	if ($json === false) {
		return null;
	}
	$data = json_decode($json, true);
	return is_array($data) ? $data : null;
}

function saveBackup($backupFile, array $backup)
{
	$dir = dirname($backupFile);
	if (!is_dir($dir)) {
		mkdir($dir, 0775, true);
	}
	$json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	return file_put_contents($backupFile, $json) !== false;
}

echo "Mode: {$mode}" . PHP_EOL;
echo "Target type: {$targetType}" . PHP_EOL;
echo "Codes: " . implode(", ", $codes) . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;
echo "Backup: {$backupFileRel}" . PHP_EOL;

if ($mode === "status") {
	foreach ($codes as $code) {
		$ib = findIblockByCode($code);
		if (!is_array($ib)) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}
		echo "[OK] {$code} => ID=" . (int)$ib["ID"] . ", TYPE=" . (string)$ib["IBLOCK_TYPE_ID"] . ", NAME=" . (string)$ib["NAME"] . PHP_EOL;
	}
	exit(0);
}

if ($mode === "apply") {
	if (!ensureTypeExists($targetType, $dryRun)) {
		exit(3);
	}

	$backup = array(
		"created_at" => date("c"),
		"target_type" => $targetType,
		"codes" => $codes,
		"items" => array(),
	);

	foreach ($codes as $code) {
		$ib = findIblockByCode($code);
		if (!is_array($ib)) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}

		$id = (int)$ib["ID"];
		$fromType = (string)$ib["IBLOCK_TYPE_ID"];
		$name = (string)$ib["NAME"];
		$backup["items"][] = array(
			"code" => $code,
			"id" => $id,
			"name" => $name,
			"from_type" => $fromType,
		);
	}

	if ($dryRun) {
		echo "[DRY] backup not saved" . PHP_EOL;
		exit(0);
	}

	if (!saveBackup($backupFile, $backup)) {
		echo "[ERROR] failed to save backup file: {$backupFile}" . PHP_EOL;
		echo "No changes were applied." . PHP_EOL;
		exit(5);
	}
	echo "[OK] backup saved" . PHP_EOL;

	foreach ($backup["items"] as $item) {
		$code = (string)$item["code"];
		$fromType = (string)$item["from_type"];

		$ib = findIblockByCode($code);
		if (!is_array($ib)) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}

		$id = (int)$ib["ID"];
		if ($fromType === $targetType) {
			echo "[SKIP] {$code} already in type {$targetType}" . PHP_EOL;
			continue;
		}

		echo "[MOVE] {$code}: {$fromType} -> {$targetType}" . PHP_EOL;
		$res = updateIblockType($id, $targetType, $dryRun);
		if (!$res["ok"]) {
			echo "  [ERROR] " . (isset($res["error"]) ? $res["error"] : "unknown error") . PHP_EOL;
			exit(4);
		}
	}

	exit(0);
}

if ($mode === "rollback") {
	$backup = loadBackup($backupFile);
	if (!is_array($backup) || empty($backup["items"]) || !is_array($backup["items"])) {
		echo "[ERROR] backup file not found or invalid: {$backupFile}" . PHP_EOL;
		exit(6);
	}

	foreach ($backup["items"] as $item) {
		$code = isset($item["code"]) ? (string)$item["code"] : "";
		$toType = isset($item["from_type"]) ? (string)$item["from_type"] : "";
		if ($code === "" || $toType === "") {
			continue;
		}

		$ib = findIblockByCode($code);
		if (!is_array($ib)) {
			echo "[MISS] {$code}" . PHP_EOL;
			continue;
		}

		$id = (int)$ib["ID"];
		$currentType = (string)$ib["IBLOCK_TYPE_ID"];
		if ($currentType === $toType) {
			echo "[SKIP] {$code} already in type {$toType}" . PHP_EOL;
			continue;
		}

		if (!ensureTypeExists($toType, $dryRun)) {
			exit(7);
		}

		echo "[ROLLBACK] {$code}: {$currentType} -> {$toType}" . PHP_EOL;
		$res = updateIblockType($id, $toType, $dryRun);
		if (!$res["ok"]) {
			echo "  [ERROR] " . (isset($res["error"]) ? $res["error"] : "unknown error") . PHP_EOL;
			exit(8);
		}
	}

	exit(0);
}

exit(0);
