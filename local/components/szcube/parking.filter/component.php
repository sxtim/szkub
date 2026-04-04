<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!function_exists("szcubeParkingFilterFindIblockByCode")) {
    function szcubeParkingFilterFindIblockByCode($code)
    {
        $res = CIBlock::GetList(array(), array("CODE" => (string)$code, "ACTIVE" => "Y"), false);
        return $res->Fetch() ?: null;
    }
}

if (!function_exists("szcubeParkingFilterNormalizeKey")) {
    function szcubeParkingFilterNormalizeKey($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        if (function_exists("mb_strtolower")) {
            $value = mb_strtolower($value);
        } else {
            $value = strtolower($value);
        }

        $value = preg_replace("/[^a-z0-9а-яё_-]+/iu", "-", $value);
        $value = preg_replace("/-+/u", "-", (string)$value);
        return trim((string)$value, "-");
    }
}

if (!function_exists("szcubeParkingFilterFilePath")) {
    function szcubeParkingFilterFilePath($value)
    {
        $fileId = (int)$value;
        if ($fileId <= 0) {
            return "";
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : "";
    }
}

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

if (!function_exists("szcubeParkingFilterPropertySingleValue")) {
    function szcubeParkingFilterPropertySingleValue(array $property)
    {
        if (isset($property["VALUE_ENUM"]) && trim((string)$property["VALUE_ENUM"]) !== "") {
            return trim((string)$property["VALUE_ENUM"]);
        }

        if (isset($property["VALUE"]) && !is_array($property["VALUE"])) {
            return trim((string)$property["VALUE"]);
        }

        return "";
    }
}

if (!function_exists("szcubeParkingFilterPropertySingleKey")) {
    function szcubeParkingFilterPropertySingleKey(array $property)
    {
        if (isset($property["VALUE_XML_ID"]) && trim((string)$property["VALUE_XML_ID"]) !== "") {
            return szcubeParkingFilterNormalizeKey($property["VALUE_XML_ID"]);
        }

        return szcubeParkingFilterNormalizeKey(szcubeParkingFilterPropertySingleValue($property));
    }
}

if (!function_exists("szcubeParkingFilterPropertyMultipleValues")) {
    function szcubeParkingFilterPropertyMultipleValues(array $property)
    {
        $source = array();
        if (isset($property["VALUE_ENUM"]) && is_array($property["VALUE_ENUM"])) {
            $source = $property["VALUE_ENUM"];
        } elseif (isset($property["VALUE"]) && is_array($property["VALUE"])) {
            $source = $property["VALUE"];
        } elseif (isset($property["VALUE"]) && trim((string)$property["VALUE"]) !== "") {
            $source = array($property["VALUE"]);
        }

        $result = array();
        foreach ($source as $item) {
            $item = trim((string)$item);
            if ($item !== "") {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists("szcubeParkingFilterRangeUpdate")) {
    function szcubeParkingFilterRangeUpdate(array &$range, $value)
    {
        $value = (float)$value;
        if (!is_finite($value)) {
            return;
        }

        if ($range["min"] === null || $value < $range["min"]) {
            $range["min"] = $value;
        }
        if ($range["max"] === null || $value > $range["max"]) {
            $range["max"] = $value;
        }
    }
}

if (!function_exists("szcubeParkingFilterRangeFinalize")) {
    function szcubeParkingFilterRangeFinalize(array $range, $fallbackMin, $fallbackMax)
    {
        $actualMin = $range["min"] !== null ? (float)$range["min"] : (float)$fallbackMin;
        $actualMax = $range["max"] !== null ? (float)$range["max"] : (float)$fallbackMax;
        $step = isset($range["step"]) ? (float)$range["step"] : 1;
        $precision = isset($range["precision"]) ? (int)$range["precision"] : 0;
        if ($actualMax <= $actualMin) {
            $actualMax = $actualMin + ($step > 0 ? $step : 1);
        }

        return array(
            "actual_min" => round($actualMin, $precision),
            "actual_max" => round($actualMax, $precision),
            "render_min" => round($actualMin, $precision),
            "render_max" => round($actualMax, $precision),
            "step" => $step,
            "precision" => $precision,
        );
    }
}

if (!function_exists("szcubeParkingFilterOptionAppend")) {
    function szcubeParkingFilterOptionAppend(array &$options, $key, $label)
    {
        $key = trim((string)$key);
        $label = trim((string)$label);
        if ($key === "" || $label === "") {
            return;
        }

        if (!isset($options[$key])) {
            $options[$key] = array(
                "key" => $key,
                "label" => $label,
                "count" => 0,
            );
        }

        $options[$key]["count"]++;
    }
}

if (!function_exists("szcubeParkingFilterRequestState")) {
    function szcubeParkingFilterRequestState()
    {
        return array(
            "projects" => function_exists("szcubeRequestCsvList") ? szcubeRequestCsvList("project") : array(),
            "types" => function_exists("szcubeRequestCsvList") ? szcubeRequestCsvList("type") : array(),
            "statuses" => function_exists("szcubeRequestCsvList") ? szcubeRequestCsvList("status") : array(),
            "price_from" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("price_from") : null,
            "price_to" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("price_to") : null,
            "area_from" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("area_from") : null,
            "area_to" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("area_to") : null,
            "level_from" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("level_from") : null,
            "level_to" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("level_to") : null,
        );
    }
}

if (!function_exists("szcubeParkingFilterHasRequestCriteria")) {
    function szcubeParkingFilterHasRequestCriteria(array $state)
    {
        foreach (array("projects", "types", "statuses") as $key) {
            if (!empty($state[$key]) && is_array($state[$key])) {
                return true;
            }
        }

        foreach (array("price_from", "price_to", "area_from", "area_to", "level_from", "level_to") as $key) {
            if ($state[$key] !== null) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("szcubeParkingFilterMatchesRequestState")) {
    function szcubeParkingFilterMatchesRequestState(array $item, array $state)
    {
        if (!empty($state["projects"]) && !in_array((string)$item["project_code"], $state["projects"], true)) {
            return false;
        }
        if (!empty($state["types"]) && !in_array((string)$item["type_key"], $state["types"], true)) {
            return false;
        }
        if (!empty($state["statuses"]) && !in_array((string)$item["status_key"], $state["statuses"], true)) {
            return false;
        }

        $priceTotal = isset($item["price_total"]) ? (float)$item["price_total"] : 0.0;
        if ($priceTotal > 0) {
            if ($state["price_from"] !== null && $priceTotal + 0.0001 < (float)$state["price_from"]) {
                return false;
            }
            if ($state["price_to"] !== null && $priceTotal - 0.0001 > (float)$state["price_to"]) {
                return false;
            }
        }

        $areaTotal = isset($item["area_total"]) ? (float)$item["area_total"] : 0.0;
        if ($areaTotal > 0) {
            if ($state["area_from"] !== null && $areaTotal + 0.0001 < (float)$state["area_from"]) {
                return false;
            }
            if ($state["area_to"] !== null && $areaTotal - 0.0001 > (float)$state["area_to"]) {
                return false;
            }
        }

        $level = isset($item["level"]) ? (float)$item["level"] : 0.0;
        if ($state["level_from"] !== null && $level + 0.0001 < (float)$state["level_from"]) {
            return false;
        }
        if ($state["level_to"] !== null && $level - 0.0001 > (float)$state["level_to"]) {
            return false;
        }

        return true;
    }
}

if (!Loader::includeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    return;
}

$parkingIblock = szcubeParkingFilterFindIblockByCode("parking");
$projectsIblock = szcubeParkingFilterFindIblockByCode("projects");

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
    $typeLabel = szcubeParkingFilterPropertySingleValue(isset($properties["PARKING_TYPE"]) ? $properties["PARKING_TYPE"] : array());
    $typeKey = szcubeParkingFilterPropertySingleKey(isset($properties["PARKING_TYPE"]) ? $properties["PARKING_TYPE"] : array());
    if ($typeKey !== "" && !in_array($typeKey, $allowedTypeKeys, true)) {
        continue;
    }
    $statusLabel = szcubeParkingFilterPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $statusKey = szcubeParkingFilterPropertySingleKey(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $badges = szcubeParkingFilterPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());
    $areaTotal = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0;
    $level = isset($properties["LEVEL"]["VALUE"]) ? (float)$properties["LEVEL"]["VALUE"] : 0;
    $priceTotal = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0;
    $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0;

    szcubeParkingFilterOptionAppend($projectOptions, $project["code"], $project["name"]);
    szcubeParkingFilterOptionAppend($typeOptions, $typeKey, $typeLabel);
    szcubeParkingFilterOptionAppend($statusOptions, $statusKey, $statusLabel);
    szcubeParkingFilterRangeUpdate($ranges["price"], $priceTotal);
    szcubeParkingFilterRangeUpdate($ranges["area"], $areaTotal);
    szcubeParkingFilterRangeUpdate($ranges["level"], $level);

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
        "favorite_key" => "parking-" . (int)$fields["ID"],
    );
}

ksort($projectOptions);
ksort($typeOptions);
ksort($statusOptions);

$arResult["PROJECTS"] = array_values($projectOptions);
$arResult["TYPES"] = array_values($typeOptions);
$arResult["STATUSES"] = array_values($statusOptions);
$rangeResult = array(
    "price" => szcubeParkingFilterRangeFinalize($ranges["price"], 0, 0),
    "area" => szcubeParkingFilterRangeFinalize($ranges["area"], 0, 0),
    "level" => szcubeParkingFilterRangeFinalize($ranges["level"], 0, 0),
);

$requestState = szcubeParkingFilterRequestState();
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
if (szcubeParkingFilterHasRequestCriteria($requestState)) {
    $filteredParkings = array_values(array_filter($filteredParkings, static function ($item) use ($requestState) {
        return szcubeParkingFilterMatchesRequestState($item, $requestState);
    }));
}

$paginationResult = szcubeBuildArrayPagination($filteredParkings, $pageSize, "PAGEN_1");
$arResult["PARKINGS"] = isset($paginationResult["items"]) && is_array($paginationResult["items"]) ? $paginationResult["items"] : array();
$arResult["COUNT"] = isset($paginationResult["count"]) ? (int)$paginationResult["count"] : count($arResult["PARKINGS"]);
$arResult["PAGINATION"] = isset($paginationResult["pagination"]) && is_array($paginationResult["pagination"]) ? $paginationResult["pagination"] : null;
$arResult["RANGES"] = $rangeResult;

$this->IncludeComponentTemplate();
