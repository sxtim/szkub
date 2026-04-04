<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!function_exists("szcubeCommercialFilterFindIblockByCode")) {
    function szcubeCommercialFilterFindIblockByCode($code)
    {
        $res = CIBlock::GetList(array(), array("=CODE" => (string)$code, "ACTIVE" => "Y"), false);
        return $res->Fetch() ?: null;
    }
}

if (!function_exists("szcubeCommercialFilterElementUrl")) {
    function szcubeCommercialFilterElementUrl($template, array $fields, $fallbackPrefix)
    {
        $template = trim((string)$template);
        if ($template !== "") {
            $url = (string)CIBlock::ReplaceDetailUrl($template, $fields, false, "E");
            if ($url !== "") {
                return $url;
            }
        }

        $code = isset($fields["CODE"]) ? trim((string)$fields["CODE"]) : "";
        if ($code === "") {
            return "";
        }

        return rtrim((string)$fallbackPrefix, "/") . "/" . $code . "/";
    }
}

if (!function_exists("szcubeCommercialFilterNormalizeKey")) {
    function szcubeCommercialFilterNormalizeKey($value)
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

if (!function_exists("szcubeCommercialFilterPropertySingleValue")) {
    function szcubeCommercialFilterPropertySingleValue(array $property)
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

if (!function_exists("szcubeCommercialFilterPropertySingleKey")) {
    function szcubeCommercialFilterPropertySingleKey(array $property)
    {
        if (isset($property["VALUE_XML_ID"]) && trim((string)$property["VALUE_XML_ID"]) !== "") {
            return szcubeCommercialFilterNormalizeKey($property["VALUE_XML_ID"]);
        }

        return szcubeCommercialFilterNormalizeKey(szcubeCommercialFilterPropertySingleValue($property));
    }
}

if (!function_exists("szcubeCommercialFilterPropertyMultipleValues")) {
    function szcubeCommercialFilterPropertyMultipleValues(array $property)
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

if (!function_exists("szcubeCommercialFilterFilePath")) {
    function szcubeCommercialFilterFilePath($value)
    {
        $fileId = (int)$value;
        if ($fileId <= 0) {
            return "";
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : "";
    }
}

if (!function_exists("szcubeCommercialFilterRangeUpdate")) {
    function szcubeCommercialFilterRangeUpdate(array &$range, $value)
    {
        $value = (float)$value;
        if ($value <= 0 || !is_finite($value)) {
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

if (!function_exists("szcubeCommercialFilterRangeFinalize")) {
    function szcubeCommercialFilterRangeFinalize(array $range, $fallbackMin, $fallbackMax)
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

if (!function_exists("szcubeCommercialFilterOptionAppend")) {
    function szcubeCommercialFilterOptionAppend(array &$options, $key, $label)
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

if (!Loader::includeModule("iblock")) {
    ShowError("Не удалось подключить модуль iblock");
    return;
}

$commercialIblock = szcubeCommercialFilterFindIblockByCode("commercial");
$projectsIblock = szcubeCommercialFilterFindIblockByCode("projects");

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

    $typeLabel = szcubeCommercialFilterPropertySingleValue(isset($properties["COMMERCIAL_TYPE"]) ? $properties["COMMERCIAL_TYPE"] : array());
    $typeKey = szcubeCommercialFilterPropertySingleKey(isset($properties["COMMERCIAL_TYPE"]) ? $properties["COMMERCIAL_TYPE"] : array());
    $statusLabel = szcubeCommercialFilterPropertySingleValue(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $statusKey = szcubeCommercialFilterPropertySingleKey(isset($properties["STATUS"]) ? $properties["STATUS"] : array());
    $badges = szcubeCommercialFilterPropertyMultipleValues(isset($properties["BADGES"]) ? $properties["BADGES"] : array());
    $featureTags = szcubeCommercialFilterPropertyMultipleValues(isset($properties["FEATURE_TAGS"]) ? $properties["FEATURE_TAGS"] : array());

    $areaTotal = isset($properties["AREA_TOTAL"]["VALUE"]) ? (float)$properties["AREA_TOTAL"]["VALUE"] : 0;
    $priceTotal = isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0;
    $priceOld = isset($properties["PRICE_OLD"]["VALUE"]) ? (float)$properties["PRICE_OLD"]["VALUE"] : 0;
    $floor = isset($properties["FLOOR"]["VALUE"]) ? (int)$properties["FLOOR"]["VALUE"] : 0;
    $houseFloors = isset($properties["HOUSE_FLOORS"]["VALUE"]) ? (int)$properties["HOUSE_FLOORS"]["VALUE"] : 0;
    $ceiling = isset($properties["CEILING"]["VALUE"]) ? (float)$properties["CEILING"]["VALUE"] : 0;
    $planImage = szcubeCommercialFilterFilePath(isset($properties["PLAN_IMAGE"]["VALUE"]) ? $properties["PLAN_IMAGE"]["VALUE"] : 0);
    if ($planImage === "" && (int)$fields["PREVIEW_PICTURE"] > 0) {
        $planImage = (string)CFile::GetPath((int)$fields["PREVIEW_PICTURE"]);
    }

    szcubeCommercialFilterOptionAppend($projectOptions, $project["code"], $project["name"]);
    szcubeCommercialFilterOptionAppend($typeOptions, $typeKey, $typeLabel);
    szcubeCommercialFilterOptionAppend($statusOptions, $statusKey, $statusLabel);
    foreach ($featureTags as $tag) {
        szcubeCommercialFilterOptionAppend($featureOptions, szcubeCommercialFilterNormalizeKey($tag), $tag);
    }
    szcubeCommercialFilterRangeUpdate($ranges["price"], $priceTotal);
    szcubeCommercialFilterRangeUpdate($ranges["area"], $areaTotal);
    szcubeCommercialFilterRangeUpdate($ranges["floor"], $floor);

    $detailUrl = trim((string)$fields["DETAIL_PAGE_URL"]);
    $detailTemplate = isset($commercialIblock["DETAIL_PAGE_URL"]) ? (string)$commercialIblock["DETAIL_PAGE_URL"] : "";
    if ($detailUrl === "" || strpos($detailUrl, "#") !== false) {
        $detailUrl = szcubeCommercialFilterElementUrl($detailTemplate, $fields, "/commerce");
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
        "feature_tags" => array_map("szcubeCommercialFilterNormalizeKey", $featureTags),
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
$arResult["RANGES"] = array(
    "price" => szcubeCommercialFilterRangeFinalize($ranges["price"], 0, 0),
    "area" => szcubeCommercialFilterRangeFinalize($ranges["area"], 0, 0),
    "floor" => szcubeCommercialFilterRangeFinalize($ranges["floor"], 1, 1),
);
$arResult["COUNT"] = count($arResult["COMMERCIALS"]);

$this->IncludeComponentTemplate();
