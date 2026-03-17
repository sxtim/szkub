<?php
/**
 * Удаляет legacy-демо-хвост квартирного контура:
 * - старые квартиры vertical-235 / vertical-236
 * - ошибочные ранние дубли с CODE=301 / XML_ID=1-3-301
 * - пустые дочерние разделы под top-section vertical
 *
 * Скрипт узконаправленный и безопасен для текущего релиза.
 *
 * CLI:
 *   php local/tools/cleanup_legacy_apartment_samples.php --dry-run=1
 *   php local/tools/cleanup_legacy_apartment_samples.php --dry-run=0
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
        echo "Usage: php local/tools/cleanup_legacy_apartment_samples.php [--dry-run=1]\n";
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
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function cleanupLegacyFindIblockByCode($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function cleanupLegacyFindSectionByCodeAndParent($iblockId, $parentId, $code)
{
    $filter = array(
        "IBLOCK_ID" => (int)$iblockId,
        "=CODE" => (string)$code,
    );
    $filter["SECTION_ID"] = $parentId > 0 ? (int)$parentId : false;

    $res = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        $filter,
        false,
        array("ID", "NAME", "CODE", "IBLOCK_SECTION_ID")
    );

    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function cleanupLegacyFindElements($iblockId, array $codeMap, array $xmlIdMap)
{
    $result = array();
    $res = CIBlockElement::GetList(
        array("ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId),
        false,
        false,
        array("ID", "NAME", "CODE", "XML_ID", "IBLOCK_SECTION_ID", "ACTIVE")
    );

    while ($row = $res->Fetch()) {
        $code = trim((string)$row["CODE"]);
        $xmlId = trim((string)$row["XML_ID"]);

        if (($code !== "" && isset($codeMap[$code])) || ($xmlId !== "" && isset($xmlIdMap[$xmlId]))) {
            $result[(int)$row["ID"]] = $row;
        }
    }

    return $result;
}

function cleanupLegacySectionHasElements($iblockId, $sectionId)
{
    $res = CIBlockElement::GetList(
        array("ID" => "ASC"),
        array(
            "IBLOCK_ID" => (int)$iblockId,
            "SECTION_ID" => (int)$sectionId,
            "INCLUDE_SUBSECTIONS" => "Y",
        ),
        false,
        array("nTopCount" => 1),
        array("ID")
    );

    return (bool)$res->Fetch();
}

function cleanupLegacyCollectDescendantSections($iblockId, $parentId)
{
    $result = array();
    $res = CIBlockSection::GetList(
        array("DEPTH_LEVEL" => "DESC", "SORT" => "ASC", "ID" => "DESC"),
        array(
            "IBLOCK_ID" => (int)$iblockId,
            "SECTION_ID" => (int)$parentId,
        ),
        false,
        array("ID", "NAME", "CODE", "IBLOCK_SECTION_ID", "DEPTH_LEVEL")
    );

    while ($row = $res->Fetch()) {
        $result[] = $row;
    }

    return $result;
}

$apartmentsIblock = cleanupLegacyFindIblockByCode("apartments");
if (!is_array($apartmentsIblock)) {
    echo "[ERROR] Apartments iblock not found" . PHP_EOL;
    exit(2);
}

$apartmentsIblockId = (int)$apartmentsIblock["ID"];
$legacyCodes = array_fill_keys(array(
    "vertical-235",
    "vertical-236",
    "301",
), true);
$legacyXmlIds = array_fill_keys(array(
    "vertical-2-6-235",
    "vertical-2-6-236",
    "1-3-301",
), true);

$legacyElements = cleanupLegacyFindElements($apartmentsIblockId, $legacyCodes, $legacyXmlIds);
if (empty($legacyElements)) {
    echo "[OK] No legacy apartment elements found" . PHP_EOL;
} else {
    foreach ($legacyElements as $row) {
        echo "[DELETE] Legacy apartment: ID=" . (int)$row["ID"] . ", CODE=" . (string)$row["CODE"] . ", XML_ID=" . (string)$row["XML_ID"] . PHP_EOL;
        if (!$dryRun && !CIBlockElement::Delete((int)$row["ID"])) {
            echo "[ERROR] Failed to delete legacy apartment ID=" . (int)$row["ID"] . PHP_EOL;
            exit(3);
        }
    }
}

$verticalSection = cleanupLegacyFindSectionByCodeAndParent($apartmentsIblockId, 0, "vertical");
if (!is_array($verticalSection)) {
    echo "[OK] Top section vertical not found, nothing to clean" . PHP_EOL;
    exit(0);
}

if (cleanupLegacySectionHasElements($apartmentsIblockId, (int)$verticalSection["ID"])) {
    echo "[WARN] Vertical section still contains elements after legacy cleanup, child sections will be kept" . PHP_EOL;
    exit(0);
}

$verticalChildren = cleanupLegacyCollectDescendantSections($apartmentsIblockId, (int)$verticalSection["ID"]);
if (empty($verticalChildren)) {
    echo "[OK] Vertical child sections already absent" . PHP_EOL;
    exit(0);
}

foreach ($verticalChildren as $section) {
    echo "[DELETE] Legacy section: ID=" . (int)$section["ID"] . ", CODE=" . (string)$section["CODE"] . PHP_EOL;
    if (!$dryRun && !CIBlockSection::Delete((int)$section["ID"])) {
        echo "[ERROR] Failed to delete legacy section ID=" . (int)$section["ID"] . PHP_EOL;
        exit(4);
    }
}

echo "[OK] Legacy apartment samples cleaned." . PHP_EOL;
exit(0);
