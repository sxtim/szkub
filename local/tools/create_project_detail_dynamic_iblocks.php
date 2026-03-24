<?php
/**
 * Создает (или проверяет) инфоблоки:
 * - project_advantages (Преимущества ЖК)
 * - project_construction (Ход строительства ЖК)
 * - project_documents (Документы ЖК)
 *
 * Запуск:
 *   php local/tools/create_project_detail_dynamic_iblocks.php
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

echo "Target site: {$siteId}" . PHP_EOL;

function findIblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function ensureIblock($siteId, $type, $code, $name)
{
	$existing = findIblockByCode($code);
	if (is_array($existing)) {
		$existingId = (int)$existing["ID"];
		$existingType = (string)$existing["IBLOCK_TYPE_ID"];
		echo "[OK] IBlock exists: {$name} (ID={$existingId}, CODE={$code}, TYPE={$existingType})" . PHP_EOL;
		return $existingId;
	}

	$ib = new CIBlock();
	$newId = (int)$ib->Add(array(
		"SITE_ID" => array($siteId),
		"NAME" => $name,
		"ACTIVE" => "Y",
		"SORT" => 500,
		"CODE" => $code,
		"IBLOCK_TYPE_ID" => $type,
		"SECTIONS" => "Y",
		"SECTION_CHOOSER" => "L",
		"GROUP_ID" => array("2" => "R"),
	));

	if ($newId <= 0) {
		echo "[ERROR] Failed to create iblock {$code}: " . $ib->LAST_ERROR . PHP_EOL;
		exit(2);
	}

	echo "[CREATE] IBlock created: {$name} (ID={$newId}, CODE={$code}, TYPE={$type})" . PHP_EOL;
	return $newId;
}

function ensureProperty($iblockId, array $propertyDef)
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
	$newPropId = $property->Add($propertyDef + array("IBLOCK_ID" => $iblockId));
	if (!$newPropId) {
		echo "[ERROR] Failed to create property {$propertyDef["CODE"]}: " . $property->LAST_ERROR . PHP_EOL;
		exit(3);
	}

	echo "[CREATE] Property created: {$propertyDef["CODE"]} (ID={$newPropId})" . PHP_EOL;
}

$projectsIblock = findIblockByCode($projectsIblockCode);
if (!is_array($projectsIblock)) {
	echo "[ERROR] Projects IBlock not found by CODE={$projectsIblockCode}. Run create_projects_iblock.php first." . PHP_EOL;
	exit(4);
}
$projectsIblockId = (int)$projectsIblock["ID"];
$projectsIblockType = (string)$projectsIblock["IBLOCK_TYPE_ID"];
if ($projectsIblockType !== "") {
	$iblockType = $projectsIblockType;
}
echo "[OK] Projects IBlock: ID={$projectsIblockId}" . PHP_EOL;
echo "IBlock type: {$iblockType}" . PHP_EOL;

$advantagesIblockId = ensureIblock($siteId, $iblockType, "project_advantages", "Преимущества ЖК");
ensureProperty($advantagesIblockId, array(
	"CODE" => "CATEGORY",
	"NAME" => "Категория",
	"PROPERTY_TYPE" => "L",
	"SORT" => 105,
	"MULTIPLE" => "N",
	"VALUES" => array(
		array("VALUE" => "Отделка", "XML_ID" => "finish", "SORT" => 100),
		array("VALUE" => "Локация", "XML_ID" => "location", "SORT" => 200),
		array("VALUE" => "Благоустройство", "XML_ID" => "landscape", "SORT" => 300),
		array("VALUE" => "Инфраструктура", "XML_ID" => "infrastructure", "SORT" => 400),
		array("VALUE" => "Фасад и материалы", "XML_ID" => "facade", "SORT" => 500),
		array("VALUE" => "Планировки", "XML_ID" => "layouts", "SORT" => 600),
	),
));
ensureProperty($advantagesIblockId, array(
	"CODE" => "PROJECT",
	"NAME" => "ЖК",
	"PROPERTY_TYPE" => "E",
	"LINK_IBLOCK_ID" => $projectsIblockId,
	"SORT" => 100,
	"MULTIPLE" => "N",
	"IS_REQUIRED" => "Y",
));
ensureProperty($advantagesIblockId, array(
	"CODE" => "LABEL",
	"NAME" => "Метка карточки",
	"PROPERTY_TYPE" => "S",
	"SORT" => 110,
	"MULTIPLE" => "N",
));

$constructionIblockId = ensureIblock($siteId, $iblockType, "project_construction", "Ход строительства ЖК");
ensureProperty($constructionIblockId, array(
	"CODE" => "PROJECT",
	"NAME" => "ЖК",
	"PROPERTY_TYPE" => "E",
	"LINK_IBLOCK_ID" => $projectsIblockId,
	"SORT" => 100,
	"MULTIPLE" => "N",
	"IS_REQUIRED" => "Y",
));
ensureProperty($constructionIblockId, array(
	"CODE" => "DATE_TEXT",
	"NAME" => "Дата (текст)",
	"PROPERTY_TYPE" => "S",
	"SORT" => 110,
	"MULTIPLE" => "N",
));
ensureProperty($constructionIblockId, array(
	"CODE" => "GALLERY",
	"NAME" => "Галерея",
	"PROPERTY_TYPE" => "F",
	"SORT" => 120,
	"MULTIPLE" => "Y",
));

$documentsIblockId = ensureIblock($siteId, $iblockType, "project_documents", "Документы ЖК");
ensureProperty($documentsIblockId, array(
	"CODE" => "PROJECT",
	"NAME" => "ЖК",
	"PROPERTY_TYPE" => "E",
	"LINK_IBLOCK_ID" => $projectsIblockId,
	"SORT" => 100,
	"MULTIPLE" => "N",
	"IS_REQUIRED" => "Y",
));
ensureProperty($documentsIblockId, array(
	"CODE" => "FILE",
	"NAME" => "Файл",
	"PROPERTY_TYPE" => "F",
	"SORT" => 110,
	"MULTIPLE" => "N",
));
ensureProperty($documentsIblockId, array(
	"CODE" => "LINK_URL",
	"NAME" => "Ссылка (если без файла)",
	"PROPERTY_TYPE" => "S",
	"SORT" => 120,
	"MULTIPLE" => "N",
));
ensureProperty($documentsIblockId, array(
	"CODE" => "LINK_TARGET",
	"NAME" => "Открывать ссылку",
	"PROPERTY_TYPE" => "L",
	"SORT" => 130,
	"MULTIPLE" => "N",
	"VALUES" => array(
		array("VALUE" => "В том же окне (_self)", "XML_ID" => "_self", "SORT" => 100, "DEF" => "Y"),
		array("VALUE" => "В новом окне (_blank)", "XML_ID" => "_blank", "SORT" => 200, "DEF" => "N"),
	),
));

echo PHP_EOL;
echo "Done." . PHP_EOL;
echo "Use in code:" . PHP_EOL;
echo "- advantages: TYPE={$iblockType}, CODE=project_advantages, ID={$advantagesIblockId}" . PHP_EOL;
echo "- construction: TYPE={$iblockType}, CODE=project_construction, ID={$constructionIblockId}" . PHP_EOL;
echo "- documents: TYPE={$iblockType}, CODE=project_documents, ID={$documentsIblockId}" . PHP_EOL;

exit(0);
