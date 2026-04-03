<?php
/**
 * Создает singleton-ИБ для страницы каталога проектов.
 *
 * Запуск:
 *   php local/tools/create_projects_catalog_page_iblock.php
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
		"site-id::",
		"type-id::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_projects_catalog_page_iblock.php [--site-id=s1] [--type-id=realty]\n";
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

$siteId = isset($_REQUEST["site_id"]) && $_REQUEST["site_id"] !== "" ? (string)$_REQUEST["site_id"] : "s1";
$typeId = isset($_REQUEST["type_id"]) && $_REQUEST["type_id"] !== "" ? (string)$_REQUEST["type_id"] : "realty";
$iblockCode = "projects_catalog_page";
$iblockName = "Проекты: каталог";
$elementCode = "catalog";
$elementName = "Каталог проектов";
$iblockMessages = array(
	"ELEMENTS_NAME" => "Страницы",
	"ELEMENT_NAME" => "Страница",
);
$defaultIntroHtml = <<<HTML
<p>На этой странице собраны жилые комплексы девелопера «КУБ» в Воронеже. Здесь можно посмотреть действующие и перспективные проекты компании, сравнить локации, сроки ввода, формат квартир и перейти к детальной странице интересующего ЖК.</p>
<p>Каталог помогает оценить портфель девелопера целиком: от домов, которые уже строятся, до проектов, готовящихся к выходу в продажу. Для удобства список можно отфильтровать по статусу проекта.</p>
HTML;

function projectsCatalogPageFindIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function projectsCatalogPageEnsureProperty($iblockId, array $propertyDef)
{
	$propRes = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => (int)$iblockId, "CODE" => $propertyDef["CODE"])
	);
	if ($propRes->Fetch()) {
		echo "[OK] Property exists: " . $propertyDef["CODE"] . PHP_EOL;
		return;
	}

	$property = new CIBlockProperty();
	$fields = array(
		"IBLOCK_ID" => (int)$iblockId,
		"NAME" => $propertyDef["NAME"],
		"CODE" => $propertyDef["CODE"],
		"PROPERTY_TYPE" => $propertyDef["PROPERTY_TYPE"],
		"ACTIVE" => "Y",
		"MULTIPLE" => isset($propertyDef["MULTIPLE"]) ? $propertyDef["MULTIPLE"] : "N",
		"IS_REQUIRED" => "N",
		"SORT" => $propertyDef["SORT"],
	);

	foreach (array("USER_TYPE", "WITH_DESCRIPTION", "COL_COUNT", "ROW_COUNT") as $fieldName) {
		if (array_key_exists($fieldName, $propertyDef)) {
			$fields[$fieldName] = $propertyDef[$fieldName];
		}
	}

	$newPropId = $property->Add($fields);
	if (!$newPropId) {
		echo "[ERROR] Failed to create property " . $propertyDef["CODE"] . ": " . $property->LAST_ERROR . PHP_EOL;
		exit(3);
	}

	echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . (int)$newPropId . ")" . PHP_EOL;
}

function projectsCatalogPageHtmlValue($html)
{
	return array(
		"VALUE" => array(
			"TEXT" => (string)$html,
			"TYPE" => "HTML",
		),
	);
}

function projectsCatalogPageFindElementId($iblockId, $code)
{
	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => (int)$iblockId, "=CODE" => (string)$code),
		false,
		false,
		array("ID")
	);
	if ($row = $res->Fetch()) {
		return (int)$row["ID"];
	}

	return 0;
}

function projectsCatalogPageCurrentHtml($iblockId, $elementId, $propertyCode)
{
	$property = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("sort" => "asc"),
		array("CODE" => (string)$propertyCode)
	)->Fetch();

	if (!is_array($property) || !isset($property["VALUE"])) {
		return "";
	}

	$value = $property["VALUE"];
	if (is_array($value) && isset($value["TEXT"])) {
		return trim((string)$value["TEXT"]);
	}

	return trim((string)$value);
}

$projectsIblock = projectsCatalogPageFindIblock("projects");
if (is_array($projectsIblock) && trim((string)$projectsIblock["IBLOCK_TYPE_ID"]) !== "") {
	$typeId = (string)$projectsIblock["IBLOCK_TYPE_ID"];
}

$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if (!$typeRes->Fetch()) {
	echo "IBlock type '" . $typeId . "' does not exist." . PHP_EOL;
	exit(2);
}

$iblock = projectsCatalogPageFindIblock($iblockCode);
if (is_array($iblock)) {
	$iblockId = (int)$iblock["ID"];
	echo "[OK] IBlock exists: ID=" . $iblockId . ", NAME=" . $iblock["NAME"] . PHP_EOL;
} else {
	$ib = new CIBlock();
	$iblockId = (int)$ib->Add(array(
		"SITE_ID" => array($siteId),
		"NAME" => $iblockName,
		"ACTIVE" => "Y",
		"SORT" => 125,
		"CODE" => $iblockCode,
		"IBLOCK_TYPE_ID" => $typeId,
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

	if ($iblockId <= 0) {
		echo "[ERROR] Failed to create iblock " . $iblockCode . ": " . $ib->LAST_ERROR . PHP_EOL;
		exit(3);
	}

	echo "[CREATE] IBlock created: ID=" . $iblockId . ", NAME=" . $iblockName . PHP_EOL;
}

CIBlock::SetMessages($iblockId, $iblockMessages);

projectsCatalogPageEnsureProperty($iblockId, array(
	"CODE" => "INTRO_TEXT",
	"NAME" => "Вступительный текст",
	"PROPERTY_TYPE" => "S",
	"USER_TYPE" => "HTML",
	"WITH_DESCRIPTION" => "N",
	"COL_COUNT" => 70,
	"ROW_COUNT" => 12,
	"SORT" => 100,
	"MULTIPLE" => "N",
));

$elementId = projectsCatalogPageFindElementId($iblockId, $elementCode);
$currentIntroHtml = $elementId > 0 ? projectsCatalogPageCurrentHtml($iblockId, $elementId, "INTRO_TEXT") : "";
$propertyValues = array(
	"INTRO_TEXT" => projectsCatalogPageHtmlValue($currentIntroHtml !== "" ? $currentIntroHtml : $defaultIntroHtml),
);
$elementFields = array(
	"IBLOCK_ID" => $iblockId,
	"ACTIVE" => "Y",
	"NAME" => $elementName,
	"CODE" => $elementCode,
	"SORT" => 100,
);

$elementApi = new CIBlockElement();
if ($elementId > 0) {
	if (!$elementApi->Update($elementId, $elementFields)) {
		echo "[ERROR] Failed to update element " . $elementCode . ": " . $elementApi->LAST_ERROR . PHP_EOL;
		exit(4);
	}
	CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $propertyValues);
	echo "[OK] Element updated: " . $elementCode . " (ID=" . $elementId . ")" . PHP_EOL;
} else {
	$elementId = (int)$elementApi->Add($elementFields);
	if ($elementId <= 0) {
		echo "[ERROR] Failed to create element " . $elementCode . ": " . $elementApi->LAST_ERROR . PHP_EOL;
		exit(4);
	}
	CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $propertyValues);
	echo "[CREATE] Element created: " . $elementCode . " (ID=" . $elementId . ")" . PHP_EOL;
}

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "- projects_catalog_page ID=" . $iblockId . PHP_EOL;
echo "- catalog element ID=" . $elementId . PHP_EOL;

exit(0);
