<?php
@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
    ? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
    : rtrim(dirname(__DIR__, 2), "/");

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

if (PHP_SAPI === "cli") {
    $options = getopt("", array(
        "parking-code::",
        "projects-code::",
        "dry-run::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/sync_parking_sections.php [--parking-code=parking] [--projects-code=projects] [--dry-run=1]\n";
        exit(0);
    }

    foreach ($options as $key => $value) {
        $_REQUEST[str_replace("-", "_", $key)] = $value;
    }
}

$prologPath = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!is_file($prologPath)) {
    echo "Bitrix bootstrap not found: " . $prologPath . PHP_EOL;
    exit(1);
}

require $prologPath;

if (!class_exists("\\Bitrix\\Main\\Loader") || !\Bitrix\Main\Loader::includeModule("iblock")) {
    echo "Failed to load iblock module" . PHP_EOL;
    exit(1);
}

function parkingSectionsFindIblockByCode($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    return $res ? $res->Fetch() : false;
}

function parkingSectionsTopSectionsByCode($iblockId)
{
    $result = array();
    $res = CIBlockSection::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId, "SECTION_ID" => false),
        false,
        array("ID", "NAME", "CODE", "SORT")
    );

    while ($row = $res->Fetch()) {
        $code = trim((string)$row["CODE"]);
        if ($code === "") {
            continue;
        }
        $result[mb_strtolower($code)] = array(
            "id" => (int)$row["ID"],
            "name" => (string)$row["NAME"],
            "code" => $code,
            "sort" => (int)$row["SORT"],
        );
    }

    return $result;
}

function parkingSectionsElementSectionIds($elementId)
{
    $ids = array();
    $res = CIBlockElement::GetElementGroups((int)$elementId, true, array("ID"));
    while ($row = $res->Fetch()) {
        $ids[] = (int)$row["ID"];
    }
    sort($ids);
    return $ids;
}

function parkingSectionsEnsureSectionsMode($iblockId, $dryRun)
{
    $iblockId = (int)$iblockId;
    if ($iblockId <= 0) {
        return false;
    }

    $res = CIBlock::GetList(array(), array("ID" => $iblockId), false);
    $row = $res ? $res->Fetch() : false;
    if (!is_array($row)) {
        return false;
    }

    $needsSync =
        (string)$row["SECTIONS"] !== "Y"
        || (string)$row["SECTION_CHOOSER"] !== "L"
        || (string)$row["SECTION_PAGE_URL"] !== "#SITE_DIR#/parking/#SECTION_CODE_PATH#/";

    if (!$needsSync) {
        echo "[OK] Sections mode already enabled" . PHP_EOL;
        return true;
    }

    echo "[SYNC] Enable sections mode" . PHP_EOL;
    if ($dryRun) {
        return true;
    }

    $ib = new CIBlock();
    return (bool)$ib->Update($iblockId, array(
        "NAME" => (string)$row["NAME"],
        "CODE" => (string)$row["CODE"],
        "IBLOCK_TYPE_ID" => (string)$row["IBLOCK_TYPE_ID"],
        "LID" => array((string)$row["LID"]),
        "LIST_PAGE_URL" => "#SITE_DIR#/parking/",
        "SECTION_PAGE_URL" => "#SITE_DIR#/parking/#SECTION_CODE_PATH#/",
        "DETAIL_PAGE_URL" => "#SITE_DIR#/parking/#ELEMENT_CODE#/",
        "SECTION_CHOOSER" => "L",
        "SECTIONS" => "Y",
        "RIGHTS_MODE" => (string)$row["RIGHTS_MODE"] !== "" ? (string)$row["RIGHTS_MODE"] : "S",
        "VERSION" => (int)$row["VERSION"] > 0 ? (int)$row["VERSION"] : 2,
        "INDEX_ELEMENT" => (string)$row["INDEX_ELEMENT"] !== "" ? (string)$row["INDEX_ELEMENT"] : "N",
        "INDEX_SECTION" => (string)$row["INDEX_SECTION"] !== "" ? (string)$row["INDEX_SECTION"] : "N",
    ));
}

$parkingCode = isset($_REQUEST["parking_code"]) && $_REQUEST["parking_code"] !== "" ? (string)$_REQUEST["parking_code"] : "parking";
$projectsCode = isset($_REQUEST["projects_code"]) && $_REQUEST["projects_code"] !== "" ? (string)$_REQUEST["projects_code"] : "projects";
$dryRun = isset($_REQUEST["dry_run"]) ? (string)$_REQUEST["dry_run"] === "1" : false;

echo "Parking iblock code: " . $parkingCode . PHP_EOL;
echo "Projects iblock code: " . $projectsCode . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$parkingIblock = parkingSectionsFindIblockByCode($parkingCode);
if (!is_array($parkingIblock) || (int)$parkingIblock["ID"] <= 0) {
    echo "[ERROR] Parking iblock not found by code: " . $parkingCode . PHP_EOL;
    exit(2);
}

