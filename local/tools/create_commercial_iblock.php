<?php
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
        "iblock-code::",
        "iblock-name::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/create_commercial_iblock.php [--site-id=s1] [--type-id=realty] [--iblock-code=commercial]\n";
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

function commercialEnsureEnumValues($propertyId, array $values)
{
    $propertyId = (int)$propertyId;
    if ($propertyId <= 0) {
        return false;
    }

    $existing = array();
    $res = CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("PROPERTY_ID" => $propertyId));
    while ($row = $res->Fetch()) {
        $key = trim((string)$row["XML_ID"]) !== "" ? trim((string)$row["XML_ID"]) : trim((string)$row["VALUE"]);
        $existing[$key] = $row;
    }

    foreach ($values as $index => $valueDef) {
        $xmlId = isset($valueDef["XML_ID"]) ? trim((string)$valueDef["XML_ID"]) : "";
        $key = $xmlId !== "" ? $xmlId : trim((string)$valueDef["VALUE"]);
        $payload = array(
            "PROPERTY_ID" => $propertyId,
            "VALUE" => (string)$valueDef["VALUE"],
            "XML_ID" => $xmlId,
            "SORT" => isset($valueDef["SORT"]) ? (int)$valueDef["SORT"] : (($index + 1) * 100),
            "DEF" => isset($valueDef["DEF"]) && (string)$valueDef["DEF"] === "Y" ? "Y" : "N",
        );

        if (isset($existing[$key])) {
            $current = $existing[$key];
            $needsUpdate = (string)$current["VALUE"] !== (string)$payload["VALUE"]
                || (string)$current["XML_ID"] !== (string)$payload["XML_ID"]
                || (int)$current["SORT"] !== (int)$payload["SORT"]
                || (string)$current["DEF"] !== (string)$payload["DEF"];

            if ($needsUpdate) {
                $enum = new CIBlockPropertyEnum();
                $enum->Update((int)$current["ID"], $payload);
                echo "[UPDATE] Property enum: " . $key . PHP_EOL;
            }
            continue;
        }

        $enum = new CIBlockPropertyEnum();
        $enum->Add($payload);
        echo "[CREATE] Property enum: " . $key . PHP_EOL;
    }

    return true;
}

$siteId = isset($_REQUEST["site_id"]) && $_REQUEST["site_id"] !== "" ? (string)$_REQUEST["site_id"] : "s1";
$typeId = isset($_REQUEST["type_id"]) && $_REQUEST["type_id"] !== "" ? (string)$_REQUEST["type_id"] : "realty";
$iblockCode = isset($_REQUEST["iblock_code"]) && $_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "commercial";
$iblockName = isset($_REQUEST["iblock_name"]) && $_REQUEST["iblock_name"] !== "" ? (string)$_REQUEST["iblock_name"] : "Коммерческие помещения";

echo "Target site: " . $siteId . PHP_EOL;
echo "IBlock type: " . $typeId . PHP_EOL;
echo "IBlock code: " . $iblockCode . PHP_EOL;

$typeExists = CIBlockType::GetList(array(), array("=ID" => $typeId))->Fetch();
if (!$typeExists) {
    echo "IBlock type '" . $typeId . "' does not exist." . PHP_EOL;
    exit(2);
}

$projectsIblock = CIBlock::GetList(array(), array("=CODE" => "projects"), false)->Fetch();
if (!$projectsIblock) {
    echo "Projects iblock not found by code: projects" . PHP_EOL;
    exit(2);
}
$projectsIblockId = (int)$projectsIblock["ID"];

