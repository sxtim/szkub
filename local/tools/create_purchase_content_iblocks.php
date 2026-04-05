<?php
/**
 * Создает (или проверяет) инфоблоки:
 * - purchase_pages
 * - purchase_page_cards
 * - mortgage_calculator_programs
 * - mortgage_calculator_banks
 *
 * CLI:
 *   php local/tools/create_purchase_content_iblocks.php
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
        echo "Usage: php local/tools/create_purchase_content_iblocks.php [--site-id=s1] [--type-id=realty]" . PHP_EOL;
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

function purchaseContentFindIblockByCode($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function purchaseContentEnsureIblock($siteId, $typeId, $code, $name, $sort, $sections)
{
    $existing = purchaseContentFindIblockByCode($code);
    if (is_array($existing)) {
        $iblockId = (int)$existing["ID"];
        $currentTypeId = trim((string)$existing["IBLOCK_TYPE_ID"]);
        if ($currentTypeId !== $typeId) {
            $ib = new CIBlock();
            if (!$ib->Update($iblockId, array("IBLOCK_TYPE_ID" => $typeId))) {
                echo "[ERROR] Failed to update iblock type for " . $code . ": " . $ib->LAST_ERROR . PHP_EOL;
                exit(3);
            }
            echo "[UPDATE] IBlock type changed: " . $name . " (ID=" . $iblockId . ", " . $currentTypeId . " -> " . $typeId . ")" . PHP_EOL;
        } else {
            echo "[OK] IBlock exists: " . $name . " (ID=" . $iblockId . ", CODE=" . $code . ")" . PHP_EOL;
        }
        return $iblockId;
    }

    $fields = array(
        "SITE_ID" => array($siteId),
        "NAME" => $name,
        "ACTIVE" => "Y",
        "SORT" => (int)$sort,
        "CODE" => $code,
        "IBLOCK_TYPE_ID" => $typeId,
        "SECTIONS" => $sections ? "Y" : "N",
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
    );

    if ($sections) {
        $fields["SECTION_CHOOSER"] = "L";
        $fields["FIELDS"]["SECTION_CODE"] = array(
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
        );
    }

    $ib = new CIBlock();
    $newId = (int)$ib->Add($fields);
    if ($newId <= 0) {
        echo "[ERROR] Failed to create iblock " . $code . ": " . $ib->LAST_ERROR . PHP_EOL;
        exit(3);
    }

    echo "[CREATE] IBlock created: " . $name . " (ID=" . $newId . ", CODE=" . $code . ")" . PHP_EOL;
    return $newId;
}

function purchaseContentEnsureProperty($iblockId, array $propertyDef)
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
        "MULTIPLE" => isset($propertyDef["MULTIPLE"]) ? (string)$propertyDef["MULTIPLE"] : "N",
        "IS_REQUIRED" => isset($propertyDef["IS_REQUIRED"]) ? (string)$propertyDef["IS_REQUIRED"] : "N",
        "SORT" => isset($propertyDef["SORT"]) ? (int)$propertyDef["SORT"] : 500,
        "COL_COUNT" => isset($propertyDef["COL_COUNT"]) ? (int)$propertyDef["COL_COUNT"] : 0,
        "ROW_COUNT" => isset($propertyDef["ROW_COUNT"]) ? (int)$propertyDef["ROW_COUNT"] : 0,
    );

    $optionalFields = array(
        "LIST_TYPE",
        "USER_TYPE",
        "USER_TYPE_SETTINGS",
        "WITH_DESCRIPTION",
        "MULTIPLE_CNT",
        "HINT",
    );
    foreach ($optionalFields as $fieldName) {
        if (array_key_exists($fieldName, $propertyDef)) {
            $fields[$fieldName] = $propertyDef[$fieldName];
        }
    }

    if (isset($propertyDef["VALUES"]) && is_array($propertyDef["VALUES"])) {
        $fields["VALUES"] = $propertyDef["VALUES"];
    }

    $newPropId = $property->Add($fields);
    if (!$newPropId) {
        echo "[ERROR] Failed to create property " . $propertyDef["CODE"] . ": " . $property->LAST_ERROR . PHP_EOL;
        exit(4);
    }

    echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . (int)$newPropId . ")" . PHP_EOL;
}

function purchaseContentDeletePropertyByCode($iblockId, $code)
{
    $propRes = CIBlockProperty::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId, "CODE" => (string)$code)
    );
    if (!($property = $propRes->Fetch())) {
        return;
    }

    if (!CIBlockProperty::Delete((int)$property["ID"])) {
        echo "[ERROR] Failed to delete property " . $code . PHP_EOL;
        exit(4);
    }

    echo "[DELETE] Property removed: " . $code . PHP_EOL;
}

function purchaseContentEnsureSection($iblockId, $code, $name, $sort)
{
    $sectionRes = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId, "=CODE" => (string)$code),
        false,
        array("ID", "NAME")
    );
    if ($section = $sectionRes->Fetch()) {
        echo "[OK] Section exists: " . $code . " (ID=" . (int)$section["ID"] . ")" . PHP_EOL;
        return (int)$section["ID"];
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
        exit(5);
    }

    echo "[CREATE] Section created: " . $code . " (ID=" . $newId . ")" . PHP_EOL;
    return $newId;
}

$referenceIblock = purchaseContentFindIblockByCode("projects");
if (is_array($referenceIblock) && trim((string)$referenceIblock["IBLOCK_TYPE_ID"]) !== "") {
    $typeId = (string)$referenceIblock["IBLOCK_TYPE_ID"];
}

$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if (!$typeRes->Fetch()) {
    echo "IBlock type '" . $typeId . "' does not exist." . PHP_EOL;
    exit(2);
}

$pagesIblockId = purchaseContentEnsureIblock($siteId, $typeId, "purchase_pages", "Страницы покупки", 560, false);
$cardsIblockId = purchaseContentEnsureIblock($siteId, $typeId, "purchase_page_cards", "Страницы покупки: карточки", 561, true);
$programsIblockId = purchaseContentEnsureIblock($siteId, $typeId, "mortgage_calculator_programs", "Ипотечный калькулятор: программы", 562, false);
$banksIblockId = purchaseContentEnsureIblock($siteId, $typeId, "mortgage_calculator_banks", "Ипотечный калькулятор: банки", 563, false);

CIBlock::SetMessages($pagesIblockId, array(
    "ELEMENTS_NAME" => "Страницы",
    "ELEMENT_NAME" => "Страница",
));

CIBlock::SetMessages($cardsIblockId, array(
    "ELEMENTS_NAME" => "Карточки",
    "ELEMENT_NAME" => "Карточка",
    "SECTIONS_NAME" => "Страницы",
    "SECTION_NAME" => "Страница",
));

CIBlock::SetMessages($programsIblockId, array(
    "ELEMENTS_NAME" => "Программы",
    "ELEMENT_NAME" => "Программа",
));

CIBlock::SetMessages($banksIblockId, array(
    "ELEMENTS_NAME" => "Банки",
    "ELEMENT_NAME" => "Банк",
));

$pagesProperties = array(
    array("CODE" => "PAGE_URL", "NAME" => "URL страницы", "PROPERTY_TYPE" => "S", "SORT" => 100, "COL_COUNT" => 80),
    array("CODE" => "HERO_TITLE", "NAME" => "Hero: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 110, "COL_COUNT" => 60),
    array("CODE" => "HERO_TEXT_1", "NAME" => "Hero: текст, абзац 1", "PROPERTY_TYPE" => "S", "SORT" => 120, "ROW_COUNT" => 5, "COL_COUNT" => 60),
    array("CODE" => "HERO_TEXT_2", "NAME" => "Hero: текст, абзац 2", "PROPERTY_TYPE" => "S", "SORT" => 130, "ROW_COUNT" => 5, "COL_COUNT" => 60),
    array("CODE" => "HERO_IMAGE", "NAME" => "Hero: изображение", "PROPERTY_TYPE" => "F", "SORT" => 140),
    array("CODE" => "HERO_IMAGE_ALT", "NAME" => "Hero: alt изображения", "PROPERTY_TYPE" => "S", "SORT" => 150, "COL_COUNT" => 60),
    array("CODE" => "PRIMARY_BUTTON_LABEL", "NAME" => "Основная кнопка: текст", "PROPERTY_TYPE" => "S", "SORT" => 200, "COL_COUNT" => 40),
    array("CODE" => "PRIMARY_BUTTON_TITLE", "NAME" => "Основная кнопка: title формы", "PROPERTY_TYPE" => "S", "SORT" => 210, "COL_COUNT" => 60),
    array("CODE" => "PRIMARY_BUTTON_NOTE", "NAME" => "Основная кнопка: note формы", "PROPERTY_TYPE" => "S", "SORT" => 220, "ROW_COUNT" => 4, "COL_COUNT" => 60),
    array("CODE" => "PRIMARY_BUTTON_SOURCE", "NAME" => "Основная кнопка: source", "PROPERTY_TYPE" => "S", "SORT" => 230, "COL_COUNT" => 40),
    array("CODE" => "SECONDARY_BUTTON_LABEL", "NAME" => "Вторичная кнопка: текст", "PROPERTY_TYPE" => "S", "SORT" => 240, "COL_COUNT" => 40),
    array("CODE" => "SECONDARY_BUTTON_URL", "NAME" => "Вторичная кнопка: URL", "PROPERTY_TYPE" => "S", "SORT" => 250, "COL_COUNT" => 80),
    array(
        "CODE" => "SHOW_CALCULATOR",
        "NAME" => "Показывать калькулятор",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 300,
        "VALUES" => array(
            array("VALUE" => "Да", "XML_ID" => "Y", "SORT" => 100),
            array("VALUE" => "Нет", "XML_ID" => "N", "SORT" => 200),
        ),
    ),
    array("CODE" => "CALCULATOR_TITLE", "NAME" => "Калькулятор: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 310, "COL_COUNT" => 60),
    array("CODE" => "CALCULATOR_SUBTITLE", "NAME" => "Калькулятор: подзаголовок", "PROPERTY_TYPE" => "S", "SORT" => 320, "ROW_COUNT" => 4, "COL_COUNT" => 60),
);

foreach ($pagesProperties as $propertyDef) {
    purchaseContentEnsureProperty($pagesIblockId, $propertyDef);
}

$cardProperties = array(
    array(
        "CODE" => "CARD_LAYOUT",
        "NAME" => "Макет карточки",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 100,
        "VALUES" => array(
            array("VALUE" => "Обычная", "XML_ID" => "default", "SORT" => 100),
            array("VALUE" => "Высокая", "XML_ID" => "tall", "SORT" => 200),
            array("VALUE" => "Низкая", "XML_ID" => "short", "SORT" => 300),
        ),
    ),
    array("CODE" => "IMAGE_ALT", "NAME" => "Alt изображения", "PROPERTY_TYPE" => "S", "SORT" => 110, "COL_COUNT" => 60),
);

foreach ($cardProperties as $propertyDef) {
    purchaseContentEnsureProperty($cardsIblockId, $propertyDef);
}

foreach (array("BLOCK_CODE", "CARD_TYPE", "BUTTON_LABEL", "BUTTON_TITLE", "BUTTON_NOTE", "BUTTON_SOURCE", "BUTTON_URL") as $deprecatedPropertyCode) {
    purchaseContentDeletePropertyByCode($cardsIblockId, $deprecatedPropertyCode);
}

$programProperties = array(
    array("CODE" => "RATE", "NAME" => "Ставка, %", "PROPERTY_TYPE" => "N", "SORT" => 100, "COL_COUNT" => 20),
);

foreach ($programProperties as $propertyDef) {
    purchaseContentEnsureProperty($programsIblockId, $propertyDef);
}

$bankProperties = array(
    array("CODE" => "MARK", "NAME" => "Короткая метка", "PROPERTY_TYPE" => "S", "SORT" => 100, "COL_COUNT" => 20),
    array("CODE" => "TONE_COLOR", "NAME" => "Фоновый цвет", "PROPERTY_TYPE" => "S", "SORT" => 110, "COL_COUNT" => 20),
    array("CODE" => "ACCENT_COLOR", "NAME" => "Акцентный цвет", "PROPERTY_TYPE" => "S", "SORT" => 120, "COL_COUNT" => 20),
);

foreach ($bankProperties as $propertyDef) {
    purchaseContentEnsureProperty($banksIblockId, $propertyDef);
}

if (isset($source["pages"]) && is_array($source["pages"])) {
    foreach ($source["pages"] as $page) {
        if (!is_array($page)) {
            continue;
        }

        $pageCode = isset($page["code"]) ? trim((string)$page["code"]) : "";
        $pageLabel = isset($page["label"]) ? trim((string)$page["label"]) : $pageCode;
        $pageSort = isset($page["sort"]) ? (int)$page["sort"] : 500;
        if ($pageCode === "" || $pageLabel === "") {
            continue;
        }

        purchaseContentEnsureSection($cardsIblockId, $pageCode, $pageLabel, $pageSort);
    }
}

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "- purchase_pages ID=" . $pagesIblockId . PHP_EOL;
echo "- purchase_page_cards ID=" . $cardsIblockId . PHP_EOL;
echo "- mortgage_calculator_programs ID=" . $programsIblockId . PHP_EOL;
echo "- mortgage_calculator_banks ID=" . $banksIblockId . PHP_EOL;

exit(0);
