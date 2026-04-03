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
        echo "Usage: php local/tools/configure_storeroom_admin_form.php [--code=storerooms] [--admin-user-id=1] [--dry-run=1]\n";
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

$iblockCode = isset($_REQUEST["code"]) && $_REQUEST["code"] !== "" ? (string)$_REQUEST["code"] : "storerooms";
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
);

$addProp($mainFields, "PROJECT");
$addProp($mainFields, "STOREROOM_NUMBER");
$addProp($mainFields, "AREA_TOTAL");
$addProp($mainFields, "PRICE_TOTAL");
$addProp($mainFields, "PRICE_OLD");
$addProp($mainFields, "STATUS");
$addProp($mainFields, "BADGES");

$tabs = array(
    "edit1" => array(
        "TAB" => "Кладовка",
        "FIELDS" => $mainFields,
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
