<?php
/**
 * Автозаполнение SVG_SLOT_ID по текущей автосетке шахматки.
 *
 * Использует ту же логику centered-layout, что и публичная шахматка:
 * - строки строятся по FLOOR/FLOOR_TO;
 * - существующие SVG_SLOT_ID не перезаписываются;
 * - непривязанные квартиры получают свободные слоты в своём ряду.
 *
 * CLI:
 *   php local/tools/fill_apartment_svg_slot_ids.php --dry-run=1
 *   php local/tools/fill_apartment_svg_slot_ids.php --dry-run=0
 *   php local/tools/fill_apartment_svg_slot_ids.php --entrance-id=19 --dry-run=0
 */

@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
    ? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
    : rtrim(dirname(__DIR__, 2), "/");

if (PHP_SAPI === "cli") {
    $options = getopt("", array(
        "iblock-id::",
        "entrance-id::",
        "dry-run::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/fill_apartment_svg_slot_ids.php [--iblock-id=12] [--entrance-id=19] [--dry-run=1]\n";
        exit(0);
    }

    foreach ($options as $key => $value) {
        $_REQUEST[str_replace("-", "_", $key)] = $value;
    }
}

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

if (!CModule::IncludeModule("iblock")) {
    echo "[ERROR] Failed to include iblock module." . PHP_EOL;
    exit(1);
}

if (!function_exists("szcubeApartmentSlotFillNormalizeUpperFloor")) {
    function szcubeApartmentSlotFillNormalizeUpperFloor($floor, $floorTo)
    {
        $floor = (int)$floor;
        $floorTo = (int)$floorTo;
        return $floorTo > $floor ? $floorTo : 0;
    }
}

if (!function_exists("szcubeApartmentSlotFillRowLabel")) {
    function szcubeApartmentSlotFillRowLabel($floor, $floorTo)
    {
        $floor = (int)$floor;
        $floorTo = szcubeApartmentSlotFillNormalizeUpperFloor($floor, $floorTo);
        if ($floorTo > 0) {
            return $floor . "-" . $floorTo;
        }

        return $floor > 0 ? (string)$floor : "";
    }
}

if (!function_exists("szcubeApartmentSlotFillSort")) {
    function szcubeApartmentSlotFillSort(array $flat)
    {
        $number = isset($flat["number"]) ? trim((string)$flat["number"]) : "";
        if ($number !== "" && preg_match("/(\\d+)/", $number, $matches)) {
            return (int)$matches[1];
        }

        return isset($flat["id"]) ? (int)$flat["id"] : 0;
    }
}

if (!function_exists("szcubeApartmentSlotFillLoadEntrances")) {
    function szcubeApartmentSlotFillLoadEntrances($iblockId, $requestedEntranceId = 0)
    {
        $iblockId = (int)$iblockId;
        $requestedEntranceId = (int)$requestedEntranceId;
        $result = array();

        $sectionRes = CIBlockSection::GetList(
            array("SORT" => "ASC", "ID" => "ASC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "ACTIVE" => "Y",
            ),
            false,
            array("ID", "NAME", "CODE", "UF_ENTRANCE_NUMBER")
        );

        while ($section = $sectionRes->GetNext()) {
            $entranceNumber = trim((string)$section["UF_ENTRANCE_NUMBER"]);
            if ($entranceNumber === "") {
                continue;
            }

            $entranceId = (int)$section["ID"];
            if ($requestedEntranceId > 0 && $entranceId !== $requestedEntranceId) {
                continue;
            }

            $result[] = array(
                "id" => $entranceId,
                "name" => trim((string)$section["NAME"]),
                "number" => $entranceNumber,
            );
        }

        return $result;
    }
}

if (!function_exists("szcubeApartmentSlotFillLoadFlats")) {
    function szcubeApartmentSlotFillLoadFlats($iblockId, $entranceId)
    {
        $iblockId = (int)$iblockId;
        $entranceId = (int)$entranceId;
        $result = array();

        $floorRes = CIBlockSection::GetList(
            array("SORT" => "DESC", "ID" => "DESC"),
            array(
                "IBLOCK_ID" => $iblockId,
                "SECTION_ID" => $entranceId,
                "ACTIVE" => "Y",
            ),
            false,
            array("ID", "UF_FLOOR_NUMBER")
        );

        while ($floor = $floorRes->GetNext()) {
            $floorSectionId = (int)$floor["ID"];
            $floorNumber = (int)$floor["UF_FLOOR_NUMBER"];

            $flatRes = CIBlockElement::GetList(
                array("SORT" => "ASC", "ID" => "ASC"),
                array(
                    "IBLOCK_ID" => $iblockId,
                    "SECTION_ID" => $floorSectionId,
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array("ID", "CODE", "NAME")
            );

            while ($flatElement = $flatRes->GetNextElement()) {
                $fields = $flatElement->GetFields();
                $properties = $flatElement->GetProperties();

                $flatFloor = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "FLOOR")
                    : (isset($properties["FLOOR"]["VALUE"]) ? (int)$properties["FLOOR"]["VALUE"] : 0);
                if ($flatFloor <= 0) {
                    $flatFloor = $floorNumber;
                }
                $flatFloorTo = function_exists("szcubeGetElementPropertyValueByCode")
                    ? (int)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "FLOOR_TO")
                    : (isset($properties["FLOOR_TO"]["VALUE"]) ? (int)$properties["FLOOR_TO"]["VALUE"] : 0);
                $flatFloorTo = szcubeApartmentSlotFillNormalizeUpperFloor($flatFloor, $flatFloorTo);
                $slotId = function_exists("szcubeGetElementPropertyValueByCode")
                    ? trim((string)szcubeGetElementPropertyValueByCode($iblockId, (int)$fields["ID"], "SVG_SLOT_ID"))
                    : (isset($properties["SVG_SLOT_ID"]["VALUE"]) ? trim((string)$properties["SVG_SLOT_ID"]["VALUE"]) : "");

                $result[] = array(
                    "id" => (int)$fields["ID"],
                    "code" => trim((string)$fields["CODE"]),
                    "name" => trim((string)$fields["NAME"]),
                    "number" => isset($properties["APARTMENT_NUMBER"]["VALUE"]) ? trim((string)$properties["APARTMENT_NUMBER"]["VALUE"]) : "",
                    "row_label" => szcubeApartmentSlotFillRowLabel($flatFloor, $flatFloorTo),
                    "slot_id" => is_array(szcubeParseApartmentChessSlotId($slotId)) ? (string)szcubeParseApartmentChessSlotId($slotId)["slot_id"] : "",
                );
            }
        }

        usort($result, static function ($left, $right) {
            $rowCompare = strcmp((string)$left["row_label"], (string)$right["row_label"]);
            if ($rowCompare !== 0) {
                return $rowCompare;
            }

            return szcubeApartmentSlotFillSort($left) <=> szcubeApartmentSlotFillSort($right);
        });

        return $result;
    }
}

