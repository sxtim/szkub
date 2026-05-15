<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

require_once $_SERVER["DOCUMENT_ROOT"] . "/local/include/realty_filter_core.php";

if (!function_exists("szcubeParkingFilterFormatPrice")) {
    function szcubeParkingFilterFormatPrice($value)
    {
        $value = (float)$value;
        if ($value <= 0) {
            return "";
        }

        return number_format($value, 0, ".", " ") . " ₽";
    }
}

$parkingDiscreteStateMap = array(
    "projects" => "project",
    "types" => "type",
    "statuses" => "status",
);
$parkingRangeStateKeys = array("price_from", "price_to", "area_from", "area_to", "level_from", "level_to");
$parkingMatchDefinition = array(
    "discrete" => array(
        "projects" => array("field" => "project_code"),
        "types" => array("field" => "type_key"),
        "statuses" => array("field" => "status_key"),
    ),
    "ranges" => array(
        array("field" => "price_total", "from" => "price_from", "to" => "price_to"),
        array("field" => "area_total", "from" => "area_from", "to" => "area_to"),
        array("field" => "level", "from" => "level_from", "to" => "level_to"),
    ),
);

if (!Loader::includeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    return;
}

$parkingIblock = szcubeRealtyFilterFindIblockByCode("parking");
$projectsIblock = szcubeRealtyFilterFindIblockByCode("projects");

$arResult = array(
    "PARKINGS" => array(),
    "COUNT" => 0,
    "PROJECTS" => array(),
    "TYPES" => array(),
    "STATUSES" => array(),
    "RANGES" => array(),
    "CATALOG_PAGE_URL" => isset($arParams["CATALOG_PAGE_URL"]) && trim((string)$arParams["CATALOG_PAGE_URL"]) !== ""
        ? trim((string)$arParams["CATALOG_PAGE_URL"])
        : "/parking/",
);
$pageSize = isset($arParams["PAGE_SIZE"]) ? max(1, (int)$arParams["PAGE_SIZE"]) : 12;

if (!$parkingIblock) {
    $this->IncludeComponentTemplate();
    return;
}

$projectMap = array();
if ($projectsIblock) {
    $projectRes = CIBlockElement::GetList(
        array("SORT" => "ASC", "NAME" => "ASC"),
        array("IBLOCK_ID" => (int)$projectsIblock["ID"], "ACTIVE" => "Y"),
        false,
        false,
        array("ID", "NAME", "CODE")
    );
    while ($projectRow = $projectRes->Fetch()) {
        $projectMap[(int)$projectRow["ID"]] = array(
            "id" => (int)$projectRow["ID"],
            "name" => (string)$projectRow["NAME"],
            "code" => (string)$projectRow["CODE"],
        );
    }
}

$projectOptions = array();
foreach ($projectMap as $project) {
    $projectCode = isset($project["code"]) ? trim((string)$project["code"]) : "";
    $projectName = isset($project["name"]) ? trim((string)$project["name"]) : "";
    if ($projectCode === "" || $projectName === "") {
        continue;
    }

    $projectOptions[$projectCode] = array(
        "key" => $projectCode,
        "label" => $projectName,
        "count" => 0,
    );
}
$typeOptions = array();
$statusOptions = array();
$allowedTypeKeys = array("underground", "ground");
$ranges = array(
    "price" => array("min" => null, "max" => null, "step" => 5000, "precision" => 0),
    "area" => array("min" => null, "max" => null, "step" => 0.1, "precision" => 1),
    "level" => array("min" => null, "max" => null, "step" => 1, "precision" => 0),
);

$elementRes = CIBlockElement::GetList(
    array("SORT" => "ASC", "NAME" => "ASC"),
    array(
        "IBLOCK_ID" => (int)$parkingIblock["ID"],
        "ACTIVE" => "Y",
    ),
    false,
    false,
    array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT")
);

