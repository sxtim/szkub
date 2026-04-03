<?php
/**
 * Безопасно синхронизирует подписи enum-значений свойства ABOUT_COMPANY_STATUS.
 *
 * Запуск:
 *   php local/tools/sync_projects_status_enums.php --dry-run=1
 *   php local/tools/sync_projects_status_enums.php --dry-run=0
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
        echo "Usage: php local/tools/sync_projects_status_enums.php [--dry-run=1]\n";
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

$propertyRes = CIBlockProperty::GetList(
    array(),
    array(
        "IBLOCK_ID" => $projectsIblockId,
        "CODE" => "ABOUT_COMPANY_STATUS",
    )
);
$property = $propertyRes->Fetch();
if (!is_array($property)) {
    echo "Property ABOUT_COMPANY_STATUS not found" . PHP_EOL;
    exit(3);
}

$expected = array(
    "building" => array(
        "VALUE" => "В продаже",
        "SORT" => 100,
    ),
    "planned" => array(
        "VALUE" => "Скоро в продаже",
        "SORT" => 200,
    ),
    "completed" => array(
        "VALUE" => "Реализован",
        "SORT" => 300,
    ),
);

$enum = new CIBlockPropertyEnum();
$processed = 0;
$updated = 0;
$missing = array();

$enumRes = CIBlockPropertyEnum::GetList(
    array("SORT" => "ASC", "ID" => "ASC"),
    array(
        "PROPERTY_ID" => (int)$property["ID"],
    )
);

while ($row = $enumRes->Fetch()) {
    $xmlId = trim((string)$row["XML_ID"]);
    if ($xmlId === "" || !isset($expected[$xmlId])) {
        continue;
    }

    $processed++;
    $target = $expected[$xmlId];
    $needsUpdate = (string)$row["VALUE"] !== (string)$target["VALUE"] || (int)$row["SORT"] !== (int)$target["SORT"];

    if (!$needsUpdate) {
        echo "[OK] " . $xmlId . " already synced" . PHP_EOL;
        unset($expected[$xmlId]);
        continue;
    }

    echo ($dryRun ? "[DRY]" : "[SYNC]") . " " . $xmlId . " => " . $target["VALUE"] . PHP_EOL;
    if (!$dryRun) {
        if (!$enum->Update((int)$row["ID"], array(
            "PROPERTY_ID" => (int)$property["ID"],
            "VALUE" => $target["VALUE"],
            "XML_ID" => $xmlId,
            "SORT" => $target["SORT"],
        ))) {
            echo "[ERROR] Failed to update enum " . $xmlId . ": " . $enum->LAST_ERROR . PHP_EOL;
            exit(4);
        }
    }

    $updated++;
    unset($expected[$xmlId]);
}

if (!empty($expected)) {
    $missing = array_keys($expected);
}

echo PHP_EOL;
echo "Processed: " . $processed . PHP_EOL;
echo "Updated: " . $updated . PHP_EOL;
if (!empty($missing)) {
    echo "Missing XML_IDs: " . implode(", ", $missing) . PHP_EOL;
}

exit(0);
