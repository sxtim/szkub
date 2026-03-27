<?php
/**
 * Создает (или проверяет) инфоблоки:
 * - about_company_page
 * - about_company_social_gallery
 *
 * Запуск:
 *   php local/tools/create_about_company_iblocks.php
 */

@set_time_limit(0);

$_SERVER["DOCUMENT_ROOT"] = isset($_SERVER["DOCUMENT_ROOT"]) && $_SERVER["DOCUMENT_ROOT"] !== ""
	? rtrim((string)$_SERVER["DOCUMENT_ROOT"], "/")
	: rtrim(dirname(__DIR__, 2), "/");

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);

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

$siteId = "s1";
$iblockType = "content";
$projectsIblockCode = "projects";

function findAboutCompanyIblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function ensureAboutCompanyIblock($siteId, $type, $code, $name)
{
	$existing = findAboutCompanyIblockByCode($code);
	if (is_array($existing)) {
		echo "[OK] IBlock exists: {$name} (ID=" . (int)$existing["ID"] . ", CODE={$code})" . PHP_EOL;
		return (int)$existing["ID"];
	}

	$ib = new CIBlock();
	$newId = (int)$ib->Add(array(
		"SITE_ID" => array($siteId),
		"NAME" => $name,
		"ACTIVE" => "Y",
		"SORT" => 520,
		"CODE" => $code,
		"IBLOCK_TYPE_ID" => $type,
		"SECTIONS" => "N",
		"GROUP_ID" => array("2" => "R"),
		"VERSION" => 2,
		"FIELDS" => array(
			"CODE" => array(
				"DEFAULT_VALUE" => array(
					"TRANSLITERATION" => "Y",
					"TRANS_LEN" => 100,
					"UNIQUE" => "Y",
					"TRANS_CASE" => "L",
					"TRANS_SPACE" => "-",
					"TRANS_OTHER" => "-",
					"TRANS_EAT" => "Y",
					"USE_GOOGLE" => "N",
				),
			),
		),
	));

	if ($newId <= 0) {
		echo "[ERROR] Failed to create iblock {$code}: " . $ib->LAST_ERROR . PHP_EOL;
		exit(2);
	}

	echo "[CREATE] IBlock created: {$name} (ID={$newId}, CODE={$code})" . PHP_EOL;
	return $newId;
}

function ensureAboutCompanyProperty($iblockId, array $propertyDef)
{
	$propRes = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $iblockId, "CODE" => $propertyDef["CODE"])
	);
	if ($propRes->Fetch()) {
		echo "[OK] Property exists: {$propertyDef["CODE"]}" . PHP_EOL;
		return;
	}

	$property = new CIBlockProperty();
	$fields = array(
		"IBLOCK_ID" => $iblockId,
		"NAME" => $propertyDef["NAME"],
		"CODE" => $propertyDef["CODE"],
		"PROPERTY_TYPE" => $propertyDef["PROPERTY_TYPE"],
		"ACTIVE" => "Y",
		"MULTIPLE" => isset($propertyDef["MULTIPLE"]) ? $propertyDef["MULTIPLE"] : "N",
		"IS_REQUIRED" => "N",
		"SORT" => $propertyDef["SORT"],
	);

	$optionalFields = array(
		"LIST_TYPE",
		"USER_TYPE",
		"USER_TYPE_SETTINGS",
		"WITH_DESCRIPTION",
		"MULTIPLE_CNT",
		"HINT",
	);
	foreach ($optionalFields as $fieldName) {
		if (array_key_exists($fieldName, $propertyDef)) {
			$fields[$fieldName] = $propertyDef[$fieldName];
		}
	}
	if (isset($propertyDef["VALUES"]) && is_array($propertyDef["VALUES"])) {
		$fields["VALUES"] = $propertyDef["VALUES"];
	}

	$newPropId = $property->Add($fields);
	if (!$newPropId) {
		echo "[ERROR] Failed to create property {$propertyDef["CODE"]}: " . $property->LAST_ERROR . PHP_EOL;
		exit(3);
	}

	echo "[CREATE] Property created: {$propertyDef["CODE"]} (ID={$newPropId})" . PHP_EOL;
}

$projectsIblock = findAboutCompanyIblockByCode($projectsIblockCode);
if (is_array($projectsIblock) && (string)$projectsIblock["IBLOCK_TYPE_ID"] !== "") {
	$iblockType = (string)$projectsIblock["IBLOCK_TYPE_ID"];
}
echo "IBlock type: {$iblockType}" . PHP_EOL;

$pageIblockId = ensureAboutCompanyIblock($siteId, $iblockType, "about_company_page", "О компании: страница");
$galleryIblockId = ensureAboutCompanyIblock($siteId, $iblockType, "about_company_social_gallery", "О компании: галерея");

