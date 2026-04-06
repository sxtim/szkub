<?php
/**
 * Создает (или проверяет) ИБ "Кроме квартир: страницы каталогов" (catalog_pages).
 *
 * CLI:
 *   php local/tools/create_catalog_pages_iblock.php
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
        "site-id::",
        "type-id::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/create_catalog_pages_iblock.php [--site-id=s1] [--type-id=realty]" . PHP_EOL;
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

$siteId = isset($_REQUEST["site_id"]) && $_REQUEST["site_id"] !== "" ? (string)$_REQUEST["site_id"] : "s1";
$typeId = isset($_REQUEST["type_id"]) && $_REQUEST["type_id"] !== "" ? (string)$_REQUEST["type_id"] : "realty";
$iblockCode = "catalog_pages";
$iblockName = "Кроме квартир: страницы каталогов";

function catalogPagesFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function catalogPagesEnsureProperty($iblockId, array $propertyDef)
{
    $propRes = CIBlockProperty::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId, "CODE" => (string)$propertyDef["CODE"])
    );
    if ($propRes->Fetch()) {
        echo "[OK] Property exists: " . $propertyDef["CODE"] . PHP_EOL;
        return;
    }

    $property = new CIBlockProperty();
    $fields = array(
        "IBLOCK_ID" => (int)$iblockId,
        "NAME" => (string)$propertyDef["NAME"],
        "CODE" => (string)$propertyDef["CODE"],
        "PROPERTY_TYPE" => (string)$propertyDef["PROPERTY_TYPE"],
        "ACTIVE" => "Y",
        "MULTIPLE" => "N",
        "IS_REQUIRED" => "N",
        "SORT" => (int)$propertyDef["SORT"],
        "COL_COUNT" => isset($propertyDef["COL_COUNT"]) ? (int)$propertyDef["COL_COUNT"] : 0,
        "ROW_COUNT" => isset($propertyDef["ROW_COUNT"]) ? (int)$propertyDef["ROW_COUNT"] : 0,
    );

    $newPropId = $property->Add($fields);
    if (!$newPropId) {
        echo "[ERROR] Failed to create property " . $propertyDef["CODE"] . ": " . $property->LAST_ERROR . PHP_EOL;
        exit(3);
    }

    echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . (int)$newPropId . ")" . PHP_EOL;
}

$referenceIblock = catalogPagesFindIblock("projects");
if (is_array($referenceIblock) && trim((string)$referenceIblock["IBLOCK_TYPE_ID"]) !== "") {
    $typeId = (string)$referenceIblock["IBLOCK_TYPE_ID"];
}

$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if (!$typeRes->Fetch()) {
    echo "IBlock type '" . $typeId . "' does not exist." . PHP_EOL;
    exit(2);
}

$iblock = catalogPagesFindIblock($iblockCode);
if (is_array($iblock)) {
    $iblockId = (int)$iblock["ID"];
    echo "[OK] IBlock exists: ID=" . $iblockId . ", NAME=" . $iblock["NAME"] . PHP_EOL;
} else {
    $ib = new CIBlock();
    $iblockId = (int)$ib->Add(array(
        "SITE_ID" => array($siteId),
        "NAME" => $iblockName,
        "ACTIVE" => "Y",
        "SORT" => 570,
        "CODE" => $iblockCode,
        "IBLOCK_TYPE_ID" => $typeId,
        "SECTIONS" => "N",
        "GROUP_ID" => array("2" => "R"),
        "VERSION" => 2,
        "FIELDS" => array(
            "CODE" => array(
                "DEFAULT_VALUE" => array(
                    "TRANSLITERATION" => "Y",
                    "TRANS_LEN" => 100,
                    "UNIQUE" => "Y",
                    "TRANS_CASE" => "L",
                    "TRANS_SPACE" => "-",
                    "TRANS_OTHER" => "-",
                    "TRANS_EAT" => "Y",
                    "USE_GOOGLE" => "N",
                ),
            ),
        ),
    ));

    if ($iblockId <= 0) {
        echo "[ERROR] Failed to create iblock " . $iblockCode . ": " . $ib->LAST_ERROR . PHP_EOL;
        exit(3);
    }

    echo "[CREATE] IBlock created: ID=" . $iblockId . ", NAME=" . $iblockName . PHP_EOL;
}

$properties = array(
    array(
        "CODE" => "INTRO_TEXT_1",
        "NAME" => "Интро: текст, абзац 1",
        "PROPERTY_TYPE" => "S",
        "SORT" => 100,
        "COL_COUNT" => 70,
        "ROW_COUNT" => 6,
    ),
    array(
        "CODE" => "INTRO_TEXT_2",
        "NAME" => "Интро: текст, абзац 2",
        "PROPERTY_TYPE" => "S",
        "SORT" => 110,
        "COL_COUNT" => 70,
        "ROW_COUNT" => 6,
    ),
    array(
        "CODE" => "INTRO_IMAGE",
        "NAME" => "Интро: изображение",
        "PROPERTY_TYPE" => "F",
        "SORT" => 120,
    ),
    array(
        "CODE" => "INTRO_IMAGE_ALT",
        "NAME" => "Интро: alt изображения",
        "PROPERTY_TYPE" => "S",
        "SORT" => 130,
        "COL_COUNT" => 70,
        "ROW_COUNT" => 1,
    ),
);

foreach ($properties as $propertyDef) {
    catalogPagesEnsureProperty($iblockId, $propertyDef);
}

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "- catalog_pages ID=" . $iblockId . PHP_EOL;

exit(0);
