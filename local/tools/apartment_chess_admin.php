<?php
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("DisableEventsCheck", true);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
    ? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
    : rtrim(dirname(__DIR__, 2), "/");

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php";

use Bitrix\Main\Loader;

global $APPLICATION, $USER;

if (!$USER || !$USER->IsAdmin()) {
    $APPLICATION->AuthForm("Доступ запрещён");
}

if (!Loader::includeModule("iblock")) {
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
    CAdminMessage::ShowMessage(array("TYPE" => "ERROR", "MESSAGE" => "Не удалось подключить модуль iblock."));
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
    return;
}

if (!function_exists("szcubeApartmentChessAdminRoomShort")) {
    function szcubeApartmentChessAdminRoomShort($value, $xmlId = "")
    {
        $xmlId = trim((string)$xmlId);
        if ($xmlId === "" && function_exists("szcubeProjectSelectorRoomBucketKey")) {
            $xmlId = szcubeProjectSelectorRoomBucketKey($value);
        }

        $map = array(
            "studio" => "СТ",
            "1k" => "1К",
            "2k" => "2К",
            "2e" => "2Е",
            "3k" => "3К",
            "3e" => "3Е",
            "4k" => "4К",
        );

        if ($xmlId !== "" && isset($map[$xmlId])) {
            return $map[$xmlId];
        }

        $value = trim((string)$value);
        return $value !== "" ? mb_strtoupper(mb_substr($value, 0, 1)) : "";
    }
}

if (!function_exists("szcubeApartmentChessAdminNormalizeUpperFloor")) {
    function szcubeApartmentChessAdminNormalizeUpperFloor($floor, $floorTo)
    {
        $floor = (int)$floor;
        $floorTo = (int)$floorTo;
        return $floorTo > $floor ? $floorTo : 0;
    }
}

if (!function_exists("szcubeApartmentChessAdminFloorLabel")) {
    function szcubeApartmentChessAdminFloorLabel($floor, $floorTo)
    {
        $floor = (int)$floor;
        $floorTo = szcubeApartmentChessAdminNormalizeUpperFloor($floor, $floorTo);
        if ($floorTo > 0) {
            return $floor . "-" . $floorTo;
        }

        return $floor > 0 ? (string)$floor : "";
    }
}

if (!function_exists("szcubeApartmentChessAdminParseRowNumber")) {
    function szcubeApartmentChessAdminParseRowNumber($label)
    {
        $label = trim((string)$label);
        if (preg_match("/^(\\d+)\\s*-\\s*(\\d+)$/", $label, $matches)) {
            return (int)$matches[2];
        }

        return (int)$label;
    }
}

if (!function_exists("szcubeApartmentChessAdminSetSlot")) {
    function szcubeApartmentChessAdminSetSlot($iblockId, $elementId, $slotId)
    {
        $iblockId = (int)$iblockId;
        $elementId = (int)$elementId;
        if ($iblockId <= 0 || $elementId <= 0) {
            return false;
        }

        $slotId = trim((string)$slotId);
        if ($slotId !== "") {
            $slotMeta = szcubeParseApartmentChessSlotId($slotId);
            if (!is_array($slotMeta)) {
                return false;
            }

            $slotId = (string)$slotMeta["slot_id"];
        } else {
            $slotId = false;
        }

        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, array(
            "SVG_SLOT_ID" => $slotId,
        ));

        return true;
    }
}

