<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!function_exists("szcubeStoreroomFilterFindIblockByCode")) {
    function szcubeStoreroomFilterFindIblockByCode($code)
    {
        $res = CIBlock::GetList(array(), array("CODE" => (string)$code, "ACTIVE" => "Y"), false);
        return $res->Fetch() ?: null;
    }
}

if (!function_exists("szcubeStoreroomFilterNormalizeKey")) {
    function szcubeStoreroomFilterNormalizeKey($value)
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

if (!function_exists("szcubeStoreroomFilterFormatPrice")) {
    function szcubeStoreroomFilterFormatPrice($value)
    {
        $value = (float)$value;
        if ($value <= 0) {
            return "";
        }

        return number_format($value, 0, ".", " ") . " ₽";
    }
}

if (!function_exists("szcubeStoreroomFilterPropertySingleValue")) {
    function szcubeStoreroomFilterPropertySingleValue(array $property)
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

if (!function_exists("szcubeStoreroomFilterPropertySingleKey")) {
    function szcubeStoreroomFilterPropertySingleKey(array $property)
    {
        if (isset($property["VALUE_XML_ID"]) && trim((string)$property["VALUE_XML_ID"]) !== "") {
            return szcubeStoreroomFilterNormalizeKey($property["VALUE_XML_ID"]);
        }

        return szcubeStoreroomFilterNormalizeKey(szcubeStoreroomFilterPropertySingleValue($property));
    }
}

if (!function_exists("szcubeStoreroomFilterPropertyMultipleValues")) {
    function szcubeStoreroomFilterPropertyMultipleValues(array $property)
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

if (!function_exists("szcubeStoreroomFilterRangeUpdate")) {
    function szcubeStoreroomFilterRangeUpdate(array &$range, $value)
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

if (!function_exists("szcubeStoreroomFilterRangeFinalize")) {
    function szcubeStoreroomFilterRangeFinalize(array $range, $fallbackMin, $fallbackMax)
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

if (!function_exists("szcubeStoreroomFilterOptionAppend")) {
    function szcubeStoreroomFilterOptionAppend(array &$options, $key, $label)
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

if (!function_exists("szcubeStoreroomFilterRequestState")) {
    function szcubeStoreroomFilterRequestState()
    {
        return array(
            "projects" => function_exists("szcubeRequestCsvList") ? szcubeRequestCsvList("project") : array(),
            "statuses" => function_exists("szcubeRequestCsvList") ? szcubeRequestCsvList("status") : array(),
            "price_from" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("price_from") : null,
            "price_to" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("price_to") : null,
            "area_from" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("area_from") : null,
            "area_to" => function_exists("szcubeRequestNumberValue") ? szcubeRequestNumberValue("area_to") : null,
        );
    }
}

if (!function_exists("szcubeStoreroomFilterHasRequestCriteria")) {
    function szcubeStoreroomFilterHasRequestCriteria(array $state)
    {
        foreach (array("projects", "statuses") as $key) {
            if (!empty($state[$key]) && is_array($state[$key])) {
                return true;
            }
        }

        foreach (array("price_from", "price_to", "area_from", "area_to") as $key) {
            if ($state[$key] !== null) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("szcubeStoreroomFilterMatchesRequestState")) {
    function szcubeStoreroomFilterMatchesRequestState(array $item, array $state)
    {
        if (!empty($state["projects"]) && !in_array((string)$item["project_code"], $state["projects"], true)) {
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

        return true;
    }
}

if (!Loader::includeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    return;
}

$storeroomsIblock = szcubeStoreroomFilterFindIblockByCode("storerooms");
$projectsIblock = szcubeStoreroomFilterFindIblockByCode("projects");

$arResult = array(
    "STOREROOMS" => array(),
    "COUNT" => 0,
    "PROJECTS" => array(),
    "STATUSES" => array(),
    "RANGES" => array(),
    "CATALOG_PAGE_URL" => isset($arParams["CATALOG_PAGE_URL"]) && trim((string)$arParams["CATALOG_PAGE_URL"]) !== ""
        ? trim((string)$arParams["CATALOG_PAGE_URL"])
        : "/storerooms/",
);
$pageSize = isset($arParams["PAGE_SIZE"]) ? max(1, (int)$arParams["PAGE_SIZE"]) : 12;

if (!$storeroomsIblock) {
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

$statusOptions = array();
$ranges = array(
    "price" => array("min" => null, "max" => null, "step" => 5000, "precision" => 0),
    "area" => array("min" => null, "max" => null, "step" => 0.1, "precision" => 1),
);

$elementRes = CIBlockElement::GetList(
    array("SORT" => "ASC", "NAME" => "ASC"),
    array(
        "IBLOCK_ID" => (int)$storeroomsIblock["ID"],
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
    $storeroomNumber = trim((string)(isset($properties["STOREROOM_NUMBER"]["VALUE"]) ? $properties["STOREROOM_NUMBER"]["VALUE"] : ""));
    if ($storeroomNumber === "") {
        $storeroomNumber = trim((string)$fields["NAME"]);
    }
    if ($storeroomNumber === "") {
        continue;
    }

    if (preg_match("/№/u", $storeroomNumber) || preg_match("/кладов/iu", $storeroomNumber)) {
        $title = $storeroomNumber;
    } else {
        $title = "Кладовка №" . $storeroomNumber;
    }

    $statusLabel = szcubeStoreroomFilterPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $statusKey = szcubeStoreroomFilterPropertySingleKey(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $badges = szcubeStoreroomFilterPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());
    $areaTotal = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0;
    $priceTotal = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0;
    $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0;

    szcubeStoreroomFilterOptionAppend($projectOptions, $project["code"], $project["name"]);
    szcubeStoreroomFilterOptionAppend($statusOptions, $statusKey, $statusLabel);
    szcubeStoreroomFilterRangeUpdate($ranges["area"], $areaTotal);
    szcubeStoreroomFilterRangeUpdate($ranges["price"], $priceTotal);

    $arResult["STOREROOMS"][] = array(
        "id" => (int)$fields["ID"],
        "code" => (string)$fields["CODE"],
        "sort" => (int)$fields["SORT"],
        "title" => $title,
        "project_code" => $project["code"],
        "project_name" => $project["name"],
        "type_key" => "",
        "type_label" => "Кладовое помещение",
        "status_key" => $statusKey,
        "status_label" => $statusLabel,
        "area_total" => $areaTotal,
        "area_total_formatted" => $areaTotal > 0 ? number_format($areaTotal, 1, ".", " ") . " м²" : "",
        "level" => 0,
        "level_label" => "",
        "price_total" => $priceTotal,
        "price_total_formatted" => szcubeStoreroomFilterFormatPrice($priceTotal),
        "price_old" => $priceOld,
        "price_old_formatted" => szcubeStoreroomFilterFormatPrice($priceOld),
        "badges" => $badges,
        "favorite_key" => "storeroom-" . (int)$fields["ID"],
    );
}

ksort($projectOptions);
ksort($statusOptions);

$arResult["PROJECTS"] = array_values($projectOptions);
$arResult["STATUSES"] = array_values($statusOptions);
$rangeResult = array(
    "area" => szcubeStoreroomFilterRangeFinalize($ranges["area"], 0, 0),
    "price" => szcubeStoreroomFilterRangeFinalize($ranges["price"], 0, 0),
);

$requestState = szcubeStoreroomFilterRequestState();
$rangeResult["price"] = szcubeResolveSelectedRange($rangeResult["price"], "price_from", "price_to");
$rangeResult["area"] = szcubeResolveSelectedRange($rangeResult["area"], "area_from", "area_to");

$requestState["price_from"] = isset($rangeResult["price"]["actual_min"]) ? (float)$rangeResult["price"]["actual_min"] : null;
$requestState["price_to"] = isset($rangeResult["price"]["actual_max"]) ? (float)$rangeResult["price"]["actual_max"] : null;
$requestState["area_from"] = isset($rangeResult["area"]["actual_min"]) ? (float)$rangeResult["area"]["actual_min"] : null;
$requestState["area_to"] = isset($rangeResult["area"]["actual_max"]) ? (float)$rangeResult["area"]["actual_max"] : null;

$filteredStorerooms = $arResult["STOREROOMS"];
if (szcubeStoreroomFilterHasRequestCriteria($requestState)) {
    $filteredStorerooms = array_values(array_filter($filteredStorerooms, static function ($item) use ($requestState) {
        return szcubeStoreroomFilterMatchesRequestState($item, $requestState);
    }));
}

$paginationResult = szcubeBuildArrayPagination($filteredStorerooms, $pageSize, "PAGEN_1");
$arResult["STOREROOMS"] = isset($paginationResult["items"]) && is_array($paginationResult["items"]) ? $paginationResult["items"] : array();
$arResult["COUNT"] = isset($paginationResult["count"]) ? (int)$paginationResult["count"] : count($arResult["STOREROOMS"]);
$arResult["PAGINATION"] = isset($paginationResult["pagination"]) && is_array($paginationResult["pagination"]) ? $paginationResult["pagination"] : null;
$arResult["RANGES"] = $rangeResult;

$this->IncludeComponentTemplate();
