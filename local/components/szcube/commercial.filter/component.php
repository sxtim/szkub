<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

require_once $_SERVER["DOCUMENT_ROOT"] . "/local/include/realty_filter_core.php";

if (!function_exists("szcubeCommercialFilterFloorShort")) {
    function szcubeCommercialFilterFloorShort($floor, $houseFloors)
    {
        $floor = (int)$floor;
        $houseFloors = (int)$houseFloors;

        if ($floor <= 0 && $houseFloors <= 0) {
            return "";
        }

        if ($floor > 0 && $houseFloors > 0) {
            return $floor . "/" . $houseFloors . " этаж";
        }

        if ($floor > 0) {
            return $floor . " этаж";
        }

        return "";
    }
}

if (!function_exists("szcubeCommercialFilterSortValue")) {
    function szcubeCommercialFilterSortValue()
    {
        $value = isset($_GET["sort"]) ? trim((string)$_GET["sort"]) : "default";
        $allowed = array("default", "price_asc", "price_desc", "floor_asc", "floor_desc", "area_desc");
        return in_array($value, $allowed, true) ? $value : "default";
    }
}

$commercialDiscreteStateMap = array(
    "projects" => "project",
    "types" => "type",
    "statuses" => "status",
    "features" => "feature",
);
$commercialRangeStateKeys = array("price_from", "price_to", "floor_from", "floor_to", "area_from", "area_to");
$commercialMatchDefinition = array(
    "discrete" => array(
        "projects" => array("field" => "project_code"),
        "types" => array("field" => "type_key"),
        "statuses" => array("field" => "status"),
        "features" => array("field" => "feature_tags", "mode" => "intersect"),
    ),
    "ranges" => array(
        array("field" => "price_total", "from" => "price_from", "to" => "price_to"),
        array("field" => "floor_max", "from" => "floor_from", "to" => "floor_to"),
        array("field" => "area_total", "from" => "area_from", "to" => "area_to"),
    ),
);
$commercialSortMap = array(
    "price_asc" => array("field" => "price_total", "direction" => "asc"),
    "price_desc" => array("field" => "price_total", "direction" => "desc"),
    "floor_asc" => array("field" => "floor_max", "direction" => "asc"),
    "floor_desc" => array("field" => "floor_max", "direction" => "desc"),
    "area_desc" => array("field" => "area_total", "direction" => "desc"),
);

if (!Loader::includeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    return;
}

$commercialIblock = szcubeRealtyFilterFindIblockByCode("commercial");
$projectsIblock = szcubeRealtyFilterFindIblockByCode("projects");

$arResult = array(
    "COMMERCIALS" => array(),
    "COUNT" => 0,
    "PROJECTS" => array(),
    "TYPES" => array(),
    "STATUSES" => array(),
    "FEATURE_TAGS" => array(),
    "RANGES" => array(),
    "CATALOG_PAGE_URL" => isset($arParams["CATALOG_PAGE_URL"]) && trim((string)$arParams["CATALOG_PAGE_URL"]) !== ""
        ? trim((string)$arParams["CATALOG_PAGE_URL"])
        : "/commerce/",
);
$pageSize = isset($arParams["PAGE_SIZE"]) ? max(1, (int)$arParams["PAGE_SIZE"]) : 12;

if (!$commercialIblock) {
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
        array("ID", "IBLOCK_ID", "NAME", "CODE")
    );
    while ($project = $projectRes->GetNextElement()) {
        $fields = $project->GetFields();
        $properties = $project->GetProperties();
        $projectCode = trim((string)$fields["CODE"]);
        if ($projectCode === "") {
            continue;
        }

        $projectMap[(int)$fields["ID"]] = array(
            "id" => (int)$fields["ID"],
            "name" => (string)$fields["NAME"],
            "code" => $projectCode,
            "delivery" => isset($properties["DELIVERY_TEXT"]["VALUE"]) ? trim((string)$properties["DELIVERY_TEXT"]["VALUE"]) : "",
            "url" => "/projects/" . $projectCode . "/",
        );
    }
}

$projectOptions = array();
foreach ($projectMap as $project) {
    if ($project["code"] === "" || $project["name"] === "") {
        continue;
    }

    $projectOptions[$project["code"]] = array(
        "key" => $project["code"],
        "label" => $project["name"],
        "count" => 0,
    );
}

$typeOptions = array();
$statusOptions = array();
$featureOptions = array();
$ranges = array(
    "price" => array("min" => null, "max" => null, "step" => 10000, "precision" => 0),
    "area" => array("min" => null, "max" => null, "step" => 0.1, "precision" => 1),
    "floor" => array("min" => null, "max" => null, "step" => 1, "precision" => 0),
);

