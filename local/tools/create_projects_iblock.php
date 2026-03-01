<?php
/**
 * Идемпотентное создание инфоблока "Проекты (ЖК)" и его свойств.
 *
 * CLI:
 *   php local/tools/create_projects_iblock.php
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
		"iblock-name::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_projects_iblock.php [--site-id=s1] [--type-id=content] [--iblock-code=projects]\n";
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
$iblockCode = isset($_REQUEST["iblock_code"]) && $_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "projects";
$iblockName = isset($_REQUEST["iblock_name"]) && $_REQUEST["iblock_name"] !== "" ? (string)$_REQUEST["iblock_name"] : "Проекты";

echo "Target site: " . $siteId . PHP_EOL;
echo "IBlock type: " . $typeId . PHP_EOL;
echo "IBlock code: " . $iblockCode . PHP_EOL;

$typeExists = false;
$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if ($typeRes->Fetch()) {
	$typeExists = true;
}
if (!$typeExists) {
	echo "IBlock type '" . $typeId . "' does not exist. Create it first (e.g. via create_news_iblock.php)." . PHP_EOL;
	exit(2);
}

$iblockId = 0;
$iblockRes = CIBlock::GetList(array(), array("TYPE" => $typeId, "=CODE" => $iblockCode), false);
if ($row = $iblockRes->Fetch()) {
	$iblockId = (int)$row["ID"];
	echo "[OK] IBlock exists: ID=" . $iblockId . ", NAME=" . $row["NAME"] . PHP_EOL;
} else {
	$ib = new CIBlock();
	$iblockFields = array(
		"ACTIVE" => "Y",
		"NAME" => $iblockName,
		"CODE" => $iblockCode,
		"IBLOCK_TYPE_ID" => $typeId,
		"LID" => array($siteId),
		"SORT" => 120,
		"LIST_PAGE_URL" => "#SITE_DIR#/projects/",
		"SECTION_PAGE_URL" => "#SITE_DIR#/projects/",
		"DETAIL_PAGE_URL" => "#SITE_DIR#/projects/#ELEMENT_CODE#/",
		"SECTION_CHOOSER" => "L",
		"INDEX_ELEMENT" => "N",
		"INDEX_SECTION" => "N",
		"SECTIONS" => "N",
		"RIGHTS_MODE" => "S",
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
		"GROUP_ID" => array(2 => "R"),
	);

	$newId = $ib->Add($iblockFields);
	if (!$newId) {
		echo "Failed to create iblock: " . $ib->LAST_ERROR . PHP_EOL;
		exit(3);
	}
	$iblockId = (int)$newId;
	echo "[CREATE] IBlock created: ID=" . $iblockId . ", NAME=" . $iblockName . PHP_EOL;
}

$requiredProperties = array(
	array(
		"CODE" => "CLASS_LABEL",
		"NAME" => "Класс ЖК (бейдж)",
		"PROPERTY_TYPE" => "S",
		"SORT" => 100,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "TAG_LABEL",
		"NAME" => "Доп. бейдж",
		"PROPERTY_TYPE" => "S",
		"SORT" => 110,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "ADDRESS",
		"NAME" => "Адрес",
		"PROPERTY_TYPE" => "S",
		"SORT" => 120,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "DELIVERY_TEXT",
		"NAME" => "Срок сдачи (текст)",
		"PROPERTY_TYPE" => "S",
		"SORT" => 130,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "ROOMS_IN_SALE",
		"NAME" => "Комнатности в продаже",
		"PROPERTY_TYPE" => "S",
		"SORT" => 140,
		"MULTIPLE" => "Y",
	),
	array(
		"CODE" => "SALE_COUNT_TEXT",
		"NAME" => "Текст в подвале карточки",
		"PROPERTY_TYPE" => "S",
		"SORT" => 150,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "PRICE_FROM_TEXT",
		"NAME" => "Цена от (текст)",
		"PROPERTY_TYPE" => "S",
		"SORT" => 160,
		"MULTIPLE" => "N",
	),
);

foreach ($requiredProperties as $propertyDef) {
	$exists = false;
	$propRes = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $iblockId, "CODE" => $propertyDef["CODE"])
	);
	if ($propRes->Fetch()) {
		$exists = true;
	}

	if ($exists) {
		echo "[OK] Property exists: " . $propertyDef["CODE"] . PHP_EOL;
		continue;
	}

	$ibp = new CIBlockProperty();
	$fields = array(
		"IBLOCK_ID" => $iblockId,
		"NAME" => $propertyDef["NAME"],
		"CODE" => $propertyDef["CODE"],
		"PROPERTY_TYPE" => $propertyDef["PROPERTY_TYPE"],
		"ACTIVE" => "Y",
		"MULTIPLE" => $propertyDef["MULTIPLE"],
		"IS_REQUIRED" => "N",
		"SORT" => $propertyDef["SORT"],
	);

	$propId = $ibp->Add($fields);
	if (!$propId) {
		echo "Failed to create property " . $propertyDef["CODE"] . ": " . $ibp->LAST_ERROR . PHP_EOL;
		exit(4);
	}
	echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . (int)$propId . ")" . PHP_EOL;
}

echo PHP_EOL;
echo "Use in code: TYPE=" . $typeId . ", CODE=" . $iblockCode . PHP_EOL;

exit(0);