$pageProperties = array(
	array("CODE" => "HERO_TEXT_1", "NAME" => "Hero: текст, абзац 1", "PROPERTY_TYPE" => "S", "SORT" => 100),
	array("CODE" => "HERO_TEXT_2", "NAME" => "Hero: текст, абзац 2", "PROPERTY_TYPE" => "S", "SORT" => 110),
	array("CODE" => "HERO_IMAGE", "NAME" => "Hero: фото", "PROPERTY_TYPE" => "F", "SORT" => 120),
	array("CODE" => "AWARD_1_LOGO", "NAME" => "Награда 1: логотип (текст)", "PROPERTY_TYPE" => "S", "SORT" => 130),
	array("CODE" => "AWARD_1_CAPTION", "NAME" => "Награда 1: подпись", "PROPERTY_TYPE" => "S", "SORT" => 131),
	array("CODE" => "AWARD_2_LOGO", "NAME" => "Награда 2: логотип (текст)", "PROPERTY_TYPE" => "S", "SORT" => 132),
	array("CODE" => "AWARD_2_CAPTION", "NAME" => "Награда 2: подпись", "PROPERTY_TYPE" => "S", "SORT" => 133),
	array("CODE" => "AWARD_3_LOGO", "NAME" => "Награда 3: логотип (текст)", "PROPERTY_TYPE" => "S", "SORT" => 134),
	array("CODE" => "AWARD_3_CAPTION", "NAME" => "Награда 3: подпись", "PROPERTY_TYPE" => "S", "SORT" => 135),
	array("CODE" => "SOCIAL_INTRO_TITLE", "NAME" => "Соцблок: левый заголовок", "PROPERTY_TYPE" => "S", "SORT" => 200),
	array("CODE" => "SOCIAL_INTRO_TEXT", "NAME" => "Соцблок: левый текст", "PROPERTY_TYPE" => "S", "SORT" => 210),
	array("CODE" => "SOCIAL_METRIC_TITLE", "NAME" => "Соцблок: верхняя карточка заголовок", "PROPERTY_TYPE" => "S", "SORT" => 220),
	array("CODE" => "SOCIAL_METRIC_TEXT", "NAME" => "Соцблок: верхняя карточка текст", "PROPERTY_TYPE" => "S", "SORT" => 221),
	array("CODE" => "SOCIAL_METRIC_IMAGE", "NAME" => "Соцблок: верхняя карточка фото", "PROPERTY_TYPE" => "F", "SORT" => 222),
	array("CODE" => "SOCIAL_METRIC_ALT", "NAME" => "Соцблок: верхняя карточка alt", "PROPERTY_TYPE" => "S", "SORT" => 223),
	array("CODE" => "SOCIAL_MATERIAL_TITLE", "NAME" => "Соцблок: правая карточка заголовок", "PROPERTY_TYPE" => "S", "SORT" => 230),
	array("CODE" => "SOCIAL_MATERIAL_TEXT", "NAME" => "Соцблок: правая карточка текст", "PROPERTY_TYPE" => "S", "SORT" => 231),
	array("CODE" => "SOCIAL_MATERIAL_IMAGE", "NAME" => "Соцблок: правая карточка фото", "PROPERTY_TYPE" => "F", "SORT" => 232),
	array("CODE" => "SOCIAL_MATERIAL_ALT", "NAME" => "Соцблок: правая карточка alt", "PROPERTY_TYPE" => "S", "SORT" => 233),
	array("CODE" => "SOCIAL_PROGRESS_TITLE", "NAME" => "Соцблок: нижний заголовок", "PROPERTY_TYPE" => "S", "SORT" => 240),
	array("CODE" => "SOCIAL_PROGRESS_TEXT", "NAME" => "Соцблок: нижний текст", "PROPERTY_TYPE" => "S", "SORT" => 241),
	array("CODE" => "PROJECTS_TITLE", "NAME" => "Наши проекты: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 300),
	array("CODE" => "SALE_TITLE", "NAME" => "Продажи: заголовок", "PROPERTY_TYPE" => "S", "SORT" => 310),
	array("CODE" => "SALE_DESCRIPTION", "NAME" => "Продажи: описание", "PROPERTY_TYPE" => "S", "SORT" => 311),
	array("CODE" => "SALE_CONTACT_TITLE", "NAME" => "Продажи: заголовок формы", "PROPERTY_TYPE" => "S", "SORT" => 320),
	array("CODE" => "SALE_CONTACT_TEXT", "NAME" => "Продажи: текст формы", "PROPERTY_TYPE" => "S", "SORT" => 321),
	array("CODE" => "SALE_BACKGROUND_IMAGE", "NAME" => "Продажи: фоновое фото", "PROPERTY_TYPE" => "F", "SORT" => 322),
);

foreach ($pageProperties as $propertyDef) {
	ensureAboutCompanyProperty($pageIblockId, $propertyDef);
}

$galleryProperties = array(
	array(
		"CODE" => "COLUMN",
		"NAME" => "Колонка",
		"PROPERTY_TYPE" => "L",
		"LIST_TYPE" => "L",
		"SORT" => 100,
		"VALUES" => array(
			array("VALUE" => "Левая", "XML_ID" => "left", "SORT" => 100),
			array("VALUE" => "Правая", "XML_ID" => "right", "SORT" => 200),
		),
	),
	array("CODE" => "LABEL", "NAME" => "Текст на карточке", "PROPERTY_TYPE" => "S", "SORT" => 110),
	array("CODE" => "ALT", "NAME" => "Alt изображения", "PROPERTY_TYPE" => "S", "SORT" => 120),
	array("CODE" => "ITEM_HEIGHT", "NAME" => "Высота карточки", "PROPERTY_TYPE" => "N", "SORT" => 130),
);

foreach ($galleryProperties as $propertyDef) {
	ensureAboutCompanyProperty($galleryIblockId, $propertyDef);
}

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "- about_company_page ID={$pageIblockId}" . PHP_EOL;
echo "- about_company_social_gallery ID={$galleryIblockId}" . PHP_EOL;

exit(0);