$elementRes = CIBlockElement::GetList(
    array("SORT" => "ASC", "NAME" => "ASC"),
    array(
        "IBLOCK_ID" => (int)$commercialIblock["ID"],
        "ACTIVE" => "Y",
    ),
    false,
    false,
    array("ID", "IBLOCK_ID", "NAME", "CODE", "SORT", "PREVIEW_PICTURE", "DETAIL_PAGE_URL")
);

while ($element = $elementRes->GetNextElement()) {
    $fields = $element->GetFields();
    $properties = $element->GetProperties();

    $projectId = isset($properties["PROJECT"]["VALUE"]) ? (int)$properties["PROJECT"]["VALUE"] : 0;
    if ($projectId <= 0 || !isset($projectMap[$projectId])) {
        continue;
    }

    $project = $projectMap[$projectId];
    $commercialNumber = trim((string)(isset($properties["COMMERCIAL_NUMBER"]["VALUE"]) ? $properties["COMMERCIAL_NUMBER"]["VALUE"] : ""));
    if ($commercialNumber === "") {
        $commercialNumber = trim((string)$fields["NAME"]);
    }

    $typeLabel = szcubeRealtyFilterPropertySingleValue(isset($properties["COMMERCIAL_TYPE"]) ? $properties["COMMERCIAL_TYPE"] : array());
    $typeKey = szcubeRealtyFilterPropertySingleKey(isset($properties["COMMERCIAL_TYPE"]) ? $properties["COMMERCIAL_TYPE"] : array());
    $statusLabel = szcubeRealtyFilterPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $statusKey = szcubeRealtyFilterPropertySingleKey(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $badges = szcubeRealtyFilterPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());
    $featureTags = szcubeRealtyFilterPropertyMultipleValues(isset($properties["FEATURE_TAGS"]) ? $properties["FEATURE_TAGS"] : array());

    $areaTotal = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0;
    $priceTotal = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0;
    $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0;
    $floor = isset($properties["FLOOR"]["VALUE"]) ? (int)$properties["FLOOR"]["VALUE"] : 0;
    $houseFloors = isset($properties["HOUSE_FLOORS"]["VALUE"]) ? (int)$properties["HOUSE_FLOORS"]["VALUE"] : 0;
    $ceiling = isset($properties["CEILING"]["VALUE"]) ? (float)$properties["CEILING"]["VALUE"] : 0;
    $planImage = szcubeRealtyFilterFilePath(isset($properties["PLAN_IMAGE"]["VALUE"]) ? $properties["PLAN_IMAGE"]["VALUE"] : 0);
    if ($planImage === "" && (int)$fields["PREVIEW_PICTURE"] > 0) {
        $planImage = (string)CFile::GetPath((int)$fields["PREVIEW_PICTURE"]);
    }

    szcubeRealtyFilterOptionAppend($projectOptions, $project["code"], $project["name"]);
    szcubeRealtyFilterOptionAppend($typeOptions, $typeKey, $typeLabel);
    szcubeRealtyFilterOptionAppend($statusOptions, $statusKey, $statusLabel);
    foreach ($featureTags as $tag) {
        szcubeRealtyFilterOptionAppend($featureOptions, szcubeRealtyFilterNormalizeKey($tag), $tag);
    }
    szcubeRealtyFilterRangeUpdate($ranges["price"], $priceTotal);
    szcubeRealtyFilterRangeUpdate($ranges["area"], $areaTotal);
    szcubeRealtyFilterRangeUpdate($ranges["floor"], $floor);

    $detailUrl = trim((string)$fields["DETAIL_PAGE_URL"]);
    $detailTemplate = isset($commercialIblock["DETAIL_PAGE_URL"]) ? (string)$commercialIblock["DETAIL_PAGE_URL"] : "";
    if ($detailUrl === "" || strpos($detailUrl, "#") !== false) {
        $detailUrl = szcubeRealtyFilterElementUrl($detailTemplate, $fields, "/commerce");
    }

    $arResult["COMMERCIALS"][] = array(
        "id" => (int)$fields["ID"],
        "code" => (string)$fields["CODE"],
        "sort" => (int)$fields["SORT"],
        "url" => $detailUrl,
        "title" => trim((string)$fields["NAME"]),
        "list_action_label" => "Подробнее",
        "project_code" => $project["code"],
        "project_name" => $project["name"],
        "project_delivery" => $project["delivery"],
        "project_url" => $project["url"],
        "board_enabled" => false,
        "type_key" => $typeKey,
        "type_label" => $typeLabel !== "" ? $typeLabel : "Коммерческое помещение",
        "rooms_bucket" => $typeKey,
        "rooms_label" => $typeLabel !== "" ? $typeLabel : "Коммерческое помещение",
        "status" => $statusKey,
        "status_label" => $statusLabel,
        "area_total" => $areaTotal > 0 ? rtrim(rtrim(number_format($areaTotal, 1, ".", ""), "0"), ".") : "",
        "floor" => $floor,
        "floor_to" => $floor,
        "floor_max" => $floor,
        "house_floors" => $houseFloors,
        "floor_short" => szcubeCommercialFilterFloorShort($floor, $houseFloors),
        "price_total" => $priceTotal,
        "price_old" => $priceOld,
        "ceiling" => $ceiling,
        "badges" => $badges,
        "feature_tags" => array_map("szcubeRealtyFilterNormalizeKey", $featureTags),
        "plan_image" => $planImage,
        "plan_alt" => isset($properties["PLAN_ALT"]["VALUE"]) && trim((string)$properties["PLAN_ALT"]["VALUE"]) !== ""
            ? trim((string)$properties["PLAN_ALT"]["VALUE"])
            : trim((string)$fields["NAME"]),
    );
}

