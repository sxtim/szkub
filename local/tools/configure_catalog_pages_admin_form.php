<?php
/**
 * Настраивает форму редактирования ИБ catalog_pages.
 *
 * CLI:
 *   php local/tools/configure_catalog_pages_admin_form.php --dry-run=1
 *   php local/tools/configure_catalog_pages_admin_form.php --dry-run=0
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
        "admin-user-id::",
        "dry-run::",
        "help::",
    ));

    if (isset($options["help"])) {
        echo "Usage: php local/tools/configure_catalog_pages_admin_form.php [--admin-user-id=1] [--dry-run=1]" . PHP_EOL;
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

$adminUserId = isset($_REQUEST["admin_user_id"]) ? (int)$_REQUEST["admin_user_id"] : 1;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

function catalogPagesAdminFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function catalogPagesAdminPropertyMap($iblockId)
{
    $result = array();
    $res = CIBlockProperty::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("IBLOCK_ID" => (int)$iblockId));
    while ($row = $res->Fetch()) {
        $code = trim((string)$row["CODE"]);
        if ($code === "") {
            continue;
        }

        $result[$code] = array(
            "ID" => (int)$row["ID"],
            "NAME" => (string)$row["NAME"],
        );
    }

    return $result;
}

$iblock = catalogPagesAdminFindIblock("catalog_pages");
if (!is_array($iblock)) {
    echo "[ERROR] IBlock not found by code: catalog_pages" . PHP_EOL;
    exit(2);
}

$iblockId = (int)$iblock["ID"];
$formId = "form_element_" . $iblockId;
$propertyMap = catalogPagesAdminPropertyMap($iblockId);

$fields = array(
    "ACTIVE" => "Активность",
    "NAME" => "Название",
    "CODE" => "Символьный код",
    "SORT" => "Сортировка",
);

if (isset($propertyMap["INTRO_TEXT_1"])) {
    $fields["PROPERTY_" . (int)$propertyMap["INTRO_TEXT_1"]["ID"]] = "Интро: текст, абзац 1";
}
if (isset($propertyMap["INTRO_TEXT_2"])) {
    $fields["PROPERTY_" . (int)$propertyMap["INTRO_TEXT_2"]["ID"]] = "Интро: текст, абзац 2";
}
if (isset($propertyMap["INTRO_IMAGE"])) {
    $fields["PROPERTY_" . (int)$propertyMap["INTRO_IMAGE"]["ID"]] = "Интро: изображение";
}
if (isset($propertyMap["INTRO_IMAGE_ALT"])) {
    $fields["PROPERTY_" . (int)$propertyMap["INTRO_IMAGE_ALT"]["ID"]] = "Интро: alt изображения";
}

$tabs = array(
    "edit1" => array(
        "TAB" => "Интро",
        "FIELDS" => $fields,
    ),
);

if ($dryRun) {
    exit(0);
}

\CAdminFormSettings::setTabsArray($formId, $tabs, true);
if ($adminUserId > 0) {
    CUserOptions::DeleteOption("form", $formId, false, $adminUserId);
    \CAdminFormSettings::setTabsArray($formId, $tabs, false, $adminUserId);
}

echo "[OK] Saved layout for catalog_pages" . PHP_EOL;

exit(0);
