<?php
/**
 * Идемпотентно готовит основу для квартир:
 * - ИБ apartments
 * - UF-поля разделов ИБ apartments для дерева ЖК/подъезд/этаж
 * - верхние разделы ЖК в ИБ apartments по существующим проектам
 *
 * CLI:
 *   php local/tools/create_apartments_iblocks.php --dry-run=1
 *   php local/tools/create_apartments_iblocks.php --dry-run=0
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
		"apartments-code::",
		"apartments-name::",
		"seed-project-sections::",
		"with-promotion-links::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/create_apartments_iblocks.php [--site-id=s1] [--type-id=realty] [--dry-run=1]\n";
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
$apartmentsCode = isset($_REQUEST["apartments_code"]) && $_REQUEST["apartments_code"] !== "" ? (string)$_REQUEST["apartments_code"] : "apartments";
$apartmentsName = isset($_REQUEST["apartments_name"]) && $_REQUEST["apartments_name"] !== "" ? (string)$_REQUEST["apartments_name"] : "Квартиры";
$seedProjectSections = !isset($_REQUEST["seed_project_sections"]) || (string)$_REQUEST["seed_project_sections"] === "" || (string)$_REQUEST["seed_project_sections"] === "1" || strtolower((string)$_REQUEST["seed_project_sections"]) === "y";
	$withPromotionLinks = isset($_REQUEST["with_promotion_links"]) && (
		(string)$_REQUEST["with_promotion_links"] === "1" || strtolower((string)$_REQUEST["with_promotion_links"]) === "y"
	);
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "Target site: " . $siteId . PHP_EOL;
echo "IBlock type: " . $typeId . PHP_EOL;
echo "Apartments code: " . $apartmentsCode . PHP_EOL;
echo "Seed project sections: " . ($seedProjectSections ? "Y" : "N") . PHP_EOL;
echo "Promotion links: " . ($withPromotionLinks ? "Y" : "N") . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

function boolToBitrix($value)
{
	return $value ? "Y" : "N";
}

function findIblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function ensureIblockType($typeId, $dryRun)
{
	$typeRes = CIBlockType::GetList(array(), array("=ID" => $typeId));
	if ($typeRes->Fetch()) {
		echo "[OK] IBlock type exists: " . $typeId . PHP_EOL;
		return true;
	}

	echo "[CREATE] IBlock type: " . $typeId . PHP_EOL;
	if ($dryRun) {
		return true;
	}

	$ibType = new CIBlockType();
	$ok = $ibType->Add(array(
		"ID" => $typeId,
		"SECTIONS" => "Y",
		"IN_RSS" => "N",
		"SORT" => 120,
		"LANG" => array(
			"ru" => array(
				"NAME" => "Недвижимость",
				"SECTION_NAME" => "Разделы",
				"ELEMENT_NAME" => "Элементы",
			),
			"en" => array(
				"NAME" => "Realty",
				"SECTION_NAME" => "Sections",
				"ELEMENT_NAME" => "Elements",
			),
		),
	));

	if (!$ok) {
		echo "[ERROR] Failed to create iblock type: " . $ibType->LAST_ERROR . PHP_EOL;
		return false;
	}

	return true;
}

function ensureIblock($siteId, $typeId, $code, $name, array $fields, $dryRun)
{
	$existing = findIblockByCode($code);
	if (is_array($existing)) {
		echo "[OK] IBlock exists: " . $name . " (ID=" . (int)$existing["ID"] . ", CODE=" . $code . ")" . PHP_EOL;
		return (int)$existing["ID"];
	}

	echo "[CREATE] IBlock: " . $name . " (CODE=" . $code . ")" . PHP_EOL;
	if ($dryRun) {
		return 0;
	}

	$ib = new CIBlock();
	$newId = (int)$ib->Add($fields + array(
		"ACTIVE" => "Y",
		"NAME" => $name,
		"CODE" => $code,
		"IBLOCK_TYPE_ID" => $typeId,
		"LID" => array($siteId),
		"SECTION_CHOOSER" => "L",
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
	));

	if ($newId <= 0) {
		echo "[ERROR] Failed to create iblock " . $code . ": " . $ib->LAST_ERROR . PHP_EOL;
		return 0;
	}

	return $newId;
}

function ensureProperty($iblockId, array $propertyDef, $dryRun)
{
	if ($iblockId <= 0) {
		echo "[SKIP] Property " . $propertyDef["CODE"] . " (dry-run, iblock ID unknown)" . PHP_EOL;
		return true;
	}

	$propRes = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $iblockId, "CODE" => $propertyDef["CODE"])
	);
	if ($propRes->Fetch()) {
		echo "[OK] Property exists: " . $propertyDef["CODE"] . PHP_EOL;
		return true;
	}

	echo "[CREATE] Property: " . $propertyDef["CODE"] . PHP_EOL;
	if ($dryRun) {
		return true;
	}

	$property = new CIBlockProperty();
	$newPropId = $property->Add($propertyDef + array("IBLOCK_ID" => $iblockId));
	if (!$newPropId) {
		echo "[ERROR] Failed to create property " . $propertyDef["CODE"] . ": " . $property->LAST_ERROR . PHP_EOL;
		return false;
	}

	return true;
}

function getUserField($entityId, $fieldName)
{
	$res = CUserTypeEntity::GetList(
		array("ID" => "ASC"),
		array("ENTITY_ID" => $entityId, "FIELD_NAME" => $fieldName)
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function ensureUserField($entityId, array $fieldDef, $dryRun)
{
	$existing = getUserField($entityId, $fieldDef["FIELD_NAME"]);
	if (is_array($existing)) {
		echo "[OK] User field exists: " . $fieldDef["FIELD_NAME"] . PHP_EOL;
		return (int)$existing["ID"];
	}

	echo "[CREATE] User field: " . $fieldDef["FIELD_NAME"] . PHP_EOL;
	if ($dryRun) {
		return 0;
	}

	$userType = new CUserTypeEntity();
	$newId = (int)$userType->Add($fieldDef + array("ENTITY_ID" => $entityId));
	if ($newId <= 0) {
		echo "[ERROR] Failed to create user field " . $fieldDef["FIELD_NAME"] . ": " . $userType->LAST_ERROR . PHP_EOL;
		return 0;
	}

	return $newId;
}

function ensureUserFieldEnumValues($userFieldId, array $values, $dryRun)
{
	$userFieldId = (int)$userFieldId;
	if ($userFieldId <= 0) {
		echo "[SKIP] Enum values (dry-run, user field ID unknown)" . PHP_EOL;
		return true;
	}

	$existing = array();
	$res = CUserFieldEnum::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("USER_FIELD_ID" => $userFieldId));
	while ($row = $res->Fetch()) {
		$key = trim((string)$row["XML_ID"]) !== "" ? trim((string)$row["XML_ID"]) : trim((string)$row["VALUE"]);
		$existing[$key] = $row;
	}

	$payload = array();
	$needUpdate = false;

	foreach ($values as $index => $valueDef) {
		$xmlId = isset($valueDef["XML_ID"]) ? trim((string)$valueDef["XML_ID"]) : "";
		$key = $xmlId !== "" ? $xmlId : trim((string)$valueDef["VALUE"]);
		$sort = isset($valueDef["SORT"]) ? (int)$valueDef["SORT"] : (($index + 1) * 100);
		$def = isset($valueDef["DEF"]) && $valueDef["DEF"] === "Y" ? "Y" : "N";

		if (isset($existing[$key])) {
			$enumId = (int)$existing[$key]["ID"];
			$payload[$enumId] = array(
				"VALUE" => (string)$valueDef["VALUE"],
				"XML_ID" => $xmlId,
				"SORT" => $sort,
				"DEF" => $def,
			);

			if (
				trim((string)$existing[$key]["VALUE"]) !== (string)$valueDef["VALUE"]
				|| trim((string)$existing[$key]["XML_ID"]) !== $xmlId
				|| (int)$existing[$key]["SORT"] !== $sort
				|| ((string)$existing[$key]["DEF"] === "Y" ? "Y" : "N") !== $def
			) {
				$needUpdate = true;
			}
			continue;
		}

		$payload["n" . $index] = array(
			"VALUE" => (string)$valueDef["VALUE"],
			"XML_ID" => $xmlId,
			"SORT" => $sort,
			"DEF" => $def,
		);
		$needUpdate = true;
	}

	if (!$needUpdate) {
		echo "[OK] Enum values up to date for UF ID " . $userFieldId . PHP_EOL;
		return true;
	}

	echo "[SYNC] Enum values for UF ID " . $userFieldId . PHP_EOL;
	if ($dryRun) {
		return true;
	}

	$enum = new CUserFieldEnum();
	return (bool)$enum->SetEnumValues($userFieldId, $payload);
}

function getUserFieldEnumIdByXmlId($userFieldId, $xmlId)
{
	$res = CUserFieldEnum::GetList(array(), array("USER_FIELD_ID" => (int)$userFieldId));
	while ($row = $res->Fetch()) {
		if (trim((string)$row["XML_ID"]) === (string)$xmlId) {
			return (int)$row["ID"];
		}
	}

	return 0;
}

function ensureTopProjectSections($apartmentsIblockId, $projectsIblockId, $nodeTypeFieldId, $dryRun)
{
	if ($apartmentsIblockId <= 0 || $projectsIblockId <= 0) {
		echo "[SKIP] Top project sections (missing iblock IDs)" . PHP_EOL;
		return true;
	}

	$projectNodeEnumId = getUserFieldEnumIdByXmlId($nodeTypeFieldId, "project");
	if ($nodeTypeFieldId > 0 && $projectNodeEnumId <= 0) {
		echo "[ERROR] UF_NODE_TYPE enum 'project' not found" . PHP_EOL;
		return false;
	}

	$existing = array();
	$sectionRes = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $apartmentsIblockId, "SECTION_ID" => false),
		false,
		array("ID", "NAME", "CODE", "UF_NODE_TYPE")
	);
	while ($row = $sectionRes->Fetch()) {
		$existing[mb_strtolower(trim((string)$row["CODE"]))] = $row;
	}

	$projectRes = CIBlockElement::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => $projectsIblockId, "ACTIVE" => "Y"),
		false,
		false,
		array("ID", "NAME", "CODE", "SORT")
	);

	$sectionApi = new CIBlockSection();
	while ($project = $projectRes->Fetch()) {
		$projectCode = trim((string)$project["CODE"]);
		if ($projectCode === "") {
			continue;
		}

		$key = mb_strtolower($projectCode);
		if (isset($existing[$key])) {
			echo "[OK] Top section exists for project: " . $projectCode . PHP_EOL;
			$currentNodeType = isset($existing[$key]["UF_NODE_TYPE"]) ? (int)$existing[$key]["UF_NODE_TYPE"] : 0;
			if ($projectNodeEnumId > 0 && $currentNodeType !== $projectNodeEnumId) {
				echo "[SYNC] Top section UF_NODE_TYPE for project: " . $projectCode . PHP_EOL;
				if (!$dryRun) {
					$sectionApi->Update((int)$existing[$key]["ID"], array(
						"UF_NODE_TYPE" => $projectNodeEnumId,
					));
				}
			}
			continue;
		}

		echo "[CREATE] Top section for project: " . $projectCode . PHP_EOL;
		if ($dryRun) {
			continue;
		}

		$newId = (int)$sectionApi->Add(array(
			"IBLOCK_ID" => $apartmentsIblockId,
			"ACTIVE" => "Y",
			"NAME" => trim((string)$project["NAME"]),
			"CODE" => $projectCode,
			"SORT" => (int)$project["SORT"] > 0 ? (int)$project["SORT"] : 500,
			"IBLOCK_SECTION_ID" => false,
			"UF_NODE_TYPE" => $projectNodeEnumId > 0 ? $projectNodeEnumId : false,
		));
		if ($newId <= 0) {
			echo "[ERROR] Failed to create top section for project " . $projectCode . ": " . $sectionApi->LAST_ERROR . PHP_EOL;
			return false;
		}
	}

	return true;
}

if (!ensureIblockType($typeId, $dryRun)) {
	exit(2);
}

$projectsIblock = findIblockByCode("projects");
if (!is_array($projectsIblock)) {
	echo "[ERROR] Projects iblock not found by CODE=projects. Run create_projects_iblock.php first." . PHP_EOL;
	exit(3);
}
$projectsIblockId = (int)$projectsIblock["ID"];

$apartmentsIblockId = ensureIblock(
	$siteId,
	$typeId,
	$apartmentsCode,
	$apartmentsName,
	array(
		"SORT" => 130,
		"LIST_PAGE_URL" => "#SITE_DIR#/apartments/",
		"SECTION_PAGE_URL" => "#SITE_DIR#/apartments/#SECTION_CODE_PATH#/",
		"DETAIL_PAGE_URL" => "#SITE_DIR#/apartments/#ELEMENT_CODE#/",
		"INDEX_ELEMENT" => "N",
		"INDEX_SECTION" => "N",
		"SECTIONS" => "Y",
	),
	$dryRun
);

if ($apartmentsIblockId < 0) {
	exit(4);
}

$apartmentsProperties = array(
	array(
		"CODE" => "PROJECT",
		"NAME" => "ЖК",
		"PROPERTY_TYPE" => "E",
		"LINK_IBLOCK_ID" => $projectsIblockId,
		"SORT" => 100,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
	),
	array(
		"CODE" => "CORPUS",
		"NAME" => "Корпус",
		"PROPERTY_TYPE" => "S",
		"SORT" => 110,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 20,
	),
	array(
		"CODE" => "ENTRANCE",
		"NAME" => "Подъезд",
		"PROPERTY_TYPE" => "S",
		"SORT" => 120,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 20,
	),
	array(
		"CODE" => "FLOOR",
		"NAME" => "Этаж",
		"PROPERTY_TYPE" => "N",
		"SORT" => 130,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
	),
	array(
		"CODE" => "HOUSE_FLOORS",
		"NAME" => "Этажность дома",
		"PROPERTY_TYPE" => "N",
		"SORT" => 140,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "APARTMENT_NUMBER",
		"NAME" => "Номер квартиры",
		"PROPERTY_TYPE" => "S",
		"SORT" => 150,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 20,
	),
	array(
		"CODE" => "ROOMS",
		"NAME" => "Комнатность",
		"PROPERTY_TYPE" => "S",
		"SORT" => 160,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 20,
	),
	array(
		"CODE" => "AREA_TOTAL",
		"NAME" => "Общая площадь",
		"PROPERTY_TYPE" => "N",
		"SORT" => 170,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
	),
	array(
		"CODE" => "AREA_LIVING",
		"NAME" => "Жилая площадь",
		"PROPERTY_TYPE" => "N",
		"SORT" => 180,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "AREA_KITCHEN",
		"NAME" => "Площадь кухни",
		"PROPERTY_TYPE" => "N",
		"SORT" => 190,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "PRICE_TOTAL",
		"NAME" => "Цена",
		"PROPERTY_TYPE" => "N",
		"SORT" => 200,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
	),
	array(
		"CODE" => "PRICE_OLD",
		"NAME" => "Старая цена",
		"PROPERTY_TYPE" => "N",
		"SORT" => 210,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "PRICE_M2",
		"NAME" => "Цена за м2",
		"PROPERTY_TYPE" => "N",
		"SORT" => 220,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "STATUS",
		"NAME" => "Статус",
		"PROPERTY_TYPE" => "L",
		"SORT" => 230,
		"MULTIPLE" => "N",
		"IS_REQUIRED" => "Y",
		"VALUES" => array(
			array("VALUE" => "Свободно", "XML_ID" => "free", "SORT" => 100, "DEF" => "Y"),
			array("VALUE" => "Забронировано", "XML_ID" => "booked", "SORT" => 200, "DEF" => "N"),
			array("VALUE" => "Продано", "XML_ID" => "sold", "SORT" => 300, "DEF" => "N"),
		),
	),
	array(
		"CODE" => "DISCOUNT_LABEL",
		"NAME" => "Плашка скидки",
		"PROPERTY_TYPE" => "S",
		"SORT" => 240,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 60,
	),
	array(
		"CODE" => "FINISH",
		"NAME" => "Отделка",
		"PROPERTY_TYPE" => "S",
		"SORT" => 250,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 50,
	),
	array(
		"CODE" => "CEILING",
		"NAME" => "Высота потолков",
		"PROPERTY_TYPE" => "N",
		"SORT" => 260,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "VIEW_TEXT",
		"NAME" => "Вид из окна",
		"PROPERTY_TYPE" => "S",
		"SORT" => 270,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 4,
		"COL_COUNT" => 90,
	),
	array(
		"CODE" => "WINDOW_SIDES",
		"NAME" => "Стороны света",
		"PROPERTY_TYPE" => "S",
		"SORT" => 280,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 50,
	),
	array(
		"CODE" => "BALCONY_TYPE",
		"NAME" => "Балкон / лоджия",
		"PROPERTY_TYPE" => "S",
		"SORT" => 290,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 60,
	),
	array(
		"CODE" => "BATHROOMS",
		"NAME" => "Количество санузлов",
		"PROPERTY_TYPE" => "N",
		"SORT" => 300,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "FEATURE_TAGS",
		"NAME" => "Особенности",
		"PROPERTY_TYPE" => "S",
		"SORT" => 310,
		"MULTIPLE" => "Y",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 60,
	),
	array(
		"CODE" => "PLAN_IMAGE",
		"NAME" => "Планировка",
		"PROPERTY_TYPE" => "F",
		"SORT" => 320,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "PLAN_TITLE",
		"NAME" => "Планировка: заголовок",
		"PROPERTY_TYPE" => "S",
		"SORT" => 321,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "PLAN_TEXT",
		"NAME" => "Планировка: описание",
		"PROPERTY_TYPE" => "S",
		"SORT" => 322,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 4,
		"COL_COUNT" => 90,
	),
	array(
		"CODE" => "PLAN_ALT",
		"NAME" => "Планировка: ALT",
		"PROPERTY_TYPE" => "S",
		"SORT" => 323,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "FLOOR_SLIDE_IMAGE",
		"NAME" => "На этаже: изображение",
		"PROPERTY_TYPE" => "F",
		"SORT" => 324,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "FLOOR_SLIDE_TITLE",
		"NAME" => "На этаже: заголовок",
		"PROPERTY_TYPE" => "S",
		"SORT" => 325,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "FLOOR_SLIDE_TEXT",
		"NAME" => "На этаже: описание",
		"PROPERTY_TYPE" => "S",
		"SORT" => 326,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 4,
		"COL_COUNT" => 90,
	),
	array(
		"CODE" => "FLOOR_SLIDE_ALT",
		"NAME" => "На этаже: ALT",
		"PROPERTY_TYPE" => "S",
		"SORT" => 327,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "BUILDING_SLIDE_IMAGE",
		"NAME" => "В корпусе: изображение",
		"PROPERTY_TYPE" => "F",
		"SORT" => 328,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "BUILDING_SLIDE_TITLE",
		"NAME" => "В корпусе: заголовок",
		"PROPERTY_TYPE" => "S",
		"SORT" => 329,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "BUILDING_SLIDE_TEXT",
		"NAME" => "В корпусе: описание",
		"PROPERTY_TYPE" => "S",
		"SORT" => 330,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 4,
		"COL_COUNT" => 90,
	),
	array(
		"CODE" => "BUILDING_SLIDE_ALT",
		"NAME" => "В корпусе: ALT",
		"PROPERTY_TYPE" => "S",
		"SORT" => 331,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "VIEW_SLIDE_IMAGE",
		"NAME" => "Вид из окна: изображение",
		"PROPERTY_TYPE" => "F",
		"SORT" => 332,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "VIEW_SLIDE_TITLE",
		"NAME" => "Вид из окна: заголовок",
		"PROPERTY_TYPE" => "S",
		"SORT" => 333,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "VIEW_SLIDE_TEXT",
		"NAME" => "Вид из окна: описание",
		"PROPERTY_TYPE" => "S",
		"SORT" => 334,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 4,
		"COL_COUNT" => 90,
	),
	array(
		"CODE" => "VIEW_SLIDE_ALT",
		"NAME" => "Вид из окна: ALT",
		"PROPERTY_TYPE" => "S",
		"SORT" => 335,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "RENDER_SLIDE_IMAGE",
		"NAME" => "Визуализация: изображение",
		"PROPERTY_TYPE" => "F",
		"SORT" => 336,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "RENDER_SLIDE_TITLE",
		"NAME" => "Визуализация: заголовок",
		"PROPERTY_TYPE" => "S",
		"SORT" => 337,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "RENDER_SLIDE_TEXT",
		"NAME" => "Визуализация: описание",
		"PROPERTY_TYPE" => "S",
		"SORT" => 338,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 4,
		"COL_COUNT" => 90,
	),
	array(
		"CODE" => "RENDER_SLIDE_ALT",
		"NAME" => "Визуализация: ALT",
		"PROPERTY_TYPE" => "S",
		"SORT" => 339,
		"MULTIPLE" => "N",
		"ROW_COUNT" => 1,
		"COL_COUNT" => 80,
	),
	array(
		"CODE" => "SVG_SLOT_ID",
		"NAME" => "ID квартиры в SVG шахматки",
		"PROPERTY_TYPE" => "S",
		"SORT" => 340,
		"MULTIPLE" => "N",
	),
	array(
		"CODE" => "SORT_IN_FLOOR",
		"NAME" => "Порядок на этаже",
		"PROPERTY_TYPE" => "N",
		"SORT" => 350,
		"MULTIPLE" => "N",
	),
);

foreach ($apartmentsProperties as $propertyDef) {
	if (!ensureProperty($apartmentsIblockId, $propertyDef, $dryRun)) {
		exit(5);
	}
}

$apartmentsSectionEntityId = $apartmentsIblockId > 0 ? "IBLOCK_" . $apartmentsIblockId . "_SECTION" : "";
$nodeTypeFieldId = 0;
if ($apartmentsSectionEntityId !== "") {
	$nodeTypeFieldId = ensureUserField($apartmentsSectionEntityId, array(
		"FIELD_NAME" => "UF_NODE_TYPE",
		"USER_TYPE_ID" => "enumeration",
		"XML_ID" => "UF_NODE_TYPE",
		"SORT" => 100,
		"MULTIPLE" => "N",
		"MANDATORY" => "N",
		"SHOW_FILTER" => "I",
		"SHOW_IN_LIST" => "Y",
		"EDIT_IN_LIST" => "Y",
		"IS_SEARCHABLE" => "N",
		"SETTINGS" => array(
			"DISPLAY" => "LIST",
			"LIST_HEIGHT" => 1,
		),
		"EDIT_FORM_LABEL" => array("ru" => "Тип узла", "en" => "Node type"),
		"LIST_COLUMN_LABEL" => array("ru" => "Тип узла", "en" => "Node type"),
	), $dryRun);
	if ($nodeTypeFieldId <= 0 && !$dryRun) {
		exit(6);
	}

	if (!ensureUserFieldEnumValues($nodeTypeFieldId, array(
		array("VALUE" => "ЖК", "XML_ID" => "project", "SORT" => 100, "DEF" => "N"),
		array("VALUE" => "Корпус", "XML_ID" => "corpus", "SORT" => 200, "DEF" => "N"),
		array("VALUE" => "Подъезд", "XML_ID" => "entrance", "SORT" => 300, "DEF" => "N"),
		array("VALUE" => "Этаж", "XML_ID" => "floor", "SORT" => 400, "DEF" => "N"),
	), $dryRun)) {
		exit(7);
	}

	$userFields = array(
		array(
			"FIELD_NAME" => "UF_ENTRANCE_NUMBER",
			"USER_TYPE_ID" => "string",
			"XML_ID" => "UF_ENTRANCE_NUMBER",
			"SORT" => 110,
			"EDIT_FORM_LABEL" => array("ru" => "Номер подъезда", "en" => "Entrance number"),
			"LIST_COLUMN_LABEL" => array("ru" => "Номер подъезда", "en" => "Entrance number"),
		),
		array(
			"FIELD_NAME" => "UF_FLOOR_NUMBER",
			"USER_TYPE_ID" => "integer",
			"XML_ID" => "UF_FLOOR_NUMBER",
			"SORT" => 120,
			"EDIT_FORM_LABEL" => array("ru" => "Номер этажа", "en" => "Floor number"),
			"LIST_COLUMN_LABEL" => array("ru" => "Номер этажа", "en" => "Floor number"),
		),
		array(
			"FIELD_NAME" => "UF_CHESS_SVG",
			"USER_TYPE_ID" => "file",
			"XML_ID" => "UF_CHESS_SVG",
			"SORT" => 130,
			"EDIT_FORM_LABEL" => array("ru" => "SVG шахматки", "en" => "Chess SVG"),
			"LIST_COLUMN_LABEL" => array("ru" => "SVG шахматки", "en" => "Chess SVG"),
		),
		array(
			"FIELD_NAME" => "UF_CHESS_IMAGE",
			"USER_TYPE_ID" => "file",
			"XML_ID" => "UF_CHESS_IMAGE",
			"SORT" => 140,
			"EDIT_FORM_LABEL" => array("ru" => "Изображение этажа", "en" => "Floor image"),
			"LIST_COLUMN_LABEL" => array("ru" => "Изображение этажа", "en" => "Floor image"),
		),
	);

	foreach ($userFields as $fieldDef) {
		$field = array(
			"FIELD_NAME" => $fieldDef["FIELD_NAME"],
			"USER_TYPE_ID" => $fieldDef["USER_TYPE_ID"],
			"XML_ID" => $fieldDef["XML_ID"],
			"SORT" => $fieldDef["SORT"],
			"MULTIPLE" => "N",
			"MANDATORY" => "N",
			"SHOW_FILTER" => "N",
			"SHOW_IN_LIST" => "Y",
			"EDIT_IN_LIST" => "N",
			"IS_SEARCHABLE" => "N",
			"EDIT_FORM_LABEL" => $fieldDef["EDIT_FORM_LABEL"],
			"LIST_COLUMN_LABEL" => $fieldDef["LIST_COLUMN_LABEL"],
		);
		if ((string)$fieldDef["USER_TYPE_ID"] === "string") {
			$field["SETTINGS"] = array("SIZE" => 20, "ROWS" => 1, "DEFAULT_VALUE" => "");
		}
		if ((string)$fieldDef["USER_TYPE_ID"] === "integer") {
			$field["SETTINGS"] = array("DEFAULT_VALUE" => 0, "SIZE" => 20, "MIN_VALUE" => 0, "MAX_VALUE" => 0);
		}

		$userFieldId = ensureUserField($apartmentsSectionEntityId, $field, $dryRun);
		if ($userFieldId <= 0 && !$dryRun) {
			exit(8);
		}
	}
}

if ($withPromotionLinks) {
	$promotionsIblock = findIblockByCode("promotions");
	if (is_array($promotionsIblock)) {
		$promotionsIblockId = (int)$promotionsIblock["ID"];
		if (!ensureProperty($promotionsIblockId, array(
			"CODE" => "LINK_PROJECTS",
			"NAME" => "Связанные ЖК",
			"PROPERTY_TYPE" => "E",
			"LINK_IBLOCK_ID" => $projectsIblockId,
			"SORT" => 120,
			"MULTIPLE" => "Y",
		), $dryRun)) {
			exit(9);
		}
		if (!ensureProperty($promotionsIblockId, array(
			"CODE" => "LINK_FLATS",
			"NAME" => "Связанные квартиры",
			"PROPERTY_TYPE" => "E",
			"LINK_IBLOCK_ID" => $apartmentsIblockId > 0 ? $apartmentsIblockId : 0,
			"SORT" => 130,
			"MULTIPLE" => "Y",
		), $dryRun)) {
			exit(10);
		}
	} else {
		echo "[WARN] Promotions iblock not found; LINK_PROJECTS/LINK_FLATS skipped." . PHP_EOL;
	}
}

if ($seedProjectSections) {
	if (!ensureTopProjectSections($apartmentsIblockId, $projectsIblockId, $nodeTypeFieldId, $dryRun)) {
		exit(11);
	}
}

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "Use in code:" . PHP_EOL;
echo "- apartments: TYPE=" . $typeId . ", CODE=" . $apartmentsCode . ($apartmentsIblockId > 0 ? ", ID=" . $apartmentsIblockId : "") . PHP_EOL;
if ($apartmentsSectionEntityId !== "") {
	echo "- apartments section entity: " . $apartmentsSectionEntityId . PHP_EOL;
}

exit(0);