if (!function_exists("szcubeApartmentChessAdminLoadFlats")) {
    function szcubeApartmentChessAdminLoadFlats($iblockId, $entranceSectionId, $projectName, $iblockTypeId, &$houseFloorsMax = 0)
    {
        $result = array();
        $houseFloorsMax = 0;

        $floorRes = CIBlockSection::GetList(
            array("SORT" => "DESC", "ID" => "DESC"),
            array(
                "IBLOCK_ID" => (int)$iblockId,
                "SECTION_ID" => (int)$entranceSectionId,
                "ACTIVE" => "Y",
            ),
            false,
            array("ID", "NAME", "CODE", "UF_FLOOR_NUMBER")
        );

        while ($floor = $floorRes->GetNext()) {
            $floorSectionId = (int)$floor["ID"];
            $flatRes = CIBlockElement::GetList(
                array("SORT" => "ASC", "ID" => "ASC"),
                array(
                    "IBLOCK_ID" => (int)$iblockId,
                    "SECTION_ID" => $floorSectionId,
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array("ID", "IBLOCK_ID", "NAME", "CODE")
            );

            while ($flatElement = $flatRes->GetNextElement()) {
                $fields = $flatElement->GetFields();
                $properties = $flatElement->GetProperties();

                $floorNumber = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "FLOOR")
                    : (isset($properties["FLOOR"]["VALUE"]) ? (int)$properties["FLOOR"]["VALUE"] : 0);
                if ($floorNumber <= 0) {
                    $floorNumber = (int)$floor["UF_FLOOR_NUMBER"];
                }
                $floorTo = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "FLOOR_TO")
                    : (isset($properties["FLOOR_TO"]["VALUE"]) ? (int)$properties["FLOOR_TO"]["VALUE"] : 0);
                $floorTo = szcubeApartmentChessAdminNormalizeUpperFloor($floorNumber, $floorTo);
                $houseFloors = isset($properties["HOUSE_FLOORS"]["VALUE"]) ? (int)$properties["HOUSE_FLOORS"]["VALUE"] : 0;
                $houseFloorsMax = max($houseFloorsMax, $houseFloors, $floorNumber, $floorTo);

                $statusXmlId = isset($properties["STATUS"]["VALUE_XML_ID"]) ? trim((string)$properties["STATUS"]["VALUE_XML_ID"]) : "free";
                $statusLabel = isset($properties["STATUS"]["VALUE"]) ? trim((string)$properties["STATUS"]["VALUE"]) : "";
                $roomsValue = isset($properties["ROOMS"]["VALUE"]) ? trim((string)$properties["ROOMS"]["VALUE"]) : "";
                $roomsXmlId = isset($properties["ROOMS"]["VALUE_XML_ID"]) ? trim((string)$properties["ROOMS"]["VALUE_XML_ID"]) : "";
                $slotId = function_exists("szcubeGetElementPropertyValueByCode")
                    ? trim((string)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "SVG_SLOT_ID"))
                    : (isset($properties["SVG_SLOT_ID"]["VALUE"]) ? trim((string)$properties["SVG_SLOT_ID"]["VALUE"]) : "");
                $number = function_exists("szcubeGetElementPropertyValueByCode")
                    ? trim((string)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "APARTMENT_NUMBER"))
                    : (isset($properties["APARTMENT_NUMBER"]["VALUE"]) ? trim((string)$properties["APARTMENT_NUMBER"]["VALUE"]) : "");
                $editUrl = "/bitrix/admin/iblock_element_edit.php?" . http_build_query(array(
                    "IBLOCK_ID" => (int)$iblockId,
                    "type" => (string)$iblockTypeId,
                    "lang" => defined("LANGUAGE_ID") ? LANGUAGE_ID : "ru",
                    "ID" => (int)$fields["ID"],
                    "find_section_section" => $floorSectionId,
                    "WF" => "Y",
                ), "", "&");

                $result[] = array(
                    "id" => (int)$fields["ID"],
                    "code" => (string)$fields["CODE"],
                    "title" => (string)$fields["NAME"],
                    "number" => $number,
                    "rooms_label" => $roomsValue,
                    "rooms_short" => szcubeApartmentChessAdminRoomShort($roomsValue, $roomsXmlId),
                    "status_xml_id" => $statusXmlId !== "" ? $statusXmlId : "free",
                    "status_label" => $statusLabel !== "" ? $statusLabel : "Свободно",
                    "floor" => $floorNumber,
                    "floor_to" => $floorTo,
                    "row_label" => szcubeApartmentChessAdminFloorLabel($floorNumber, $floorTo),
                    "slot_id" => is_array(szcubeParseApartmentChessSlotId($slotId)) ? (string)szcubeParseApartmentChessSlotId($slotId)["slot_id"] : "",
                    "area_total" => isset($properties["AREA_TOTAL"]["VALUE"]) ? trim((string)$properties["AREA_TOTAL"]["VALUE"]) : "",
                    "price_total" => isset($properties["PRICE_TOTAL"]["VALUE"]) ? (float)$properties["PRICE_TOTAL"]["VALUE"] : 0,
                    "project_name" => $projectName,
                    "edit_url" => $editUrl,
                    "floor_section_id" => $floorSectionId,
                );
            }
        }

        usort($result, static function ($left, $right) {
            $floorCompare = ((int)$right["floor"]) <=> ((int)$left["floor"]);
            if ($floorCompare !== 0) {
                return $floorCompare;
            }

            return strcmp((string)$left["number"], (string)$right["number"]);
        });

        return $result;
    }
}

