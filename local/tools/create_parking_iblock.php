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
        echo "Usage: php local/tools/create_parking_iblock.php [--site-id=s1] [--type-id=realty] [--iblock-code=parking]\n";
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

function parkingEnsureEnumValues($propertyId, array $values)
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

    $processed = array();
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
            $processed[$key] = true;
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

        $processed[$key] = true;
        $enum = new CIBlockPropertyEnum();
        $enum->Add($payload);
        echo "[CREATE] Property enum: " . $key . PHP_EOL;
    }

    return true;
}

$siteId = isset($_REQUEST["site_id"]) && $_REQUEST["site_id"] !== "" ? (string)$_REQUEST["site_id"] : "s1";
$typeId = isset($_REQUEST["type_id"]) && $_REQUEST["type_id"] !== "" ? (string)$_REQUEST["type_id"] : "realty";
$iblockCode = isset($_REQUEST["iblock_code"]) && $_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "parking";
$iblockName = isset($_REQUEST["iblock_name"]) && $_REQUEST["iblock_name"] !== "" ? (string)$_REQUEST["iblock_name"] : "Паркинг";

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
        || (string)$row["SECTION_PAGE_URL"] !== "#SITE_DIR#/parking/#SECTION_CODE_PATH#/";

    if ($needsIblockSync) {
        $ib = new CIBlock();
        $updated = $ib->Update($iblockId, array(
            "IBLOCK_TYPE_ID" => $typeId,
            "CODE" => $iblockCode,
            "NAME" => isset($row["NAME"]) && trim((string)$row["NAME"]) !== "" ? (string)$row["NAME"] : $iblockName,
            "LID" => array($siteId),
            "LIST_PAGE_URL" => "#SITE_DIR#/parking/",
            "SECTION_PAGE_URL" => "#SITE_DIR#/parking/#SECTION_CODE_PATH#/",
            "DETAIL_PAGE_URL" => "#SITE_DIR#/parking/#ELEMENT_CODE#/",
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
        "SORT" => 130,
        "LIST_PAGE_URL" => "#SITE_DIR#/parking/",
        "SECTION_PAGE_URL" => "#SITE_DIR#/parking/#SECTION_CODE_PATH#/",
        "DETAIL_PAGE_URL" => "#SITE_DIR#/parking/#ELEMENT_CODE#/",
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
    array(
        "CODE" => "PROJECT",
        "NAME" => "ЖК",
        "PROPERTY_TYPE" => "E",
        "LINK_IBLOCK_ID" => $projectsIblockId,
        "SORT" => 100,
    ),
    array(
        "CODE" => "PARKING_NUMBER",
        "NAME" => "Номер места",
        "PROPERTY_TYPE" => "S",
        "SORT" => 110,
    ),
    array(
        "CODE" => "PARKING_TYPE",
        "NAME" => "Тип места",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 120,
        "VALUES" => array(
            array("VALUE" => "Подземный паркинг", "XML_ID" => "underground", "SORT" => 100, "DEF" => "Y"),
            array("VALUE" => "Наземный паркинг", "XML_ID" => "ground", "SORT" => 200),
        ),
    ),
    array(
        "CODE" => "LEVEL",
        "NAME" => "Уровень",
        "PROPERTY_TYPE" => "N",
        "SORT" => 130,
    ),
    array(
        "CODE" => "AREA_TOTAL",
        "NAME" => "Площадь, м²",
        "PROPERTY_TYPE" => "N",
        "SORT" => 140,
    ),
    array(
        "CODE" => "PRICE_TOTAL",
        "NAME" => "Цена",
        "PROPERTY_TYPE" => "N",
        "SORT" => 150,
    ),
    array(
        "CODE" => "PRICE_OLD",
        "NAME" => "Старая цена",
        "PROPERTY_TYPE" => "N",
        "SORT" => 160,
    ),
    array(
        "CODE" => "STATUS",
        "NAME" => "Статус",
        "PROPERTY_TYPE" => "L",
        "LIST_TYPE" => "L",
        "SORT" => 170,
        "VALUES" => array(
            array("VALUE" => "В продаже", "XML_ID" => "available", "SORT" => 100, "DEF" => "Y"),
            array("VALUE" => "Забронировано", "XML_ID" => "booked", "SORT" => 200),
            array("VALUE" => "Продано", "XML_ID" => "sold", "SORT" => 300),
        ),
    ),
    array(
        "CODE" => "BADGES",
        "NAME" => "Бейджи",
        "PROPERTY_TYPE" => "S",
        "MULTIPLE" => "Y",
        "SORT" => 180,
    ),
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
        parkingEnsureEnumValues($propertyId, $propertyDef["VALUES"]);
    }
}

echo PHP_EOL . "Use in code: TYPE=" . $typeId . ", CODE=" . $iblockCode . PHP_EOL;
