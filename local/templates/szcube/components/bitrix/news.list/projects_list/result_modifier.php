<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!function_exists("projectsListRoomBucketKey")) {
    function projectsListRoomBucketKey($value)
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

if (!function_exists("projectsListRoomBuckets")) {
    function projectsListRoomBuckets()
    {
        return array(
            "studio" => array("label" => "Студия", "sort" => 0),
            "1k" => array("label" => "1к", "sort" => 10),
            "2k" => array("label" => "2к", "sort" => 20),
            "2e" => array("label" => "2е", "sort" => 21),
            "3k" => array("label" => "3к", "sort" => 30),
            "3e" => array("label" => "3е", "sort" => 31),
            "4k" => array("label" => "4к", "sort" => 40),
        );
    }
}

if (!function_exists("projectsListPluralizeFlats")) {
    function projectsListPluralizeFlats($count)
    {
        $count = abs((int)$count);
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return "квартира";
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return "квартиры";
        }

        return "квартир";
    }
}

if (!function_exists("projectsListFormatPriceFrom")) {
    function projectsListFormatPriceFrom($value)
    {
        $value = (float)$value;
        if ($value <= 0) {
            return "";
        }

        return "от " . number_format($value, 0, ".", " ") . " р.";
    }
}

if (!function_exists("projectsListIsPubliclyHiddenStatus")) {
    function projectsListIsPubliclyHiddenStatus($statusKey, $statusLabel = "")
    {
        $statusKey = trim(mb_strtolower((string)$statusKey));
        $statusLabel = trim(mb_strtolower((string)$statusLabel));

        if ($statusKey === "sold") {
            return true;
        }

        return $statusLabel !== "" && preg_match("/^продан[а-я]*$/u", $statusLabel) === 1;
    }
}

if (!Loader::includeModule("iblock") || empty($arResult["ITEMS"]) || !is_array($arResult["ITEMS"])) {
    return;
}

$apartmentsIblockId = function_exists("szcubeGetIblockIdByCode") ? (int)szcubeGetIblockIdByCode("apartments") : 0;
if ($apartmentsIblockId <= 0) {
    return;
}

$projectIds = array();
foreach ($arResult["ITEMS"] as $item) {
    $projectId = isset($item["ID"]) ? (int)$item["ID"] : 0;
    if ($projectId > 0) {
        $projectIds[] = $projectId;
    }
}
$projectIds = array_values(array_unique($projectIds));

if (empty($projectIds)) {
    return;
}

$roomBuckets = projectsListRoomBuckets();
$projectSaleMeta = array();
foreach ($projectIds as $projectId) {
    $projectSaleMeta[$projectId] = array(
        "count" => 0,
        "min_price" => 0.0,
        "rooms" => array(),
    );
}

$flatRes = CIBlockElement::GetList(
    array("ID" => "ASC"),
    array(
        "IBLOCK_ID" => $apartmentsIblockId,
        "ACTIVE" => "Y",
        "PROPERTY_PROJECT" => $projectIds,
    ),
    false,
    false,
    array("ID", "IBLOCK_ID")
);

while ($flatElement = $flatRes->GetNextElement()) {
    $flatProperties = $flatElement->GetProperties();

    $projectId = isset($flatProperties["PROJECT"]["VALUE"]) ? (int)$flatProperties["PROJECT"]["VALUE"] : 0;
    if ($projectId <= 0 || !isset($projectSaleMeta[$projectId])) {
        continue;
    }

    $statusKey = isset($flatProperties["STATUS"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["STATUS"]["VALUE_XML_ID"]) : "";
    $statusLabel = isset($flatProperties["STATUS"]["VALUE"]) ? trim((string)$flatProperties["STATUS"]["VALUE"]) : "";
    if (projectsListIsPubliclyHiddenStatus($statusKey, $statusLabel)) {
        continue;
    }

    $projectSaleMeta[$projectId]["count"]++;

    $priceTotal = isset($flatProperties["PRICE_TOTAL"]["VALUE"]) ? (float)$flatProperties["PRICE_TOTAL"]["VALUE"] : 0.0;
    if ($priceTotal > 0 && ($projectSaleMeta[$projectId]["min_price"] <= 0 || $priceTotal < $projectSaleMeta[$projectId]["min_price"])) {
        $projectSaleMeta[$projectId]["min_price"] = $priceTotal;
    }

    $roomBucket = isset($flatProperties["ROOMS"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["ROOMS"]["VALUE_XML_ID"]) : "";
    $roomLabel = isset($flatProperties["ROOMS"]["VALUE"]) ? trim((string)$flatProperties["ROOMS"]["VALUE"]) : "";
    if ($roomBucket === "") {
        $roomBucket = projectsListRoomBucketKey($roomLabel);
    }
    if ($roomBucket !== "" && isset($roomBuckets[$roomBucket])) {
        $projectSaleMeta[$projectId]["rooms"][$roomBucket] = $roomBuckets[$roomBucket];
    }
}

foreach ($arResult["ITEMS"] as $index => $item) {
    $projectId = isset($item["ID"]) ? (int)$item["ID"] : 0;
    if ($projectId <= 0 || !isset($projectSaleMeta[$projectId])) {
        continue;
    }

    $meta = $projectSaleMeta[$projectId];
    if ((int)$meta["count"] <= 0) {
        continue;
    }

    $rooms = array_values($meta["rooms"]);
    usort($rooms, static function ($left, $right) {
        return ((int)$left["sort"]) <=> ((int)$right["sort"]);
    });

    $arResult["ITEMS"][$index]["AUTO_PROJECT_CARD"] = array(
        "SALE_ROOMS" => array_values(array_map(static function ($room) {
            return isset($room["label"]) ? (string)$room["label"] : "";
        }, $rooms)),
        "SALE_COUNT_TEXT" => (int)$meta["count"] . " " . projectsListPluralizeFlats((int)$meta["count"]),
        "PRICE_FROM_TEXT" => projectsListFormatPriceFrom((float)$meta["min_price"]),
    );
}
