<?php
/**
 * Импортирует текущую статику intro-блоков в ИБ catalog_pages.
 *
 * CLI:
 *   php local/tools/import_catalog_pages_from_static.php --dry-run=1
 *   php local/tools/import_catalog_pages_from_static.php --dry-run=0
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
        echo "Usage: php local/tools/import_catalog_pages_from_static.php [--dry-run=1]" . PHP_EOL;
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

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$sourceFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/catalog-pages-source.php";
if (!is_file($sourceFile)) {
    echo "Source file not found: " . $sourceFile . PHP_EOL;
    exit(2);
}

$source = require $sourceFile;
if (!is_array($source) || !isset($source["pages"]) || !is_array($source["pages"])) {
    echo "Source file returned unexpected data type" . PHP_EOL;
    exit(2);
}

function catalogPagesImportFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function catalogPagesImportFindElementId($iblockId, $code)
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

function catalogPagesImportMakeFileArray($path)
{
    $path = trim((string)$path);
    if ($path === "") {
        return false;
    }

    $absPath = $_SERVER["DOCUMENT_ROOT"] . str_replace("\\", "/", $path);
    if (!is_file($absPath)) {
        return false;
    }

    return CFile::MakeFileArray($absPath);
}

$iblock = catalogPagesImportFindIblock("catalog_pages");
if (!is_array($iblock)) {
    echo "[ERROR] IBlock not found by code: catalog_pages" . PHP_EOL;
    exit(2);
}

$iblockId = (int)$iblock["ID"];
$elementApi = new CIBlockElement();

foreach ($source["pages"] as $page) {
    $code = trim((string)$page["code"]);
    if ($code === "") {
        continue;
    }

    $elementId = catalogPagesImportFindElementId($iblockId, $code);
    $fields = array(
        "IBLOCK_ID" => $iblockId,
        "ACTIVE" => "Y",
        "NAME" => trim((string)$page["label"]),
        "CODE" => $code,
        "SORT" => (int)$page["sort"],
    );

    $propertyValues = array(
        "INTRO_TEXT_1" => trim((string)$page["intro_text_1"]),
        "INTRO_TEXT_2" => trim((string)$page["intro_text_2"]),
        "INTRO_IMAGE_ALT" => trim((string)$page["intro_image_alt"]),
    );

    $file = catalogPagesImportMakeFileArray((string)$page["intro_image"]);
    if ($file !== false) {
        $propertyValues["INTRO_IMAGE"] = array("VALUE" => $file);
    }

    if ($elementId > 0) {
        echo "[UPDATE] catalog_page :: " . $code . PHP_EOL;
        if ($dryRun) {
            continue;
        }

        if (!$elementApi->Update($elementId, $fields)) {
            echo "[ERROR] Failed to update element " . $code . ": " . $elementApi->LAST_ERROR . PHP_EOL;
            exit(3);
        }
        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $propertyValues);
        continue;
    }

    echo "[CREATE] catalog_page :: " . $code . PHP_EOL;
    if ($dryRun) {
        continue;
    }

    $newId = (int)$elementApi->Add($fields);
    if ($newId <= 0) {
        echo "[ERROR] Failed to create element " . $code . ": " . $elementApi->LAST_ERROR . PHP_EOL;
        exit(3);
    }
    CIBlockElement::SetPropertyValuesEx($newId, $iblockId, $propertyValues);
}

echo PHP_EOL;
echo "Done." . PHP_EOL;

exit(0);
