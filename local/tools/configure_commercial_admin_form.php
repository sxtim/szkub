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
        "code::",
        "admin-user-id::",
        "dry-run::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/configure_commercial_admin_form.php [--code=commercial] [--admin-user-id=1] [--dry-run=1]\n";
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

$iblockCode = isset($_REQUEST["code"]) && $_REQUEST["code"] !== "" ? (string)$_REQUEST["code"] : "commercial";
$adminUserId = isset($_REQUEST["admin_user_id"]) ? (int)$_REQUEST["admin_user_id"] : 1;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "IBlock code: " . $iblockCode . PHP_EOL;
echo "Admin user ID: " . $adminUserId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

$iblock = CIBlock::GetList(array(), array("CODE" => $iblockCode), false)->Fetch();
if (!$iblock) {
    echo "[ERROR] IBlock not found by code: " . $iblockCode . PHP_EOL;
    exit(2);
}

$propertyMap = array();
$propRes = CIBlockProperty::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("IBLOCK_ID" => (int)$iblock["ID"]));
while ($row = $propRes->Fetch()) {
    $code = trim((string)$row["CODE"]);
    if ($code !== "") {
        $propertyMap[$code] = array("ID" => (int)$row["ID"], "NAME" => (string)$row["NAME"]);
    }
}

$addProp = static function (array &$fields, $code) use ($propertyMap) {
    if (isset($propertyMap[$code])) {
        $fields["PROPERTY_" . (int)$propertyMap[$code]["ID"]] = $propertyMap[$code]["NAME"];
    }
};

$mainFields = array(
    "ACTIVE" => "Активность",
    "NAME" => "Название",
    "CODE" => "Символьный код",
    "XML_ID" => "Внешний код",
    "SORT" => "Сортировка",
    "IBLOCK_ELEMENT_SECTION_ID" => "Основной раздел",
    "PREVIEW_PICTURE" => "Картинка карточки",
);

$addProp($mainFields, "PROJECT");
$addProp($mainFields, "CORPUS");
$addProp($mainFields, "ENTRANCE");
$addProp($mainFields, "FLOOR");
$addProp($mainFields, "HOUSE_FLOORS");
$addProp($mainFields, "COMMERCIAL_NUMBER");
$addProp($mainFields, "COMMERCIAL_TYPE");
$addProp($mainFields, "AREA_TOTAL");
$addProp($mainFields, "PRICE_TOTAL");
$addProp($mainFields, "PRICE_OLD");
$addProp($mainFields, "PRICE_M2");
$addProp($mainFields, "STATUS");
$addProp($mainFields, "BADGES");
$addProp($mainFields, "FEATURE_TAGS");
$addProp($mainFields, "DESCRIPTION");
$addProp($mainFields, "CEILING");
$addProp($mainFields, "SEPARATE_ENTRANCE");
$addProp($mainFields, "SHOWCASE_WINDOWS");
$addProp($mainFields, "WET_POINT");
$addProp($mainFields, "POWER_KW");
$addProp($mainFields, "FINISH");

$mediaFields = array();
$addProp($mediaFields, "PLAN_IMAGE");
$addProp($mediaFields, "PLAN_TITLE");
$addProp($mediaFields, "PLAN_TEXT");
$addProp($mediaFields, "PLAN_ALT");
$addProp($mediaFields, "FLOOR_SLIDE_IMAGE");
$addProp($mediaFields, "FLOOR_SLIDE_TITLE");
$addProp($mediaFields, "FLOOR_SLIDE_TEXT");
$addProp($mediaFields, "FLOOR_SLIDE_ALT");
$addProp($mediaFields, "BUILDING_SLIDE_IMAGE");
$addProp($mediaFields, "BUILDING_SLIDE_TITLE");
$addProp($mediaFields, "BUILDING_SLIDE_TEXT");
$addProp($mediaFields, "BUILDING_SLIDE_ALT");
$addProp($mediaFields, "VIEW_SLIDE_IMAGE");
$addProp($mediaFields, "VIEW_SLIDE_TITLE");
$addProp($mediaFields, "VIEW_SLIDE_TEXT");
$addProp($mediaFields, "VIEW_SLIDE_ALT");
$addProp($mediaFields, "RENDER_SLIDE_IMAGE");
$addProp($mediaFields, "RENDER_SLIDE_TITLE");
$addProp($mediaFields, "RENDER_SLIDE_TEXT");
$addProp($mediaFields, "RENDER_SLIDE_ALT");

$tabs = array(
    "edit1" => array(
        "TAB" => "Коммерция",
        "FIELDS" => $mainFields,
    ),
    "edit2" => array(
        "TAB" => "Медиа",
        "FIELDS" => $mediaFields,
    ),
);

$formId = "form_element_" . (int)$iblock["ID"];
echo "[FORM] " . $formId . PHP_EOL;
foreach ($tabs as $tabId => $tab) {
    echo "[TAB] " . $tabId . " :: " . $tab["TAB"] . " :: fields=" . count($tab["FIELDS"]) . PHP_EOL;
}

if ($dryRun) {
    exit(0);
}

\CAdminFormSettings::setTabsArray($formId, $tabs, true);
if ($adminUserId > 0) {
    CUserOptions::DeleteOption("form", $formId, false, $adminUserId);
    \CAdminFormSettings::setTabsArray($formId, $tabs, false, $adminUserId);
}

echo "[OK] Form layout saved for common settings" . PHP_EOL;
if ($adminUserId > 0) {
    echo "[OK] Form layout saved for user ID=" . $adminUserId . PHP_EOL;
}

