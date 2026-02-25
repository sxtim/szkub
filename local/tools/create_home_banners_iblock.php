<?php
/**
 * Идемпотентное создание инфоблока "Баннеры главной" и его свойств.
 *
 * CLI:
 *   php local/tools/create_home_banners_iblock.php
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
		echo "Usage: php local/tools/create_home_banners_iblock.php [--site-id=s1] [--type-id=content] [--iblock-code=home_banners]" . PHP_EOL;
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
$iblockCode = isset($_REQUEST["iblock_code"]) && $_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "home_banners";
$iblockName = isset($_REQUEST["iblock_name"]) && $_REQUEST["iblock_name"] !== "" ? (string)$_REQUEST["iblock_name"] : "Баннеры главной";

echo "Target site: " . $siteId . PHP_EOL;
echo "IBlock type: " . $typeId . PHP_EOL;
echo "IBlock code: " . $iblockCode . PHP_EOL;

$typeRes = CIBlockType::GetList(array(), array("ID" => $typeId));
if (!$typeRes->Fetch()) {
	echo "IBlock type '" . $typeId . "' does not exist. Create it first." . PHP_EOL;
	exit(2);
}

$iblockId = 0;
$iblockRes = CIBlock::GetList(array(), array("TYPE" => $typeId, "CODE" => $iblockCode), false);
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
		"LIST_PAGE_URL" => "#SITE_DIR#/",
		"SECTION_PAGE_URL" => "#SITE_DIR#/",
		"DETAIL_PAGE_URL" => "",
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
		"CODE" => "SLOT",
		"NAME" => "Слот баннера",
		"PROPERTY_TYPE" => "L",
		"SORT" => 100,
	),
	array(
		"CODE" => "LINK_URL",
		"NAME" => "Ссылка",
		"PROPERTY_TYPE" => "S",
		"SORT" => 110,
	),
	array(
		"CODE" => "LINK_TARGET",
		"NAME" => "Target ссылки",
		"PROPERTY_TYPE" => "S",
		"SORT" => 120,
	),
);

$propertyIds = array();
foreach ($requiredProperties as $propertyDef) {
	$propertyId = 0;
	$propRes = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $iblockId, "CODE" => $propertyDef["CODE"])
	);
	if ($prop = $propRes->Fetch()) {
		$propertyId = (int)$prop["ID"];
		echo "[OK] Property exists: " . $propertyDef["CODE"] . PHP_EOL;
	} else {
		$ibp = new CIBlockProperty();
		$fields = array(
			"IBLOCK_ID" => $iblockId,
			"NAME" => $propertyDef["NAME"],
			"CODE" => $propertyDef["CODE"],
			"PROPERTY_TYPE" => $propertyDef["PROPERTY_TYPE"],
			"ACTIVE" => "Y",
			"MULTIPLE" => "N",
			"IS_REQUIRED" => $propertyDef["CODE"] === "SLOT" ? "Y" : "N",
			"SORT" => $propertyDef["SORT"],
		);
		if ($propertyDef["CODE"] === "SLOT") {
			$fields["LIST_TYPE"] = "L";
		}
		$propertyId = (int)$ibp->Add($fields);
		if ($propertyId <= 0) {
			echo "Failed to create property " . $propertyDef["CODE"] . ": " . $ibp->LAST_ERROR . PHP_EOL;
			exit(4);
		}
		echo "[CREATE] Property created: " . $propertyDef["CODE"] . " (ID=" . $propertyId . ")" . PHP_EOL;
	}
	$propertyIds[$propertyDef["CODE"]] = $propertyId;
}

$slotEnums = array(
	array("XML_ID" => "MAIN", "VALUE" => "Главный баннер", "SORT" => 100, "DEF" => "Y"),
	array("XML_ID" => "ASIDE_TOP", "VALUE" => "Правый верхний", "SORT" => 110, "DEF" => "N"),
	array("XML_ID" => "ASIDE_BOTTOM", "VALUE" => "Правый нижний", "SORT" => 120, "DEF" => "N"),
	array("XML_ID" => "BOTTOM_LEFT", "VALUE" => "Нижний левый", "SORT" => 130, "DEF" => "N"),
	array("XML_ID" => "BOTTOM_RIGHT", "VALUE" => "Нижний правый", "SORT" => 140, "DEF" => "N"),
);

if (!empty($propertyIds["SLOT"])) {
	$slotPropertyId = (int)$propertyIds["SLOT"];
	$existingEnumsByXml = array();
	$enumRes = CIBlockPropertyEnum::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("PROPERTY_ID" => $slotPropertyId));
	while ($enum = $enumRes->Fetch()) {
		$existingEnumsByXml[(string)$enum["XML_ID"]] = (int)$enum["ID"];
	}

	$enumEntity = new CIBlockPropertyEnum();
	foreach ($slotEnums as $slotEnum) {
		if (isset($existingEnumsByXml[$slotEnum["XML_ID"]])) {
			echo "[OK] SLOT enum exists: " . $slotEnum["XML_ID"] . PHP_EOL;
			continue;
		}

		$enumId = $enumEntity->Add(array(
			"PROPERTY_ID" => $slotPropertyId,
			"VALUE" => $slotEnum["VALUE"],
			"XML_ID" => $slotEnum["XML_ID"],
			"SORT" => $slotEnum["SORT"],
			"DEF" => $slotEnum["DEF"],
		));
		if (!$enumId) {
			echo "Failed to create SLOT enum " . $slotEnum["XML_ID"] . PHP_EOL;
			exit(5);
		}
		echo "[CREATE] SLOT enum created: " . $slotEnum["XML_ID"] . " (ID=" . (int)$enumId . ")" . PHP_EOL;
	}
}

echo PHP_EOL;
echo "Use in code: TYPE=" . $typeId . ", CODE=" . $iblockCode . PHP_EOL;

exit(0);