if (!function_exists("szcubeApartmentSlotFillBuildSuggestedMap")) {
    function szcubeApartmentSlotFillBuildSuggestedMap(array $flats, $defaultMaxColumns = 10)
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

        $suggested = array();

        foreach ($rowsMap as $rowLabel => $rowFlats) {
            usort($rowFlats, static function ($left, $right) {
                return szcubeApartmentSlotFillSort($left) <=> szcubeApartmentSlotFillSort($right);
            });

            $explicitCells = array();
            $pendingFlats = array();

            foreach ($rowFlats as $flat) {
                $slotMeta = szcubeParseApartmentChessSlotId(isset($flat["slot_id"]) ? $flat["slot_id"] : "");
                if (is_array($slotMeta) && (string)$slotMeta["row_label"] === (string)$rowLabel) {
                    $columnIndex = (int)$slotMeta["column"] - 1;
                    if ($columnIndex >= 0 && $columnIndex < $maxColumns && !isset($explicitCells[$columnIndex])) {
                        $explicitCells[$columnIndex] = $flat;
                        continue;
                    }
                }

                $pendingFlats[] = $flat;
            }

            $freeIndexes = array();
            for ($columnIndex = 0; $columnIndex < $maxColumns; $columnIndex++) {
                if (!isset($explicitCells[$columnIndex])) {
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

                $columnNumber = $freeIndexes[$freeListIndex] + 1;
                $suggested[(int)$flat["id"]] = szcubeBuildApartmentChessSlotId($rowLabel, $columnNumber);
            }
        }

        return $suggested;
    }
}

$iblockId = isset($_REQUEST["iblock_id"]) ? (int)$_REQUEST["iblock_id"] : 0;
if ($iblockId <= 0 && function_exists("szcubeGetIblockIdByCode")) {
    $iblockId = (int)szcubeGetIblockIdByCode("apartments");
}
$entranceId = isset($_REQUEST["entrance_id"]) ? (int)$_REQUEST["entrance_id"] : 0;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

if ($iblockId <= 0) {
    echo "[ERROR] Apartments iblock not found." . PHP_EOL;
    exit(2);
}

echo "IBlock ID: " . $iblockId . PHP_EOL;
echo "Entrance ID: " . ($entranceId > 0 ? $entranceId : "all") . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$entrances = szcubeApartmentSlotFillLoadEntrances($iblockId, $entranceId);
if (empty($entrances)) {
    echo "[OK] No entrance sections found." . PHP_EOL;
    exit(0);
}

$updatedCount = 0;
foreach ($entrances as $entrance) {
    echo PHP_EOL . "[ENTRANCE] " . $entrance["name"] . " (ID=" . (int)$entrance["id"] . ")" . PHP_EOL;

    $flats = szcubeApartmentSlotFillLoadFlats($iblockId, (int)$entrance["id"]);
    if (empty($flats)) {
        echo "[OK] No flats found in entrance." . PHP_EOL;
        continue;
    }

    $suggestedMap = szcubeApartmentSlotFillBuildSuggestedMap($flats, 10);
    foreach ($flats as $flat) {
        $flatId = (int)$flat["id"];
        $currentSlotId = trim((string)$flat["slot_id"]);
        if ($currentSlotId !== "") {
            echo "[SKIP] " . $flat["code"] . " keeps slot " . $currentSlotId . PHP_EOL;
            continue;
        }

        if (!isset($suggestedMap[$flatId]) || trim((string)$suggestedMap[$flatId]) === "") {
            echo "[WARN] No suggested slot for " . $flat["code"] . PHP_EOL;
            continue;
        }

        $slotId = trim((string)$suggestedMap[$flatId]);
        echo "[FILL] " . $flat["code"] . " -> " . $slotId . PHP_EOL;
        if (!$dryRun) {
            CIBlockElement::SetPropertyValuesEx($flatId, $iblockId, array(
                "SVG_SLOT_ID" => $slotId,
            ));
        }
        $updatedCount++;
    }
}

echo PHP_EOL . "[OK] SVG slot autofill " . ($dryRun ? "previewed" : "completed") . ". Updated: " . $updatedCount . PHP_EOL;
exit(0);
