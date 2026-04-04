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
        echo "Usage: php local/tools/import_commercial_seed.php [--dry-run=1]\n";
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

function commercialSeedFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("CODE" => (string)$code), false);
    return $res->Fetch() ?: null;
}

function commercialSeedProjectMap($iblockId)
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

function commercialSeedEnumMap($propertyId)
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

function commercialSeedPropertyIdMap($iblockId)
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

function commercialSeedFile($relativePath)
{
    $relativePath = "/" . ltrim((string)$relativePath, "/");
    $absolutePath = $_SERVER["DOCUMENT_ROOT"] . $relativePath;
    if (!is_file($absolutePath)) {
        return null;
    }

    return CFile::MakeFileArray($absolutePath);
}

$commercialIblock = commercialSeedFindIblock("commercial");
$projectsIblock = commercialSeedFindIblock("projects");

if (!$commercialIblock || !$projectsIblock) {
    echo "[ERROR] Required iblocks not found (commercial/projects)" . PHP_EOL;
    exit(2);
}

$commercialIblockId = (int)$commercialIblock["ID"];
$projectMap = commercialSeedProjectMap((int)$projectsIblock["ID"]);
$propertyIdMap = commercialSeedPropertyIdMap($commercialIblockId);

$requiredProperties = array("PROJECT", "COMMERCIAL_TYPE", "STATUS", "FINISH", "SEPARATE_ENTRANCE", "SHOWCASE_WINDOWS", "WET_POINT");
foreach ($requiredProperties as $requiredCode) {
    if (!isset($propertyIdMap[$requiredCode])) {
        echo "[ERROR] Required property missing: " . $requiredCode . PHP_EOL;
        exit(3);
    }
}

$enumMaps = array(
    "COMMERCIAL_TYPE" => commercialSeedEnumMap($propertyIdMap["COMMERCIAL_TYPE"]),
    "STATUS" => commercialSeedEnumMap($propertyIdMap["STATUS"]),
    "FINISH" => commercialSeedEnumMap($propertyIdMap["FINISH"]),
    "SEPARATE_ENTRANCE" => commercialSeedEnumMap($propertyIdMap["SEPARATE_ENTRANCE"]),
    "SHOWCASE_WINDOWS" => commercialSeedEnumMap($propertyIdMap["SHOWCASE_WINDOWS"]),
    "WET_POINT" => commercialSeedEnumMap($propertyIdMap["WET_POINT"]),
);

$planImage = "/local/templates/szcube/img/apartments/1 этаж 2е 92.8 с антресолью 1.jpg";
$floorImage = "/local/templates/szcube/img/projects/Group.svg";
$buildingImage = "/local/templates/szcube/img/projects/image_15.jpg";
$viewImage = "/local/templates/szcube/img/projects/div.image-lazy__image.jpg";
$renderImage = "/local/templates/szcube/img/figma-d19d0bcf-14ae-4fb3-a3dc-4363edabe21a.png";