$iblockId = 0;
$iblockRes = CIBlock::GetList(array(), array("=CODE" => $iblockCode), false);
if ($row = $iblockRes->Fetch()) {
    $iblockId = (int)$row["ID"];
    echo "[OK] IBlock exists: ID=" . $iblockId . ", NAME=" . $row["NAME"] . PHP_EOL;

    $needsIblockSync =
        (string)$row["SECTIONS"] !== "Y"
        || (string)$row["SECTION_CHOOSER"] !== "L"
        || (string)$row["SECTION_PAGE_URL"] !== "#SITE_DIR#/commerce/#SECTION_CODE_PATH#/";

    if ($needsIblockSync) {
        $ib = new CIBlock();
        $updated = $ib->Update($iblockId, array(
            "IBLOCK_TYPE_ID" => $typeId,
            "CODE" => $iblockCode,
            "NAME" => isset($row["NAME"]) && trim((string)$row["NAME"]) !== "" ? (string)$row["NAME"] : $iblockName,
            "LID" => array($siteId),
            "LIST_PAGE_URL" => "#SITE_DIR#/commerce/",
            "SECTION_PAGE_URL" => "#SITE_DIR#/commerce/#SECTION_CODE_PATH#/",
            "DETAIL_PAGE_URL" => "#SITE_DIR#/commerce/#ELEMENT_CODE#/",
            "SECTION_CHOOSER" => "L",
            "INDEX_ELEMENT" => "N",
            "INDEX_SECTION" => "N",
            "SECTIONS" => "Y",
            "RIGHTS_MODE" => "S",
            "VERSION" => 2,
        ));

        if (!$updated) {
            echo "[ERROR] Failed to sync iblock settings: " . $ib->LAST_ERROR . PHP_EOL;
            exit(3);
        }

        echo "[SYNC] IBlock sections mode enabled" . PHP_EOL;
    }
} else {
    $ib = new CIBlock();
    $newId = $ib->Add(array(
        "ACTIVE" => "Y",
        "NAME" => $iblockName,
        "CODE" => $iblockCode,
        "IBLOCK_TYPE_ID" => $typeId,
        "LID" => array($siteId),
        "SORT" => 132,
        "LIST_PAGE_URL" => "#SITE_DIR#/commerce/",
        "SECTION_PAGE_URL" => "#SITE_DIR#/commerce/#SECTION_CODE_PATH#/",
        "DETAIL_PAGE_URL" => "#SITE_DIR#/commerce/#ELEMENT_CODE#/",
        "SECTION_CHOOSER" => "L",
        "INDEX_ELEMENT" => "N",
        "INDEX_SECTION" => "N",
        "SECTIONS" => "Y",
        "RIGHTS_MODE" => "S",
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
        "GROUP_ID" => array(2 => "R"),
    ));

    if (!$newId) {
        echo "Failed to create iblock: " . $ib->LAST_ERROR . PHP_EOL;
        exit(3);
    }

    $iblockId = (int)$newId;
    echo "[CREATE] IBlock created: ID=" . $iblockId . ", NAME=" . $iblockName . PHP_EOL;
}