if (!function_exists("szcubeApartmentChessAdminBuildBoard")) {
    function szcubeApartmentChessAdminBuildBoard(array $flats, array $projectDuplexRows, $renderFloors, $defaultMaxColumns = 10)
    {
        $rowsMap = array();
        $maxColumns = max(1, (int)$defaultMaxColumns);

        foreach ($flats as $flat) {
            $rowLabel = trim((string)$flat["row_label"]);
            if ($rowLabel === "") {
                continue;
            }

            if (!isset($rowsMap[$rowLabel])) {
                $rowsMap[$rowLabel] = array();
            }
            $rowsMap[$rowLabel][] = $flat;

            $slotMeta = szcubeParseApartmentChessSlotId(isset($flat["slot_id"]) ? $flat["slot_id"] : "");
            if (is_array($slotMeta)) {
                $maxColumns = max($maxColumns, (int)$slotMeta["column"]);
            }
        }

        foreach ($rowsMap as $rowFlats) {
            $maxColumns = max($maxColumns, count($rowFlats));
        }

        $coveredRegularFloors = array();
        foreach ($projectDuplexRows as $definition) {
            $label = isset($definition["label"]) ? trim((string)$definition["label"]) : "";
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
                $coveredRegularFloors[$floor] = true;
            }
        }

        $orderedLabels = array();
        foreach ($projectDuplexRows as $label => $definition) {
            $orderedLabels[] = (string)$label;
        }
        for ($floor = (int)$renderFloors; $floor >= 1; $floor--) {
            if (isset($coveredRegularFloors[$floor])) {
                continue;
            }
            $orderedLabels[] = (string)$floor;
        }

        $rows = array();
        $conflicts = array();
        $suggestedSlots = array();

        foreach ($orderedLabels as $rowLabel) {
            $rowFlats = isset($rowsMap[$rowLabel]) ? array_values($rowsMap[$rowLabel]) : array();
            $cells = array();
            for ($column = 1; $column <= $maxColumns; $column++) {
                $slotId = szcubeBuildApartmentChessSlotId($rowLabel, $column);
                $cells[] = array(
                    "slot_id" => $slotId,
                    "column" => $column,
                    "flat" => null,
                    "is_mapped" => false,
                    "is_suggested" => false,
                );
            }

            $explicitCells = array();
            $pendingFlats = array();
            foreach ($rowFlats as $flat) {
                $slotMeta = szcubeParseApartmentChessSlotId(isset($flat["slot_id"]) ? $flat["slot_id"] : "");
                if (is_array($slotMeta) && (string)$slotMeta["row_label"] === (string)$rowLabel) {
                    $columnIndex = (int)$slotMeta["column"] - 1;
                    if ($columnIndex >= 0 && $columnIndex < $maxColumns) {
                        if (!isset($explicitCells[$columnIndex])) {
                            $explicitCells[$columnIndex] = $flat;
                            continue;
                        }

                        $conflicts[] = "Конфликт слота " . $slotMeta["slot_id"] . ": " . $flat["title"];
                    }
                }

                $pendingFlats[] = $flat;
            }

            foreach ($explicitCells as $columnIndex => $flat) {
                $cells[$columnIndex]["flat"] = $flat;
                $cells[$columnIndex]["is_mapped"] = true;
            }

            if (!empty($pendingFlats)) {
                $freeIndexes = array();
                foreach ($cells as $columnIndex => $cell) {
                    if (!is_array($cell["flat"])) {
                        $freeIndexes[] = $columnIndex;
                    }
                }

                $startOffset = max(0, (int)floor((count($freeIndexes) - count($pendingFlats)) / 2));
                foreach ($pendingFlats as $flatIndex => $flat) {
                    $freeListIndex = $startOffset + $flatIndex;
                    if (!isset($freeIndexes[$freeListIndex])) {
                        $freeListIndex = $flatIndex;
                    }
                    if (!isset($freeIndexes[$freeListIndex])) {
                        continue;
                    }

                    $columnIndex = $freeIndexes[$freeListIndex];
                    $suggestedSlots[(int)$flat["id"]] = (string)$cells[$columnIndex]["slot_id"];
                }
            }

            $rows[] = array(
                "label" => (string)$rowLabel,
                "number" => szcubeApartmentChessAdminParseRowNumber($rowLabel),
                "cells" => $cells,
            );
        }

        return array(
            "max_columns" => $maxColumns,
            "rows" => $rows,
            "conflicts" => array_values(array_unique($conflicts)),
            "suggested_slots" => $suggestedSlots,
        );
    }
}

function szcubeApartmentChessAdminBuildSelfUrl(array $params = array())
{
    $base = "/local/tools/apartment_chess_admin.php";
    $query = array(
        "lang" => defined("LANGUAGE_ID") ? LANGUAGE_ID : "ru",
        "IBLOCK_ID" => isset($_REQUEST["IBLOCK_ID"]) ? (int)$_REQUEST["IBLOCK_ID"] : 0,
        "ENTRANCE_ID" => isset($_REQUEST["ENTRANCE_ID"]) ? (int)$_REQUEST["ENTRANCE_ID"] : 0,
    );

    foreach ($params as $key => $value) {
        if ($value === null || $value === "") {
            unset($query[$key]);
            continue;
        }

        $query[$key] = $value;
    }

    return $base . "?" . http_build_query($query, "", "&");
}

$iblockId = isset($_REQUEST["IBLOCK_ID"]) ? (int)$_REQUEST["IBLOCK_ID"] : 0;
if ($iblockId <= 0) {
    $iblockId = function_exists("szcubeGetIblockIdByCode") ? (int)szcubeGetIblockIdByCode("apartments") : 0;
}

$entranceId = isset($_REQUEST["ENTRANCE_ID"]) ? (int)$_REQUEST["ENTRANCE_ID"] : 0;
if ($entranceId <= 0 && isset($_REQUEST["SECTION_ID"])) {
    $entranceId = (int)$_REQUEST["SECTION_ID"];
}
if ($entranceId <= 0 && isset($_REQUEST["ID"])) {
    $entranceId = (int)$_REQUEST["ID"];
}
if ($entranceId > 0) {
    $entranceId = (int)szcubeResolveApartmentEntranceSectionId($entranceId);
}

$backUrl = isset($_REQUEST["back_url"]) ? trim((string)$_REQUEST["back_url"]) : "";
$message = null;