$projectsIblock = parkingSectionsFindIblockByCode($projectsCode);
if (!is_array($projectsIblock) || (int)$projectsIblock["ID"] <= 0) {
    echo "[ERROR] Projects iblock not found by code: " . $projectsCode . PHP_EOL;
    exit(2);
}

$parkingIblockId = (int)$parkingIblock["ID"];
$projectsIblockId = (int)$projectsIblock["ID"];

if (!parkingSectionsEnsureSectionsMode($parkingIblockId, $dryRun)) {
    echo "[ERROR] Failed to enable sections mode for parking iblock" . PHP_EOL;
    exit(3);
}

$existingSections = parkingSectionsTopSectionsByCode($parkingIblockId);
$sectionApi = new CIBlockSection();
$sectionMap = array();

$projectRes = CIBlockElement::GetList(
    array("SORT" => "ASC", "NAME" => "ASC"),
    array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
    false,
    false,
    array("ID", "NAME", "CODE", "SORT")
);

while ($project = $projectRes->Fetch()) {
    $projectId = (int)$project["ID"];
    $projectCode = trim((string)$project["CODE"]);
    $projectName = trim((string)$project["NAME"]);
    $projectSort = (int)$project["SORT"] > 0 ? (int)$project["SORT"] : 500;

    if ($projectId <= 0 || $projectCode === "" || $projectName === "") {
        continue;
    }

    $key = mb_strtolower($projectCode);
    if (isset($existingSections[$key])) {
        $sectionMap[$projectId] = (int)$existingSections[$key]["id"];
        echo "[OK] Section exists for project: " . $projectCode . PHP_EOL;
        continue;
    }

    echo "[CREATE] Section for project: " . $projectCode . PHP_EOL;
    if ($dryRun) {
        continue;
    }

    $sectionId = (int)$sectionApi->Add(array(
        "IBLOCK_ID" => $parkingIblockId,
        "ACTIVE" => "Y",
        "NAME" => $projectName,
        "CODE" => $projectCode,
        "SORT" => $projectSort,
        "IBLOCK_SECTION_ID" => false,
    ));

    if ($sectionId <= 0) {
        echo "[ERROR] Failed to create section for project " . $projectCode . ": " . $sectionApi->LAST_ERROR . PHP_EOL;
        exit(4);
    }

    $sectionMap[$projectId] = $sectionId;
}

if (!$dryRun) {
    $existingSections = parkingSectionsTopSectionsByCode($parkingIblockId);
    $sectionMap = array();

    $projectRes = CIBlockElement::GetList(
        array("SORT" => "ASC", "NAME" => "ASC"),
        array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
        false,
        false,
        array("ID", "CODE")
    );
    while ($project = $projectRes->Fetch()) {
        $projectId = (int)$project["ID"];
        $projectCode = trim((string)$project["CODE"]);
        $key = mb_strtolower($projectCode);
        if ($projectId > 0 && $projectCode !== "" && isset($existingSections[$key])) {
            $sectionMap[$projectId] = (int)$existingSections[$key]["id"];
        }
    }
}

$parkingRes = CIBlockElement::GetList(
    array("SORT" => "ASC", "ID" => "ASC"),
    array("IBLOCK_ID" => $parkingIblockId, "ACTIVE" => "Y"),
    false,
    false,
    array("ID", "NAME", "IBLOCK_SECTION_ID", "IBLOCK_ID")
);

while ($parking = $parkingRes->GetNextElement()) {
    $fields = $parking->GetFields();
    $properties = $parking->GetProperties();

    $elementId = (int)$fields["ID"];
    $projectId = isset($properties["PROJECT"]["VALUE"]) ? (int)$properties["PROJECT"]["VALUE"] : 0;
    $targetSectionId = isset($sectionMap[$projectId]) ? (int)$sectionMap[$projectId] : 0;

    if ($elementId <= 0 || $targetSectionId <= 0) {
        echo "[SKIP] Element without target section: ID=" . $elementId . PHP_EOL;
        continue;
    }

    $currentSectionIds = parkingSectionsElementSectionIds($elementId);
    if (count($currentSectionIds) === 1 && (int)$currentSectionIds[0] === $targetSectionId) {
        echo "[OK] Element section synced: ID=" . $elementId . PHP_EOL;
        continue;
    }

    echo "[MOVE] Element ID=" . $elementId . " -> section " . $targetSectionId . PHP_EOL;
    if ($dryRun) {
        continue;
    }

    $ok = CIBlockElement::SetElementSection($elementId, array($targetSectionId), false);
    if (!$ok) {
        echo "[ERROR] Failed to move element ID=" . $elementId . PHP_EOL;
        exit(5);
    }
}

echo "[OK] Parking sections sync finished" . PHP_EOL;
