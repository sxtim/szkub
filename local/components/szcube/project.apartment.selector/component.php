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
        $value = trim((string)$value);
        if ($value === "") {
            return 999;
        }

        if (preg_match("/(\\d+)/", $value, $matches)) {
            return (int)$matches[1];
        }

        return 999;
    }
}

if (!function_exists("szcubeProjectSelectorRoomShort")) {
    function szcubeProjectSelectorRoomShort($value)
    {
        $value = trim((string)$value);
        if ($value === "") {
            return "";
        }

        if (preg_match("/(\\d+)/", $value, $matches)) {
            return (string)$matches[1];
        }

        return mb_substr($value, 0, 1);
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
$cacheTime = isset($arParams["CACHE_TIME"]) ? (int)$arParams["CACHE_TIME"] : 36000000;

if ($projectCode === "" || $dataProjectCode === "") {
    return;
}

$cacheId = array($projectId, $projectCode, $dataProjectCode, $sceneMode, $sceneImage, $sceneSvgPath, $mapUrl, $mapLabel, $constructionSubtitle, $sceneConfig);
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
                $houseFloors = isset($flatProperties["HOUSE_FLOORS"]["VALUE"]) ? (int)$flatProperties["HOUSE_FLOORS"]["VALUE"] : 0;
                $planImage = szcubeProjectSelectorFilePath(isset($flatProperties["PLAN_IMAGE"]["VALUE"]) ? $flatProperties["PLAN_IMAGE"]["VALUE"] : 0);
                $flatNumber = isset($flatProperties["APARTMENT_NUMBER"]["VALUE"]) ? trim((string)$flatProperties["APARTMENT_NUMBER"]["VALUE"]) : "";
                $finish = isset($flatProperties["FINISH"]["VALUE"]) ? trim((string)$flatProperties["FINISH"]["VALUE"]) : "";

                $flatData = array(
                    "id" => (int)$flatFields["ID"],
                    "code" => (string)$flatFields["CODE"],
                    "title" => (string)$flatFields["NAME"],
                    "url" => (string)$flatUrl,
                    "number" => $flatNumber,
                    "rooms" => $rooms,
                    "rooms_short" => szcubeProjectSelectorRoomShort($rooms),
                    "area_total" => isset($flatProperties["AREA_TOTAL"]["VALUE"]) ? trim((string)$flatProperties["AREA_TOTAL"]["VALUE"]) : "",
                    "price_total" => $priceTotal,
                    "status_xml_id" => $statusXmlId !== "" ? $statusXmlId : "free",
                    "status_label" => $statusLabel !== "" ? $statusLabel : "Свободно",
                    "finish" => $finish,
                    "plan_image" => $planImage,
                    "floor" => $floorNumber,
                    "entrance" => $entranceData["number"],
                );

                if (!isset($entranceData["floors_map"][$floorNumber])) {
                    $entranceData["floors_map"][$floorNumber] = array();
                }
                $entranceData["floors_map"][$floorNumber][] = $flatData;

                $entranceData["stats"]["count"]++;
                if (isset($entranceData["stats"][$flatData["status_xml_id"]])) {
                    $entranceData["stats"][$flatData["status_xml_id"]]++;
                }
                if ($priceTotal > 0 && ($entranceData["stats"]["min_price"] <= 0 || $priceTotal < $entranceData["stats"]["min_price"])) {
                    $entranceData["stats"]["min_price"] = $priceTotal;
                }
                if ($houseFloors > $entranceData["house_floors"]) {
                    $entranceData["house_floors"] = $houseFloors;
                }

                $roomGroupKey = $rooms !== "" ? $rooms : "other";
                if (!isset($entranceData["room_groups"][$roomGroupKey])) {
                    $entranceData["room_groups"][$roomGroupKey] = array(
                        "label" => $rooms !== "" ? $rooms : "Квартиры",
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

        if (!empty($entranceData["floors_map"])) {
            $entranceData["has_lots"] = true;

            foreach ($entranceData["floors_map"] as &$floorFlats) {
                usort($floorFlats, static function ($left, $right) {
                    return szcubeProjectSelectorFlatSort($left) <=> szcubeProjectSelectorFlatSort($right);
                });
            }
            unset($floorFlats);

            $maxColumns = 10;
            $maxFloorNumber = 0;
            foreach ($entranceData["floors_map"] as $floorNumber => $floorFlats) {
                $maxColumns = max($maxColumns, count($floorFlats));
                $maxFloorNumber = max($maxFloorNumber, (int)$floorNumber);
            }

            $renderFloors = max($entranceData["house_floors"], $maxFloorNumber);
            if ($renderFloors <= 0) {
                $renderFloors = $maxFloorNumber > 0 ? $maxFloorNumber : 1;
            }

            $rows = array();
            for ($currentFloor = $renderFloors; $currentFloor >= 1; $currentFloor--) {
                $rowFlats = isset($entranceData["floors_map"][$currentFloor]) ? array_values($entranceData["floors_map"][$currentFloor]) : array();
                $cells = array_fill(0, $maxColumns, null);
                $filledCount = count($rowFlats);
                $startIndex = $filledCount > 0 ? (int)floor(($maxColumns - $filledCount) / 2) : 0;
                for ($column = 0; $column < $filledCount; $column++) {
                    $cells[$startIndex + $column] = $rowFlats[$column];
                }

                $rows[] = array(
                    "number" => $currentFloor,
                    "cells" => $cells,
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

        $entrances[] = $entranceData;
    }

    if (empty($entrances)) {
        $this->AbortResultCache();
        return;
    }

    $initialEntranceId = "";
    foreach ($entrances as $entrance) {
        if (!empty($entrance["has_lots"])) {
            $initialEntranceId = (string)$entrance["id"];
            break;
        }
    }
    if ($initialEntranceId === "" && !empty($entrances)) {
        $initialEntranceId = (string)$entrances[0]["id"];
    }
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
        "ENTRANCES" => $entrances,
        "INITIAL_ENTRANCE_ID" => $initialEntranceId,
    );

    $this->IncludeComponentTemplate();
}
