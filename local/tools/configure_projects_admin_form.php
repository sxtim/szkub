<?php
/**
 * Настраивает форму редактирования элемента ИБ projects в админке Bitrix.
 *
 * CLI:
 *   php local/tools/configure_projects_admin_form.php --dry-run=1
 *   php local/tools/configure_projects_admin_form.php --dry-run=0
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
		"admin-user-id::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/configure_projects_admin_form.php [--code=projects] [--admin-user-id=1] [--dry-run=1]\n";
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

if (!class_exists("\\Bitrix\\Main\\Loader")) {
	echo "Bitrix Loader class is unavailable" . PHP_EOL;
	exit(1);
}

if (!\Bitrix\Main\Loader::includeModule("iblock")) {
	echo "Failed to load iblock module" . PHP_EOL;
	exit(1);
}

$iblockCode = isset($_REQUEST["code"]) && $_REQUEST["code"] !== "" ? (string)$_REQUEST["code"] : "projects";
$adminUserId = isset($_REQUEST["admin_user_id"]) ? (int)$_REQUEST["admin_user_id"] : 1;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "IBlock code: " . $iblockCode . PHP_EOL;
echo "Admin user ID: " . $adminUserId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function findIblockByCodeForProjectsAdminForm($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function getPropertyMapForProjectsAdminForm($iblockId)
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

function addPropertyFieldForProjectsAdminForm(array &$fields, array $propertyMap, $code)
{
	if (!isset($propertyMap[$code])) {
		return;
	}

	$fields["PROPERTY_" . (int)$propertyMap[$code]["ID"]] = (string)$propertyMap[$code]["NAME"];
}

function addPropertyFieldWithCustomLabelForProjectsAdminForm(array &$fields, array $propertyMap, $code, $label)
{
	if (!isset($propertyMap[$code])) {
		return;
	}

	$fields["PROPERTY_" . (int)$propertyMap[$code]["ID"]] = (string)$label;
}

$iblock = findIblockByCodeForProjectsAdminForm($iblockCode);
if (!is_array($iblock)) {
	echo "[ERROR] IBlock not found by code: " . $iblockCode . PHP_EOL;
	exit(2);
}

$iblockId = (int)$iblock["ID"];
$formId = "form_element_" . $iblockId;
$propertyMap = getPropertyMapForProjectsAdminForm($iblockId);

$mainFields = array(
	"ACTIVE" => "Активность",
);
addPropertyFieldWithCustomLabelForProjectsAdminForm($mainFields, $propertyMap, "ABOUT_COMPANY_STATUS", "Статус проекта");
addPropertyFieldWithCustomLabelForProjectsAdminForm($mainFields, $propertyMap, "HOME_SHOW", "Показывать на главной");
$mainFields += array(
	"NAME" => "Название",
	"CODE" => "Символьный код",
	"XML_ID" => "Внешний код",
	"SORT" => "Сортировка",
	"ACTIVE_FROM" => "Дата активности",
	"PREVIEW_PICTURE" => "Картинка превью",
);

$catalogFields = array();
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "CLASS_LABEL");
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "TAG_LABEL");
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "ADDRESS");
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "DELIVERY_TEXT");
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "ROOMS_IN_SALE");
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "SALE_COUNT_TEXT");
addPropertyFieldForProjectsAdminForm($catalogFields, $propertyMap, "PRICE_FROM_TEXT");

$projectDetailFields = array();
addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_IMAGE");
addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_TITLE_SUFFIX");
addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_TEXT_1");
addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_TEXT_2");
addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_TEXT_3");
for ($i = 1; $i <= 4; $i++) {
	addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_F" . $i . "_LABEL");
	addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "ABOUT_F" . $i . "_VALUE");
}
addPropertyFieldForProjectsAdminForm($projectDetailFields, $propertyMap, "CONSTRUCTION_SUBTITLE");

$aboutCompanyFields = array();
addPropertyFieldForProjectsAdminForm($aboutCompanyFields, $propertyMap, "ABOUT_COMPANY_SHOW");
addPropertyFieldForProjectsAdminForm($aboutCompanyFields, $propertyMap, "ABOUT_COMPANY_SALE_SHOW");
addPropertyFieldForProjectsAdminForm($aboutCompanyFields, $propertyMap, "ABOUT_COMPANY_IMAGE");
addPropertyFieldForProjectsAdminForm($aboutCompanyFields, $propertyMap, "ABOUT_COMPANY_TEXT_1");
addPropertyFieldForProjectsAdminForm($aboutCompanyFields, $propertyMap, "ABOUT_COMPANY_TEXT_2");

$tabs = array(
	"edit1" => array(
		"TAB" => "Основное",
		"FIELDS" => $mainFields,
	),
	"edit2" => array(
		"TAB" => "Каталог",
		"FIELDS" => $catalogFields,
	),
	"edit3" => array(
		"TAB" => "Страница ЖК",
		"FIELDS" => $projectDetailFields,
	),
	"edit4" => array(
		"TAB" => "О компании",
		"FIELDS" => $aboutCompanyFields,
	),
);

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

exit(0);
