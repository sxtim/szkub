<?php
/**
 * Создает singleton-ИБ для HTML-кодов карт страниц:
 * - home
 * - projects
 *
 * CLI:
 *   php local/tools/create_page_maps_iblock.php
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
		"iblock-code::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_page_maps_iblock.php [--site-id=s1] [--type-id=content] [--iblock-code=page_maps]\n";
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
$typeId = isset($_REQUEST["type_id"]) && $_REQUEST["type_id"] !== "" ? (string)$_REQUEST["type_id"] : "content";
$iblockCode = isset($_REQUEST["iblock_code"]) && $_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "page_maps";
$iblockName = "Карты: страницы";

function pageMapsFindIblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function pageMapsEnsureProperty($iblockId, array $propertyDef)
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

function pageMapsHtmlPropertyValue($html)
{
	return array(
		"VALUE" => array(
			"TEXT" => (string)$html,
			"TYPE" => "HTML",
		),
	);
}

function pageMapsFindElementId($iblockId, $code)
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

function pageMapsCurrentMapValue($iblockId, $elementId)
{
	$property = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("sort" => "asc"),
		array("CODE" => "MAP_EMBED")
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

function pageMapsProjectElements()
{
	$result = array();
	$projectsIblock = CIBlock::GetList(array(), array("=CODE" => "projects"), false)->Fetch();
	if (!is_array($projectsIblock)) {
		return $result;
	}

	$projectRes = CIBlockElement::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array(
			"IBLOCK_ID" => (int)$projectsIblock["ID"],
			"ACTIVE" => "Y",
		),
		false,
		false,
		array("ID", "NAME", "CODE", "SORT")
	);

	$sort = 300;
	while ($project = $projectRes->Fetch()) {
		$code = trim((string)$project["CODE"]);
		if ($code === "") {
			continue;
		}

		$result[] = array(
			"NAME" => "ЖК: " . trim((string)$project["NAME"]),
			"CODE" => "project-" . mb_strtolower($code),
			"SORT" => $sort,
			"PROPERTY_VALUES" => array(
				"MAP_EMBED" => pageMapsHtmlPropertyValue(""),
			),
		);
		$sort += 10;
	}

	return $result;
}

$typeExists = false;
$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if ($typeRes->Fetch()) {
	$typeExists = true;
}
if (!$typeExists) {
	echo "IBlock type '" . $typeId . "' does not exist." . PHP_EOL;
	exit(2);
}

$iblockId = 0;
$iblock = pageMapsFindIblockByCode($iblockCode);
if (is_array($iblock)) {
	$iblockId = (int)$iblock["ID"];
	echo "[OK] IBlock exists: ID=" . $iblockId . ", NAME=" . $iblock["NAME"] . PHP_EOL;
} else {
	$ib = new CIBlock();
	$newId = $ib->Add(array(
		"SITE_ID" => array($siteId),
		"NAME" => $iblockName,
		"ACTIVE" => "Y",
		"SORT" => 540,
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

	if (!$newId) {
		echo "[ERROR] Failed to create iblock " . $iblockCode . ": " . $ib->LAST_ERROR . PHP_EOL;
		exit(3);
	}

	$iblockId = (int)$newId;
	echo "[CREATE] IBlock created: ID=" . $iblockId . ", NAME=" . $iblockName . PHP_EOL;
}

pageMapsEnsureProperty($iblockId, array(
	"CODE" => "MAP_EMBED",
	"NAME" => "Карта: embed-код",
	"PROPERTY_TYPE" => "S",
	"USER_TYPE" => "HTML",
	"WITH_DESCRIPTION" => "N",
	"COL_COUNT" => 70,
	"ROW_COUNT" => 18,
	"SORT" => 100,
	"MULTIPLE" => "N",
));

$defaultHomeMap = <<<'HTML'
<script
  type="text/javascript"
  charset="utf-8"
  async
  src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A63d5c308a514e6051740664513673798031dff7881c5e77acadec4c223fd286f&width=100%25&height=500&lang=ru_RU&scroll=false"
></script>
HTML;

$elements = array(
	array(
		"NAME" => "Главная",
		"CODE" => "home",
		"SORT" => 100,
		"PROPERTY_VALUES" => array(
			"MAP_EMBED" => pageMapsHtmlPropertyValue($defaultHomeMap),
		),
	),
	array(
		"NAME" => "Проекты",
		"CODE" => "projects",
		"SORT" => 200,
		"PROPERTY_VALUES" => array(
			"MAP_EMBED" => pageMapsHtmlPropertyValue(""),
		),
	),
);

$elements = array_merge($elements, pageMapsProjectElements());

$elementApi = new CIBlockElement();
foreach ($elements as $elementFields) {
	$elementId = pageMapsFindElementId($iblockId, $elementFields["CODE"]);
	$propertyValues = $elementFields["PROPERTY_VALUES"];
	if ($elementId > 0) {
		$currentMapValue = pageMapsCurrentMapValue($iblockId, $elementId);
		if ($currentMapValue !== "") {
			$propertyValues["MAP_EMBED"] = pageMapsHtmlPropertyValue($currentMapValue);
		}
	}

	$fields = array(
		"IBLOCK_ID" => $iblockId,
		"ACTIVE" => "Y",
		"NAME" => $elementFields["NAME"],
		"CODE" => $elementFields["CODE"],
		"SORT" => $elementFields["SORT"],
		"PROPERTY_VALUES" => $propertyValues,
	);

	if ($elementId > 0) {
		if (!$elementApi->Update($elementId, $fields)) {
			echo "[ERROR] Failed to update element " . $elementFields["CODE"] . ": " . $elementApi->LAST_ERROR . PHP_EOL;
			exit(4);
		}
		CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $propertyValues);
		echo "[OK] Element updated: " . $elementFields["CODE"] . " (ID=" . $elementId . ")" . PHP_EOL;
		continue;
	}

	$newElementId = $elementApi->Add($fields);
	if (!$newElementId) {
		echo "[ERROR] Failed to create element " . $elementFields["CODE"] . ": " . $elementApi->LAST_ERROR . PHP_EOL;
		exit(6);
	}

	echo "[CREATE] Element created: " . $elementFields["CODE"] . " (ID=" . (int)$newElementId . ")" . PHP_EOL;
}

echo PHP_EOL;
echo "- page_maps ID=" . $iblockId . PHP_EOL;

exit(0);
