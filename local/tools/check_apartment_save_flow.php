<?php
/**
 * Локальная проверка менеджерского сценария для квартир:
 * - меняет entrance/floor/apartment_number у образца,
 * - убеждается, что CODE/XML_ID/раздел перестроились,
 * - возвращает исходные значения.
 *
 * Не для продового rollout. Используется как smoke-test перед релизом.
 *
 * CLI:
 *   php local/tools/check_apartment_save_flow.php
 *   php local/tools/check_apartment_save_flow.php --code=kollekciya-301
 */

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
        "code::",
        "temp-entrance::",
        "temp-floor::",
        "temp-number::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/check_apartment_save_flow.php [--code=kollekciya-301] [--temp-entrance=2] [--temp-floor=4] [--temp-number=401]\n";
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

if (!CModule::IncludeModule("iblock")) {
    echo "Failed to load iblock module" . PHP_EOL;
    exit(1);
}

function checkApartmentSaveFlowFindIblockId($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return (int)$row["ID"];
    }

    return 0;
}

function checkApartmentSaveFlowPropertyMap($iblockId)
{
    $map = array();
    $res = CIBlockProperty::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("IBLOCK_ID" => (int)$iblockId));
    while ($row = $res->Fetch()) {
        $propertyCode = trim((string)$row["CODE"]);
        if ($propertyCode === "") {
            continue;
        }

        $map[$propertyCode] = (int)$row["ID"];
    }

    return $map;
}

function checkApartmentSaveFlowFindElementByCode($iblockId, $code)
{
    $res = CIBlockElement::GetList(
        array("ID" => "ASC"),
        array(
            "IBLOCK_ID" => (int)$iblockId,
            "=CODE" => (string)$code,
        ),
        false,
        array("nTopCount" => 1),
        array("ID", "IBLOCK_ID", "IBLOCK_SECTION_ID", "CODE", "XML_ID", "NAME")
    );

    return $res->Fetch() ?: null;
}

function checkApartmentSaveFlowPropertyValue($iblockId, $elementId, $propertyCode)
{
    $res = CIBlockElement::GetProperty(
        (int)$iblockId,
        (int)$elementId,
        array("SORT" => "ASC", "ID" => "ASC"),
        array("CODE" => (string)$propertyCode)
    );
    if ($row = $res->Fetch()) {
        return trim((string)$row["VALUE"]);
    }

    return "";
}

function checkApartmentSaveFlowSectionCodes($iblockId, $sectionId)
{
    $codes = array();
    $nav = CIBlockSection::GetNavChain((int)$iblockId, (int)$sectionId, array("ID", "CODE"));
    while ($row = $nav->Fetch()) {
        $codes[] = trim((string)$row["CODE"]);
    }

    return $codes;
}

function checkApartmentSaveFlowSnapshot($iblockId, array $element)
{
    $elementId = (int)$element["ID"];
    $projectId = (int)checkApartmentSaveFlowPropertyValue($iblockId, $elementId, "PROJECT");
    $projectCode = $projectId > 0 && function_exists("szcubeGetProjectCodeById") ? (string)szcubeGetProjectCodeById($projectId) : "";

    return array(
        "id" => $elementId,
        "code" => trim((string)$element["CODE"]),
        "xml_id" => trim((string)$element["XML_ID"]),
        "project_id" => $projectId,
        "project_code" => $projectCode,
        "section_id" => (int)$element["IBLOCK_SECTION_ID"],
        "section_path" => implode("/", checkApartmentSaveFlowSectionCodes($iblockId, (int)$element["IBLOCK_SECTION_ID"])),
        "entrance" => checkApartmentSaveFlowPropertyValue($iblockId, $elementId, "ENTRANCE"),
        "floor" => checkApartmentSaveFlowPropertyValue($iblockId, $elementId, "FLOOR"),
        "apartment_number" => checkApartmentSaveFlowPropertyValue($iblockId, $elementId, "APARTMENT_NUMBER"),
    );
}

function checkApartmentSaveFlowUpdateIdentity($iblockId, $elementId, $name, array $propertyMap, $entrance, $floor, $apartmentNumber)
{
    CIBlockElement::SetPropertyValuesEx((int)$elementId, (int)$iblockId, array(
        "ENTRANCE" => (string)$entrance,
        "FLOOR" => (int)$floor,
        "APARTMENT_NUMBER" => (string)$apartmentNumber,
    ));

    $api = new CIBlockElement();
    $updateFields = array(
        "IBLOCK_ID" => (int)$iblockId,
        "NAME" => (string)$name,
    );

    if (!$api->Update((int)$elementId, $updateFields, false, false, true)) {
        return (string)$api->LAST_ERROR;
    }

    return "";
}

$iblockId = checkApartmentSaveFlowFindIblockId("apartments");
if ($iblockId <= 0) {
    echo "[ERROR] Apartments iblock not found" . PHP_EOL;
    exit(2);
}

$apartmentCode = isset($_REQUEST["code"]) && trim((string)$_REQUEST["code"]) !== "" ? trim((string)$_REQUEST["code"]) : "kollekciya-301";
$tempEntrance = isset($_REQUEST["temp_entrance"]) && trim((string)$_REQUEST["temp_entrance"]) !== "" ? trim((string)$_REQUEST["temp_entrance"]) : "2";
$tempFloor = isset($_REQUEST["temp_floor"]) ? (int)$_REQUEST["temp_floor"] : 4;
$tempNumber = isset($_REQUEST["temp_number"]) && trim((string)$_REQUEST["temp_number"]) !== "" ? trim((string)$_REQUEST["temp_number"]) : "401";