$properties = array(
    array("CODE" => "PROJECT", "NAME" => "ЖК", "PROPERTY_TYPE" => "E", "LINK_IBLOCK_ID" => $projectsIblockId, "SORT" => 100, "IS_REQUIRED" => "Y"),
    array("CODE" => "CORPUS", "NAME" => "Корпус", "PROPERTY_TYPE" => "S", "SORT" => 110, "ROW_COUNT" => 1, "COL_COUNT" => 20),
    array("CODE" => "ENTRANCE", "NAME" => "Вход / секция", "PROPERTY_TYPE" => "S", "SORT" => 120, "ROW_COUNT" => 1, "COL_COUNT" => 20),
    array("CODE" => "FLOOR", "NAME" => "Этаж", "PROPERTY_TYPE" => "N", "SORT" => 130, "IS_REQUIRED" => "Y"),
    array("CODE" => "HOUSE_FLOORS", "NAME" => "Этажность дома", "PROPERTY_TYPE" => "N", "SORT" => 140),
    array("CODE" => "COMMERCIAL_NUMBER", "NAME" => "Номер помещения", "PROPERTY_TYPE" => "S", "SORT" => 150, "IS_REQUIRED" => "Y", "ROW_COUNT" => 1, "COL_COUNT" => 20),
    array(
        "CODE" => "COMMERCIAL_TYPE",
        "NAME" => "Тип помещения",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 160,
        "IS_REQUIRED" => "Y",
        "VALUES" => array(
            array("VALUE" => "Торговое помещение", "XML_ID" => "retail", "SORT" => 100),
            array("VALUE" => "Офис", "XML_ID" => "office", "SORT" => 200),
            array("VALUE" => "Сервисное помещение", "XML_ID" => "service", "SORT" => 300),
            array("VALUE" => "Свободное назначение", "XML_ID" => "free_use", "SORT" => 400),
        ),
    ),
    array("CODE" => "AREA_TOTAL", "NAME" => "Площадь, м²", "PROPERTY_TYPE" => "N", "SORT" => 170, "IS_REQUIRED" => "Y"),
    array("CODE" => "PRICE_TOTAL", "NAME" => "Цена", "PROPERTY_TYPE" => "N", "SORT" => 180, "IS_REQUIRED" => "Y"),
    array("CODE" => "PRICE_OLD", "NAME" => "Старая цена", "PROPERTY_TYPE" => "N", "SORT" => 190),
    array("CODE" => "PRICE_M2", "NAME" => "Цена за м²", "PROPERTY_TYPE" => "N", "SORT" => 200),
    array(
        "CODE" => "STATUS",
        "NAME" => "Статус",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 210,
        "IS_REQUIRED" => "Y",
        "VALUES" => array(
            array("VALUE" => "В продаже", "XML_ID" => "available", "SORT" => 100, "DEF" => "Y"),
            array("VALUE" => "Забронировано", "XML_ID" => "booked", "SORT" => 200),
            array("VALUE" => "Продано", "XML_ID" => "sold", "SORT" => 300),
        ),
    ),
    array("CODE" => "BADGES", "NAME" => "Бейджи", "PROPERTY_TYPE" => "S", "MULTIPLE" => "Y", "SORT" => 220, "ROW_COUNT" => 1, "COL_COUNT" => 60),
    array("CODE" => "FEATURE_TAGS", "NAME" => "Особенности", "PROPERTY_TYPE" => "S", "MULTIPLE" => "Y", "SORT" => 230, "ROW_COUNT" => 1, "COL_COUNT" => 60),
    array("CODE" => "DESCRIPTION", "NAME" => "Описание", "PROPERTY_TYPE" => "S", "SORT" => 240, "ROW_COUNT" => 6, "COL_COUNT" => 90),
    array("CODE" => "CEILING", "NAME" => "Высота потолков", "PROPERTY_TYPE" => "N", "SORT" => 250),
    array(
        "CODE" => "SEPARATE_ENTRANCE",
        "NAME" => "Отдельный вход",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 260,
        "VALUES" => array(
            array("VALUE" => "Есть", "XML_ID" => "yes", "SORT" => 100),
            array("VALUE" => "Нет", "XML_ID" => "no", "SORT" => 200),
        ),
    ),
    array(
        "CODE" => "SHOWCASE_WINDOWS",
        "NAME" => "Витринные окна",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 270,
        "VALUES" => array(
            array("VALUE" => "Есть", "XML_ID" => "yes", "SORT" => 100),
            array("VALUE" => "Нет", "XML_ID" => "no", "SORT" => 200),
        ),
    ),
    array(
        "CODE" => "WET_POINT",
        "NAME" => "Мокрая точка",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 280,
        "VALUES" => array(
            array("VALUE" => "Есть", "XML_ID" => "yes", "SORT" => 100),
            array("VALUE" => "Нет", "XML_ID" => "no", "SORT" => 200),
        ),
    ),
    array("CODE" => "POWER_KW", "NAME" => "Мощность, кВт", "PROPERTY_TYPE" => "N", "SORT" => 290),
    array(
        "CODE" => "FINISH",
        "NAME" => "Отделка",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 300,
        "VALUES" => array(
            array("VALUE" => "Без отделки", "XML_ID" => "shell", "SORT" => 100),
            array("VALUE" => "White box", "XML_ID" => "whitebox", "SORT" => 200),
            array("VALUE" => "С отделкой", "XML_ID" => "finish", "SORT" => 300),
        ),
    ),
    array("CODE" => "PLAN_IMAGE", "NAME" => "Планировка", "PROPERTY_TYPE" => "F", "SORT" => 320),
    array("CODE" => "PLAN_TITLE", "NAME" => "Планировка: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 321, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "PLAN_TEXT", "NAME" => "Планировка: описание", "PROPERTY_TYPE" => "S", "SORT" => 322, "ROW_COUNT" => 4, "COL_COUNT" => 90),
    array("CODE" => "PLAN_ALT", "NAME" => "Планировка: ALT", "PROPERTY_TYPE" => "S", "SORT" => 323, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "FLOOR_SLIDE_IMAGE", "NAME" => "На этаже: изображение", "PROPERTY_TYPE" => "F", "SORT" => 324),
    array("CODE" => "FLOOR_SLIDE_TITLE", "NAME" => "На этаже: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 325, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "FLOOR_SLIDE_TEXT", "NAME" => "На этаже: описание", "PROPERTY_TYPE" => "S", "SORT" => 326, "ROW_COUNT" => 4, "COL_COUNT" => 90),
    array("CODE" => "FLOOR_SLIDE_ALT", "NAME" => "На этаже: ALT", "PROPERTY_TYPE" => "S", "SORT" => 327, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "BUILDING_SLIDE_IMAGE", "NAME" => "В корпусе: изображение", "PROPERTY_TYPE" => "F", "SORT" => 328),
    array("CODE" => "BUILDING_SLIDE_TITLE", "NAME" => "В корпусе: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 329, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "BUILDING_SLIDE_TEXT", "NAME" => "В корпусе: описание", "PROPERTY_TYPE" => "S", "SORT" => 330, "ROW_COUNT" => 4, "COL_COUNT" => 90),
    array("CODE" => "BUILDING_SLIDE_ALT", "NAME" => "В корпусе: ALT", "PROPERTY_TYPE" => "S", "SORT" => 331, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "VIEW_SLIDE_IMAGE", "NAME" => "Вид: изображение", "PROPERTY_TYPE" => "F", "SORT" => 332),
    array("CODE" => "VIEW_SLIDE_TITLE", "NAME" => "Вид: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 333, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "VIEW_SLIDE_TEXT", "NAME" => "Вид: описание", "PROPERTY_TYPE" => "S", "SORT" => 334, "ROW_COUNT" => 4, "COL_COUNT" => 90),
    array("CODE" => "VIEW_SLIDE_ALT", "NAME" => "Вид: ALT", "PROPERTY_TYPE" => "S", "SORT" => 335, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "RENDER_SLIDE_IMAGE", "NAME" => "Визуализация: изображение", "PROPERTY_TYPE" => "F", "SORT" => 336),
    array("CODE" => "RENDER_SLIDE_TITLE", "NAME" => "Визуализация: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 337, "ROW_COUNT" => 1, "COL_COUNT" => 80),
    array("CODE" => "RENDER_SLIDE_TEXT", "NAME" => "Визуализация: описание", "PROPERTY_TYPE" => "S", "SORT" => 338, "ROW_COUNT" => 4, "COL_COUNT" => 90),
    array("CODE" => "RENDER_SLIDE_ALT", "NAME" => "Визуализация: ALT", "PROPERTY_TYPE" => "S", "SORT" => 339, "ROW_COUNT" => 1, "COL_COUNT" => 80),
);

foreach ($properties as $propertyDef) {
    $existing = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $iblockId, "CODE" => $propertyDef["CODE"]))->Fetch();
    if ($existing) {
        $propertyId = (int)$existing["ID"];
        echo "[OK] Property exists: " . $propertyDef["CODE"] . PHP_EOL;
    } else {
        $property = new CIBlockProperty();
        $propertyId = (int)$property->Add($propertyDef + array("IBLOCK_ID" => $iblockId));
        if ($propertyId <= 0) {
            echo "[ERROR] Failed to create property " . $propertyDef["CODE"] . ": " . $property->LAST_ERROR . PHP_EOL;
            exit(4);
        }
        echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . $propertyId . ")" . PHP_EOL;
    }

    if (isset($propertyDef["VALUES"]) && is_array($propertyDef["VALUES"])) {
        commercialEnsureEnumValues($propertyId, $propertyDef["VALUES"]);
    }
}

echo PHP_EOL . "Use in code: TYPE=" . $typeId . ", CODE=" . $iblockCode . PHP_EOL;