if ($iblockId <= 0 || $entranceId <= 0) {
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
    CAdminMessage::ShowMessage(array("TYPE" => "ERROR", "MESSAGE" => "Не удалось определить подъезд для управления шахматкой."));
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
    return;
}

$entranceSection = szcubeGetSectionById($entranceId);
if (!is_array($entranceSection)) {
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";
    CAdminMessage::ShowMessage(array("TYPE" => "ERROR", "MESSAGE" => "Подъезд не найден."));
    require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
    return;
}

$projectSection = (int)$entranceSection["IBLOCK_SECTION_ID"] > 0 ? szcubeGetSectionById((int)$entranceSection["IBLOCK_SECTION_ID"]) : null;
$projectSectionId = is_array($projectSection) ? (int)$projectSection["ID"] : 0;
$projectName = is_array($projectSection) ? trim((string)$projectSection["NAME"]) : "";
$entranceTitle = trim((string)$entranceSection["NAME"]);
$entranceNumber = trim((string)$entranceSection["UF_ENTRANCE_NUMBER"]);
$iblockInfo = CIBlock::GetList(array(), array("ID" => $iblockId), false)->Fetch();
$iblockTypeId = is_array($iblockInfo) ? trim((string)$iblockInfo["IBLOCK_TYPE_ID"]) : "realty";

$houseFloorsMax = 0;
$flats = szcubeApartmentChessAdminLoadFlats($iblockId, $entranceId, $projectName, $iblockTypeId, $houseFloorsMax);
$flatsById = array();
foreach ($flats as $flat) {
    $flatsById[(int)$flat["id"]] = $flat;
}

$projectDuplexRows = array();
if ($projectSectionId > 0) {
    $siblingEntranceRes = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array(
            "IBLOCK_ID" => $iblockId,
            "SECTION_ID" => $projectSectionId,
            "ACTIVE" => "Y",
        ),
        false,
        array("ID")
    );
    while ($siblingEntrance = $siblingEntranceRes->Fetch()) {
        $siblingFloorsRes = CIBlockSection::GetList(
            array("SORT" => "DESC", "ID" => "DESC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "SECTION_ID" => (int)$siblingEntrance["ID"],
                "ACTIVE" => "Y",
            ),
            false,
            array("ID", "UF_FLOOR_NUMBER")
        );
        while ($siblingFloor = $siblingFloorsRes->Fetch()) {
            $flatRes = CIBlockElement::GetList(
                array("SORT" => "ASC", "ID" => "ASC"),
                array(
                    "IBLOCK_ID" => $iblockId,
                    "SECTION_ID" => (int)$siblingFloor["ID"],
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array("ID")
            );
            while ($flatElement = $flatRes->GetNextElement()) {
                $properties = $flatElement->GetProperties();
                $flatId = (int)$flatElement->GetFields()["ID"];
                $floor = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($iblockId, $flatId, "FLOOR")
                    : (isset($properties["FLOOR"]["VALUE"]) ? (int)$properties["FLOOR"]["VALUE"] : 0);
                if ($floor <= 0) {
                    $floor = (int)$siblingFloor["UF_FLOOR_NUMBER"];
                }
                $floorTo = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($iblockId, $flatId, "FLOOR_TO")
                    : (isset($properties["FLOOR_TO"]["VALUE"]) ? (int)$properties["FLOOR_TO"]["VALUE"] : 0);
                $floorTo = szcubeApartmentChessAdminNormalizeUpperFloor($floor, $floorTo);
                if ($floorTo > $floor) {
                    $label = szcubeApartmentChessAdminFloorLabel($floor, $floorTo);
                    $projectDuplexRows[$label] = array(
                        "label" => $label,
                        "number" => $floorTo,
                    );
                }
            }
        }
    }
}

uasort($projectDuplexRows, static function ($left, $right) {
    return ((int)$right["number"]) <=> ((int)$left["number"]);
});

$renderFloors = max(1, $houseFloorsMax);
$board = szcubeApartmentChessAdminBuildBoard($flats, $projectDuplexRows, $renderFloors, 10);
$suggestedSlots = isset($board["suggested_slots"]) && is_array($board["suggested_slots"]) ? $board["suggested_slots"] : array();

