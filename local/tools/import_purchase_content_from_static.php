<?php
/**
 * Импортирует текущую статику purchase-страниц в ИБ:
 * - purchase_pages
 * - purchase_page_cards
 * - mortgage_calculator_programs
 * - mortgage_calculator_banks
 *
 * CLI:
 *   php local/tools/import_purchase_content_from_static.php --dry-run=1
 *   php local/tools/import_purchase_content_from_static.php --dry-run=0
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
        echo "Usage: php local/tools/import_purchase_content_from_static.php [--dry-run=1]" . PHP_EOL;
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

$sourceFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/purchase-pages-source.php";
if (!is_file($sourceFile)) {
    echo "Source file not found: " . $sourceFile . PHP_EOL;
    exit(2);
}

$source = require $sourceFile;
if (!is_array($source)) {
    echo "Source file returned unexpected data type" . PHP_EOL;
    exit(2);
}

function purchaseContentImportFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function purchaseContentImportPropertyMap($iblockId)
{
    $result = array();
    $res = CIBlockProperty::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId)
    );
    while ($row = $res->Fetch()) {
        $code = trim((string)$row["CODE"]);
        if ($code === "") {
            continue;
        }

        $result[$code] = array(
            "ID" => (int)$row["ID"],
            "NAME" => (string)$row["NAME"],
            "TYPE" => (string)$row["PROPERTY_TYPE"],
        );
    }

    return $result;
}

function purchaseContentImportFindElementId($iblockId, $code)
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

function purchaseContentImportMakeFileArray($path)
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

function purchaseContentImportFindSectionId($iblockId, $code)
{
    $res = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId, "=CODE" => (string)$code),
        false,
        array("ID")
    );
    if ($row = $res->Fetch()) {
        return (int)$row["ID"];
    }

    return 0;
}

function purchaseContentImportEnsureSection($iblockId, $code, $name, $sort, $dryRun)
{
    $sectionId = purchaseContentImportFindSectionId($iblockId, $code);
    if ($sectionId > 0) {
        return $sectionId;
    }

    echo ($dryRun ? "[CREATE] " : "[CREATE] ") . "Section: " . $code . PHP_EOL;
    if ($dryRun) {
        return 0;
    }

    $section = new CIBlockSection();
    $newId = (int)$section->Add(array(
        "IBLOCK_ID" => (int)$iblockId,
        "ACTIVE" => "Y",
        "NAME" => (string)$name,
        "CODE" => (string)$code,
        "SORT" => (int)$sort,
    ));
    if ($newId <= 0) {
        echo "[ERROR] Failed to create section " . $code . ": " . $section->LAST_ERROR . PHP_EOL;
        exit(3);
    }

    return $newId;
}

function purchaseContentImportResolveEnumId($propertyId, $xmlId, $fallbackValue = "")
{
    $propertyId = (int)$propertyId;
    if ($propertyId <= 0) {
        return false;
    }

    $fallbackId = false;
    $res = CIBlockPropertyEnum::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("PROPERTY_ID" => $propertyId)
    );
    while ($row = $res->Fetch()) {
        $rowXmlId = trim((string)$row["XML_ID"]);
        $rowValue = trim((string)$row["VALUE"]);
        if ($xmlId !== "" && $rowXmlId === (string)$xmlId) {
            return (int)$row["ID"];
        }
        if ($fallbackValue !== "" && $rowValue === (string)$fallbackValue && $fallbackId === false) {
            $fallbackId = (int)$row["ID"];
        }
    }

    return $fallbackId;
}

function purchaseContentImportBuildPageFields(array $page, array $propertyMap)
{
    $fields = array(
        "ACTIVE" => "Y",
        "NAME" => (string)$page["label"],
        "CODE" => (string)$page["code"],
        "SORT" => (int)$page["sort"],
    );

    $propertyValues = array();

    $stringMap = array(
        "PAGE_URL" => "url",
        "HERO_TITLE" => "hero_title",
        "HERO_TEXT_1" => "hero_text_1",
        "HERO_TEXT_2" => "hero_text_2",
        "HERO_IMAGE_ALT" => "hero_image_alt",
        "PRIMARY_BUTTON_LABEL" => "primary_button_label",
        "PRIMARY_BUTTON_TITLE" => "primary_button_title",
        "PRIMARY_BUTTON_NOTE" => "primary_button_note",
        "PRIMARY_BUTTON_SOURCE" => "primary_button_source",
        "SECONDARY_BUTTON_LABEL" => "secondary_button_label",
        "SECONDARY_BUTTON_URL" => "secondary_button_url",
        "CALCULATOR_TITLE" => "calculator_title",
        "CALCULATOR_SUBTITLE" => "calculator_subtitle",
    );

    foreach ($stringMap as $propertyCode => $sourceKey) {
        if (!isset($propertyMap[$propertyCode])) {
            continue;
        }
        $propertyValues[$propertyCode] = trim((string)$page[$sourceKey]);
    }

    if (isset($propertyMap["SHOW_CALCULATOR"])) {
        $enumId = purchaseContentImportResolveEnumId(
            (int)$propertyMap["SHOW_CALCULATOR"]["ID"],
            strtoupper(trim((string)$page["show_calculator"])),
            strtoupper(trim((string)$page["show_calculator"])) === "Y" ? "Да" : "Нет"
        );
        if ($enumId !== false) {
            $propertyValues["SHOW_CALCULATOR"] = $enumId;
        }
    }

    if (isset($propertyMap["HERO_IMAGE"])) {
        $file = purchaseContentImportMakeFileArray((string)$page["hero_image"]);
        if ($file !== false) {
            $propertyValues["HERO_IMAGE"] = array("VALUE" => $file);
        }
    }

    if (!empty($propertyValues)) {
        $fields["PROPERTY_VALUES"] = $propertyValues;
    }

    return $fields;
}

function purchaseContentImportBuildCardFields(array $card, $sectionId, array $propertyMap)
{
    $fields = array(
        "ACTIVE" => "Y",
        "IBLOCK_SECTION_ID" => (int)$sectionId,
        "NAME" => (string)$card["title"],
        "CODE" => (string)$card["code"],
        "SORT" => (int)$card["sort"],
        "PREVIEW_TEXT" => (string)$card["text"],
        "PREVIEW_TEXT_TYPE" => "text",
    );

    $previewPicture = purchaseContentImportMakeFileArray((string)$card["image"]);
    if ($previewPicture !== false) {
        $fields["PREVIEW_PICTURE"] = $previewPicture;
    }

    $propertyValues = array();

    if (isset($propertyMap["CARD_LAYOUT"])) {
        $enumId = purchaseContentImportResolveEnumId((int)$propertyMap["CARD_LAYOUT"]["ID"], (string)$card["layout"], "Обычная");
        if ($enumId !== false) {
            $propertyValues["CARD_LAYOUT"] = $enumId;
        }
    }

    $stringMap = array(
        "IMAGE_ALT" => "image_alt",
    );

    foreach ($stringMap as $propertyCode => $sourceKey) {
        if (!isset($propertyMap[$propertyCode]) || !isset($card[$sourceKey])) {
            continue;
        }
        $propertyValues[$propertyCode] = trim((string)$card[$sourceKey]);
    }

    if (!empty($propertyValues)) {
        $fields["PROPERTY_VALUES"] = $propertyValues;
    }

    return $fields;
}

function purchaseContentImportBuildProgramFields(array $program, array $propertyMap)
{
    $fields = array(
        "ACTIVE" => "Y",
        "NAME" => (string)$program["label"],
        "CODE" => (string)$program["code"],
        "SORT" => (int)$program["sort"],
    );

    if (isset($propertyMap["RATE"])) {
        $fields["PROPERTY_VALUES"] = array(
            "RATE" => (float)$program["rate"],
        );
    }

    return $fields;
}

function purchaseContentImportBuildBankFields(array $bank, array $propertyMap)
{
    $fields = array(
        "ACTIVE" => "Y",
        "NAME" => (string)$bank["label"],
        "CODE" => (string)$bank["code"],
        "SORT" => (int)$bank["sort"],
    );

    $propertyValues = array();
    $stringMap = array(
        "MARK" => "mark",
        "TONE_COLOR" => "tone",
        "ACCENT_COLOR" => "accent",
    );
    foreach ($stringMap as $propertyCode => $sourceKey) {
        if (!isset($propertyMap[$propertyCode])) {
            continue;
        }
        $propertyValues[$propertyCode] = trim((string)$bank[$sourceKey]);
    }

    if (!empty($propertyValues)) {
        $fields["PROPERTY_VALUES"] = $propertyValues;
    }

    return $fields;
}

function purchaseContentImportUpsertElement($iblockId, $code, array $fields, $dryRun, $entityLabel)
{
    $elementId = purchaseContentImportFindElementId($iblockId, $code);
    $action = $elementId > 0 ? "UPDATE" : "CREATE";
    $propertyValues = array();

    if (isset($fields["PROPERTY_VALUES"]) && is_array($fields["PROPERTY_VALUES"])) {
        $propertyValues = $fields["PROPERTY_VALUES"];
        unset($fields["PROPERTY_VALUES"]);
    }

    echo "[" . $action . "] " . $entityLabel . " :: " . $code . PHP_EOL;
    if ($dryRun) {
        return $elementId;
    }

    $element = new CIBlockElement();
    if ($elementId > 0) {
        if (!$element->Update((int)$elementId, $fields)) {
            echo "[ERROR] Failed to update " . $entityLabel . " " . $code . ": " . $element->LAST_ERROR . PHP_EOL;
            exit(4);
        }
        if (!empty($propertyValues)) {
            CIBlockElement::SetPropertyValuesEx((int)$elementId, (int)$iblockId, $propertyValues);
        }
        return (int)$elementId;
    }

    $fields["IBLOCK_ID"] = (int)$iblockId;
    $newId = (int)$element->Add($fields);
    if ($newId <= 0) {
        echo "[ERROR] Failed to create " . $entityLabel . " " . $code . ": " . $element->LAST_ERROR . PHP_EOL;
        exit(4);
    }

    if (!empty($propertyValues)) {
        CIBlockElement::SetPropertyValuesEx((int)$newId, (int)$iblockId, $propertyValues);
    }

    return $newId;
}

$pagesIblock = purchaseContentImportFindIblock("purchase_pages");
$cardsIblock = purchaseContentImportFindIblock("purchase_page_cards");
$programsIblock = purchaseContentImportFindIblock("mortgage_calculator_programs");
$banksIblock = purchaseContentImportFindIblock("mortgage_calculator_banks");

if (!is_array($pagesIblock) || !is_array($cardsIblock) || !is_array($programsIblock) || !is_array($banksIblock)) {
    echo "One or more target iblocks are missing. Run create_purchase_content_iblocks.php first." . PHP_EOL;
    exit(2);
}

$pagesIblockId = (int)$pagesIblock["ID"];
$cardsIblockId = (int)$cardsIblock["ID"];
$programsIblockId = (int)$programsIblock["ID"];
$banksIblockId = (int)$banksIblock["ID"];

$pagePropertyMap = purchaseContentImportPropertyMap($pagesIblockId);
$cardPropertyMap = purchaseContentImportPropertyMap($cardsIblockId);
$programPropertyMap = purchaseContentImportPropertyMap($programsIblockId);
$bankPropertyMap = purchaseContentImportPropertyMap($banksIblockId);

$pageMeta = array();
if (isset($source["pages"]) && is_array($source["pages"])) {
    foreach ($source["pages"] as $page) {
        if (!is_array($page) || empty($page["code"])) {
            continue;
        }

        $pageCode = trim((string)$page["code"]);
        $pageMeta[$pageCode] = $page;
        $fields = purchaseContentImportBuildPageFields($page, $pagePropertyMap);
        purchaseContentImportUpsertElement($pagesIblockId, $pageCode, $fields, $dryRun, "purchase_page");
    }
}

if (isset($source["cards"]) && is_array($source["cards"])) {
    foreach ($source["cards"] as $pageCode => $cards) {
        $pageCode = trim((string)$pageCode);
        if ($pageCode === "" || !is_array($cards)) {
            continue;
        }

        $pageLabel = isset($pageMeta[$pageCode]["label"]) ? (string)$pageMeta[$pageCode]["label"] : $pageCode;
        $pageSort = isset($pageMeta[$pageCode]["sort"]) ? (int)$pageMeta[$pageCode]["sort"] : 500;
        $sectionId = purchaseContentImportEnsureSection($cardsIblockId, $pageCode, $pageLabel, $pageSort, $dryRun);
        if ($dryRun && $sectionId <= 0) {
            $sectionId = purchaseContentImportFindSectionId($cardsIblockId, $pageCode);
        }

        foreach ($cards as $card) {
            if (!is_array($card) || empty($card["code"])) {
                continue;
            }

            $fields = purchaseContentImportBuildCardFields($card, $sectionId, $cardPropertyMap);
            purchaseContentImportUpsertElement($cardsIblockId, (string)$card["code"], $fields, $dryRun, "purchase_card");
        }
    }
}

if (isset($source["calculator"]["programs"]) && is_array($source["calculator"]["programs"])) {
    foreach ($source["calculator"]["programs"] as $program) {
        if (!is_array($program) || empty($program["code"])) {
            continue;
        }

        $fields = purchaseContentImportBuildProgramFields($program, $programPropertyMap);
        purchaseContentImportUpsertElement($programsIblockId, (string)$program["code"], $fields, $dryRun, "mortgage_program");
    }
}

if (isset($source["calculator"]["banks"]) && is_array($source["calculator"]["banks"])) {
    foreach ($source["calculator"]["banks"] as $bank) {
        if (!is_array($bank) || empty($bank["code"])) {
            continue;
        }

        $fields = purchaseContentImportBuildBankFields($bank, $bankPropertyMap);
        purchaseContentImportUpsertElement($banksIblockId, (string)$bank["code"], $fields, $dryRun, "mortgage_bank");
    }
}

echo PHP_EOL;
echo "Done." . PHP_EOL;

exit(0);
