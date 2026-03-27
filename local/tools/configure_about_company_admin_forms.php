<?php
/**
 * Настраивает формы редактирования ИБ about_company_page и about_company_social_gallery.
 *
 * CLI:
 *   php local/tools/configure_about_company_admin_forms.php --dry-run=1
 *   php local/tools/configure_about_company_admin_forms.php --dry-run=0
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
		echo "Usage: php local/tools/configure_about_company_admin_forms.php [--admin-user-id=1] [--dry-run=1]\n";
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

echo "Admin user ID: " . $adminUserId . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function findAboutCompanyAdminFormIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function getAboutCompanyAdminFormPropertyMap($iblockId)
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

function addAboutCompanyAdminFormProperty(array &$fields, array $propertyMap, $code)
{
	if (!isset($propertyMap[$code])) {
		return;
	}

	$fields["PROPERTY_" . (int)$propertyMap[$code]["ID"]] = (string)$propertyMap[$code]["NAME"];
}

function saveAboutCompanyAdminTabs($iblockCode, array $tabs, $adminUserId, $dryRun)
{
	$iblock = findAboutCompanyAdminFormIblock($iblockCode);
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

	echo "[OK] Saved layout for " . $iblockCode . PHP_EOL;
}

$pageIblock = findAboutCompanyAdminFormIblock("about_company_page");
if (is_array($pageIblock)) {
	$pagePropertyMap = getAboutCompanyAdminFormPropertyMap((int)$pageIblock["ID"]);

	$pageMainFields = array(
		"ACTIVE" => "Активность",
		"NAME" => "Название",
		"CODE" => "Символьный код",
		"SORT" => "Сортировка",
	);

	$heroFields = $pageMainFields;
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "HERO_TEXT_1");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "HERO_TEXT_2");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "HERO_IMAGE");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "AWARD_1_LOGO");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "AWARD_1_CAPTION");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "AWARD_2_LOGO");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "AWARD_2_CAPTION");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "AWARD_3_LOGO");
	addAboutCompanyAdminFormProperty($heroFields, $pagePropertyMap, "AWARD_3_CAPTION");

	$socialFields = array();
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_INTRO_TITLE");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_INTRO_TEXT");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_METRIC_TITLE");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_METRIC_TEXT");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_METRIC_IMAGE");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_METRIC_ALT");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_MATERIAL_TITLE");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_MATERIAL_TEXT");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_MATERIAL_IMAGE");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_MATERIAL_ALT");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_PROGRESS_TITLE");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "SOCIAL_PROGRESS_TEXT");
	addAboutCompanyAdminFormProperty($socialFields, $pagePropertyMap, "PROJECTS_TITLE");

	$saleFields = array();
	addAboutCompanyAdminFormProperty($saleFields, $pagePropertyMap, "SALE_TITLE");
	addAboutCompanyAdminFormProperty($saleFields, $pagePropertyMap, "SALE_DESCRIPTION");
	addAboutCompanyAdminFormProperty($saleFields, $pagePropertyMap, "SALE_CONTACT_TITLE");
	addAboutCompanyAdminFormProperty($saleFields, $pagePropertyMap, "SALE_CONTACT_TEXT");
	addAboutCompanyAdminFormProperty($saleFields, $pagePropertyMap, "SALE_BACKGROUND_IMAGE");

	$pageTabs = array(
		"edit1" => array(
			"TAB" => "Hero",
			"FIELDS" => $heroFields,
		),
		"edit2" => array(
			"TAB" => "Социальный блок",
			"FIELDS" => $socialFields,
		),
		"edit3" => array(
			"TAB" => "Продажи",
			"FIELDS" => $saleFields,
		),
	);

	saveAboutCompanyAdminTabs("about_company_page", $pageTabs, $adminUserId, $dryRun);
}

$galleryIblock = findAboutCompanyAdminFormIblock("about_company_social_gallery");
if (is_array($galleryIblock)) {
	$galleryPropertyMap = getAboutCompanyAdminFormPropertyMap((int)$galleryIblock["ID"]);
	$galleryFields = array(
		"ACTIVE" => "Активность",
		"NAME" => "Название",
		"CODE" => "Символьный код",
		"SORT" => "Сортировка",
		"PREVIEW_PICTURE" => "Изображение",
	);
	addAboutCompanyAdminFormProperty($galleryFields, $galleryPropertyMap, "COLUMN");
	addAboutCompanyAdminFormProperty($galleryFields, $galleryPropertyMap, "LABEL");
	addAboutCompanyAdminFormProperty($galleryFields, $galleryPropertyMap, "ALT");
	addAboutCompanyAdminFormProperty($galleryFields, $galleryPropertyMap, "ITEM_HEIGHT");

	$galleryTabs = array(
		"edit1" => array(
			"TAB" => "Карточка",
			"FIELDS" => $galleryFields,
		),
	);

	saveAboutCompanyAdminTabs("about_company_social_gallery", $galleryTabs, $adminUserId, $dryRun);
}

exit(0);