if ($_SERVER["REQUEST_METHOD"] === "POST" && check_bitrix_sessid()) {
    $action = isset($_POST["action"]) ? trim((string)$_POST["action"]) : "";
    $redirectParams = array();

    if ($backUrl !== "") {
        $redirectParams["back_url"] = $backUrl;
    }

    if ($action === "assign_slot") {
        $flatId = isset($_POST["flat_id"]) ? (int)$_POST["flat_id"] : 0;
        $slotMeta = szcubeParseApartmentChessSlotId(isset($_POST["slot_id"]) ? $_POST["slot_id"] : "");
        if ($flatId <= 0 || !isset($flatsById[$flatId]) || !is_array($slotMeta)) {
            $redirectParams["message"] = "assign_error";
            LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
        }

        $flat = $flatsById[$flatId];
        if ((string)$slotMeta["row_label"] !== (string)$flat["row_label"]) {
            $redirectParams["message"] = "assign_row_error";
            LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
        }

        foreach ($flats as $candidateFlat) {
            if ((int)$candidateFlat["id"] === $flatId) {
                continue;
            }

            if ((string)$candidateFlat["slot_id"] === (string)$slotMeta["slot_id"]) {
                $redirectParams["message"] = "assign_busy";
                LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
            }
        }

        if (!szcubeApartmentChessAdminSetSlot($iblockId, $flatId, (string)$slotMeta["slot_id"])) {
            $redirectParams["message"] = "assign_error";
            LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
        }

        $redirectParams["message"] = "assign_ok";
        LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
    }

    if ($action === "clear_slot") {
        $flatId = isset($_POST["flat_id"]) ? (int)$_POST["flat_id"] : 0;
        if ($flatId <= 0 || !isset($flatsById[$flatId]) || !szcubeApartmentChessAdminSetSlot($iblockId, $flatId, "")) {
            $redirectParams["message"] = "clear_error";
            LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
        }

        $redirectParams["message"] = "clear_ok";
        LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
    }

    if ($action === "autofill_slots") {
        $updatedCount = 0;
        foreach ($flats as $flat) {
            $flatId = (int)$flat["id"];
            if (trim((string)$flat["slot_id"]) !== "") {
                continue;
            }
            if (!isset($suggestedSlots[$flatId]) || trim((string)$suggestedSlots[$flatId]) === "") {
                continue;
            }
            if (szcubeApartmentChessAdminSetSlot($iblockId, $flatId, (string)$suggestedSlots[$flatId])) {
                $updatedCount++;
            }
        }

        $redirectParams["message"] = $updatedCount > 0 ? "autofill_ok" : "autofill_skip";
        LocalRedirect(szcubeApartmentChessAdminBuildSelfUrl($redirectParams));
    }
}

$messageCode = isset($_GET["message"]) ? trim((string)$_GET["message"]) : "";
$messageMap = array(
    "assign_ok" => array("TYPE" => "OK", "MESSAGE" => "Квартира привязана к выбранной клетке."),
    "assign_busy" => array("TYPE" => "ERROR", "MESSAGE" => "Слот уже занят другой квартирой."),
    "assign_row_error" => array("TYPE" => "ERROR", "MESSAGE" => "Нельзя привязать квартиру к клетке другого этажа."),
    "assign_error" => array("TYPE" => "ERROR", "MESSAGE" => "Не удалось сохранить привязку квартиры."),
    "clear_ok" => array("TYPE" => "OK", "MESSAGE" => "Привязка квартиры снята."),
    "clear_error" => array("TYPE" => "ERROR", "MESSAGE" => "Не удалось снять привязку."),
    "autofill_ok" => array("TYPE" => "OK", "MESSAGE" => "Непривязанные квартиры авторасставлены по текущей сетке."),
    "autofill_skip" => array("TYPE" => "OK", "MESSAGE" => "Новых непривязанных квартир для авторасстановки не найдено."),
);
if ($messageCode !== "" && isset($messageMap[$messageCode])) {
    $message = $messageMap[$messageCode];
}

$APPLICATION->SetTitle("Управление шахматкой: " . ($entranceTitle !== "" ? $entranceTitle : "Подъезд"));
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php";

$contextItems = array();
if ($backUrl !== "") {
    $contextItems[] = array(
        "TEXT" => "Назад",
        "LINK" => $backUrl,
        "ICON" => "btn_list",
    );
}
$contextItems[] = array(
    "TEXT" => "Редактировать подъезд",
    "LINK" => "/bitrix/admin/iblock_section_edit.php?" . http_build_query(array(
        "IBLOCK_ID" => $iblockId,
        "type" => $iblockTypeId,
        "lang" => defined("LANGUAGE_ID") ? LANGUAGE_ID : "ru",
        "ID" => $entranceId,
    ), "", "&"),
    "ICON" => "btn_edit",
);

$context = new CAdminContextMenu($contextItems);
$context->Show();

if (is_array($message)) {
    CAdminMessage::ShowMessage($message);
}

