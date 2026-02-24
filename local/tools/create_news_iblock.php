<?php
/**
 * Идемпотентное создание типа инфоблока и инфоблока "Новости".
 *
 * CLI:
 *   php local/tools/create_news_iblock.php
 *   php local/tools/create_news_iblock.php --site-id=s1 --type-id=content --iblock-code=news
 *
 * Web (под админом):
 *   /local/tools/create_news_iblock.php
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
		"type-name-ru::",
		"type-name-en::",
		"iblock-code::",
		"iblock-name::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_news_iblock.php [--site-id=s1] [--type-id=content] [--iblock-code=news]\n";
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
$typeNameRu = isset($_REQUEST["type_name_ru"]) && $_REQUEST["type_name_ru"] !== "" ? (string)$_REQUEST["type_name_ru"] : "Контент";
$typeNameEn = isset($_REQUEST["type_name_en"]) && $_REQUEST["type_name_en"] !== "" ? (string)$_REQUEST["type_name_en"] : "Content";
$iblockCode = isset($_REQUEST["iblock_code"]) && $_REQUEST["iblock_code"] !== "" ? (string)$_REQUEST["iblock_code"] : "news";
$iblockName = isset($_REQUEST["iblock_name"]) && $_REQUEST["iblock_name"] !== "" ? (string)$_REQUEST["iblock_name"] : "Новости";

echo "Target site: " . $siteId . PHP_EOL;
echo "IBlock type: " . $typeId . PHP_EOL;
echo "IBlock code: " . $iblockCode . PHP_EOL;

// 1) Ensure iblock type exists.
$typeExists = false;
$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
if ($typeRow = $typeRes->Fetch()) {
	$typeExists = true;
	echo "[OK] IBlock type exists: " . $typeId . PHP_EOL;
}

if (!$typeExists) {
	$ibType = new CIBlockType();
	$typeFields = array(
		"ID" => $typeId,
		"SECTIONS" => "N",
		"IN_RSS" => "N",
		"SORT" => 100,
		"LANG" => array(
			"ru" => array(
				"NAME" => $typeNameRu,
				"SECTION_NAME" => "Разделы",
				"ELEMENT_NAME" => "Элементы",
			),
			"en" => array(
				"NAME" => $typeNameEn,
				"SECTION_NAME" => "Sections",
				"ELEMENT_NAME" => "Elements",
			),
		),
	);

	if (!$ibType->Add($typeFields)) {
		echo "Failed to create iblock type: " . $ibType->LAST_ERROR . PHP_EOL;
		exit(2);
	}
	echo "[CREATE] IBlock type created: " . $typeId . PHP_EOL;
}

// 2) Ensure iblock exists.
$existingIblock = null;
$iblockRes = CIBlock::GetList(
	array(),
	array(
		"TYPE" => $typeId,
		"=CODE" => $iblockCode,
	),
	false
);
if ($row = $iblockRes->Fetch()) {
	$existingIblock = $row;
}

if ($existingIblock) {
	echo "[OK] IBlock exists: ID=" . (int)$existingIblock["ID"] . ", NAME=" . $existingIblock["NAME"] . PHP_EOL;
	echo PHP_EOL;
	echo "Use in code:" . PHP_EOL;
	echo "TYPE=" . $typeId . ", CODE=" . $iblockCode . PHP_EOL;
	exit(0);
}

$ib = new CIBlock();
$iblockFields = array(
	"ACTIVE" => "Y",
	"NAME" => $iblockName,
	"CODE" => $iblockCode,
	"IBLOCK_TYPE_ID" => $typeId,
	"LID" => array($siteId),
	"SORT" => 100,
	"LIST_PAGE_URL" => "#SITE_DIR#/news/",
	"SECTION_PAGE_URL" => "#SITE_DIR#/news/",
	"DETAIL_PAGE_URL" => "#SITE_DIR#/news/#ELEMENT_CODE#/",
	"SECTION_CHOOSER" => "L",
	"CANONICAL_PAGE_URL" => "",
	"INDEX_ELEMENT" => "N",
	"INDEX_SECTION" => "N",
	"WORKFLOW" => "N",
	"BIZPROC" => "N",
	"SECTIONS" => "N",
	"RIGHTS_MODE" => "S",
	"VERSION" => 2,
	"FIELDS" => array(
		"CODE" => array(
			"IS_REQUIRED" => "N",
			"DEFAULT_VALUE" => array(
				"TRANSLITERATION" => "Y",
				"TRANS_LEN" => 100,
				"UNIQUE" => "Y",
				"TRANSLITERATION_CHARS" => "",
				"TRANS_CASE" => "L",
				"TRANS_SPACE" => "-",
				"TRANS_OTHER" => "-",
				"TRANS_EAT" => "Y",
				"USE_GOOGLE" => "N",
			),
		),
	),
	"GROUP_ID" => array(
		2 => "R", // Все пользователи - чтение
	),
);

$newIblockId = $ib->Add($iblockFields);
if (!$newIblockId) {
	echo "Failed to create iblock: " . $ib->LAST_ERROR . PHP_EOL;
	exit(3);
}

echo "[CREATE] IBlock created: ID=" . (int)$newIblockId . ", NAME=" . $iblockName . PHP_EOL;
echo PHP_EOL;
echo "Use in code:" . PHP_EOL;
echo "TYPE=" . $typeId . ", CODE=" . $iblockCode . PHP_EOL;

exit(0);