$seed = array(
    array(
        "CODE" => "kollekciya-cm-101",
        "NAME" => "Торговое помещение №101",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 100,
        "NUMBER" => "101",
        "TYPE" => "retail",
        "CORPUS" => "1",
        "ENTRANCE" => "Отдельный вход с улицы",
        "FLOOR" => 1,
        "HOUSE_FLOORS" => 7,
        "AREA" => 84.5,
        "PRICE" => 18900000,
        "PRICE_OLD" => 20350000,
        "STATUS" => "available",
        "BADGES" => array("Первая линия", "Трафик с улицы"),
        "FEATURES" => array("Отдельный вход", "Витринные окна"),
        "DESCRIPTION" => "Помещение на первой линии с витринным остеклением и удобной посадкой под ритейл или кофейню.",
        "CEILING" => 4.2,
        "SEPARATE_ENTRANCE" => "yes",
        "SHOWCASE_WINDOWS" => "yes",
        "WET_POINT" => "yes",
        "POWER_KW" => 25,
        "FINISH" => "shell",
        "PLAN_TITLE" => "План помещения 101",
        "PLAN_TEXT" => "Широкий фасад, витринная линия и глубина помещения под торговый зал и склад.",
        "FLOOR_TITLE" => "Расположение на этаже",
        "FLOOR_TEXT" => "Угловой блок рядом с основным входом в проект.",
        "BUILDING_TITLE" => "Положение в корпусе",
        "BUILDING_TEXT" => "Помещение развернуто в сторону главного пешеходного маршрута.",
        "VIEW_TITLE" => "Фасад и окружение",
        "VIEW_TEXT" => "Открывается на благоустроенную улицу и витринный фронт корпуса.",
        "RENDER_TITLE" => "Визуализация витрин",
        "RENDER_TEXT" => "Формат подходит для кофейни, сервиса или шоурума.",
    ),
    array(
        "CODE" => "kollekciya-cm-102",
        "NAME" => "Сервисное помещение №102",
        "PROJECT_CODE" => "kollekciya",
        "SORT" => 110,
        "NUMBER" => "102",
        "TYPE" => "service",
        "CORPUS" => "1",
        "ENTRANCE" => "Галерея первого этажа",
        "FLOOR" => 1,
        "HOUSE_FLOORS" => 7,
        "AREA" => 62.3,
        "PRICE" => 13250000,
        "PRICE_OLD" => 0,
        "STATUS" => "booked",
        "BADGES" => array("У входной группы"),
        "FEATURES" => array("Мокрая точка"),
        "DESCRIPTION" => "Помещение под beauty, медицину или сервис рядом с центральной входной группой.",
        "CEILING" => 3.9,
        "SEPARATE_ENTRANCE" => "no",
        "SHOWCASE_WINDOWS" => "yes",
        "WET_POINT" => "yes",
        "POWER_KW" => 18,
        "FINISH" => "whitebox",
        "PLAN_TITLE" => "План помещения 102",
        "PLAN_TEXT" => "Компактный сервисный блок с правильной геометрией и местом под ресепшен.",
        "FLOOR_TITLE" => "Положение на этаже",
        "FLOOR_TEXT" => "Помещение расположено у входной галереи проекта.",
        "BUILDING_TITLE" => "Положение в корпусе",
        "BUILDING_TEXT" => "Непосредственная близость к жилому трафику и дворовому маршруту.",
        "VIEW_TITLE" => "Входная зона",
        "VIEW_TEXT" => "Формирует сервисный фронт первого этажа с удобным доступом для резидентов.",
        "RENDER_TITLE" => "Визуализация сервиса",
        "RENDER_TEXT" => "Подходит под салон, студию или компактный сервис.",
    ),
    array(
        "CODE" => "vertical-cm-201",
        "NAME" => "Офис №201",
        "PROJECT_CODE" => "vertical",
        "SORT" => 200,
        "NUMBER" => "201",
        "TYPE" => "office",
        "CORPUS" => "А",
        "ENTRANCE" => "Секция 1",
        "FLOOR" => 2,
        "HOUSE_FLOORS" => 18,
        "AREA" => 96.8,
        "PRICE" => 17100000,
        "PRICE_OLD" => 18450000,
        "STATUS" => "available",
        "BADGES" => array("Вид на парк"),
        "FEATURES" => array("Гибкая планировка", "Высокие потолки"),
        "DESCRIPTION" => "Офисный блок с панорамным остеклением и возможностью гибко разделить пространство на кабинеты и open space.",
        "CEILING" => 3.6,
        "SEPARATE_ENTRANCE" => "no",
        "SHOWCASE_WINDOWS" => "no",
        "WET_POINT" => "no",
        "POWER_KW" => 18,
        "FINISH" => "whitebox",
        "PLAN_TITLE" => "План офиса 201",
        "PLAN_TEXT" => "Панорамная часть вдоль фасада и эффективная глубина для переговорных и рабочих мест.",
        "FLOOR_TITLE" => "Положение на этаже",
        "FLOOR_TEXT" => "Второй этаж делового блока над ритейлом.",
        "BUILDING_TITLE" => "Положение в корпусе",
        "BUILDING_TEXT" => "Ориентация на набережную и визуальный контакт с городской средой.",
        "VIEW_TITLE" => "Вид из окон",
        "VIEW_TEXT" => "Окна выходят на зелень и прогулочный маршрут вдоль набережной.",
        "RENDER_TITLE" => "Визуализация офиса",
        "RENDER_TEXT" => "Подходит под представительство, студию или клиентский офис.",
    ),
    array(
        "CODE" => "vertical-cm-202",
        "NAME" => "Помещение свободного назначения №202",
        "PROJECT_CODE" => "vertical",
        "SORT" => 210,
        "NUMBER" => "202",
        "TYPE" => "free_use",
        "CORPUS" => "А",
        "ENTRANCE" => "Первый этаж, отдельный блок",
        "FLOOR" => 1,
        "HOUSE_FLOORS" => 18,
        "AREA" => 128.4,
        "PRICE" => 22400000,
        "PRICE_OLD" => 0,
        "STATUS" => "sold",
        "BADGES" => array("Большая площадь"),
        "FEATURES" => array("Свободная планировка", "Выделенная зона разгрузки"),
        "DESCRIPTION" => "Крупный угловой блок под showroom, семейный сервис или офис продаж.",
        "CEILING" => 4.4,
        "SEPARATE_ENTRANCE" => "yes",
        "SHOWCASE_WINDOWS" => "yes",
        "WET_POINT" => "yes",
        "POWER_KW" => 35,
        "FINISH" => "shell",
        "PLAN_TITLE" => "План помещения 202",
        "PLAN_TEXT" => "Угловой сценарий с двумя фасадами и большим пятном свободной планировки.",
        "FLOOR_TITLE" => "Положение на этаже",
        "FLOOR_TEXT" => "Отдельный коммерческий блок первого этажа рядом с активным фасадом.",
        "BUILDING_TITLE" => "Положение в корпусе",
        "BUILDING_TEXT" => "Помещение формирует один из якорных углов коммерческого фронта проекта.",
        "VIEW_TITLE" => "Фасад помещения",
        "VIEW_TEXT" => "Два фасада и хорошая читаемость с прогулочного маршрута.",
        "RENDER_TITLE" => "Визуализация пространства",
        "RENDER_TEXT" => "Подходит под якорный сервис или крупный шоурум.",
    ),
);