$flatCount = count($flats);
$mappedCount = 0;
$suggestedCount = 0;
foreach ($flats as $flat) {
    if (trim((string)$flat["slot_id"]) !== "") {
        $mappedCount++;
    } elseif (isset($suggestedSlots[(int)$flat["id"]])) {
        $suggestedCount++;
    }
}
?>
<style>
  .adm-chess {
    display: grid;
    grid-template-columns: minmax(340px, 420px) minmax(0, 1fr);
    gap: 20px;
    align-items: start;
  }

  .adm-chess__panel,
  .adm-chess__board-wrap {
    background: #fff;
    border: 1px solid #dce3e8;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }

  .adm-chess__panel {
    padding: 16px;
    position: sticky;
    top: 16px;
  }

  .adm-chess__title {
    margin: 0 0 6px;
    font-size: 18px;
    line-height: 1.3;
    font-weight: 600;
    color: #1f252b;
  }

  .adm-chess__subtitle,
  .adm-chess__stats {
    margin: 0 0 12px;
    color: #59656f;
    font-size: 13px;
    line-height: 1.5;
  }

  .adm-chess__actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 16px;
  }

  .adm-chess__list {
    display: grid;
    gap: 10px;
  }

  .adm-chess__flat {
    display: grid;
    gap: 8px;
    padding: 12px;
    border: 1px solid #d7dfe5;
    border-radius: 4px;
    background: #fafcfd;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
  }

  .adm-chess__flat:hover,
  .adm-chess__flat.is-hovered {
    border-color: #86b8ff;
    box-shadow: 0 0 0 1px rgba(47, 114, 220, 0.15);
  }

  .adm-chess__flat.is-selected {
    border-color: #2fbfd4;
    background: #f1fbfd;
    box-shadow: 0 0 0 1px rgba(47, 191, 212, 0.24);
  }

  .adm-chess__flat-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
  }

  .adm-chess__flat-title {
    font-weight: 600;
    color: #1f252b;
  }

  .adm-chess__flat-meta,
  .adm-chess__flat-slot {
    font-size: 12px;
    line-height: 1.45;
    color: #59656f;
  }

  .adm-chess__flat-status {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    background: #eef3f8;
    color: #3f4c58;
  }

  .adm-chess__flat-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .adm-chess__board-wrap {
    padding: 16px;
  }

  .adm-chess__board-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
  }

  .adm-chess__board-title {
    margin: 0;
    font-size: 18px;
    line-height: 1.3;
    font-weight: 600;
    color: #1f252b;
  }

  .adm-chess__board-note {
    margin: 0;
    color: #59656f;
    font-size: 12px;
    line-height: 1.45;
  }

  .adm-chess__rows {
    display: grid;
    gap: 8px;
  }

  .adm-chess__row {
    display: grid;
    grid-template-columns: 58px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
  }

  .adm-chess__row-label {
    font-size: 14px;
    font-weight: 600;
    color: #55616b;
    text-align: right;
  }

  .adm-chess__cells {
    display: grid;
    grid-template-columns: repeat(var(--adm-chess-columns), minmax(0, 1fr));
    gap: 8px;
  }

  .adm-chess__cell {
    min-height: 46px;
    padding: 6px;
    border: 1px solid #d6dfe6;
    border-radius: 4px;
    background: #f7fafc;
    color: #1f252b;
    font-size: 12px;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease, transform 0.15s ease;
  }

  .adm-chess__cell:hover,
  .adm-chess__cell.is-hovered {
    border-color: #86b8ff;
    box-shadow: 0 0 0 1px rgba(47, 114, 220, 0.15);
  }

  .adm-chess__cell.is-selected-slot {
    border-color: #2fbfd4;
    box-shadow: 0 0 0 1px rgba(47, 191, 212, 0.22);
    transform: scale(1.02);
  }

  .adm-chess__cell.is-empty {
    background: #fbfdff;
    color: #9aa7b2;
    font-weight: 500;
  }

  .adm-chess__cell.is-suggested {
    border-style: dashed;
    background: #fbfdff;
    color: #5d6a75;
  }

  .adm-chess__cell.is-booked {
    background:
      repeating-linear-gradient(
        -45deg,
        rgba(232, 149, 109, 0.2) 0,
        rgba(232, 149, 109, 0.2) 4px,
        rgba(255, 255, 255, 0.95) 4px,
        rgba(255, 255, 255, 0.95) 8px
      ),
      #fff;
  }

  .adm-chess__cell.is-sold {
    background: #edf2f6;
    color: #8c99a5;
  }

  .adm-chess__legend {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    margin-top: 16px;
    color: #59656f;
    font-size: 12px;
  }

  .adm-chess__legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .adm-chess__legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid #cfd8df;
    background: #f7fafc;
  }

  .adm-chess__legend-dot.is-suggested {
    border-style: dashed;
  }

  .adm-chess__legend-dot.is-booked {
    background:
      repeating-linear-gradient(
        -45deg,
        rgba(232, 149, 109, 0.2) 0,
        rgba(232, 149, 109, 0.2) 4px,
        rgba(255, 255, 255, 0.95) 4px,
        rgba(255, 255, 255, 0.95) 8px
      ),
      #fff;
  }

  .adm-chess__legend-dot.is-sold {
    background: #edf2f6;
  }

  .adm-chess__conflicts {
    margin: 16px 0 0;
    padding: 12px 14px;
    border-radius: 4px;
    background: #fff7e5;
    border: 1px solid #f0d28a;
    color: #7d5a00;
  }

  .adm-chess__conflicts ul {
    margin: 8px 0 0 18px;
  }

  @media (max-width: 1200px) {
    .adm-chess {
      grid-template-columns: 1fr;
    }

    .adm-chess__panel {
      position: static;
    }
  }
</style>

<form id="adm-chess-form" method="post" action="<?= htmlspecialcharsbx(szcubeApartmentChessAdminBuildSelfUrl(array("back_url" => $backUrl !== "" ? $backUrl : null))) ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="action" value="" data-chess-action />
    <input type="hidden" name="flat_id" value="" data-chess-flat-id />
    <input type="hidden" name="slot_id" value="" data-chess-slot-id />
