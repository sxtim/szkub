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
        echo "Usage: php local/tools/import_storeroom_seed.php [--dry-run=1]\n";
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

function storeroomSeedFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("CODE" => (string)$code), false);
    return $res->Fetch() ?: null;
}

function storeroomSeedProjectMap($iblockId)
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

function storeroomSeedEnumMap($propertyId)
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

function storeroomSeedPropertyIdMap($iblockId)
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

$storeroomsIblock = storeroomSeedFindIblock("storerooms");
$projectsIblock = storeroomSeedFindIblock("projects");

if (!$storeroomsIblock || !$projectsIblock) {
    echo "[ERROR] Required iblocks not found (storerooms/projects)" . PHP_EOL;
    exit(2);
}

$storeroomsIblockId = (int)$storeroomsIblock["ID"];
$projectMap = storeroomSeedProjectMap((int)$projectsIblock["ID"]);
$propertyIdMap = storeroomSeedPropertyIdMap($storeroomsIblockId);

$requiredProperties = array("PROJECT", "STATUS");
foreach ($requiredProperties as $requiredCode) {
    if (!isset($propertyIdMap[$requiredCode])) {
        echo "[ERROR] Required property missing: " . $requiredCode . PHP_EOL;
        exit(3);
    }
}

$statusEnumMap = storeroomSeedEnumMap($propertyIdMap["STATUS"]);

$seed = array(
    array(
        "CODE" => "kollekciya-s-101",
        "NAME" => "Кладовка №101",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 100,
        "NUMBER" => "101",
        "AREA" => 3.6,
        "PRICE" => 320000,
        "PRICE_OLD" => 355000,
        "STATUS" => "available",
        "BADGES" => array("Рядом с лифтом"),
    ),
    array(
        "CODE" => "kollekciya-s-204",
        "NAME" => "Кладовка №204",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 110,
        "NUMBER" => "204",
        "AREA" => 4.1,
        "PRICE" => 295000,
        "PRICE_OLD" => 0,
        "STATUS" => "booked",
        "BADGES" => array(),
    ),
    array(
        "CODE" => "vertical-s-018",
        "NAME" => "Кладовка №018",
        "PROJECT_CODE" => "vertical",
        "SORT" => 200,
        "NUMBER" => "018",
        "AREA" => 3.2,
        "PRICE" => 278000,
        "PRICE_OLD" => 0,
        "STATUS" => "available",
        "BADGES" => array("Быстрый доступ"),
    ),
    array(
        "CODE" => "vertical-s-033",
        "NAME" => "Кладовка №033",
        "PROJECT_CODE" => "vertical",
        "SORT" => 210,
        "NUMBER" => "033",
        "AREA" => 4.5,
        "PRICE" => 309000,
        "PRICE_OLD" => 339000,
        "STATUS" => "sold",
        "BADGES" => array(),
    ),
    array(
        "CODE" => "krasnoznamennaya-s-007",
        "NAME" => "Кладовка №007",
        "PROJECT_CODE" => "krasnoznamennaya",
        "SORT" => 300,
        "NUMBER" => "007",
        "AREA" => 3.1,
        "PRICE" => 265000,
        "PRICE_OLD" => 0,
        "STATUS" => "available",
        "BADGES" => array("Старт продаж"),
    ),
    array(
        "CODE" => "krasnoznamennaya-s-021",
        "NAME" => "Кладовка №021",
        "PROJECT_CODE" => "krasnoznamennaya",
        "SORT" => 310,
        "NUMBER" => "021",
        "AREA" => 4.8,
        "PRICE" => 289000,
        "PRICE_OLD" => 315000,
        "STATUS" => "booked",
        "BADGES" => array("Увеличенный объем"),
    ),
);

foreach ($seed as $item) {
    $projectCode = (string)$item["PROJECT_CODE"];
    if (!isset($projectMap[$projectCode])) {
        echo "[SKIP] Project not found: " . $projectCode . PHP_EOL;
        continue;
    }

    $statusEnumId = isset($statusEnumMap[$item["STATUS"]]) ? (int)$statusEnumMap[$item["STATUS"]] : 0;
    if ($statusEnumId <= 0) {
        echo "[SKIP] Enum not found for " . $item["CODE"] . PHP_EOL;
        continue;
    }

    $existing = CIBlockElement::GetList(
        array(),
        array("IBLOCK_ID" => $storeroomsIblockId, "CODE" => (string)$item["CODE"]),
        false,
        false,
        array("ID", "CODE")
    )->Fetch();

    $fields = array(
        "IBLOCK_ID" => $storeroomsIblockId,
        "ACTIVE" => "Y",
        "NAME" => (string)$item["NAME"],
        "CODE" => (string)$item["CODE"],
        "SORT" => (int)$item["SORT"],
        "PROPERTY_VALUES" => array(
            "PROJECT" => $projectMap[$projectCode]["ID"],
            "STOREROOM_NUMBER" => (string)$item["NUMBER"],
            "AREA_TOTAL" => (string)$item["AREA"],
            "PRICE_TOTAL" => (string)$item["PRICE"],
            "PRICE_OLD" => (string)$item["PRICE_OLD"],
            "STATUS" => $statusEnumId,
            "BADGES" => isset($item["BADGES"]) ? (array)$item["BADGES"] : array(),
        ),
    );

    if ($existing) {
        echo "[UPDATE] storeroom :: " . $item["CODE"] . PHP_EOL;
        if (!$dryRun) {
            $element = new CIBlockElement();
            $element->Update((int)$existing["ID"], $fields);
            CIBlockElement::SetPropertyValuesEx((int)$existing["ID"], $storeroomsIblockId, $fields["PROPERTY_VALUES"]);
        }
        continue;
    }

    echo "[CREATE] storeroom :: " . $item["CODE"] . PHP_EOL;
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
