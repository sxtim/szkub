<?php
/**
 * Настраивает формы редактирования ИБ:
 * - purchase_pages
 * - purchase_page_cards
 * - mortgage_calculator_programs
 * - mortgage_calculator_banks
 *
 * CLI:
 *   php local/tools/configure_purchase_content_admin_forms.php --dry-run=1
 *   php local/tools/configure_purchase_content_admin_forms.php --dry-run=0
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
        echo "Usage: php local/tools/configure_purchase_content_admin_forms.php [--admin-user-id=1] [--dry-run=1]" . PHP_EOL;
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

echo "Admin user ID: " . $adminUserId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function purchaseContentAdminFindIblock($code)
{
    $res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
    if ($row = $res->Fetch()) {
        return $row;
    }

    return null;
}

function purchaseContentAdminPropertyMap($iblockId)
{
    $result = array();
    $res = CIBlockProperty::GetList(
        array("SORT" => "ASC", "ID" => "ASC"),
        array("IBLOCK_ID" => (int)$iblockId)
    );
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

function purchaseContentAdminAddPropertyField(array &$fields, array $propertyMap, $code)
{
    if (!isset($propertyMap[$code])) {
        return;
    }

    $fields["PROPERTY_" . (int)$propertyMap[$code]["ID"]] = (string)$propertyMap[$code]["NAME"];
}

function purchaseContentAdminSaveTabs($iblockCode, array $tabs, $adminUserId, $dryRun)
{
    $iblock = purchaseContentAdminFindIblock($iblockCode);
    if (!is_array($iblock)) {
        echo "[WARN] IBlock not found by code: " . $iblockCode . PHP_EOL;
        return;
    }

    $formId = "form_element_" . (int)$iblock["ID"];
    echo "[FORM] " . $formId . PHP_EOL;
    foreach ($tabs as $tabId => $tab) {
        echo "[TAB] " . $tabId . " :: " . $tab["TAB"] . " :: fields=" . count($tab["FIELDS"]) . PHP_EOL;
    }

    if ($dryRun) {
        return;
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
}

$purchasePagesIblock = purchaseContentAdminFindIblock("purchase_pages");
if (is_array($purchasePagesIblock)) {
    $propertyMap = purchaseContentAdminPropertyMap((int)$purchasePagesIblock["ID"]);

    $mainFields = array(
        "ACTIVE" => "Активность",
        "NAME" => "Название вкладки",
        "CODE" => "Символьный код",
        "SORT" => "Сортировка",
    );
    purchaseContentAdminAddPropertyField($mainFields, $propertyMap, "PAGE_URL");

    $heroFields = array();
    purchaseContentAdminAddPropertyField($heroFields, $propertyMap, "HERO_TITLE");
    purchaseContentAdminAddPropertyField($heroFields, $propertyMap, "HERO_TEXT_1");
    purchaseContentAdminAddPropertyField($heroFields, $propertyMap, "HERO_TEXT_2");
    purchaseContentAdminAddPropertyField($heroFields, $propertyMap, "HERO_IMAGE");
    purchaseContentAdminAddPropertyField($heroFields, $propertyMap, "HERO_IMAGE_ALT");

    $ctaFields = array();
    purchaseContentAdminAddPropertyField($ctaFields, $propertyMap, "PRIMARY_BUTTON_LABEL");
    purchaseContentAdminAddPropertyField($ctaFields, $propertyMap, "PRIMARY_BUTTON_TITLE");
    purchaseContentAdminAddPropertyField($ctaFields, $propertyMap, "PRIMARY_BUTTON_NOTE");
    purchaseContentAdminAddPropertyField($ctaFields, $propertyMap, "PRIMARY_BUTTON_SOURCE");
    purchaseContentAdminAddPropertyField($ctaFields, $propertyMap, "SECONDARY_BUTTON_LABEL");
    purchaseContentAdminAddPropertyField($ctaFields, $propertyMap, "SECONDARY_BUTTON_URL");

    $calculatorFields = array();
    purchaseContentAdminAddPropertyField($calculatorFields, $propertyMap, "SHOW_CALCULATOR");
    purchaseContentAdminAddPropertyField($calculatorFields, $propertyMap, "CALCULATOR_TITLE");
    purchaseContentAdminAddPropertyField($calculatorFields, $propertyMap, "CALCULATOR_SUBTITLE");

    $tabs = array(
        "edit1" => array(
            "TAB" => "Основное",
            "FIELDS" => $mainFields,
        ),
        "edit2" => array(
            "TAB" => "Hero",
            "FIELDS" => $heroFields,
        ),
        "edit3" => array(
            "TAB" => "Действия",
            "FIELDS" => $ctaFields,
        ),
        "edit4" => array(
            "TAB" => "Калькулятор",
            "FIELDS" => $calculatorFields,
        ),
    );

    purchaseContentAdminSaveTabs("purchase_pages", $tabs, $adminUserId, $dryRun);
}

$cardsIblock = purchaseContentAdminFindIblock("purchase_page_cards");
if (is_array($cardsIblock)) {
    $propertyMap = purchaseContentAdminPropertyMap((int)$cardsIblock["ID"]);

    $fields = array(
        "ACTIVE" => "Активность",
        "IBLOCK_ELEMENT_SECTION_ID" => "Страница",
        "NAME" => "Заголовок",
        "CODE" => "Символьный код",
        "SORT" => "Сортировка",
        "PREVIEW_PICTURE" => "Изображение",
        "PREVIEW_TEXT" => "Текст",
    );
    purchaseContentAdminAddPropertyField($fields, $propertyMap, "CARD_LAYOUT");
    purchaseContentAdminAddPropertyField($fields, $propertyMap, "IMAGE_ALT");

    $tabs = array(
        "edit1" => array(
            "TAB" => "Карточка",
            "FIELDS" => $fields,
        ),
    );

    purchaseContentAdminSaveTabs("purchase_page_cards", $tabs, $adminUserId, $dryRun);
}

$programsIblock = purchaseContentAdminFindIblock("mortgage_calculator_programs");
if (is_array($programsIblock)) {
    $propertyMap = purchaseContentAdminPropertyMap((int)$programsIblock["ID"]);
    $fields = array(
        "ACTIVE" => "Активность",
        "NAME" => "Название",
        "CODE" => "Символьный код",
        "SORT" => "Сортировка",
    );
    purchaseContentAdminAddPropertyField($fields, $propertyMap, "RATE");

    $tabs = array(
        "edit1" => array(
            "TAB" => "Программа",
            "FIELDS" => $fields,
        ),
    );

    purchaseContentAdminSaveTabs("mortgage_calculator_programs", $tabs, $adminUserId, $dryRun);
}

$banksIblock = purchaseContentAdminFindIblock("mortgage_calculator_banks");
if (is_array($banksIblock)) {
    $propertyMap = purchaseContentAdminPropertyMap((int)$banksIblock["ID"]);
    $fields = array(
        "ACTIVE" => "Активность",
        "NAME" => "Название",
        "CODE" => "Символьный код",
        "SORT" => "Сортировка",
    );
    purchaseContentAdminAddPropertyField($fields, $propertyMap, "MARK");
    purchaseContentAdminAddPropertyField($fields, $propertyMap, "TONE_COLOR");
    purchaseContentAdminAddPropertyField($fields, $propertyMap, "ACCENT_COLOR");

    $tabs = array(
        "edit1" => array(
            "TAB" => "Банк",
            "FIELDS" => $fields,
        ),
    );

    purchaseContentAdminSaveTabs("mortgage_calculator_banks", $tabs, $adminUserId, $dryRun);
}

exit(0);