</form>

<div class="adm-chess">
  <aside class="adm-chess__panel">
    <h1 class="adm-chess__title"><?= htmlspecialcharsbx($projectName !== "" ? $projectName . " · " . $entranceTitle : $entranceTitle) ?></h1>
    <p class="adm-chess__subtitle">
      <?= htmlspecialcharsbx($entranceNumber !== "" ? ("Подъезд " . $entranceNumber) : $entranceTitle) ?>.
      Визуальная привязка квартир к клеткам шахматки.
    </p>
    <p class="adm-chess__stats">
      Квартир: <?= (int)$flatCount ?>.
      Привязано: <?= (int)$mappedCount ?>.
      Непривязано: <?= (int)$suggestedCount ?>.
    </p>

    <div class="adm-chess__actions">
      <button class="adm-btn adm-btn-save" type="button" data-chess-autofill>Авторасставить непривязанные</button>
      <?php if ($backUrl !== ""): ?>
        <a class="adm-btn" href="<?= htmlspecialcharsbx($backUrl) ?>">Назад</a>
      <?php endif; ?>
    </div>

    <div class="adm-chess__list" data-chess-flat-list>
      <?php foreach ($flats as $flat): ?>
        <?php
        $flatId = (int)$flat["id"];
        $explicitSlot = trim((string)$flat["slot_id"]);
        $isMapped = $explicitSlot !== "";
        ?>
        <article
          class="adm-chess__flat"
          tabindex="0"
          data-flat-item="<?= $flatId ?>"
          data-flat-slot-id="<?= htmlspecialcharsbx($explicitSlot) ?>"
          data-flat-row-label="<?= htmlspecialcharsbx((string)$flat["row_label"]) ?>"
        >
          <div class="adm-chess__flat-top">
            <div class="adm-chess__flat-title">
              <?= htmlspecialcharsbx((string)$flat["number"] !== "" ? ("№" . (string)$flat["number"]) : (string)$flat["title"]) ?>
            </div>
            <span class="adm-chess__flat-status"><?= htmlspecialcharsbx((string)$flat["status_label"]) ?></span>
          </div>
          <div class="adm-chess__flat-meta">
            <?= htmlspecialcharsbx((string)$flat["rooms_label"]) ?>
            <?php if (trim((string)$flat["area_total"]) !== ""): ?>
              · <?= htmlspecialcharsbx((string)$flat["area_total"]) ?> м²
            <?php endif; ?>
            · <?= htmlspecialcharsbx((string)$flat["row_label"]) ?> этаж
          </div>
          <div class="adm-chess__flat-slot">
            <?php if ($isMapped): ?>
              Слот: <?= htmlspecialcharsbx($explicitSlot) ?>
            <?php else: ?>
              Слот не задан
            <?php endif; ?>
          </div>
          <div class="adm-chess__flat-actions">
            <a class="adm-btn" href="<?= htmlspecialcharsbx((string)$flat["edit_url"]) ?>">Редактировать квартиру</a>
            <?php if ($isMapped): ?>
              <button class="adm-btn" type="button" data-chess-clear-slot="<?= $flatId ?>">Снять слот</button>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </aside>

  <section class="adm-chess__board-wrap">
    <div class="adm-chess__board-head">
      <div>
        <h2 class="adm-chess__board-title"><?= htmlspecialcharsbx($entranceTitle !== "" ? $entranceTitle : "Шахматка подъезда") ?></h2>
        <p class="adm-chess__board-note">
          Выберите квартиру слева и кликните по свободной клетке, чтобы назначить или перенести слот.
          Клик по занятой клетке открывает квартиру на редактирование.
        </p>
      </div>
    </div>

    <div class="adm-chess__rows">
      <?php foreach ($board["rows"] as $row): ?>
        <div class="adm-chess__row">
          <div class="adm-chess__row-label"><?= htmlspecialcharsbx((string)$row["label"]) ?></div>
          <div class="adm-chess__cells" style="--adm-chess-columns: <?= (int)$board["max_columns"] ?>;">
            <?php foreach ($row["cells"] as $cell): ?>
              <?php
              $flat = isset($cell["flat"]) && is_array($cell["flat"]) ? $cell["flat"] : null;
              $cellClasses = array("adm-chess__cell");
              if (!$flat) {
                  $cellClasses[] = "is-empty";
              } else {
                  $cellClasses[] = "is-" . trim((string)$flat["status_xml_id"]);
                  if (!empty($cell["is_suggested"])) {
                      $cellClasses[] = "is-suggested";
                  }
              }
              ?>
              <button
                class="<?= htmlspecialcharsbx(implode(" ", $cellClasses)) ?>"
                type="button"
                data-slot-button="<?= htmlspecialcharsbx((string)$cell["slot_id"]) ?>"
                data-slot-row-label="<?= htmlspecialcharsbx((string)$row["label"]) ?>"
                data-slot-flat-id="<?= $flat ? (int)$flat["id"] : 0 ?>"
                data-slot-edit-url="<?= htmlspecialcharsbx($flat ? (string)$flat["edit_url"] : "") ?>"
              >
                <?php if ($flat): ?>
                  <?= htmlspecialcharsbx((string)$flat["rooms_short"]) ?>
                <?php else: ?>
                  ·
                <?php endif; ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="adm-chess__legend" aria-label="Состояния клеток">
      <span class="adm-chess__legend-item"><span class="adm-chess__legend-dot"></span>Свободная клетка</span>
      <span class="adm-chess__legend-item"><span class="adm-chess__legend-dot is-booked"></span>Забронирована</span>
      <span class="adm-chess__legend-item"><span class="adm-chess__legend-dot is-sold"></span>Продана</span>
    </div>

    <?php if (!empty($board["conflicts"])): ?>
      <div class="adm-chess__conflicts">
        Найдены конфликты привязки слотов:
        <ul>
          <?php foreach ($board["conflicts"] as $conflict): ?>
            <li><?= htmlspecialcharsbx((string)$conflict) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </section>