foreach ($seed as $item) {
    $projectCode = (string)$item["PROJECT_CODE"];
    if (!isset($projectMap[$projectCode])) {
        echo "[SKIP] Project not found: " . $projectCode . PHP_EOL;
        continue;
    }

    $enumPayload = array(
        "COMMERCIAL_TYPE" => isset($enumMaps["COMMERCIAL_TYPE"][$item["TYPE"]]) ? (int)$enumMaps["COMMERCIAL_TYPE"][$item["TYPE"]] : 0,
        "STATUS" => isset($enumMaps["STATUS"][$item["STATUS"]]) ? (int)$enumMaps["STATUS"][$item["STATUS"]] : 0,
        "FINISH" => isset($enumMaps["FINISH"][$item["FINISH"]]) ? (int)$enumMaps["FINISH"][$item["FINISH"]] : 0,
        "SEPARATE_ENTRANCE" => isset($enumMaps["SEPARATE_ENTRANCE"][$item["SEPARATE_ENTRANCE"]]) ? (int)$enumMaps["SEPARATE_ENTRANCE"][$item["SEPARATE_ENTRANCE"]] : 0,
        "SHOWCASE_WINDOWS" => isset($enumMaps["SHOWCASE_WINDOWS"][$item["SHOWCASE_WINDOWS"]]) ? (int)$enumMaps["SHOWCASE_WINDOWS"][$item["SHOWCASE_WINDOWS"]] : 0,
        "WET_POINT" => isset($enumMaps["WET_POINT"][$item["WET_POINT"]]) ? (int)$enumMaps["WET_POINT"][$item["WET_POINT"]] : 0,
    );
    foreach ($enumPayload as $enumCode => $enumId) {
        if ($enumId <= 0) {
            echo "[SKIP] Enum not found for " . $item["CODE"] . " :: " . $enumCode . PHP_EOL;
            continue 2;
        }
    }

    $area = (float)$item["AREA"];
    $price = (float)$item["PRICE"];
    $priceM2 = $area > 0 ? round($price / $area, 0) : 0;

    $propertyValues = array(
        "PROJECT" => $projectMap[$projectCode]["ID"],
        "CORPUS" => (string)$item["CORPUS"],
        "ENTRANCE" => (string)$item["ENTRANCE"],
        "FLOOR" => (string)$item["FLOOR"],
        "HOUSE_FLOORS" => (string)$item["HOUSE_FLOORS"],
        "COMMERCIAL_NUMBER" => (string)$item["NUMBER"],
        "COMMERCIAL_TYPE" => $enumPayload["COMMERCIAL_TYPE"],
        "AREA_TOTAL" => (string)$item["AREA"],
        "PRICE_TOTAL" => (string)$item["PRICE"],
        "PRICE_OLD" => (string)$item["PRICE_OLD"],
        "PRICE_M2" => (string)$priceM2,
        "STATUS" => $enumPayload["STATUS"],
        "BADGES" => isset($item["BADGES"]) ? (array)$item["BADGES"] : array(),
        "FEATURE_TAGS" => isset($item["FEATURES"]) ? (array)$item["FEATURES"] : array(),
        "DESCRIPTION" => (string)$item["DESCRIPTION"],
        "CEILING" => (string)$item["CEILING"],
        "SEPARATE_ENTRANCE" => $enumPayload["SEPARATE_ENTRANCE"],
        "SHOWCASE_WINDOWS" => $enumPayload["SHOWCASE_WINDOWS"],
        "WET_POINT" => $enumPayload["WET_POINT"],
        "POWER_KW" => (string)$item["POWER_KW"],
        "FINISH" => $enumPayload["FINISH"],
        "PLAN_TITLE" => (string)$item["PLAN_TITLE"],
        "PLAN_TEXT" => (string)$item["PLAN_TEXT"],
        "PLAN_ALT" => (string)$item["NAME"],
        "FLOOR_SLIDE_TITLE" => (string)$item["FLOOR_TITLE"],
        "FLOOR_SLIDE_TEXT" => (string)$item["FLOOR_TEXT"],
        "FLOOR_SLIDE_ALT" => "Схема этажа " . (string)$item["NUMBER"],
        "BUILDING_SLIDE_TITLE" => (string)$item["BUILDING_TITLE"],
        "BUILDING_SLIDE_TEXT" => (string)$item["BUILDING_TEXT"],
        "BUILDING_SLIDE_ALT" => "Положение помещения " . (string)$item["NUMBER"] . " в корпусе",
        "VIEW_SLIDE_TITLE" => (string)$item["VIEW_TITLE"],
        "VIEW_SLIDE_TEXT" => (string)$item["VIEW_TEXT"],
        "VIEW_SLIDE_ALT" => "Вид помещения " . (string)$item["NUMBER"],
        "RENDER_SLIDE_TITLE" => (string)$item["RENDER_TITLE"],
        "RENDER_SLIDE_TEXT" => (string)$item["RENDER_TEXT"],
        "RENDER_SLIDE_ALT" => "Визуализация помещения " . (string)$item["NUMBER"],
    );

    $planFile = commercialSeedFile($planImage);
    $floorFile = commercialSeedFile($floorImage);
    $buildingFile = commercialSeedFile($buildingImage);
    $viewFile = commercialSeedFile($viewImage);
    $renderFile = commercialSeedFile($renderImage);
    if ($planFile) {
        $propertyValues["PLAN_IMAGE"] = $planFile;
    }
    if ($floorFile) {
        $propertyValues["FLOOR_SLIDE_IMAGE"] = $floorFile;
    }
    if ($buildingFile) {
        $propertyValues["BUILDING_SLIDE_IMAGE"] = $buildingFile;
    }
    if ($viewFile) {
        $propertyValues["VIEW_SLIDE_IMAGE"] = $viewFile;
    }
    if ($renderFile) {
        $propertyValues["RENDER_SLIDE_IMAGE"] = $renderFile;
    }

    $existing = CIBlockElement::GetList(
        array(),
        array("IBLOCK_ID" => $commercialIblockId, "CODE" => (string)$item["CODE"]),
        false,
        false,
        array("ID", "CODE")
    )->Fetch();

    $fields = array(
        "IBLOCK_ID" => $commercialIblockId,
        "ACTIVE" => "Y",
        "NAME" => (string)$item["NAME"],
        "CODE" => (string)$item["CODE"],
        "SORT" => (int)$item["SORT"],
        "PREVIEW_PICTURE" => $planFile,
        "PROPERTY_VALUES" => $propertyValues,
    );

    if ($existing) {
        echo "[UPDATE] commercial :: " . $item["CODE"] . PHP_EOL;
        if (!$dryRun) {
            $element = new CIBlockElement();
            $updateFields = $fields;
            unset($updateFields["PROPERTY_VALUES"]);
            $element->Update((int)$existing["ID"], $updateFields);
            CIBlockElement::SetPropertyValuesEx((int)$existing["ID"], $commercialIblockId, $propertyValues);
        }
        continue;
    }

    echo "[CREATE] commercial :: " . $item["CODE"] . PHP_EOL;
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