$propertyMap = checkApartmentSaveFlowPropertyMap($iblockId);
foreach (array("ENTRANCE", "FLOOR", "APARTMENT_NUMBER") as $requiredPropertyCode) {
    if (!isset($propertyMap[$requiredPropertyCode])) {
        echo "[ERROR] Property not found: " . $requiredPropertyCode . PHP_EOL;
        exit(3);
    }
}

$element = checkApartmentSaveFlowFindElementByCode($iblockId, $apartmentCode);
if (!is_array($element)) {
    echo "[ERROR] Apartment not found by code: " . $apartmentCode . PHP_EOL;
    exit(4);
}

$original = checkApartmentSaveFlowSnapshot($iblockId, $element);
$expectedProjectCode = isset($original["project_code"]) ? trim((string)$original["project_code"]) : "";
if ($expectedProjectCode === "") {
    echo "[ERROR] Apartment project code is empty" . PHP_EOL;
    exit(5);
}

$expectedForwardCode = szcubeBuildApartmentCode($expectedProjectCode, $tempNumber);
$expectedForwardXmlId = szcubeBuildApartmentXmlId($expectedProjectCode, $tempEntrance, $tempFloor, $tempNumber);
$expectedForwardPath = $expectedProjectCode . "/podezd-" . szcubeNormalizeApartmentCodePart($tempEntrance) . "/" . sprintf("floor-%02d", $tempFloor);

echo "[SOURCE] " . $apartmentCode . PHP_EOL;
echo "[ORIGINAL] " . json_encode($original, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$updateError = checkApartmentSaveFlowUpdateIdentity(
    $iblockId,
    (int)$element["ID"],
    (string)$element["NAME"],
    $propertyMap,
    $tempEntrance,
    $tempFloor,
    $tempNumber
);
if ($updateError !== "") {
    echo "[ERROR] Forward update failed: " . $updateError . PHP_EOL;
    exit(6);
}

$updated = checkApartmentSaveFlowFindElementByCode($iblockId, $expectedForwardCode);
if (!is_array($updated)) {
    echo "[ERROR] Updated apartment not found by code: " . $expectedForwardCode . PHP_EOL;
    exit(7);
}

$updatedSnapshot = checkApartmentSaveFlowSnapshot($iblockId, $updated);
echo "[UPDATED] " . json_encode($updatedSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$forwardErrors = array();
if ($updatedSnapshot["code"] !== $expectedForwardCode) {
    $forwardErrors[] = "CODE";
}
if ($updatedSnapshot["xml_id"] !== $expectedForwardXmlId) {
    $forwardErrors[] = "XML_ID";
}
if ($updatedSnapshot["section_path"] !== $expectedForwardPath) {
    $forwardErrors[] = "SECTION_PATH";
}
if ((string)$updatedSnapshot["entrance"] !== (string)$tempEntrance) {
    $forwardErrors[] = "ENTRANCE";
}
if ((int)$updatedSnapshot["floor"] !== (int)$tempFloor) {
    $forwardErrors[] = "FLOOR";
}
if ((string)$updatedSnapshot["apartment_number"] !== (string)$tempNumber) {
    $forwardErrors[] = "APARTMENT_NUMBER";
}

$restoreError = checkApartmentSaveFlowUpdateIdentity(
    $iblockId,
    (int)$updated["ID"],
    (string)$updated["NAME"],
    $propertyMap,
    $original["entrance"],
    (int)$original["floor"],
    $original["apartment_number"]
);
if ($restoreError !== "") {
    echo "[ERROR] Restore update failed: " . $restoreError . PHP_EOL;
    exit(8);
}

$restored = checkApartmentSaveFlowFindElementByCode($iblockId, $original["code"]);
if (!is_array($restored)) {
    echo "[ERROR] Restored apartment not found by code: " . $original["code"] . PHP_EOL;
    exit(9);
}

$restoredSnapshot = checkApartmentSaveFlowSnapshot($iblockId, $restored);
echo "[RESTORED] " . json_encode($restoredSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

$restoreErrors = array();
if ($restoredSnapshot["code"] !== $original["code"]) {
    $restoreErrors[] = "CODE";
}
if ($restoredSnapshot["xml_id"] !== $original["xml_id"]) {
    $restoreErrors[] = "XML_ID";
}
if ($restoredSnapshot["section_path"] !== $original["section_path"]) {
    $restoreErrors[] = "SECTION_PATH";
}
if ((string)$restoredSnapshot["entrance"] !== (string)$original["entrance"]) {
    $restoreErrors[] = "ENTRANCE";
}
if ((int)$restoredSnapshot["floor"] !== (int)$original["floor"]) {
    $restoreErrors[] = "FLOOR";
}
if ((string)$restoredSnapshot["apartment_number"] !== (string)$original["apartment_number"]) {
    $restoreErrors[] = "APARTMENT_NUMBER";
}

if (!empty($forwardErrors)) {
    echo "[ERROR] Forward sync mismatch: " . implode(", ", $forwardErrors) . PHP_EOL;
    exit(10);
}

if (!empty($restoreErrors)) {
    echo "[ERROR] Restore sync mismatch: " . implode(", ", $restoreErrors) . PHP_EOL;
    exit(11);
}

echo "[OK] Apartment save flow verified." . PHP_EOL;
exit(0);
