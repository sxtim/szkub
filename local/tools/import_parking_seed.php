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
        "dry-run::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/import_parking_seed.php [--dry-run=1]\n";
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

function parkingSeedFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("CODE" => (string)$code), false);
    return $res->Fetch() ?: null;
}

function parkingSeedProjectMap($iblockId)
{
    $result = array();
    $res = CIBlockElement::GetList(
        array("SORT" => "ASC", "NAME" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId, "ACTIVE" => "Y"),
        false,
        false,
        array("ID", "NAME", "CODE")
    );
    while ($row = $res->Fetch()) {
        $code = trim((string)$row["CODE"]);
        if ($code === "") {
            continue;
        }
        $result[$code] = array(
            "ID" => (int)$row["ID"],
            "NAME" => (string)$row["NAME"],
            "CODE" => $code,
        );
    }

    return $result;
}

function parkingSeedEnumMap($propertyId)
{
    $result = array();
    $res = CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("PROPERTY_ID" => (int)$propertyId));
    while ($row = $res->Fetch()) {
        $key = trim((string)$row["XML_ID"]);
        if ($key === "") {
            $key = trim((string)$row["VALUE"]);
        }
        if ($key !== "") {
            $result[$key] = (int)$row["ID"];
        }
    }

    return $result;
}

function parkingSeedPropertyIdMap($iblockId)
{
    $result = array();
    $res = CIBlockProperty::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("IBLOCK_ID" => (int)$iblockId));
    while ($row = $res->Fetch()) {
        $code = trim((string)$row["CODE"]);
        if ($code !== "") {
            $result[$code] = (int)$row["ID"];
        }
    }

    return $result;
}

$parkingIblock = parkingSeedFindIblock("parking");
$projectsIblock = parkingSeedFindIblock("projects");

if (!$parkingIblock || !$projectsIblock) {
    echo "[ERROR] Required iblocks not found (parking/projects)" . PHP_EOL;
    exit(2);
}

$parkingIblockId = (int)$parkingIblock["ID"];
$projectMap = parkingSeedProjectMap((int)$projectsIblock["ID"]);
$propertyIdMap = parkingSeedPropertyIdMap($parkingIblockId);

$requiredProperties = array("PROJECT", "PARKING_TYPE", "STATUS");
foreach ($requiredProperties as $requiredCode) {
    if (!isset($propertyIdMap[$requiredCode])) {
        echo "[ERROR] Required property missing: " . $requiredCode . PHP_EOL;
        exit(3);
    }
}

$typeEnumMap = parkingSeedEnumMap($propertyIdMap["PARKING_TYPE"]);
$statusEnumMap = parkingSeedEnumMap($propertyIdMap["STATUS"]);

$seed = array(
    array(
        "CODE" => "kollekciya-p-101",
        "NAME" => "Парковочное место №101",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 100,
        "NUMBER" => "101",
        "TYPE" => "underground",
        "LEVEL" => -1,
        "AREA" => 13.8,
        "PRICE" => 1250000,
        "PRICE_OLD" => 1370000,
        "STATUS" => "available",
        "BADGES" => array("Теплый паркинг"),
    ),
    array(
        "CODE" => "kollekciya-p-214",
        "NAME" => "Парковочное место №214",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 110,
        "NUMBER" => "214",
        "TYPE" => "underground",
        "LEVEL" => -2,
        "AREA" => 14.1,
        "PRICE" => 1190000,
        "PRICE_OLD" => 0,
        "STATUS" => "booked",
        "BADGES" => array(),
    ),
    array(
        "CODE" => "kollekciya-p-318",
        "NAME" => "Парковочное место №318",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 120,
        "NUMBER" => "318",
        "TYPE" => "underground",
        "LEVEL" => -2,
        "AREA" => 14.9,
        "PRICE" => 1280000,
        "PRICE_OLD" => 2150000,
        "STATUS" => "sold",
        "BADGES" => array("Рядом с лифтом"),
    ),
    array(
        "CODE" => "vertical-p-011",
        "NAME" => "Парковочное место №011",
        "PROJECT_CODE" => "vertical",
        "SORT" => 200,
        "NUMBER" => "011",
        "TYPE" => "ground",
        "LEVEL" => 1,
        "AREA" => 13.2,
        "PRICE" => 820000,
        "PRICE_OLD" => 0,
        "STATUS" => "available",
        "BADGES" => array("У въезда"),
    ),
    array(
        "CODE" => "vertical-p-052",
        "NAME" => "Парковочное место №052",
        "PROJECT_CODE" => "vertical",
        "SORT" => 210,
        "NUMBER" => "052",
        "TYPE" => "ground",
        "LEVEL" => 1,
        "AREA" => 13.1,
        "PRICE" => 790000,
        "PRICE_OLD" => 0,
        "STATUS" => "available",
        "BADGES" => array("У въезда"),
    ),
    array(
        "CODE" => "vertical-p-122",
        "NAME" => "Парковочное место №122",
        "PROJECT_CODE" => "vertical",
        "SORT" => 220,
        "NUMBER" => "122",
        "TYPE" => "underground",
        "LEVEL" => -1,
        "AREA" => 14.4,
        "PRICE" => 990000,
        "PRICE_OLD" => 1080000,
        "STATUS" => "booked",
        "BADGES" => array(),
    ),
);

