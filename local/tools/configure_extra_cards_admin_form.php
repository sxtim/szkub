<?php
/**
 * Настраивает форму редактирования элемента ИБ extra_cards в админке Bitrix.
 *
 * CLI:
 *   php local/tools/configure_extra_cards_admin_form.php --dry-run=1
 *   php local/tools/configure_extra_cards_admin_form.php --dry-run=0
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
		echo "Usage: php local/tools/configure_extra_cards_admin_form.php [--code=extra_cards] [--admin-user-id=1] [--dry-run=1]" . PHP_EOL;
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

$iblockCode = isset($_REQUEST["code"]) && $_REQUEST["code"] !== "" ? (string)$_REQUEST["code"] : "extra_cards";
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

$fields = array(
	"ACTIVE" => "Активность",
	"NAME" => "Заголовок",
	"CODE" => "Символьный код",
	"XML_ID" => "Внешний код",
	"SORT" => "Сортировка",
	"IBLOCK_ELEMENT_SECTION_ID" => "Раздел",
	"PREVIEW_PICTURE" => "Изображение",
);

if (isset($propertyMap["LINK_URL"])) {
	$fields["PROPERTY_" . (int)$propertyMap["LINK_URL"]["ID"]] = (string)$propertyMap["LINK_URL"]["NAME"];
}

$tabs = array(
	"edit1" => array(
		"TAB" => "Карточка",
		"FIELDS" => $fields,
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

exit(0);
