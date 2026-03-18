<?php
/**
 * Настраивает форму редактирования элемента ИБ apartments в админке Bitrix.
 *
 * Оставляет одну длинную форму без лишних служебных полей.
 *
 * CLI:
 *   php local/tools/configure_apartments_admin_form.php --dry-run=1
 *   php local/tools/configure_apartments_admin_form.php --dry-run=0
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
		echo "Usage: php local/tools/configure_apartments_admin_form.php [--code=apartments] [--admin-user-id=1] [--dry-run=1]\n";
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

$iblockCode = isset($_REQUEST["code"]) && $_REQUEST["code"] !== "" ? (string)$_REQUEST["code"] : "apartments";
$adminUserId = isset($_REQUEST["admin_user_id"]) ? (int)$_REQUEST["admin_user_id"] : 1;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "IBlock code: " . $iblockCode . PHP_EOL;
echo "Admin user ID: " . $adminUserId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function findIblockByCodeForAdminForm($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function getPropertyMapForAdminForm($iblockId)
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

function propertyFieldIdForAdminForm(array $propertyMap, $code)
{
	return isset($propertyMap[$code]) ? "PROPERTY_" . (int)$propertyMap[$code]["ID"] : null;
}

function addPropertyFieldForAdminForm(array &$fields, array $propertyMap, $code)
{
	if (!isset($propertyMap[$code])) {
		return;
	}

	$fields["PROPERTY_" . (int)$propertyMap[$code]["ID"]] = (string)$propertyMap[$code]["NAME"];
}

$iblock = findIblockByCodeForAdminForm($iblockCode);
if (!is_array($iblock)) {
	echo "[ERROR] IBlock not found by code: " . $iblockCode . PHP_EOL;
	exit(2);
}

$iblockId = (int)$iblock["ID"];
$formId = "form_element_" . $iblockId;
$propertyMap = getPropertyMapForAdminForm($iblockId);

$mainFields = array(
	"ACTIVE" => "Активность",
	"NAME" => "Название",
	"CODE" => "Символьный код",
	"XML_ID" => "Внешний код",
);
$mainFields["IBLOCK_ELEMENT_SECTION_ID"] = "Основной раздел";
$mainFields["SORT"] = "Сортировка";
addPropertyFieldForAdminForm($mainFields, $propertyMap, "PROJECT");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "CORPUS");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "ENTRANCE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "FLOOR");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "FLOOR_TO");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "HOUSE_FLOORS");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "APARTMENT_NUMBER");

addPropertyFieldForAdminForm($mainFields, $propertyMap, "PRICE_TOTAL");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "PRICE_OLD");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "PRICE_M2");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "STATUS");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "BADGES");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "ROOMS");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "AREA_TOTAL");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "AREA_LIVING");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "AREA_KITCHEN");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "FINISH");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "CEILING");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "VIEW_TEXT");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "WINDOW_SIDES");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "BALCONY_TYPE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "BATHROOMS");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "FEATURE_TAGS");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "PLAN_IMAGE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "PLAN_ALT");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "FLOOR_SLIDE_IMAGE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "FLOOR_SLIDE_ALT");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "BUILDING_SLIDE_IMAGE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "BUILDING_SLIDE_ALT");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "VIEW_SLIDE_IMAGE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "VIEW_SLIDE_ALT");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "RENDER_SLIDE_IMAGE");
addPropertyFieldForAdminForm($mainFields, $propertyMap, "RENDER_SLIDE_ALT");

$tabs = array(
	"edit1" => array(
		"TAB" => "Элемент",
		"FIELDS" => $mainFields,
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