ksort($projectOptions);
uasort($typeOptions, static function ($left, $right) {
    return strcmp((string)$left["label"], (string)$right["label"]);
});
ksort($statusOptions);
uasort($featureOptions, static function ($left, $right) {
    return strcmp((string)$left["label"], (string)$right["label"]);
});

$arResult["PROJECTS"] = array_values($projectOptions);
$arResult["TYPES"] = array_values($typeOptions);
$arResult["STATUSES"] = array_values($statusOptions);
$arResult["FEATURE_TAGS"] = array_values($featureOptions);
$rangeResult = array(
    "price" => szcubeRealtyFilterRangeFinalize($ranges["price"], 0, 0, true),
    "area" => szcubeRealtyFilterRangeFinalize($ranges["area"], 0, 0, true),
    "floor" => szcubeRealtyFilterRangeFinalize($ranges["floor"], 1, 1, true),
);
$requestState = szcubeRealtyFilterRequestState($commercialDiscreteStateMap, $commercialRangeStateKeys);
$currentSort = szcubeCommercialFilterSortValue();

$rangeResult["price"] = szcubeResolveSelectedRange($rangeResult["price"], "price_from", "price_to");
$rangeResult["area"] = szcubeResolveSelectedRange($rangeResult["area"], "area_from", "area_to");
$rangeResult["floor"] = szcubeResolveSelectedRange($rangeResult["floor"], "floor_from", "floor_to");

$requestState["price_from"] = isset($rangeResult["price"]["actual_min"]) ? (float)$rangeResult["price"]["actual_min"] : null;
$requestState["price_to"] = isset($rangeResult["price"]["actual_max"]) ? (float)$rangeResult["price"]["actual_max"] : null;
$requestState["area_from"] = isset($rangeResult["area"]["actual_min"]) ? (float)$rangeResult["area"]["actual_min"] : null;
$requestState["area_to"] = isset($rangeResult["area"]["actual_max"]) ? (float)$rangeResult["area"]["actual_max"] : null;
$requestState["floor_from"] = isset($rangeResult["floor"]["actual_min"]) ? (float)$rangeResult["floor"]["actual_min"] : null;
$requestState["floor_to"] = isset($rangeResult["floor"]["actual_max"]) ? (float)$rangeResult["floor"]["actual_max"] : null;

$filteredCommercials = $arResult["COMMERCIALS"];
if (szcubeRealtyFilterHasRequestCriteria($requestState, array_keys($commercialDiscreteStateMap), $commercialRangeStateKeys)) {
    $filteredCommercials = array_values(array_filter($filteredCommercials, static function ($item) use ($requestState, $commercialMatchDefinition) {
        return szcubeRealtyFilterMatchesRequestState($item, $requestState, $commercialMatchDefinition);
    }));
}

$filteredCommercials = szcubeRealtyFilterSortItems(
    $filteredCommercials,
    $currentSort,
    $commercialSortMap,
    static function (array $items) {
        usort($items, static function ($left, $right) {
            $sortDiff = ((int)$left["sort"] <=> (int)$right["sort"]);
            if ($sortDiff !== 0) {
                return $sortDiff;
            }

            return ((int)$left["id"] <=> (int)$right["id"]);
        });

        return $items;
    }
);
$paginationResult = szcubeBuildArrayPagination($filteredCommercials, $pageSize, "PAGEN_1");

$arResult["COMMERCIALS"] = isset($paginationResult["items"]) && is_array($paginationResult["items"]) ? $paginationResult["items"] : array();
$arResult["COUNT"] = isset($paginationResult["count"]) ? (int)$paginationResult["count"] : count($arResult["COMMERCIALS"]);
$arResult["PAGINATION"] = isset($paginationResult["pagination"]) && is_array($paginationResult["pagination"]) ? $paginationResult["pagination"] : null;
$arResult["CURRENT_SORT"] = $currentSort;
$arResult["RANGES"] = $rangeResult;

$this->IncludeComponentTemplate();
