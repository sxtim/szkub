<?php
/**
 * Создает (или проверяет) ИБ "Кроме квартир" (extra_cards).
 *
 * CLI:
 *   php local/tools/create_extra_cards_iblock.php
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
		"projects-code::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_extra_cards_iblock.php [--site-id=s1] [--type-id=content] [--projects-code=projects]" . PHP_EOL;
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

$siteId = isset($_REQUEST["site_id"]) && $_REQUEST["site_id"] !== "" ? (string)$_REQUEST["site_id"] : "s1";
$typeId = isset($_REQUEST["type_id"]) && $_REQUEST["type_id"] !== "" ? (string)$_REQUEST["type_id"] : "content";
$projectsCode = isset($_REQUEST["projects_code"]) && $_REQUEST["projects_code"] !== "" ? (string)$_REQUEST["projects_code"] : "projects";
$iblockCode = "extra_cards";
$iblockName = "Кроме квартир";

function extraCardsFindIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function extraCardsEnsureProperty($iblockId, array $propertyDef)
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
		"SORT" => isset($propertyDef["SORT"]) ? (int)$propertyDef["SORT"] : 500,
		"COL_COUNT" => isset($propertyDef["COL_COUNT"]) ? (int)$propertyDef["COL_COUNT"] : 0,
		"ROW_COUNT" => isset($propertyDef["ROW_COUNT"]) ? (int)$propertyDef["ROW_COUNT"] : 0,
	);

	$newPropId = $property->Add($fields);
	if (!$newPropId) {
		echo "[ERROR] Failed to create property " . $propertyDef["CODE"] . ": " . $property->LAST_ERROR . PHP_EOL;
		exit(3);
	}

	echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . (int)$newPropId . ")" . PHP_EOL;
}

$projectsIblock = extraCardsFindIblock($projectsCode);
if (is_array($projectsIblock) && trim((string)$projectsIblock["IBLOCK_TYPE_ID"]) !== "") {
	$typeId = (string)$projectsIblock["IBLOCK_TYPE_ID"];
}

$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if (!$typeRes->Fetch()) {
	echo "IBlock type '" . $typeId . "' does not exist." . PHP_EOL;
	exit(2);
}

$iblock = extraCardsFindIblock($iblockCode);
if (is_array($iblock)) {
	$iblockId = (int)$iblock["ID"];
	echo "[OK] IBlock exists: ID=" . $iblockId . ", NAME=" . $iblock["NAME"] . ", TYPE=" . $iblock["IBLOCK_TYPE_ID"] . PHP_EOL;
} else {
	$ib = new CIBlock();
	$iblockId = (int)$ib->Add(array(
		"SITE_ID" => array($siteId),
		"NAME" => $iblockName,
		"ACTIVE" => "Y",
		"SORT" => 140,
		"CODE" => $iblockCode,
		"IBLOCK_TYPE_ID" => $typeId,
		"SECTIONS" => "Y",
		"SECTION_CHOOSER" => "L",
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
			"SECTION_CODE" => array(
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

	echo "[CREATE] IBlock created: ID=" . $iblockId . ", NAME=" . $iblockName . ", TYPE=" . $typeId . PHP_EOL;
}

CIBlock::SetMessages($iblockId, array(
	"ELEMENTS_NAME" => "Карточки",
	"ELEMENT_NAME" => "Карточка",
	"SECTIONS_NAME" => "Разделы",
	"SECTION_NAME" => "Раздел",
));

extraCardsEnsureProperty($iblockId, array(
	"CODE" => "LINK_URL",
	"NAME" => "Ссылка",
	"PROPERTY_TYPE" => "S",
	"SORT" => 100,
	"COL_COUNT" => 80,
));

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "- extra_cards ID=" . $iblockId . PHP_EOL;

exit(0);