while ($element = $elementRes->GetNextElement()) {
    $fields = $element->GetFields();
    $properties = $element->GetProperties();

    $projectId = isset($properties["PROJECT"]["VALUE"]) ? (int)$properties["PROJECT"]["VALUE"] : 0;
    if ($projectId <= 0 || !isset($projectMap[$projectId])) {
        continue;
    }

    $project = $projectMap[$projectId];
    $parkingNumber = trim((string)(isset($properties["PARKING_NUMBER"]["VALUE"]) ? $properties["PARKING_NUMBER"]["VALUE"] : ""));
    if ($parkingNumber === "") {
        $parkingNumber = trim((string)$fields["NAME"]);
    }

    $title = preg_match("/№/u", $parkingNumber) ? $parkingNumber : ("Парковочное место №" . $parkingNumber);
    $typeLabel = szcubeRealtyFilterPropertySingleValue(isset($properties["PARKING_TYPE"]) ? $properties["PARKING_TYPE"] : array());
    $typeKey = szcubeRealtyFilterPropertySingleKey(isset($properties["PARKING_TYPE"]) ? $properties["PARKING_TYPE"] : array());
    if ($typeKey !== "" && !in_array($typeKey, $allowedTypeKeys, true)) {
        continue;
    }
    $statusLabel = szcubeRealtyFilterPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $statusKey = szcubeRealtyFilterPropertySingleKey(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $badges = szcubeRealtyFilterPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());
    $areaTotal = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0;
    $level = isset($properties["LEVEL"]["VALUE"]) ? (float)$properties["LEVEL"]["VALUE"] : 0;
    $priceTotal = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0;
    $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0;

    szcubeRealtyFilterOptionAppend($projectOptions, $project["code"], $project["name"]);
    szcubeRealtyFilterOptionAppend($typeOptions, $typeKey, $typeLabel);
    szcubeRealtyFilterOptionAppend($statusOptions, $statusKey, $statusLabel);
    szcubeRealtyFilterRangeUpdate($ranges["price"], $priceTotal, true);
    szcubeRealtyFilterRangeUpdate($ranges["area"], $areaTotal, true);
    szcubeRealtyFilterRangeUpdate($ranges["level"], $level, true);

    $arResult["PARKINGS"][] = array(
        "id" => (int)$fields["ID"],
        "code" => (string)$fields["CODE"],
        "sort" => (int)$fields["SORT"],
        "title" => $title,
        "project_code" => $project["code"],
        "project_name" => $project["name"],
        "type_key" => $typeKey,
        "type_label" => $typeLabel,
        "status_key" => $statusKey,
        "status_label" => $statusLabel,
        "area_total" => $areaTotal,
        "area_total_formatted" => $areaTotal > 0 ? number_format($areaTotal, 1, ".", " ") . " м²" : "",
        "level" => $level,
        "level_label" => $level !== 0.0 ? "Уровень " . number_format($level, 0, ".", "") : "",
        "price_total" => $priceTotal,
        "price_total_formatted" => szcubeParkingFilterFormatPrice($priceTotal),
        "price_old" => $priceOld,
        "price_old_formatted" => szcubeParkingFilterFormatPrice($priceOld),
        "badges" => $badges,
        "favorite" => array(
            "entity_type" => "parking",
            "entity_id" => (int)$fields["ID"],
            "key" => "parking:" . (int)$fields["ID"],
        ),
    );
}

ksort($projectOptions);
ksort($typeOptions);
ksort($statusOptions);

$arResult["PROJECTS"] = array_values($projectOptions);
$arResult["TYPES"] = array_values($typeOptions);
$arResult["STATUSES"] = array_values($statusOptions);
$rangeResult = array(
    "price" => szcubeRealtyFilterRangeFinalize($ranges["price"], 0, 0, true),
    "area" => szcubeRealtyFilterRangeFinalize($ranges["area"], 0, 0, true),
    "level" => szcubeRealtyFilterRangeFinalize($ranges["level"], 0, 0, true),
);

$requestState = szcubeRealtyFilterRequestState($parkingDiscreteStateMap, $parkingRangeStateKeys);
$rangeResult["price"] = szcubeResolveSelectedRange($rangeResult["price"], "price_from", "price_to");
$rangeResult["area"] = szcubeResolveSelectedRange($rangeResult["area"], "area_from", "area_to");
$rangeResult["level"] = szcubeResolveSelectedRange($rangeResult["level"], "level_from", "level_to");

$requestState["price_from"] = isset($rangeResult["price"]["actual_min"]) ? (float)$rangeResult["price"]["actual_min"] : null;
$requestState["price_to"] = isset($rangeResult["price"]["actual_max"]) ? (float)$rangeResult["price"]["actual_max"] : null;
$requestState["area_from"] = isset($rangeResult["area"]["actual_min"]) ? (float)$rangeResult["area"]["actual_min"] : null;
$requestState["area_to"] = isset($rangeResult["area"]["actual_max"]) ? (float)$rangeResult["area"]["actual_max"] : null;
$requestState["level_from"] = isset($rangeResult["level"]["actual_min"]) ? (float)$rangeResult["level"]["actual_min"] : null;
$requestState["level_to"] = isset($rangeResult["level"]["actual_max"]) ? (float)$rangeResult["level"]["actual_max"] : null;

$filteredParkings = $arResult["PARKINGS"];
if (szcubeRealtyFilterHasRequestCriteria($requestState, array_keys($parkingDiscreteStateMap), $parkingRangeStateKeys)) {
    $filteredParkings = array_values(array_filter($filteredParkings, static function ($item) use ($requestState, $parkingMatchDefinition) {
        return szcubeRealtyFilterMatchesRequestState($item, $requestState, $parkingMatchDefinition);
    }));
}

$paginationResult = szcubeBuildArrayPagination($filteredParkings, $pageSize, "PAGEN_1");
$arResult["PARKINGS"] = isset($paginationResult["items"]) && is_array($paginationResult["items"]) ? $paginationResult["items"] : array();
$arResult["COUNT"] = isset($paginationResult["count"]) ? (int)$paginationResult["count"] : count($arResult["PARKINGS"]);
$arResult["PAGINATION"] = isset($paginationResult["pagination"]) && is_array($paginationResult["pagination"]) ? $paginationResult["pagination"] : null;
$arResult["RANGES"] = $rangeResult;

$this->IncludeComponentTemplate();
