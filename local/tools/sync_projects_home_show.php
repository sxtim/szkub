<?php
/**
 * Безопасно синхронизирует только флаг HOME_SHOW у существующих проектов.
 *
 * Запуск:
 *   php local/tools/sync_projects_home_show.php --dry-run=1
 *   php local/tools/sync_projects_home_show.php --dry-run=0
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
        "dry-run::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/sync_projects_home_show.php [--dry-run=1]\n";
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

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
$projectsIblockId = function_exists("szcubeGetIblockIdByCode") ? (int)szcubeGetIblockIdByCode("projects") : 0;
if ($projectsIblockId <= 0) {
    echo "Projects iblock not found" . PHP_EOL;
    exit(2);
}

$yesEnumId = function_exists("szcubeGetPropertyEnumIdByXmlId") ? (int)szcubeGetPropertyEnumIdByXmlId($projectsIblockId, "HOME_SHOW", "Y") : 0;
$noEnumId = function_exists("szcubeGetPropertyEnumIdByXmlId") ? (int)szcubeGetPropertyEnumIdByXmlId($projectsIblockId, "HOME_SHOW", "N") : 0;
if ($yesEnumId <= 0 || $noEnumId <= 0) {
    echo "HOME_SHOW enum values not found. Run create_projects_iblock.php first." . PHP_EOL;
    exit(3);
}

$dataFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/projects-hardcode-source.php";
if (!is_file($dataFile)) {
    echo "Source file not found: " . $dataFile . PHP_EOL;
    exit(4);
}

$items = require $dataFile;
if (!is_array($items)) {
    echo "Source file returned unexpected data" . PHP_EOL;
    exit(4);
}

$processed = 0;
$updated = 0;
$skipped = 0;

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $code = isset($item["code"]) ? trim((string)$item["code"]) : "";
    if ($code === "") {
        continue;
    }

    $targetEnumId = strtoupper(trim((string)($item["home_show"] ?? "N"))) === "Y" ? $yesEnumId : $noEnumId;

    $elementRes = CIBlockElement::GetList(
        array(),
        array(
            "IBLOCK_ID" => $projectsIblockId,
            "=CODE" => $code,
        ),
        false,
        array("nTopCount" => 1),
        array("ID", "NAME")
    );
    $element = $elementRes->Fetch();
    if (!is_array($element)) {
        echo "[SKIP] Project not found by code: " . $code . PHP_EOL;
        $skipped++;
        continue;
    }

    $elementId = (int)$element["ID"];
    $currentProperty = CIBlockElement::GetProperty(
        $projectsIblockId,
        $elementId,
        array("sort" => "asc"),
        array("CODE" => "HOME_SHOW")
    )->Fetch();
    $currentEnumId = is_array($currentProperty) ? (int)$currentProperty["VALUE_ENUM_ID"] : 0;

    $processed++;
    if ($currentEnumId === $targetEnumId) {
        echo "[OK] " . $code . " already synced" . PHP_EOL;
        continue;
    }

    echo ($dryRun ? "[DRY]" : "[SYNC]") . " " . $code . " => " . ($targetEnumId === $yesEnumId ? "Y" : "N") . PHP_EOL;
    if ($dryRun) {
        $updated++;
        continue;
    }

    CIBlockElement::SetPropertyValuesEx($elementId, $projectsIblockId, array(
        "HOME_SHOW" => $targetEnumId,
    ));
    $updated++;
}

echo PHP_EOL;
echo "Processed: " . $processed . PHP_EOL;
echo "Updated: " . $updated . PHP_EOL;
echo "Skipped: " . $skipped . PHP_EOL;

exit(0);
