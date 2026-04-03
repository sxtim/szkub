<?php
/**
 * Настраивает форму редактирования singleton-ИБ page_maps.
 *
 * CLI:
 *   php local/tools/configure_page_maps_admin_form.php --dry-run=1
 *   php local/tools/configure_page_maps_admin_form.php --dry-run=0
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
		echo "Usage: php local/tools/configure_page_maps_admin_form.php [--admin-user-id=1] [--dry-run=1]\n";
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

$adminUserId = isset($_REQUEST["admin_user_id"]) ? (int)$_REQUEST["admin_user_id"] : 1;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

function pageMapsAdminFindIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function pageMapsAdminPropertyMap($iblockId)
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

$iblock = pageMapsAdminFindIblock("page_maps");
if (!is_array($iblock)) {
	echo "[ERROR] IBlock not found by code: page_maps" . PHP_EOL;
	exit(2);
}

$iblockId = (int)$iblock["ID"];
$formId = "form_element_" . $iblockId;
$propertyMap = pageMapsAdminPropertyMap($iblockId);

$fields = array(
	"ACTIVE" => "Активность",
	"NAME" => "Название",
	"CODE" => "Символьный код",
	"SORT" => "Сортировка",
);

if (isset($propertyMap["MAP_EMBED"])) {
	$fields["PROPERTY_" . (int)$propertyMap["MAP_EMBED"]["ID"]] = "Карта: embed-код";
}

$tabs = array(
	"edit1" => array(
		"TAB" => "Карта",
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

echo "[OK] Saved layout for page_maps" . PHP_EOL;

exit(0);
