<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!function_exists("szcubeApartmentFilterFindIblockByCode")) {
    function szcubeApartmentFilterFindIblockByCode($code)
    {
        $res = CIBlock::GetList(array(), array("=CODE" => (string)$code, "ACTIVE" => "Y"), false);
        return $res->Fetch() ?: null;
    }
}

if (!function_exists("szcubeApartmentFilterElementUrl")) {
    function szcubeApartmentFilterElementUrl($template, array $fields, $fallbackPrefix)
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

if (!function_exists("szcubeApartmentFilterFilePath")) {
    function szcubeApartmentFilterFilePath($value)
    {
        $fileId = (int)$value;
        if ($fileId <= 0) {
            return "";
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : "";
    }
}

if (!function_exists("szcubeApartmentFilterRoomBucketKey")) {
    function szcubeApartmentFilterRoomBucketKey($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        if (preg_match("/ст|stud|studio/iu", $value)) {
            return "studio";
        }

        if (preg_match("/евро\\s*дв|евродв|\\b2\\s*[еe]\\b|\\b2e\\b/iu", $value)) {
            return "2e";
        }

        if (preg_match("/евро\\s*тр|евротр|\\b3\\s*[еe]\\b|\\b3e\\b/iu", $value)) {
            return "3e";
        }

        if (preg_match("/\\b1\\s*(?:[- ]?ком|[кk])\\b|\\b1k\\b/iu", $value)) {
            return "1k";
        }

        if (preg_match("/\\b2\\s*(?:[- ]?ком|[кk])\\b|\\b2k\\b/iu", $value)) {
            return "2k";
        }

        if (preg_match("/\\b3\\s*(?:[- ]?ком|[кk])\\b|\\b3k\\b/iu", $value)) {
            return "3k";
        }

        if (preg_match("/\\b4\\s*(?:[- ]?ком|[кk])\\b|\\b4k\\b/iu", $value)) {
            return "4k";
        }

        if (preg_match("/(\d+)/", $value, $matches)) {
            $number = (int)$matches[1];
            if ($number >= 4) {
                return "4k";
            }

            return $number . "k";
        }

        return "";
    }
}

if (!function_exists("szcubeApartmentFilterRoomFullLabel")) {
    function szcubeApartmentFilterRoomFullLabel($value)
    {
        $map = array(
            "studio" => "Студия",
            "1k" => "1-комнатная",
            "2k" => "2-комнатная",
            "2e" => "Евродвушка",
            "3k" => "3-комнатная",
            "3e" => "Евротрешка",
            "4k" => "4-комнатная",
        );

        $key = szcubeApartmentFilterRoomBucketKey($value);
        if ($key !== "" && isset($map[$key])) {
            return $map[$key];
        }

        return trim((string)$value);
    }
}

if (!function_exists("szcubeApartmentFilterRoomBuckets")) {
    function szcubeApartmentFilterRoomBuckets()
    {
        return array(
            "studio" => array("key" => "studio", "label" => "Студия", "sort" => 0, "count" => 0),
            "1k" => array("key" => "1k", "label" => "1к", "sort" => 10, "count" => 0),
            "2k" => array("key" => "2k", "label" => "2к", "sort" => 20, "count" => 0),
            "2e" => array("key" => "2e", "label" => "2е", "sort" => 21, "count" => 0),
            "3k" => array("key" => "3k", "label" => "3к", "sort" => 30, "count" => 0),
            "3e" => array("key" => "3e", "label" => "3е", "sort" => 31, "count" => 0),
            "4k" => array("key" => "4k", "label" => "4к", "sort" => 40, "count" => 0),
        );
    }
}

if (!function_exists("szcubeApartmentFilterNormalizeKey")) {
    function szcubeApartmentFilterNormalizeKey($value)
    {
        $value = trim((string)$value);
        $value = mb_strtolower($value);
        $value = preg_replace("/[^a-z0-9а-яё_-]+/iu", "-", $value);
        $value = preg_replace("/-+/u", "-", $value);
        return trim((string)$value, "-");
    }
}

if (!function_exists("szcubeApartmentFilterRangeUpdate")) {
    function szcubeApartmentFilterRangeUpdate(array &$range, $value)
    {
        $value = (float)$value;
        if ($value <= 0) {
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

if (!function_exists("szcubeApartmentFilterRangeFinalize")) {
    function szcubeApartmentFilterRangeFinalize(array $range, $fallbackMin, $fallbackMax)
    {
        $actualMin = $range["min"] !== null ? (float)$range["min"] : (float)$fallbackMin;
        $actualMax = $range["max"] !== null ? (float)$range["max"] : (float)$fallbackMax;
        if ($actualMax < $actualMin) {
            $actualMax = $actualMin;
        }

        $step = isset($range["step"]) ? (float)$range["step"] : 1;
        $precision = isset($range["precision"]) ? (int)$range["precision"] : 0;
        $renderMin = $actualMin;
        $renderMax = $actualMax;

        if ($precision >= 0) {
            $actualMin = round($actualMin, $precision);
            $actualMax = round($actualMax, $precision);
            $renderMin = round($renderMin, $precision);
            $renderMax = round($renderMax, $precision);
        }

        return array(
            "actual_min" => $actualMin,
            "actual_max" => $actualMax,
            "render_min" => $renderMin,
            "render_max" => $renderMax,
            "step" => $step,
            "precision" => $precision,
        );
    }
}

if (!function_exists("szcubeApartmentFilterMultiPropertyValues")) {
    function szcubeApartmentFilterMultiPropertyValues(array $property)
    {
        $value = isset($property["VALUE"]) ? $property["VALUE"] : array();
        if (!is_array($value)) {
            $value = array($value);
        }

        $result = array();
        foreach ($value as $item) {
            $item = trim((string)$item);
            if ($item !== "") {
                $result[] = $item;
            }
        }

        return array_values(array_unique($result));
    }
}

if (!function_exists("szcubeApartmentFilterDiscountBadge")) {
    function szcubeApartmentFilterDiscountBadge($priceTotal, $priceOld)
    {
        $priceTotal = (float)$priceTotal;
        $priceOld = (float)$priceOld;
        if ($priceOld <= 0 || $priceTotal <= 0 || $priceOld <= $priceTotal) {
            return "";
        }

        $discountPercent = (int)round((($priceOld - $priceTotal) / $priceOld) * 100);
        if ($discountPercent <= 0) {
            return "";
        }

        return "Скидка " . $discountPercent . "%";
    }
}

if (!function_exists("szcubeApartmentFilterIsPubliclyHiddenStatus")) {
    function szcubeApartmentFilterIsPubliclyHiddenStatus($statusKey, $statusLabel = "")
    {
        $statusKey = trim(mb_strtolower((string)$statusKey));
        $statusLabel = trim(mb_strtolower((string)$statusLabel));

        if ($statusKey === "sold") {
            return true;
        }

        return $statusLabel !== "" && preg_match("/^продан[а-я]*$/u", $statusLabel) === 1;
    }
}

if (!function_exists("szcubeApartmentFilterNormalizeUpperFloor")) {
    function szcubeApartmentFilterNormalizeUpperFloor($floor, $floorTo)
    {
        $floor = (int)$floor;
        $floorTo = (int)$floorTo;
        return $floorTo > $floor ? $floorTo : 0;
    }
}

if (!function_exists("szcubeApartmentFilterIsDuplex")) {
    function szcubeApartmentFilterIsDuplex($floor, $floorTo)
    {
        return szcubeApartmentFilterNormalizeUpperFloor($floor, $floorTo) > 0;
    }
}

if (!function_exists("szcubeApartmentFilterFloorMax")) {
    function szcubeApartmentFilterFloorMax($floor, $floorTo)
    {
        $floor = (int)$floor;
        $normalizedFloorTo = szcubeApartmentFilterNormalizeUpperFloor($floor, $floorTo);
        return $normalizedFloorTo > 0 ? $normalizedFloorTo : $floor;
    }
}

if (!function_exists("szcubeApartmentFilterNormalizeHouseFloors")) {
    function szcubeApartmentFilterNormalizeHouseFloors($floor, $floorTo, $houseFloors)
    {
        $houseFloors = (int)$houseFloors;
        $floorMax = szcubeApartmentFilterFloorMax($floor, $floorTo);

        return max($houseFloors, $floorMax);
    }
}

if (!function_exists("szcubeApartmentFilterFloorLabel")) {
    function szcubeApartmentFilterFloorLabel($floor, $floorTo, $houseFloors, $compact = false)
    {
        $floor = (int)$floor;
        $houseFloors = szcubeApartmentFilterNormalizeHouseFloors($floor, $floorTo, $houseFloors);
        $normalizedFloorTo = szcubeApartmentFilterNormalizeUpperFloor($floor, $floorTo);
        if ($normalizedFloorTo > 0) {
            return $floor > 0 ? ($floor . "-" . $normalizedFloorTo . " этаж") : "";
        }

        if ($floor <= 0) {
            return $houseFloors > 0 ? ($houseFloors . " этаж") : "";
        }

        if ($compact && $houseFloors > 0) {
            return $floor . "/" . $houseFloors . " этаж";
        }

        return $houseFloors > 0 ? ($floor . " этаж из " . $houseFloors) : ($floor . " этаж");
    }
}

if (!function_exists("szcubeApartmentFilterBuildBadges")) {
    function szcubeApartmentFilterBuildBadges(array $manualBadges, $priceTotal, $priceOld, $floor = 0, $floorTo = 0)
    {
        $badges = array();
        foreach ($manualBadges as $badge) {
            $badge = trim((string)$badge);
            if ($badge !== "") {
                $badges[] = $badge;
            }
        }

        if (szcubeApartmentFilterIsDuplex($floor, $floorTo) && !in_array("Двухуровневая", $badges, true)) {
            $badges[] = "Двухуровневая";
        }

        $discountBadge = szcubeApartmentFilterDiscountBadge($priceTotal, $priceOld);
        if ($discountBadge !== "" && !in_array($discountBadge, $badges, true)) {
            $badges[] = $discountBadge;
        }

        return array_values(array_unique($badges));
    }
}

$cacheTime = isset($arParams["CACHE_TIME"]) ? (int)$arParams["CACHE_TIME"] : 36000000;
$projectsPageUrl = isset($arParams["PROJECTS_PAGE_URL"]) && trim((string)$arParams["PROJECTS_PAGE_URL"]) !== ""
    ? trim((string)$arParams["PROJECTS_PAGE_URL"])
    : "/projects/";
$catalogPageUrl = isset($arParams["CATALOG_PAGE_URL"]) && trim((string)$arParams["CATALOG_PAGE_URL"]) !== ""
    ? trim((string)$arParams["CATALOG_PAGE_URL"])
    : "/apartments/";

if ($this->StartResultCache(false, array($projectsPageUrl, $catalogPageUrl))) {
    if (!Loader::includeModule("iblock")) {
        $this->AbortResultCache();
        return;
    }

    $apartmentsIblock = szcubeApartmentFilterFindIblockByCode("apartments");
    $projectsIblock = szcubeApartmentFilterFindIblockByCode("projects");
    if (!is_array($apartmentsIblock) || !is_array($projectsIblock)) {
        $this->AbortResultCache();
        return;
    }

    $apartmentsIblockId = (int)$apartmentsIblock["ID"];
    $projectsIblockId = (int)$projectsIblock["ID"];
    $apartmentsDetailTemplate = isset($apartmentsIblock["DETAIL_PAGE_URL"]) ? (string)$apartmentsIblock["DETAIL_PAGE_URL"] : "";
    $projectsDetailTemplate = isset($projectsIblock["DETAIL_PAGE_URL"]) ? (string)$projectsIblock["DETAIL_PAGE_URL"] : "";

    $projectMap = array();
    $projectRes = CIBlockElement::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array(
            "IBLOCK_ID" => $projectsIblockId,
            "ACTIVE" => "Y",
        ),
        false,
        false,
        array("ID", "IBLOCK_ID", "NAME", "CODE", "DETAIL_PAGE_URL", "SORT")
    );

    while ($projectElement = $projectRes->GetNextElement()) {
        $project = $projectElement->GetFields();
        $projectProperties = $projectElement->GetProperties();
        $project["DETAIL_PAGE_URL"] = szcubeApartmentFilterElementUrl($projectsDetailTemplate, $project, $projectsPageUrl);
        $project["DELIVERY_TEXT"] = isset($projectProperties["DELIVERY_TEXT"]["VALUE"]) ? trim((string)$projectProperties["DELIVERY_TEXT"]["VALUE"]) : "";
        $projectMap[(int)$project["ID"]] = $project;
    }

    $projects = array();
    $rooms = szcubeApartmentFilterRoomBuckets();
    $statuses = array();
    $finishes = array();
    $featureTags = array();
    $flats = array();
    $ranges = array(
        "price" => array("min" => null, "max" => null, "step" => 1000, "precision" => 0),
        "area" => array("min" => null, "max" => null, "step" => 0.01, "precision" => 2),
        "floor" => array("min" => null, "max" => null, "step" => 1, "precision" => 0),
        "ceiling" => array("min" => null, "max" => null, "step" => 0.01, "precision" => 2),
    );

    $flatRes = CIBlockElement::GetList(
        array("ID" => "ASC"),
        array(
            "IBLOCK_ID" => $apartmentsIblockId,
            "ACTIVE" => "Y",
        ),
        false,
        false,
        array("ID", "IBLOCK_ID", "NAME", "CODE", "XML_ID", "DETAIL_PAGE_URL")
    );

    while ($flatElement = $flatRes->GetNextElement()) {
        $flatFields = $flatElement->GetFields();
        $flatProperties = $flatElement->GetProperties();

        $projectId = isset($flatProperties["PROJECT"]["VALUE"]) ? (int)$flatProperties["PROJECT"]["VALUE"] : 0;
        if ($projectId <= 0 || !isset($projectMap[$projectId])) {
            continue;
        }

        $project = $projectMap[$projectId];
        $projectCode = trim((string)$project["CODE"]);
        if ($projectCode === "") {
            continue;
        }

        $flatUrl = trim((string)$flatFields["DETAIL_PAGE_URL"]);
        if ($flatUrl === "") {
            $flatUrl = szcubeApartmentFilterElementUrl($apartmentsDetailTemplate, $flatFields, $catalogPageUrl);
        }

        $statusKey = isset($flatProperties["STATUS"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["STATUS"]["VALUE_XML_ID"]) : "";
        $statusLabel = isset($flatProperties["STATUS"]["VALUE"]) ? trim((string)$flatProperties["STATUS"]["VALUE"]) : "";
        if (szcubeApartmentFilterIsPubliclyHiddenStatus($statusKey, $statusLabel)) {
            continue;
        }

        $roomsLabel = isset($flatProperties["ROOMS"]["VALUE"]) ? trim((string)$flatProperties["ROOMS"]["VALUE"]) : "";
        $roomBucket = isset($flatProperties["ROOMS"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["ROOMS"]["VALUE_XML_ID"]) : "";
        if ($roomBucket === "") {
            $roomBucket = szcubeApartmentFilterRoomBucketKey($roomsLabel);
        }
        if ($roomsLabel === "" && $roomBucket !== "") {
            $roomsLabel = szcubeApartmentFilterRoomFullLabel($roomBucket);
        }
        if ($roomBucket !== "" && isset($rooms[$roomBucket])) {
            $rooms[$roomBucket]["count"]++;
        }

        $priceTotal = isset($flatProperties["PRICE_TOTAL"]["VALUE"]) ? (float)$flatProperties["PRICE_TOTAL"]["VALUE"] : 0.0;
        $floor = isset($flatProperties["FLOOR"]["VALUE"]) ? (int)$flatProperties["FLOOR"]["VALUE"] : 0;
        $floorTo = isset($flatProperties["FLOOR_TO"]["VALUE"]) ? (int)$flatProperties["FLOOR_TO"]["VALUE"] : 0;
        $floorMax = szcubeApartmentFilterFloorMax($floor, $floorTo);
        $areaTotal = isset($flatProperties["AREA_TOTAL"]["VALUE"]) ? (float)$flatProperties["AREA_TOTAL"]["VALUE"] : 0.0;
        $ceiling = isset($flatProperties["CEILING"]["VALUE"]) ? (float)$flatProperties["CEILING"]["VALUE"] : 0.0;
        $finishLabel = isset($flatProperties["FINISH"]["VALUE"]) ? trim((string)$flatProperties["FINISH"]["VALUE"]) : "";
        $finishKey = isset($flatProperties["FINISH"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["FINISH"]["VALUE_XML_ID"]) : "";
        $flatFeatureTags = szcubeApartmentFilterMultiPropertyValues(isset($flatProperties["FEATURE_TAGS"]) && is_array($flatProperties["FEATURE_TAGS"]) ? $flatProperties["FEATURE_TAGS"] : array());
        $priceOld = isset($flatProperties["PRICE_OLD"]["VALUE"]) ? (float)$flatProperties["PRICE_OLD"]["VALUE"] : 0.0;
        $houseFloors = szcubeApartmentFilterNormalizeHouseFloors(
            $floor,
            $floorTo,
            isset($flatProperties["HOUSE_FLOORS"]["VALUE"]) ? (int)$flatProperties["HOUSE_FLOORS"]["VALUE"] : 0
        );
        $planImage = szcubeApartmentFilterFilePath(isset($flatProperties["PLAN_IMAGE"]["VALUE"]) ? $flatProperties["PLAN_IMAGE"]["VALUE"] : 0);
        $planAlt = isset($flatProperties["PLAN_ALT"]["VALUE"]) ? trim((string)$flatProperties["PLAN_ALT"]["VALUE"]) : "";
        $manualBadges = szcubeApartmentFilterMultiPropertyValues(isset($flatProperties["BADGES"]) && is_array($flatProperties["BADGES"]) ? $flatProperties["BADGES"] : array());
        $badges = szcubeApartmentFilterBuildBadges($manualBadges, $priceTotal, $priceOld, $floor, $floorTo);
        $floorDisplay = szcubeApartmentFilterFloorLabel($floor, $floorTo, $houseFloors, false);
        $floorShort = szcubeApartmentFilterFloorLabel($floor, $floorTo, $houseFloors, true);

        if (!isset($projects[$projectCode])) {
            $projects[$projectCode] = array(
                "id" => (int)$project["ID"],
                "code" => $projectCode,
                "name" => trim((string)$project["NAME"]),
                "url" => trim((string)$project["DETAIL_PAGE_URL"]),
                "filter_url" => "/projects/detail.php?code=" . rawurlencode($projectCode),
                "delivery" => isset($project["DELIVERY_TEXT"]) ? trim((string)$project["DELIVERY_TEXT"]) : "",
                "count" => 0,
                "sort" => isset($project["SORT"]) ? (int)$project["SORT"] : 500,
            );
        }
        $projects[$projectCode]["count"]++;

        if ($statusKey !== "" && $statusLabel !== "") {
            if (!isset($statuses[$statusKey])) {
                $statuses[$statusKey] = array(
                    "key" => $statusKey,
                    "label" => $statusLabel,
                    "count" => 0,
                );
            }
            $statuses[$statusKey]["count"]++;
        }

        if ($finishKey !== "") {
            if (!isset($finishes[$finishKey])) {
                $finishes[$finishKey] = array(
                    "key" => $finishKey,
                    "label" => $finishLabel,
                    "count" => 0,
                );
            }
            $finishes[$finishKey]["count"]++;
        }

        $featureTagKeys = array();
        foreach ($flatFeatureTags as $tagLabel) {
            $tagKey = szcubeApartmentFilterNormalizeKey($tagLabel);
            if ($tagKey === "") {
                continue;
            }

            $featureTagKeys[] = $tagKey;
            if (!isset($featureTags[$tagKey])) {
                $featureTags[$tagKey] = array(
                    "key" => $tagKey,
                    "label" => $tagLabel,
                    "count" => 0,
                );
            }
            $featureTags[$tagKey]["count"]++;
        }

        szcubeApartmentFilterRangeUpdate($ranges["price"], $priceTotal);
        szcubeApartmentFilterRangeUpdate($ranges["area"], $areaTotal);
        szcubeApartmentFilterRangeUpdate($ranges["floor"], $floor);
        szcubeApartmentFilterRangeUpdate($ranges["floor"], $floorMax);
        szcubeApartmentFilterRangeUpdate($ranges["ceiling"], $ceiling);

        $flats[] = array(
            "id" => (int)$flatFields["ID"],
            "code" => trim((string)$flatFields["CODE"]),
            "xml_id" => trim((string)$flatFields["XML_ID"]),
            "url" => $flatUrl,
            "project_code" => $projectCode,
            "project_name" => trim((string)$project["NAME"]),
            "project_url" => trim((string)$project["DETAIL_PAGE_URL"]),
            "project_filter_url" => "/projects/detail.php?code=" . rawurlencode($projectCode),
            "project_delivery" => isset($project["DELIVERY_TEXT"]) ? trim((string)$project["DELIVERY_TEXT"]) : "",
            "rooms_label" => $roomsLabel,
            "rooms_bucket" => $roomBucket,
            "price_total" => $priceTotal,
            "price_old" => $priceOld,
            "floor" => $floor,
            "floor_to" => szcubeApartmentFilterNormalizeUpperFloor($floor, $floorTo),
            "floor_max" => $floorMax,
            "floor_display" => $floorDisplay,
            "floor_short" => $floorShort,
            "house_floors" => $houseFloors,
            "area_total" => $areaTotal,
            "ceiling" => $ceiling,
            "status" => $statusKey,
            "status_label" => $statusLabel,
            "finish" => $finishKey,
            "finish_label" => $finishLabel,
            "badges" => $badges,
            "plan_image" => $planImage,
            "plan_alt" => $planAlt !== "" ? $planAlt : trim((string)$flatFields["NAME"]),
            "feature_tags" => array_values(array_unique($featureTagKeys)),
        );
    }

    uasort($projects, static function ($left, $right) {
        $sortDiff = ((int)$left["sort"] <=> (int)$right["sort"]);
        if ($sortDiff !== 0) {
            return $sortDiff;
        }

        return mb_strtolower((string)$left["name"]) <=> mb_strtolower((string)$right["name"]);
    });

    uasort($finishes, static function ($left, $right) {
        return mb_strtolower((string)$left["label"]) <=> mb_strtolower((string)$right["label"]);
    });

    uasort($featureTags, static function ($left, $right) {
        return mb_strtolower((string)$left["label"]) <=> mb_strtolower((string)$right["label"]);
    });

    $arResult = array(
        "PROJECTS" => array_values($projects),
        "ROOMS" => array_values($rooms),
        "STATUSES" => array_values($statuses),
        "FINISHES" => array_values($finishes),
        "FEATURE_TAGS" => array_values($featureTags),
        "RANGES" => array(
            "price" => szcubeApartmentFilterRangeFinalize($ranges["price"], 0, 1000000),
            "area" => szcubeApartmentFilterRangeFinalize($ranges["area"], 0, 100),
            "floor" => szcubeApartmentFilterRangeFinalize($ranges["floor"], 1, 10),
            "ceiling" => szcubeApartmentFilterRangeFinalize($ranges["ceiling"], 0, 3),
        ),
        "FLATS" => $flats,
        "COUNT" => count($flats),
        "PROJECTS_PAGE_URL" => $projectsPageUrl,
        "CATALOG_PAGE_URL" => $catalogPageUrl,
    );

    $this->IncludeComponentTemplate();
}