foreach ($seed as $item) {
    $projectCode = (string)$item["PROJECT_CODE"];
    if (!isset($projectMap[$projectCode])) {
        echo "[SKIP] Project not found: " . $projectCode . PHP_EOL;
        continue;
    }

    $typeEnumId = isset($typeEnumMap[$item["TYPE"]]) ? (int)$typeEnumMap[$item["TYPE"]] : 0;
    $statusEnumId = isset($statusEnumMap[$item["STATUS"]]) ? (int)$statusEnumMap[$item["STATUS"]] : 0;
    if ($typeEnumId <= 0 || $statusEnumId <= 0) {
        echo "[SKIP] Enum not found for " . $item["CODE"] . PHP_EOL;
        continue;
    }

    $existing = CIBlockElement::GetList(
        array(),
        array("IBLOCK_ID" => $parkingIblockId, "CODE" => (string)$item["CODE"]),
        false,
        false,
        array("ID", "CODE")
    )->Fetch();

    $fields = array(
        "IBLOCK_ID" => $parkingIblockId,
        "ACTIVE" => "Y",
        "NAME" => (string)$item["NAME"],
        "CODE" => (string)$item["CODE"],
        "SORT" => (int)$item["SORT"],
        "PROPERTY_VALUES" => array(
            "PROJECT" => $projectMap[$projectCode]["ID"],
            "PARKING_NUMBER" => (string)$item["NUMBER"],
            "PARKING_TYPE" => $typeEnumId,
            "LEVEL" => (string)$item["LEVEL"],
            "AREA_TOTAL" => (string)$item["AREA"],
            "PRICE_TOTAL" => (string)$item["PRICE"],
            "PRICE_OLD" => (string)$item["PRICE_OLD"],
            "STATUS" => $statusEnumId,
            "BADGES" => isset($item["BADGES"]) ? (array)$item["BADGES"] : array(),
        ),
    );

    if ($existing) {
        echo "[UPDATE] parking :: " . $item["CODE"] . PHP_EOL;
        if (!$dryRun) {
            $element = new CIBlockElement();
            $element->Update((int)$existing["ID"], $fields);
            CIBlockElement::SetPropertyValuesEx((int)$existing["ID"], $parkingIblockId, $fields["PROPERTY_VALUES"]);
        }
        continue;
    }

    echo "[CREATE] parking :: " . $item["CODE"] . PHP_EOL;
    if (!$dryRun) {
        $element = new CIBlockElement();
        $newId = $element->Add($fields);
        if (!$newId) {
            echo "[ERROR] Failed to create " . $item["CODE"] . ": " . $element->LAST_ERROR . PHP_EOL;
            exit(4);
        }
    }
}

echo PHP_EOL . "Done." . PHP_EOL;
