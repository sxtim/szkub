<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

require_once $_SERVER["DOCUMENT_ROOT"] . "/local/include/realty_filter_core.php";

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

$storeroomDiscreteStateMap = array(
    "projects" => "project",
    "statuses" => "status",
);
$storeroomRangeStateKeys = array("price_from", "price_to", "area_from", "area_to");
$storeroomMatchDefinition = array(
    "discrete" => array(
        "projects" => array("field" => "project_code"),
        "statuses" => array("field" => "status_key"),
    ),
    "ranges" => array(
        array("field" => "price_total", "from" => "price_from", "to" => "price_to"),
        array("field" => "area_total", "from" => "area_from", "to" => "area_to"),
    ),
);

if (!Loader::includeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    return;
}

$storeroomsIblock = szcubeRealtyFilterFindIblockByCode("storerooms");
$projectsIblock = szcubeRealtyFilterFindIblockByCode("projects");

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

    $statusLabel = szcubeRealtyFilterPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $statusKey = szcubeRealtyFilterPropertySingleKey(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    if (szcubeRealtyFilterIsPubliclyHiddenStatus($statusKey, $statusLabel, false)) {
        continue;
    }
    $badges = szcubeRealtyFilterPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());
    $areaTotal = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0;
    $priceTotal = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0;
    $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0;

    szcubeRealtyFilterOptionAppend($projectOptions, $project["code"], $project["name"]);
    szcubeRealtyFilterOptionAppend($statusOptions, $statusKey, $statusLabel);
    szcubeRealtyFilterRangeUpdate($ranges["area"], $areaTotal, true);
    szcubeRealtyFilterRangeUpdate($ranges["price"], $priceTotal, true);

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
        "favorite" => array(
            "entity_type" => "storeroom",
            "entity_id" => (int)$fields["ID"],
            "key" => "storeroom:" . (int)$fields["ID"],
        ),
    );
}

ksort($projectOptions);
ksort($statusOptions);

$arResult["PROJECTS"] = array_values($projectOptions);
$arResult["STATUSES"] = array_values($statusOptions);
$rangeResult = array(
    "area" => szcubeRealtyFilterRangeFinalize($ranges["area"], 0, 0, true),
    "price" => szcubeRealtyFilterRangeFinalize($ranges["price"], 0, 0, true),
);

$requestState = szcubeRealtyFilterRequestState($storeroomDiscreteStateMap, $storeroomRangeStateKeys);
$rangeResult["price"] = szcubeResolveSelectedRange($rangeResult["price"], "price_from", "price_to");
$rangeResult["area"] = szcubeResolveSelectedRange($rangeResult["area"], "area_from", "area_to");

$requestState["price_from"] = isset($rangeResult["price"]["actual_min"]) ? (float)$rangeResult["price"]["actual_min"] : null;
$requestState["price_to"] = isset($rangeResult["price"]["actual_max"]) ? (float)$rangeResult["price"]["actual_max"] : null;
$requestState["area_from"] = isset($rangeResult["area"]["actual_min"]) ? (float)$rangeResult["area"]["actual_min"] : null;
$requestState["area_to"] = isset($rangeResult["area"]["actual_max"]) ? (float)$rangeResult["area"]["actual_max"] : null;

$filteredStorerooms = $arResult["STOREROOMS"];
if (szcubeRealtyFilterHasRequestCriteria($requestState, array_keys($storeroomDiscreteStateMap), $storeroomRangeStateKeys)) {
    $filteredStorerooms = array_values(array_filter($filteredStorerooms, static function ($item) use ($requestState, $storeroomMatchDefinition) {
        return szcubeRealtyFilterMatchesRequestState($item, $requestState, $storeroomMatchDefinition);
    }));
}

$paginationResult = szcubeBuildArrayPagination($filteredStorerooms, $pageSize, "PAGEN_1");
$arResult["STOREROOMS"] = isset($paginationResult["items"]) && is_array($paginationResult["items"]) ? $paginationResult["items"] : array();
$arResult["COUNT"] = isset($paginationResult["count"]) ? (int)$paginationResult["count"] : count($arResult["STOREROOMS"]);
$arResult["PAGINATION"] = isset($paginationResult["pagination"]) && is_array($paginationResult["pagination"]) ? $paginationResult["pagination"] : null;
$arResult["RANGES"] = $rangeResult;

$this->IncludeComponentTemplate();
