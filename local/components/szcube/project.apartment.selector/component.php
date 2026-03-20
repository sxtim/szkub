<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!function_exists("szcubeProjectSelectorMoney")) {
    function szcubeProjectSelectorMoney($value)
    {
        $value = (float)$value;
        if ($value <= 0) {
            return "";
        }

        if ($value >= 1000000) {
            $millions = floor($value / 100000) / 10;
            $formatted = number_format($millions, 1, ".", "");
            $formatted = preg_replace("/\\.0$/", "", $formatted);

            return $formatted . " млн";
        }

        return number_format($value, 0, ".", " ");
    }
}

if (!function_exists("szcubeProjectSelectorFilePath")) {
    function szcubeProjectSelectorFilePath($value)
    {
        $fileId = (int)$value;
        if ($fileId <= 0) {
            return "";
        }

        $path = CFile::GetPath($fileId);
        return $path ? (string)$path : "";
    }
}

if (!function_exists("szcubeProjectSelectorReadSvg")) {
    function szcubeProjectSelectorReadSvg($path)
    {
        $path = trim((string)$path);
        if ($path === "") {
            return "";
        }

        $absolutePath = $_SERVER["DOCUMENT_ROOT"] . $path;
        if (!is_file($absolutePath)) {
            return "";
        }

        $contents = (string)file_get_contents($absolutePath);
        return trim($contents);
    }
}

if (!function_exists("szcubeProjectSelectorRoomSort")) {
    function szcubeProjectSelectorRoomSort($value)
    {
        $map = array(
            "studio" => 0,
            "1k" => 10,
            "2k" => 20,
            "2e" => 21,
            "3k" => 30,
            "3e" => 31,
            "4k" => 40,
        );

        $key = szcubeProjectSelectorRoomBucketKey($value);
        return isset($map[$key]) ? (int)$map[$key] : 999;
    }
}

if (!function_exists("szcubeProjectSelectorRoomShort")) {
    function szcubeProjectSelectorRoomShort($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        $key = szcubeProjectSelectorRoomBucketKey($value);
        $map = array(
            "studio" => "СТ",
            "1k" => "1К",
            "2k" => "2К",
            "2e" => "2Е",
            "3k" => "3К",
            "3e" => "3Е",
            "4k" => "4К",
        );

        if ($key !== "" && isset($map[$key])) {
            return $map[$key];
        }

        if (preg_match("/ст|stud|studio/iu", $value)) {
            return "СТ";
        }

        return mb_substr($value, 0, 1);
    }
}

if (!function_exists("szcubeProjectSelectorRoomLabel")) {
    function szcubeProjectSelectorRoomLabel($value)
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

        $key = szcubeProjectSelectorRoomBucketKey($value);
        if ($key !== "" && isset($map[$key])) {
            return $map[$key];
        }

        return trim((string)$value);
    }
}