</div>

<script>
  (function() {
    const form = document.getElementById("adm-chess-form");
    if (!form) return;

    const actionInput = form.querySelector("[data-chess-action]");
    const flatIdInput = form.querySelector("[data-chess-flat-id]");
    const slotIdInput = form.querySelector("[data-chess-slot-id]");
    const flatItems = Array.from(document.querySelectorAll("[data-flat-item]"));
    const slotButtons = Array.from(document.querySelectorAll("[data-slot-button]"));
    const clearButtons = Array.from(document.querySelectorAll("[data-chess-clear-slot]"));
    const autofillButton = document.querySelector("[data-chess-autofill]");

    let selectedFlatId = 0;

    const updateSelectedState = () => {
      flatItems.forEach((item) => {
        const isSelected = Number(item.getAttribute("data-flat-item")) === selectedFlatId;
        item.classList.toggle("is-selected", isSelected);
      });

      slotButtons.forEach((button) => {
        const isSelected = Number(button.getAttribute("data-slot-flat-id")) === selectedFlatId && selectedFlatId > 0;
        button.classList.toggle("is-selected-slot", isSelected);
      });
    };

    const clearHover = () => {
      flatItems.forEach((item) => item.classList.remove("is-hovered"));
      slotButtons.forEach((button) => button.classList.remove("is-hovered"));
    };

    const highlightBySlot = (slotId) => {
      clearHover();
      if (!slotId) return;

      flatItems.forEach((item) => {
        if (item.getAttribute("data-flat-slot-id") === slotId) {
          item.classList.add("is-hovered");
        }
      });

      slotButtons.forEach((button) => {
        if (button.getAttribute("data-slot-button") === slotId) {
          button.classList.add("is-hovered");
        }
      });
    };

    const submitAction = (action, flatId, slotId) => {
      actionInput.value = action;
      flatIdInput.value = flatId ? String(flatId) : "";
      slotIdInput.value = slotId || "";
      form.submit();
    };

    flatItems.forEach((item) => {
      const flatId = Number(item.getAttribute("data-flat-item"));
      const slotId = item.getAttribute("data-flat-slot-id") || "";

      item.addEventListener("click", (event) => {
        const clearButton = event.target.closest("[data-chess-clear-slot]");
        if (clearButton) {
          return;
        }

        const editLink = event.target.closest("a");
        if (editLink) {
          return;
        }

        selectedFlatId = selectedFlatId === flatId ? 0 : flatId;
        updateSelectedState();
      });

      item.addEventListener("mouseenter", () => {
        highlightBySlot(slotId);
      });

      item.addEventListener("mouseleave", () => {
        clearHover();
      });

      item.addEventListener("focus", () => {
        highlightBySlot(slotId);
      });

      item.addEventListener("blur", () => {
        clearHover();
      });
    });

    clearButtons.forEach((button) => {
      button.addEventListener("click", (event) => {
        event.preventDefault();
        const flatId = Number(button.getAttribute("data-chess-clear-slot"));
        if (!flatId) return;
        submitAction("clear_slot", flatId, "");
      });
    });

    slotButtons.forEach((button) => {
      const flatId = Number(button.getAttribute("data-slot-flat-id"));
      const slotId = button.getAttribute("data-slot-button") || "";
      const editUrl = button.getAttribute("data-slot-edit-url") || "";

      button.addEventListener("mouseenter", () => {
        if (slotId) {
          highlightBySlot(slotId);
        }
      });

      button.addEventListener("mouseleave", () => {
        clearHover();
      });

      button.addEventListener("click", () => {
        if (flatId > 0 && !selectedFlatId) {
          if (editUrl) {
            window.location.href = editUrl;
          }
          return;
        }

        if (flatId > 0 && selectedFlatId && selectedFlatId !== flatId) {
          window.alert("Слот уже занят. Сначала снимите привязку у текущей квартиры.");
          return;
        }

        if (!selectedFlatId) {
          window.alert("Сначала выберите квартиру в списке слева.");
          return;
        }

        submitAction("assign_slot", selectedFlatId, slotId);
      });
    });

    autofillButton?.addEventListener("click", () => {
      submitAction("autofill_slots", 0, "");
    });

    updateSelectedState();
  })();
</script>

<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php";