if (!function_exists("szcubeProjectSelectorRoomBucketKey")) {
    function szcubeProjectSelectorRoomBucketKey($value)
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

if (!function_exists("szcubeProjectSelectorFlatSort")) {
    function szcubeProjectSelectorFlatSort(array $flat)
    {
        $number = isset($flat["number"]) ? trim((string)$flat["number"]) : "";
        if ($number !== "" && preg_match("/(\\d+)/", $number, $matches)) {
            return (int)$matches[1];
        }

        return isset($flat["id"]) ? (int)$flat["id"] : 0;
    }
}

if (!function_exists("szcubeProjectSelectorNormalizeKey")) {
    function szcubeProjectSelectorNormalizeKey($value)
    {
        $value = trim((string)$value);
        $value = mb_strtolower($value);
        $value = preg_replace("/[^a-z0-9а-яё_-]+/iu", "-", $value);
        $value = preg_replace("/-+/u", "-", $value);

        return trim((string)$value, "-");
    }
}

if (!function_exists("szcubeProjectSelectorMultiPropertyValues")) {
    function szcubeProjectSelectorMultiPropertyValues(array $property)
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

if (!function_exists("szcubeProjectSelectorDiscountBadge")) {
    function szcubeProjectSelectorDiscountBadge($priceTotal, $priceOld)
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

if (!function_exists("szcubeProjectSelectorNormalizeUpperFloor")) {
    function szcubeProjectSelectorNormalizeUpperFloor($floor, $floorTo)
    {
        $floor = (int)$floor;
        $floorTo = (int)$floorTo;
        return $floorTo > $floor ? $floorTo : 0;
    }
}

if (!function_exists("szcubeProjectSelectorIsDuplex")) {
    function szcubeProjectSelectorIsDuplex($floor, $floorTo)
    {
        return szcubeProjectSelectorNormalizeUpperFloor($floor, $floorTo) > 0;
    }
}

if (!function_exists("szcubeProjectSelectorFloorMax")) {
    function szcubeProjectSelectorFloorMax($floor, $floorTo)
    {
        $floor = (int)$floor;
        $normalizedFloorTo = szcubeProjectSelectorNormalizeUpperFloor($floor, $floorTo);
        return $normalizedFloorTo > 0 ? $normalizedFloorTo : $floor;
    }
}

if (!function_exists("szcubeProjectSelectorNormalizeHouseFloors")) {
    function szcubeProjectSelectorNormalizeHouseFloors($floor, $floorTo, $houseFloors)
    {
        $houseFloors = (int)$houseFloors;
        $floorMax = szcubeProjectSelectorFloorMax($floor, $floorTo);

        return max($houseFloors, $floorMax);
    }
}

if (!function_exists("szcubeProjectSelectorFloorLabel")) {
    function szcubeProjectSelectorFloorLabel($floor, $floorTo, $houseFloors, $compact = false)
    {
        $floor = (int)$floor;
        $houseFloors = szcubeProjectSelectorNormalizeHouseFloors($floor, $floorTo, $houseFloors);
        $normalizedFloorTo = szcubeProjectSelectorNormalizeUpperFloor($floor, $floorTo);
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

if (!function_exists("szcubeProjectSelectorBuildSlotId")) {
    function szcubeProjectSelectorBuildSlotId($rowLabel, $columnNumber)
    {
        $rowLabel = trim((string)$rowLabel);
        if (preg_match("/^(\\d+)\\s*-\\s*(\\d+)$/", $rowLabel, $matches)) {
            $rowLabel = ((int)$matches[1]) . "-" . ((int)$matches[2]);
        } elseif (preg_match("/^\\d+$/", $rowLabel)) {
            $rowLabel = (string)((int)$rowLabel);
        }
        $columnNumber = (int)$columnNumber;
        if ($rowLabel === "" || $columnNumber <= 0) {
            return "";
        }

        return "r" . $rowLabel . "-c" . str_pad((string)$columnNumber, 2, "0", STR_PAD_LEFT);
    }
}

if (!function_exists("szcubeProjectSelectorParseSlotId")) {
    function szcubeProjectSelectorParseSlotId($slotId)
    {
        $slotId = trim((string)$slotId);
        if ($slotId === "") {
            return null;
        }

        if (!preg_match("/^r(\\d+(?:-\\d+)?)-c(\\d+)$/i", $slotId, $matches)) {
            return null;
        }

        $rowLabel = trim((string)$matches[1]);
        if (preg_match("/^(\\d+)\\s*-\\s*(\\d+)$/", $rowLabel, $rowMatches)) {
            $rowLabel = ((int)$rowMatches[1]) . "-" . ((int)$rowMatches[2]);
        } elseif (preg_match("/^\\d+$/", $rowLabel)) {
            $rowLabel = (string)((int)$rowLabel);
        }
        $columnNumber = (int)$matches[2];
        if ($rowLabel === "" || $columnNumber <= 0) {
            return null;
        }

        return array(
            "row_label" => $rowLabel,
            "column" => $columnNumber,
            "slot_id" => szcubeProjectSelectorBuildSlotId($rowLabel, $columnNumber),
        );
    }
}

if (!function_exists("szcubeProjectSelectorBuildRowCells")) {
    function szcubeProjectSelectorBuildRowCells($rowLabel, array $rowFlats, $maxColumns)
    {
        $maxColumns = max(1, (int)$maxColumns);
        $cells = array_fill(0, $maxColumns, null);
        $explicit = array();
        $implicit = array();

        foreach ($rowFlats as $flat) {
            $slotMeta = szcubeProjectSelectorParseSlotId(isset($flat["slot_id"]) ? $flat["slot_id"] : "");
            if (is_array($slotMeta) && (string)$slotMeta["row_label"] === (string)$rowLabel) {
                $columnIndex = (int)$slotMeta["column"] - 1;
                if ($columnIndex >= 0 && $columnIndex < $maxColumns && !isset($explicit[$columnIndex])) {
                    $explicit[$columnIndex] = $flat;
                    continue;
                }
            }

            $implicit[] = $flat;
        }

        foreach ($explicit as $columnIndex => $flat) {
            $cells[$columnIndex] = $flat;
        }

        if (empty($implicit)) {
            return $cells;
        }

        $freeIndexes = array();
        foreach ($cells as $columnIndex => $cell) {
            if ($cell === null) {
                $freeIndexes[] = $columnIndex;
            }
        }

        if (empty($freeIndexes)) {
            return $cells;
        }

        $startOffset = max(0, (int)floor((count($freeIndexes) - count($implicit)) / 2));
        foreach ($implicit as $index => $flat) {
            $freeIndexPosition = $startOffset + $index;
            if (!isset($freeIndexes[$freeIndexPosition])) {
                $freeIndexPosition = $index;
            }
            if (!isset($freeIndexes[$freeIndexPosition])) {
                break;
            }

            $cells[$freeIndexes[$freeIndexPosition]] = $flat;
        }

        return $cells;
    }
}

if (!function_exists("szcubeProjectSelectorBuildCoveredDuplexFloors")) {
    function szcubeProjectSelectorBuildCoveredDuplexFloors(array $duplexRows)
    {
        $coveredFloors = array();

        foreach ($duplexRows as $duplexRow) {
            $label = "";
            if (is_array($duplexRow) && isset($duplexRow["label"])) {
                $label = trim((string)$duplexRow["label"]);
            } elseif (is_string($duplexRow)) {
                $label = trim($duplexRow);
            }

            if (!preg_match("/^(\\d+)\\s*-\\s*(\\d+)$/", $label, $matches)) {
                continue;
            }

            $from = (int)$matches[1];
            $to = (int)$matches[2];
            if ($to < $from) {
                $tmp = $from;
                $from = $to;
                $to = $tmp;
            }

            for ($floor = $from; $floor <= $to; $floor++) {
                $coveredFloors[$floor] = true;
            }
        }

        return $coveredFloors;
    }
}

if (!function_exists("szcubeProjectSelectorVisibleRowCells")) {
    function szcubeProjectSelectorVisibleRowCells(array $cells)
    {
        $firstOccupiedIndex = -1;
        $lastOccupiedIndex = -1;
        foreach ($cells as $index => $cell) {
            if (is_array($cell)) {
                if ($firstOccupiedIndex < 0) {
                    $firstOccupiedIndex = (int)$index;
                }
                $lastOccupiedIndex = (int)$index;
            }
        }

        if ($firstOccupiedIndex < 0 || $lastOccupiedIndex < 0) {
            return array(
                "columns" => 1,
                "cells" => array_slice($cells, 0, 1),
            );
        }

        $length = ($lastOccupiedIndex - $firstOccupiedIndex) + 1;

        return array(
            "columns" => max(1, $length),
            "cells" => array_slice($cells, $firstOccupiedIndex, $length),
        );
    }
}

if (!function_exists("szcubeProjectSelectorBuildBadges")) {
    function szcubeProjectSelectorBuildBadges(array $manualBadges, $priceTotal, $priceOld, $floor = 0, $floorTo = 0)
    {
        $badges = array();
        foreach ($manualBadges as $badge) {
            $badge = trim((string)$badge);
            if ($badge !== "") {
                $badges[] = $badge;
            }
        }

        if (szcubeProjectSelectorIsDuplex($floor, $floorTo) && !in_array("Двухуровневая", $badges, true)) {
            $badges[] = "Двухуровневая";
        }

        $discountBadge = szcubeProjectSelectorDiscountBadge($priceTotal, $priceOld);
        if ($discountBadge !== "" && !in_array($discountBadge, $badges, true)) {
            $badges[] = $discountBadge;
        }

        return array_values(array_unique($badges));
    }
}

if (!function_exists("szcubeProjectSelectorNormalizeFilterState")) {
    function szcubeProjectSelectorNormalizeFilterState($value)
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === "") {
                return array();
            }

            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, "UTF-8");

            $decoded = json_decode($value, true);
            if (!is_array($decoded)) {
                return array();
            }
            $value = $decoded;
        }

        if (!is_array($value)) {
            return array();
        }

        $normalizeValues = static function ($items) {
            if (!is_array($items)) {
                return array();
            }

            $result = array();
            foreach ($items as $item) {
                $item = trim((string)$item);
                if ($item !== "") {
                    $result[] = $item;
                }
            }

            return array_values(array_unique($result));
        };

        $normalizeNumber = static function ($item) {
            if ($item === null || $item === "") {
                return null;
            }

            $number = (float)$item;
            return is_finite($number) ? $number : null;
        };

        return array(
            "projects" => $normalizeValues(isset($value["projects"]) ? $value["projects"] : array()),
            "rooms" => $normalizeValues(isset($value["rooms"]) ? $value["rooms"] : array()),
            "statuses" => $normalizeValues(isset($value["statuses"]) ? $value["statuses"] : array()),
            "finishes" => $normalizeValues(isset($value["finishes"]) ? $value["finishes"] : array()),
            "features" => $normalizeValues(isset($value["features"]) ? $value["features"] : array()),
            "priceFrom" => $normalizeNumber(isset($value["priceFrom"]) ? $value["priceFrom"] : null),
            "priceTo" => $normalizeNumber(isset($value["priceTo"]) ? $value["priceTo"] : null),
            "floorFrom" => $normalizeNumber(isset($value["floorFrom"]) ? $value["floorFrom"] : null),
            "floorTo" => $normalizeNumber(isset($value["floorTo"]) ? $value["floorTo"] : null),
            "areaFrom" => $normalizeNumber(isset($value["areaFrom"]) ? $value["areaFrom"] : null),
            "areaTo" => $normalizeNumber(isset($value["areaTo"]) ? $value["areaTo"] : null),
            "ceilingFrom" => $normalizeNumber(isset($value["ceilingFrom"]) ? $value["ceilingFrom"] : null),
            "ceilingTo" => $normalizeNumber(isset($value["ceilingTo"]) ? $value["ceilingTo"] : null),
        );
    }
}

if (!function_exists("szcubeProjectSelectorFilterHasCriteria")) {
    function szcubeProjectSelectorFilterHasCriteria(array $filter)
    {
        foreach (array("projects", "rooms", "statuses", "finishes", "features") as $key) {
            if (!empty($filter[$key])) {
                return true;
            }
        }

        foreach (array("priceFrom", "priceTo", "floorFrom", "floorTo", "areaFrom", "areaTo", "ceilingFrom", "ceilingTo") as $key) {
            if (isset($filter[$key]) && $filter[$key] !== null) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists("szcubeProjectSelectorFlatMatchesFilter")) {
    function szcubeProjectSelectorFlatMatchesFilter(array $flat, array $filter, array $allowedProjectCodes)
    {
        $allowedProjectCodes = array_values(array_unique(array_filter(array_map("strval", $allowedProjectCodes))));
        if (!empty($filter["projects"])) {
            if (empty(array_intersect($filter["projects"], $allowedProjectCodes))) {
                return false;
            }
        }

        if (!empty($filter["rooms"]) && !in_array((string)$flat["rooms_bucket"], $filter["rooms"], true)) {
            return false;
        }
        if (!empty($filter["statuses"]) && !in_array((string)$flat["status"], $filter["statuses"], true)) {
            return false;
        }
        if (!empty($filter["finishes"]) && !in_array((string)$flat["finish"], $filter["finishes"], true)) {
            return false;
        }
        if (!empty($filter["features"])) {
            $tags = isset($flat["feature_tags"]) && is_array($flat["feature_tags"]) ? $flat["feature_tags"] : array();
            if (empty(array_intersect($filter["features"], $tags))) {
                return false;
            }
        }

        $ranges = array(
            array("flat" => "price_total", "from" => "priceFrom", "to" => "priceTo"),
            array("flat" => "area_total", "from" => "areaFrom", "to" => "areaTo"),
            array("flat" => "ceiling", "from" => "ceilingFrom", "to" => "ceilingTo"),
        );

        foreach ($ranges as $range) {
            $flatValue = isset($flat[$range["flat"]]) ? (float)$flat[$range["flat"]] : 0.0;
            if ($flatValue <= 0) {
                continue;
            }

            $from = isset($filter[$range["from"]]) ? $filter[$range["from"]] : null;
            $to = isset($filter[$range["to"]]) ? $filter[$range["to"]] : null;
            if ($from !== null && $flatValue + 0.0001 < (float)$from) {
                return false;
            }
            if ($to !== null && $flatValue - 0.0001 > (float)$to) {
                return false;
            }
        }

        $floorFrom = isset($filter["floorFrom"]) ? $filter["floorFrom"] : null;
        $floorTo = isset($filter["floorTo"]) ? $filter["floorTo"] : null;
        $flatFloorFrom = isset($flat["floor"]) ? (float)$flat["floor"] : 0.0;
        $flatFloorTo = isset($flat["floor_to"]) && (float)$flat["floor_to"] > $flatFloorFrom
            ? (float)$flat["floor_to"]
            : $flatFloorFrom;
        if ($flatFloorFrom > 0) {
            if ($floorFrom !== null && $flatFloorTo + 0.0001 < (float)$floorFrom) {
                return false;
            }
            if ($floorTo !== null && $flatFloorFrom - 0.0001 > (float)$floorTo) {
                return false;
            }
        }

        return true;
    }
}

$projectId = isset($arParams["PROJECT_ID"]) ? (int)$arParams["PROJECT_ID"] : 0;
$projectCode = isset($arParams["PROJECT_CODE"]) ? trim((string)$arParams["PROJECT_CODE"]) : "";
$dataProjectCode = isset($arParams["DATA_PROJECT_CODE"]) && trim((string)$arParams["DATA_PROJECT_CODE"]) !== ""
    ? trim((string)$arParams["DATA_PROJECT_CODE"])
    : $projectCode;
$projectName = isset($arParams["PROJECT_NAME"]) ? trim((string)$arParams["PROJECT_NAME"]) : "";
$sceneMode = isset($arParams["SCENE_MODE"]) && trim((string)$arParams["SCENE_MODE"]) !== "" ? trim((string)$arParams["SCENE_MODE"]) : "single_building";
$sceneImage = isset($arParams["SCENE_IMAGE"]) ? trim((string)$arParams["SCENE_IMAGE"]) : "";
$sceneSvgPath = isset($arParams["SCENE_SVG_PATH"]) ? trim((string)$arParams["SCENE_SVG_PATH"]) : "";
$mapUrl = isset($arParams["MAP_URL"]) ? trim((string)$arParams["MAP_URL"]) : "";
$mapLabel = isset($arParams["MAP_LABEL"]) && trim((string)$arParams["MAP_LABEL"]) !== "" ? trim((string)$arParams["MAP_LABEL"]) : "На карте";
$constructionSubtitle = isset($arParams["CONSTRUCTION_SUBTITLE"]) ? trim((string)$arParams["CONSTRUCTION_SUBTITLE"]) : "";
$sceneConfig = isset($arParams["SCENE_CONFIG"]) && is_array($arParams["SCENE_CONFIG"]) ? $arParams["SCENE_CONFIG"] : array();
$apartmentFilterRaw = isset($arParams["APARTMENT_FILTER"]) ? $arParams["APARTMENT_FILTER"] : array();
$apartmentFilterState = szcubeProjectSelectorNormalizeFilterState($apartmentFilterRaw);
$hasAppliedFilter = szcubeProjectSelectorFilterHasCriteria($apartmentFilterState);
$requestedInitialView = isset($arParams["INITIAL_VIEW"]) ? trim((string)$arParams["INITIAL_VIEW"]) : "";
$targetFlatCode = isset($arParams["TARGET_FLAT_CODE"]) ? trim((string)$arParams["TARGET_FLAT_CODE"]) : "";
$targetFlatCode = preg_replace("/[^a-z0-9_-]/i", "", $targetFlatCode);
$cacheTime = isset($arParams["CACHE_TIME"]) ? (int)$arParams["CACHE_TIME"] : 36000000;

if ($projectCode === "" || $dataProjectCode === "") {
    return;
}

$cacheId = array($projectId, $projectCode, $dataProjectCode, $sceneMode, $sceneImage, $sceneSvgPath, $mapUrl, $mapLabel, $constructionSubtitle, $sceneConfig, $apartmentFilterState);
if ($this->StartResultCache(false, $cacheId)) {
    if (!Loader::includeModule("iblock")) {
        $this->AbortResultCache();
        return;
    }

    $apartmentsIblock = CIBlock::GetList(array(), array("=CODE" => "apartments", "ACTIVE" => "Y"), false)->Fetch();
    if (!is_array($apartmentsIblock)) {
        $this->AbortResultCache();
        return;
    }

    $apartmentsIblockId = (int)$apartmentsIblock["ID"];
    $detailUrlTemplate = (string)$apartmentsIblock["DETAIL_PAGE_URL"];
    $projectSection = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array(
            "IBLOCK_ID" => $apartmentsIblockId,
            "SECTION_ID" => false,
            "=CODE" => $dataProjectCode,
            "ACTIVE" => "Y",
        ),
        false,
        array("ID", "NAME", "CODE")
    )->Fetch();

    if (!is_array($projectSection)) {
        $this->AbortResultCache();
        return;
    }

    $entrances = array();
    $filteredLotsCount = 0;
    $matchedTargetEntranceId = "";
    $entranceRes = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array(
            "IBLOCK_ID" => $apartmentsIblockId,
            "SECTION_ID" => (int)$projectSection["ID"],
            "ACTIVE" => "Y",
        ),
        false,
        array("ID", "NAME", "CODE", "UF_ENTRANCE_NUMBER", "UF_PIN_X", "UF_PIN_Y", "UF_PIN_LABEL")
    );

    while ($entrance = $entranceRes->GetNext()) {
        $entranceId = (int)$entrance["ID"];
        $entranceNumber = trim((string)$entrance["UF_ENTRANCE_NUMBER"]);
        $entranceTitle = trim((string)$entrance["UF_PIN_LABEL"]);
        if ($entranceTitle === "") {
            $entranceTitle = trim((string)$entrance["NAME"]) !== "" ? (string)$entrance["NAME"] : ("Подъезд " . $entranceNumber);
        }

        $entrancePinX = trim((string)$entrance["UF_PIN_X"]);
        $entrancePinY = trim((string)$entrance["UF_PIN_Y"]);
        if ($entrancePinX === "") {
            $entrancePinX = "35";
        }
        if ($entrancePinY === "") {
            $entrancePinY = "16";
        }

        $entranceData = array(
            "id" => "entrance-" . $entranceId,
            "section_id" => $entranceId,
            "number" => $entranceNumber !== "" ? $entranceNumber : (string)$entranceId,
            "title" => $entranceTitle,
            "pin_x" => $entrancePinX,
            "pin_y" => $entrancePinY,
            "has_lots" => false,
            "stats" => array(
                "count" => 0,
                "free" => 0,
                "booked" => 0,
                "sold" => 0,
                "min_price" => 0,
            ),
            "house_floors" => 0,
            "room_groups" => array(),
            "floors_map" => array(),
            "duplex_rows_map" => array(),
            "checkerboard" => array(),
        );

        $floorRes = CIBlockSection::GetList(
            array("SORT" => "DESC", "ID" => "DESC"),
            array(
                "IBLOCK_ID" => $apartmentsIblockId,
                "SECTION_ID" => $entranceId,
                "ACTIVE" => "Y",
            ),
            false,
            array("ID", "NAME", "CODE", "UF_FLOOR_NUMBER")
        );

        while ($floor = $floorRes->GetNext()) {
            $floorSectionId = (int)$floor["ID"];
            $floorNumber = (int)$floor["UF_FLOOR_NUMBER"];
            if ($floorNumber <= 0) {
                continue;
            }

            $flatRes = CIBlockElement::GetList(
                array("SORT" => "ASC", "ID" => "ASC"),
                array(
                    "IBLOCK_ID" => $apartmentsIblockId,
                    "SECTION_ID" => $floorSectionId,
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array("ID", "IBLOCK_ID", "NAME", "CODE", "DETAIL_PAGE_URL")
            );

            while ($flatElement = $flatRes->GetNextElement()) {
                $flatFields = $flatElement->GetFields();
                $flatProperties = $flatElement->GetProperties();
                $priceTotal = isset($flatProperties["PRICE_TOTAL"]["VALUE"]) ? (float)$flatProperties["PRICE_TOTAL"]["VALUE"] : 0;
                $statusXmlId = isset($flatProperties["STATUS"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["STATUS"]["VALUE_XML_ID"]) : "free";
                $statusLabel = isset($flatProperties["STATUS"]["VALUE"]) ? trim((string)$flatProperties["STATUS"]["VALUE"]) : "";
                $flatUrl = (string)$flatFields["DETAIL_PAGE_URL"];

                if ($flatUrl === "" && $detailUrlTemplate !== "") {
                    $flatUrl = CIBlock::ReplaceDetailUrl($detailUrlTemplate, $flatFields, false, "E");
                }

                $rooms = isset($flatProperties["ROOMS"]["VALUE"]) ? trim((string)$flatProperties["ROOMS"]["VALUE"]) : "";
                $roomsBucket = isset($flatProperties["ROOMS"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["ROOMS"]["VALUE_XML_ID"]) : "";
                if ($roomsBucket === "") {
                    $roomsBucket = szcubeProjectSelectorRoomBucketKey($rooms);
                }
                $roomsLabel = $rooms !== "" ? $rooms : szcubeProjectSelectorRoomLabel($roomsBucket);
                $flatFloor = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($apartmentsIblockId, (int)$flatFields["ID"], "FLOOR")
                    : (isset($flatProperties["FLOOR"]["VALUE"]) ? (int)$flatProperties["FLOOR"]["VALUE"] : 0);
                if ($flatFloor <= 0) {
                    $flatFloor = $floorNumber;
                }
                $floorTo = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($apartmentsIblockId, (int)$flatFields["ID"], "FLOOR_TO")
                    : (isset($flatProperties["FLOOR_TO"]["VALUE"]) ? (int)$flatProperties["FLOOR_TO"]["VALUE"] : 0);
                $houseFloors = szcubeProjectSelectorNormalizeHouseFloors(
                    $flatFloor,
                    $floorTo,
                    isset($flatProperties["HOUSE_FLOORS"]["VALUE"]) ? (int)$flatProperties["HOUSE_FLOORS"]["VALUE"] : 0
                );
                $planImage = szcubeProjectSelectorFilePath(isset($flatProperties["PLAN_IMAGE"]["VALUE"]) ? $flatProperties["PLAN_IMAGE"]["VALUE"] : 0);
                $flatNumber = isset($flatProperties["APARTMENT_NUMBER"]["VALUE"]) ? trim((string)$flatProperties["APARTMENT_NUMBER"]["VALUE"]) : "";
                $finish = isset($flatProperties["FINISH"]["VALUE"]) ? trim((string)$flatProperties["FINISH"]["VALUE"]) : "";
                $finishKey = isset($flatProperties["FINISH"]["VALUE_XML_ID"]) ? trim((string)$flatProperties["FINISH"]["VALUE_XML_ID"]) : "";
                $priceOld = isset($flatProperties["PRICE_OLD"]["VALUE"]) ? (float)$flatProperties["PRICE_OLD"]["VALUE"] : 0;
                $planAlt = isset($flatProperties["PLAN_ALT"]["VALUE"]) ? trim((string)$flatProperties["PLAN_ALT"]["VALUE"]) : "";
                $areaTotal = isset($flatProperties["AREA_TOTAL"]["VALUE"]) ? (float)$flatProperties["AREA_TOTAL"]["VALUE"] : 0.0;
                $ceiling = isset($flatProperties["CEILING"]["VALUE"]) ? (float)$flatProperties["CEILING"]["VALUE"] : 0.0;
                $featureTags = szcubeProjectSelectorMultiPropertyValues(isset($flatProperties["FEATURE_TAGS"]) && is_array($flatProperties["FEATURE_TAGS"]) ? $flatProperties["FEATURE_TAGS"] : array());
                $manualBadges = szcubeProjectSelectorMultiPropertyValues(isset($flatProperties["BADGES"]) && is_array($flatProperties["BADGES"]) ? $flatProperties["BADGES"] : array());
                $slotId = function_exists("szcubeGetElementPropertyValueByCode")
                    ? trim((string)szcubeGetElementPropertyValueByCode($apartmentsIblockId, (int)$flatFields["ID"], "SVG_SLOT_ID"))
                    : (isset($flatProperties["SVG_SLOT_ID"]["VALUE"]) ? trim((string)$flatProperties["SVG_SLOT_ID"]["VALUE"]) : "");
                $featureTagKeys = array();
                foreach ($featureTags as $featureTag) {
                    $featureKey = szcubeProjectSelectorNormalizeKey($featureTag);
                    if ($featureKey !== "") {
                        $featureTagKeys[] = $featureKey;
                    }
                }
                $featureTagKeys = array_values(array_unique($featureTagKeys));
                $normalizedFloorTo = szcubeProjectSelectorNormalizeUpperFloor($flatFloor, $floorTo);
                $floorDisplay = szcubeProjectSelectorFloorLabel($flatFloor, $normalizedFloorTo, $houseFloors, false);
                $floorShort = szcubeProjectSelectorFloorLabel($flatFloor, $normalizedFloorTo, $houseFloors, true);
                $badges = szcubeProjectSelectorBuildBadges($manualBadges, $priceTotal, $priceOld, $flatFloor, $normalizedFloorTo);

                $flatFilterData = array(
                    "rooms_bucket" => $roomsBucket,
                    "price_total" => $priceTotal,
                    "floor" => $flatFloor,
                    "floor_to" => $normalizedFloorTo,
                    "area_total" => $areaTotal,
                    "ceiling" => $ceiling,
                    "status" => $statusXmlId !== "" ? $statusXmlId : "free",
                    "finish" => $finishKey,
                    "feature_tags" => $featureTagKeys,
                );
                if ($hasAppliedFilter && !szcubeProjectSelectorFlatMatchesFilter($flatFilterData, $apartmentFilterState, array($projectCode, $dataProjectCode))) {
                    continue;
                }

                $flatData = array(
                    "id" => (int)$flatFields["ID"],
                    "code" => (string)$flatFields["CODE"],
                    "title" => (string)$flatFields["NAME"],
                    "url" => (string)$flatUrl,
                    "number" => $flatNumber,
                    "rooms" => $roomsLabel,
                    "rooms_label" => $roomsLabel,
                    "rooms_short" => szcubeProjectSelectorRoomShort($roomsBucket !== "" ? $roomsBucket : $roomsLabel),
                    "area_total" => isset($flatProperties["AREA_TOTAL"]["VALUE"]) ? trim((string)$flatProperties["AREA_TOTAL"]["VALUE"]) : "",
                    "price_total" => $priceTotal,
                    "price_old" => $priceOld,
                    "status_xml_id" => $statusXmlId !== "" ? $statusXmlId : "free",
                    "status_label" => $statusLabel !== "" ? $statusLabel : "Свободно",
                    "finish" => $finish,
                    "badges" => $badges,
                    "plan_image" => $planImage,
                    "plan_alt" => $planAlt !== "" ? $planAlt : ((string)$flatFields["NAME"] !== "" ? (string)$flatFields["NAME"] : "Планировка"),
                    "floor" => $floorNumber,
                    "floor_to" => $normalizedFloorTo,
                    "floor_display" => $floorDisplay,
                    "floor_short" => $floorShort,
                    "house_floors" => $houseFloors,
                    "entrance" => $entranceData["number"],
                    "slot_id" => $slotId,
                );

                if ($targetFlatCode !== "" && strcasecmp($flatData["code"], $targetFlatCode) === 0) {
                    $matchedTargetEntranceId = (string)$entranceData["id"];
                }

                if ($normalizedFloorTo > $flatFloor) {
                    $duplexRowKey = $flatFloor . "-" . $normalizedFloorTo;
                    if (!isset($entranceData["duplex_rows_map"][$duplexRowKey])) {
                        $entranceData["duplex_rows_map"][$duplexRowKey] = array(
                            "key" => $duplexRowKey,
                            "number" => $normalizedFloorTo,
                            "label" => $flatFloor . "-" . $normalizedFloorTo,
                            "cells" => array(),
                        );
                    }
                    $entranceData["duplex_rows_map"][$duplexRowKey]["cells"][] = $flatData;
                } else {
                    if (!isset($entranceData["floors_map"][$flatFloor])) {
                        $entranceData["floors_map"][$flatFloor] = array();
                    }
                    $entranceData["floors_map"][$flatFloor][] = $flatData;
                }

                $entranceData["stats"]["count"]++;
                $filteredLotsCount++;
                if (isset($entranceData["stats"][$flatData["status_xml_id"]])) {
                    $entranceData["stats"][$flatData["status_xml_id"]]++;
                }
                if ($priceTotal > 0 && ($entranceData["stats"]["min_price"] <= 0 || $priceTotal < $entranceData["stats"]["min_price"])) {
                    $entranceData["stats"]["min_price"] = $priceTotal;
                }
                if ($houseFloors > $entranceData["house_floors"]) {
                    $entranceData["house_floors"] = $houseFloors;
                }

                $roomGroupKey = $rooms !== "" ? szcubeProjectSelectorRoomBucketKey($rooms) : "other";
                if (!isset($entranceData["room_groups"][$roomGroupKey])) {
                    $entranceData["room_groups"][$roomGroupKey] = array(
                        "filter_value" => $roomGroupKey,
                        "label" => $rooms !== "" ? $roomsLabel : "Квартиры",
                        "count" => 0,
                        "min_price" => 0,
                        "sort" => szcubeProjectSelectorRoomSort($rooms),
                    );
                }
                $entranceData["room_groups"][$roomGroupKey]["count"]++;
                if ($priceTotal > 0 && ($entranceData["room_groups"][$roomGroupKey]["min_price"] <= 0 || $priceTotal < $entranceData["room_groups"][$roomGroupKey]["min_price"])) {
                    $entranceData["room_groups"][$roomGroupKey]["min_price"] = $priceTotal;
                }
            }
        }

        $hasCheckerboardRows = !empty($entranceData["floors_map"]) || !empty($entranceData["duplex_rows_map"]);
        if ($hasAppliedFilter && !$hasCheckerboardRows) {
            continue;
        }

        if ($hasCheckerboardRows) {
            $entranceData["has_lots"] = true;

            foreach ($entranceData["floors_map"] as &$floorFlats) {
                usort($floorFlats, static function ($left, $right) {
                    return szcubeProjectSelectorFlatSort($left) <=> szcubeProjectSelectorFlatSort($right);
                });
            }
            unset($floorFlats);
            foreach ($entranceData["duplex_rows_map"] as &$duplexRow) {
                usort($duplexRow["cells"], static function ($left, $right) {
                    return szcubeProjectSelectorFlatSort($left) <=> szcubeProjectSelectorFlatSort($right);
                });
            }
            unset($duplexRow);

            $maxColumns = 10;
            $maxFloorNumber = 0;
            foreach ($entranceData["floors_map"] as $floorNumber => $floorFlats) {
                $maxColumns = max($maxColumns, count($floorFlats));
                $maxFloorNumber = max($maxFloorNumber, (int)$floorNumber);
                foreach ($floorFlats as $flat) {
                    $slotMeta = szcubeProjectSelectorParseSlotId(isset($flat["slot_id"]) ? $flat["slot_id"] : "");
                    if (is_array($slotMeta)) {
                        $maxColumns = max($maxColumns, (int)$slotMeta["column"]);
                    }
                }
            }
            foreach ($entranceData["duplex_rows_map"] as $duplexRow) {
                $maxColumns = max($maxColumns, count(isset($duplexRow["cells"]) && is_array($duplexRow["cells"]) ? $duplexRow["cells"] : array()));
                if (isset($duplexRow["cells"]) && is_array($duplexRow["cells"])) {
                    foreach ($duplexRow["cells"] as $flat) {
                        $slotMeta = szcubeProjectSelectorParseSlotId(isset($flat["slot_id"]) ? $flat["slot_id"] : "");
                        if (is_array($slotMeta)) {
                            $maxColumns = max($maxColumns, (int)$slotMeta["column"]);
                        }
                    }
                }
            }

            $renderFloors = max($entranceData["house_floors"], $maxFloorNumber);
            if ($renderFloors <= 0) {
                $renderFloors = $maxFloorNumber > 0 ? $maxFloorNumber : 1;
            }

            $rows = array();
            $duplexRows = array_values($entranceData["duplex_rows_map"]);
            usort($duplexRows, static function ($left, $right) {
                return ((int)$right["number"]) <=> ((int)$left["number"]);
            });
            $coveredDuplexFloors = szcubeProjectSelectorBuildCoveredDuplexFloors($duplexRows);
            foreach ($duplexRows as $duplexRow) {
                $rowFlats = isset($duplexRow["cells"]) && is_array($duplexRow["cells"]) ? array_values($duplexRow["cells"]) : array();
                $cells = szcubeProjectSelectorBuildRowCells((string)$duplexRow["label"], $rowFlats, $maxColumns);
                $visibleRow = szcubeProjectSelectorVisibleRowCells($cells);

                $rows[] = array(
                    "number" => (int)$duplexRow["number"],
                    "label" => (string)$duplexRow["label"],
                    "cells" => $cells,
                    "visible_cells" => isset($visibleRow["cells"]) && is_array($visibleRow["cells"]) ? $visibleRow["cells"] : $cells,
                    "visible_columns" => isset($visibleRow["columns"]) ? max(1, (int)$visibleRow["columns"]) : max(1, count($cells)),
                );
            }
            for ($currentFloor = $renderFloors; $currentFloor >= 1; $currentFloor--) {
                if (isset($coveredDuplexFloors[$currentFloor])) {
                    continue;
                }
                $rowFlats = isset($entranceData["floors_map"][$currentFloor]) ? array_values($entranceData["floors_map"][$currentFloor]) : array();
                $cells = szcubeProjectSelectorBuildRowCells((string)$currentFloor, $rowFlats, $maxColumns);
                $visibleRow = szcubeProjectSelectorVisibleRowCells($cells);

                $rows[] = array(
                    "number" => $currentFloor,
                    "label" => (string)$currentFloor,
                    "cells" => $cells,
                    "visible_cells" => isset($visibleRow["cells"]) && is_array($visibleRow["cells"]) ? $visibleRow["cells"] : $cells,
                    "visible_columns" => isset($visibleRow["columns"]) ? max(1, (int)$visibleRow["columns"]) : max(1, count($cells)),
                );
            }

            $roomGroups = array_values($entranceData["room_groups"]);
            usort($roomGroups, static function ($left, $right) {
                $sortCompare = ((int)$left["sort"]) <=> ((int)$right["sort"]);
                if ($sortCompare !== 0) {
                    return $sortCompare;
                }

                return strcmp((string)$left["label"], (string)$right["label"]);
            });

            $entranceData["room_groups"] = $roomGroups;
            $entranceData["checkerboard"] = array(
                "max_columns" => $maxColumns,
                "rows" => $rows,
            );
        } else {
            $entranceData["room_groups"] = array();
            $entranceData["checkerboard"] = array(
                "max_columns" => 0,
                "rows" => array(),
            );
        }

        $entranceData["subtitle"] = "";
        if ($entranceData["house_floors"] > 0) {
            $entranceData["subtitle"] = $entranceData["house_floors"] . " этажей";
        }
        unset($entranceData["floors_map"]);
        unset($entranceData["duplex_rows_map"]);

        $entrances[] = $entranceData;
    }

    $projectDuplexRows = array();
    foreach ($entrances as $entranceIndex => $entrance) {
        if (!isset($entrance["checkerboard"]["rows"]) || !is_array($entrance["checkerboard"]["rows"])) {
            continue;
        }

        foreach ($entrance["checkerboard"]["rows"] as $row) {
            $label = isset($row["label"]) ? trim((string)$row["label"]) : "";
            if ($label === "" || !preg_match("/^(\\d+)\\s*-\\s*(\\d+)$/", $label, $matches)) {
                continue;
            }

            $projectDuplexRows[$label] = array(
                "label" => $label,
                "number" => (int)$matches[2],
            );
        }
    }

    if (!empty($projectDuplexRows)) {
        uasort($projectDuplexRows, static function ($left, $right) {
            return ((int)$right["number"]) <=> ((int)$left["number"]);
        });

        foreach ($entrances as $entranceIndex => $entrance) {
            if (!isset($entrances[$entranceIndex]["checkerboard"]["rows"]) || !is_array($entrances[$entranceIndex]["checkerboard"]["rows"])) {
                continue;
            }

            $coveredDuplexFloors = szcubeProjectSelectorBuildCoveredDuplexFloors($projectDuplexRows);
            $rowsByLabel = array();
            $regularRows = array();
            foreach ($entrances[$entranceIndex]["checkerboard"]["rows"] as $row) {
                $label = isset($row["label"]) ? trim((string)$row["label"]) : "";
                if ($label !== "" && isset($projectDuplexRows[$label])) {
                    $rowsByLabel[$label] = $row;
                    continue;
                }

                if ($label !== "" && preg_match("/^\\d+$/", $label) && isset($coveredDuplexFloors[(int)$label])) {
                    continue;
                }

                $regularRows[] = $row;
            }

            $maxColumns = isset($entrances[$entranceIndex]["checkerboard"]["max_columns"])
                ? max(1, (int)$entrances[$entranceIndex]["checkerboard"]["max_columns"])
                : 10;

            $rows = array();
            foreach ($projectDuplexRows as $label => $definition) {
                if (isset($rowsByLabel[$label])) {
                    $rows[] = $rowsByLabel[$label];
                    continue;
                }

                $rows[] = array(
                    "number" => (int)$definition["number"],
                    "label" => (string)$definition["label"],
                    "cells" => array_fill(0, $maxColumns, null),
                    "visible_cells" => array(null),
                    "visible_columns" => 1,
                );
            }

            foreach ($regularRows as $row) {
                $rows[] = $row;
            }

            $entrances[$entranceIndex]["checkerboard"]["rows"] = $rows;
        }
        unset($entrance);
    }

    if (empty($entrances)) {
        $sceneSvgMarkup = szcubeProjectSelectorReadSvg($sceneSvgPath);
        $arResult = array(
            "PROJECT" => array(
                "ID" => $projectId,
                "CODE" => $projectCode,
                "NAME" => $projectName,
                "SCENE_MODE" => $sceneMode,
                "SCENE_IMAGE" => $sceneImage,
                "SCENE_SVG" => $sceneSvgMarkup,
                "MAP_URL" => $mapUrl,
                "MAP_LABEL" => $mapLabel,
                "CONSTRUCTION_SUBTITLE" => $constructionSubtitle,
                "SCENE_CONFIG" => $sceneConfig,
            ),
            "ENTRANCES" => array(),
            "INITIAL_ENTRANCE_ID" => "",
            "EMPTY_MESSAGE" => $hasAppliedFilter ? "По текущим параметрам квартиры не найдены." : "Квартиры пока не добавлены.",
        );

        $this->IncludeComponentTemplate();
        return;
    }

    $initialEntranceId = "";
    if ($matchedTargetEntranceId !== "") {
        $initialEntranceId = $matchedTargetEntranceId;
    } else {
        foreach ($entrances as $entrance) {
            if (!empty($entrance["has_lots"])) {
                $initialEntranceId = (string)$entrance["id"];
                break;
            }
        }
        if ($initialEntranceId === "" && !empty($entrances)) {
            $initialEntranceId = (string)$entrances[0]["id"];
        }
    }
    $sceneSvgMarkup = szcubeProjectSelectorReadSvg($sceneSvgPath);
    $initialView = ($hasAppliedFilter && count($entrances) === 1 && $filteredLotsCount > 0) ? "board" : "scene";
    if ($requestedInitialView === "board" && $matchedTargetEntranceId !== "") {
        $initialView = "board";
    }

    $arResult = array(
        "PROJECT" => array(
            "ID" => $projectId,
            "CODE" => $projectCode,
            "NAME" => $projectName,
            "SCENE_MODE" => $sceneMode,
            "SCENE_IMAGE" => $sceneImage,
            "SCENE_SVG" => $sceneSvgMarkup,
            "MAP_URL" => $mapUrl,
            "MAP_LABEL" => $mapLabel,
            "CONSTRUCTION_SUBTITLE" => $constructionSubtitle,
            "SCENE_CONFIG" => $sceneConfig,
        ),
        "ENTRANCES" => $entrances,
        "INITIAL_ENTRANCE_ID" => $initialEntranceId,
        "INITIAL_VIEW" => $initialView,
        "INITIAL_FLAT_CODE" => $matchedTargetEntranceId !== "" ? $targetFlatCode : "",
        "FILTER_STATE" => $apartmentFilterState,
    );

    $this->IncludeComponentTemplate();
}
