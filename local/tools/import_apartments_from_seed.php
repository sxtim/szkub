<?php
/**
 * Импортирует тестовую структуру квартир в ИБ apartments:
 * - использует верхние разделы ЖК, созданные create_apartments_iblocks.php
 * - создает подъезды и этажи
 * - создает/обновляет квартиры
 *
 * CLI:
 *   php local/tools/import_apartments_from_seed.php --dry-run=1
 *   php local/tools/import_apartments_from_seed.php --dry-run=0
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
		"source::",
		"dry-run::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/import_apartments_from_seed.php [--source=/local/tools/data/apartments-seed.php] [--dry-run=1]\n";
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

$sourceRel = isset($_REQUEST["source"]) && $_REQUEST["source"] !== "" ? (string)$_REQUEST["source"] : "/local/tools/data/apartments-seed.php";
$sourceFile = strpos($sourceRel, "/") === 0 ? $_SERVER["DOCUMENT_ROOT"] . $sourceRel : $_SERVER["DOCUMENT_ROOT"] . "/" . ltrim($sourceRel, "/");
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

echo "Source: " . $sourceFile . PHP_EOL;
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

if (!is_file($sourceFile)) {
	echo "[ERROR] Source file not found: " . $sourceFile . PHP_EOL;
	exit(2);
}

$items = require $sourceFile;
if (!is_array($items)) {
	echo "[ERROR] Source file returned invalid data" . PHP_EOL;
	exit(3);
}

function findIblockByCodeForApartmentImport($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function findProjectByCodeForApartmentImport($iblockId, $code)
{
	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => (int)$iblockId, "=CODE" => $code),
		false,
		false,
		array("ID", "NAME", "CODE")
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function getSectionEntityIdForApartmentImport($iblockId)
{
	return "IBLOCK_" . (int)$iblockId . "_SECTION";
}

function getUserFieldRowForApartmentImport($entityId, $fieldName)
{
	$res = CUserTypeEntity::GetList(array("ID" => "ASC"), array("ENTITY_ID" => $entityId, "FIELD_NAME" => $fieldName));
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function getUserFieldEnumIdForApartmentImport($entityId, $fieldName, $xmlId)
{
	$field = getUserFieldRowForApartmentImport($entityId, $fieldName);
	if (!is_array($field)) {
		return 0;
	}

	$res = CUserFieldEnum::GetList(array("SORT" => "ASC"), array("USER_FIELD_ID" => (int)$field["ID"]));
	while ($row = $res->Fetch()) {
		if (trim((string)$row["XML_ID"]) === (string)$xmlId) {
			return (int)$row["ID"];
		}
	}

	return 0;
}

function getPropertyRowForApartmentImport($iblockId, $code)
{
	$res = CIBlockProperty::GetList(array("SORT" => "ASC", "ID" => "ASC"), array("IBLOCK_ID" => (int)$iblockId, "CODE" => $code));
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function getPropertyEnumIdForApartmentImport($iblockId, $propertyCode, $xmlId)
{
	$property = getPropertyRowForApartmentImport($iblockId, $propertyCode);
	if (!is_array($property)) {
		return 0;
	}

	$res = CIBlockPropertyEnum::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => (int)$iblockId, "CODE" => $propertyCode)
	);
	while ($row = $res->Fetch()) {
		if (trim((string)$row["XML_ID"]) === (string)$xmlId) {
			return (int)$row["ID"];
		}
	}

	return 0;
}

function normalizeRoomXmlIdForApartmentImport($value)
{
	$value = trim((string)$value);
	if ($value === "") {
		return "";
	}

	$valueLower = mb_strtolower($value);
	if ($valueLower === "studio" || preg_match("/студ/iu", $value)) {
		return "studio";
	}
	if ($valueLower === "2e" || preg_match("/евро\\s*дв|евродв|\\b2\\s*[еe]\\b/iu", $value)) {
		return "2e";
	}
	if ($valueLower === "3e" || preg_match("/евро\\s*тр|евротр|\\b3\\s*[еe]\\b/iu", $value)) {
		return "3e";
	}
	if (preg_match("/^1k$/iu", $value) || preg_match("/\\b1\\s*(?:[- ]?ком|[кk])\\b/iu", $value)) {
		return "1k";
	}
	if (preg_match("/^2k$/iu", $value) || preg_match("/\\b2\\s*(?:[- ]?ком|[кk])\\b/iu", $value)) {
		return "2k";
	}
	if (preg_match("/^3k$/iu", $value) || preg_match("/\\b3\\s*(?:[- ]?ком|[кk])\\b/iu", $value)) {
		return "3k";
	}
	if (preg_match("/^4k$/iu", $value) || preg_match("/\\b4\\s*(?:[- ]?ком|[кk])\\b/iu", $value)) {
		return "4k";
	}

	return $valueLower;
}

function normalizeFinishXmlIdForApartmentImport($value)
{
	$value = trim((string)$value);
	if ($value === "") {
		return "";
	}

	$valueLower = mb_strtolower($value);
	if ($valueLower === "no_finish" || preg_match("/без\\s+отделк/iu", $value)) {
		return "no_finish";
	}
	if ($valueLower === "whitebox" || preg_match("/предчист/iu", $value)) {
		return "whitebox";
	}
	if ($valueLower === "finish" || preg_match("/чистов/iu", $value)) {
		return "finish";
	}
	if ($valueLower === "design" || preg_match("/дизайнер/iu", $value)) {
		return "design";
	}

	return $valueLower;
}

function makeFileArrayForApartmentImport($path)
{
	$path = trim((string)$path);
	if ($path === "") {
		return false;
	}

	$path = str_replace("\\", "/", $path);
	$absolutePath = $_SERVER["DOCUMENT_ROOT"] . $path;
	if (!is_file($absolutePath)) {
		return false;
	}

	return CFile::MakeFileArray($absolutePath);
}

function normalizeSectionCodeForApartmentImport($value)
{
	$value = trim((string)$value);
	$value = mb_strtolower($value);
	$value = preg_replace("/[^a-z0-9_-]+/u", "-", $value);
	$value = preg_replace("/-+/u", "-", $value);
	return trim((string)$value, "-");
}

function normalizeElementCodePartForApartmentImport($value)
{
	$value = trim((string)$value);
	$value = mb_strtolower($value);
	$value = preg_replace("/[^a-z0-9_-]+/u", "-", $value);
	$value = preg_replace("/-+/u", "-", $value);
	return trim((string)$value, "-");
}

function buildApartmentCodeForImport(array $item)
{
	$explicitCode = isset($item["code"]) ? normalizeElementCodePartForApartmentImport($item["code"]) : "";
	if ($explicitCode !== "") {
		return $explicitCode;
	}

	$projectCode = isset($item["project_code"]) ? normalizeElementCodePartForApartmentImport($item["project_code"]) : "";
	$corpus = isset($item["corpus"]) ? normalizeElementCodePartForApartmentImport($item["corpus"]) : "";
	$apartmentNumber = isset($item["apartment_number"]) ? normalizeElementCodePartForApartmentImport($item["apartment_number"]) : "";

	if (function_exists("szcubeBuildApartmentCode")) {
		return (string)szcubeBuildApartmentCode($projectCode, $apartmentNumber, $corpus);
	}

	$parts = array($projectCode);
	if ($corpus !== "") {
		$parts[] = "c" . ltrim($corpus, "c");
	}
	$parts[] = $apartmentNumber;

	$parts = array_values(array_filter($parts, static function ($value) {
		return trim((string)$value) !== "";
	}));

	return implode("-", $parts);
}

function buildApartmentXmlIdForImport(array $item, $fallbackCode)
{
	$explicitXmlId = isset($item["xml_id"]) ? trim((string)$item["xml_id"]) : "";
	if ($explicitXmlId !== "") {
		return $explicitXmlId;
	}

	$externalId = isset($item["external_id"]) ? trim((string)$item["external_id"]) : "";
	if ($externalId !== "") {
		return $externalId;
	}

	return trim((string)$fallbackCode);
}

function findSectionByCodeAndParentForApartmentImport($iblockId, $parentId, $code)
{
	$res = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array(
			"IBLOCK_ID" => (int)$iblockId,
			"SECTION_ID" => $parentId > 0 ? (int)$parentId : false,
			"=CODE" => $code,
		),
		false,
		array("ID", "NAME", "CODE", "UF_NODE_TYPE", "UF_ENTRANCE_NUMBER", "UF_FLOOR_NUMBER")
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function updateSectionIfNeededForApartmentImport($sectionId, array $fields, $dryRun)
{
	if ($dryRun) {
		return true;
	}

	$sectionApi = new CIBlockSection();
	return (bool)$sectionApi->Update((int)$sectionId, $fields);
}

function ensureSectionForApartmentImport($iblockId, $parentId, $code, $name, array $fields, $dryRun)
{
	static $virtualSections = array();
	static $nextVirtualId = -1;

	$cacheKey = (int)$iblockId . ":" . (int)$parentId . ":" . (string)$code;
	if ($dryRun && isset($virtualSections[$cacheKey])) {
		echo "[OK] Section exists (virtual): " . $code . " (ID=" . (int)$virtualSections[$cacheKey] . ")" . PHP_EOL;
		return (int)$virtualSections[$cacheKey];
	}

	$existing = findSectionByCodeAndParentForApartmentImport($iblockId, $parentId, $code);
	if (is_array($existing)) {
		echo "[OK] Section exists: " . $code . " (ID=" . (int)$existing["ID"] . ")" . PHP_EOL;
		$updateFields = array(
			"NAME" => $name,
			"ACTIVE" => "Y",
			"SORT" => isset($fields["SORT"]) ? (int)$fields["SORT"] : 500,
		) + $fields;
		updateSectionIfNeededForApartmentImport((int)$existing["ID"], $updateFields, $dryRun);
		return (int)$existing["ID"];
	}

	echo "[CREATE] Section: " . $code . " (" . $name . ")" . PHP_EOL;
	if ($dryRun) {
		$virtualSections[$cacheKey] = $nextVirtualId;
		$nextVirtualId--;
		return (int)$virtualSections[$cacheKey];
	}

	$sectionApi = new CIBlockSection();
	$newId = (int)$sectionApi->Add(array(
		"IBLOCK_ID" => (int)$iblockId,
		"IBLOCK_SECTION_ID" => $parentId > 0 ? (int)$parentId : false,
		"ACTIVE" => "Y",
		"NAME" => $name,
		"CODE" => $code,
		"SORT" => isset($fields["SORT"]) ? (int)$fields["SORT"] : 500,
	) + $fields);
	if ($newId <= 0) {
		echo "[ERROR] Failed to create section " . $code . ": " . $sectionApi->LAST_ERROR . PHP_EOL;
		return 0;
	}

	$virtualSections[$cacheKey] = $newId;
	return $newId;
}

function findApartmentElementByCodeForImport($iblockId, $code)
{
	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => (int)$iblockId, "=CODE" => $code),
		false,
		false,
		array("ID", "IBLOCK_SECTION_ID", "NAME", "CODE")
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function findApartmentElementByXmlIdForImport($iblockId, $xmlId)
{
	$xmlId = trim((string)$xmlId);
	if ($xmlId === "") {
		return null;
	}

	$res = CIBlockElement::GetList(
		array(),
		array("IBLOCK_ID" => (int)$iblockId, "=XML_ID" => $xmlId),
		false,
		false,
		array("ID", "IBLOCK_SECTION_ID", "NAME", "CODE", "XML_ID")
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function findApartmentElementByIdentityForImport($iblockId, array $identity)
{
	$projectId = isset($identity["project_id"]) ? (int)$identity["project_id"] : 0;
	$entrance = isset($identity["entrance"]) ? trim((string)$identity["entrance"]) : "";
	$floor = isset($identity["floor"]) ? (int)$identity["floor"] : 0;
	$apartmentNumber = isset($identity["apartment_number"]) ? trim((string)$identity["apartment_number"]) : "";

	if ($projectId <= 0 || $entrance === "" || $floor <= 0 || $apartmentNumber === "") {
		return null;
	}

	$res = CIBlockElement::GetList(
		array(),
		array(
			"IBLOCK_ID" => (int)$iblockId,
			"PROPERTY_PROJECT" => $projectId,
			"PROPERTY_ENTRANCE" => $entrance,
			"PROPERTY_FLOOR" => $floor,
			"PROPERTY_APARTMENT_NUMBER" => $apartmentNumber,
		),
		false,
		false,
		array("ID", "IBLOCK_SECTION_ID", "NAME", "CODE", "XML_ID")
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function resolveExistingApartmentElementForImport($iblockId, $xmlId, $code, array $identity)
{
	$existing = findApartmentElementByXmlIdForImport($iblockId, $xmlId);
	if (is_array($existing)) {
		return $existing;
	}

	$existing = findApartmentElementByCodeForImport($iblockId, $code);
	if (is_array($existing)) {
		return $existing;
	}

	return findApartmentElementByIdentityForImport($iblockId, $identity);
}

function upsertApartmentElementForImport($iblockId, $xmlId, $code, array $identity, array $fields, array $propertyValues, $dryRun)
{
	$existing = resolveExistingApartmentElementForImport($iblockId, $xmlId, $code, $identity);
	$action = is_array($existing) ? "UPDATE" : "CREATE";
	echo "[" . $action . "] Apartment: " . $code . ($xmlId !== "" ? " (XML_ID=" . $xmlId . ")" : "") . PHP_EOL;

	if ($dryRun) {
		return true;
	}

	$filePropertyCodes = array(
		"PLAN_IMAGE",
		"FLOOR_SLIDE_IMAGE",
		"BUILDING_SLIDE_IMAGE",
		"VIEW_SLIDE_IMAGE",
		"RENDER_SLIDE_IMAGE",
	);
	$scalarPropertyValues = array();
	$filePropertyValues = array();
	foreach ($propertyValues as $propertyCode => $propertyValue) {
		if (in_array((string)$propertyCode, $filePropertyCodes, true)) {
			$filePropertyValues[$propertyCode] = $propertyValue;
			continue;
		}

		$scalarPropertyValues[$propertyCode] = $propertyValue;
	}

	$elementApi = new CIBlockElement();
	if (is_array($existing)) {
		if (!empty($scalarPropertyValues)) {
			CIBlockElement::SetPropertyValuesEx((int)$existing["ID"], $iblockId, $scalarPropertyValues);
		}
		$ok = $elementApi->Update((int)$existing["ID"], $fields);
		if (!$ok) {
			echo "[ERROR] Failed to update apartment " . $code . ": " . $elementApi->LAST_ERROR . PHP_EOL;
			return false;
		}

		foreach ($filePropertyValues as $propertyCode => $fileValue) {
			CIBlockElement::SetPropertyValueCode((int)$existing["ID"], $propertyCode, array("VALUE" => $fileValue));
		}

		return true;
	}

	$newId = (int)$elementApi->Add($fields);
	if ($newId <= 0) {
		echo "[ERROR] Failed to create apartment " . $code . ": " . $elementApi->LAST_ERROR . PHP_EOL;
		return false;
	}

	if (!empty($scalarPropertyValues)) {
		CIBlockElement::SetPropertyValuesEx($newId, $iblockId, $scalarPropertyValues);
	}
	foreach ($filePropertyValues as $propertyCode => $fileValue) {
		CIBlockElement::SetPropertyValueCode($newId, $propertyCode, array("VALUE" => $fileValue));
	}

	return true;
}

$apartmentsIblock = findIblockByCodeForApartmentImport("apartments");
$projectsIblock = findIblockByCodeForApartmentImport("projects");
if (!is_array($apartmentsIblock) || !is_array($projectsIblock)) {
	echo "[ERROR] Required iblocks not found. Run create_apartments_iblocks.php first." . PHP_EOL;
	exit(4);
}

$apartmentsIblockId = (int)$apartmentsIblock["ID"];
$projectsIblockId = (int)$projectsIblock["ID"];
$sectionEntityId = getSectionEntityIdForApartmentImport($apartmentsIblockId);
$projectNodeTypeId = getUserFieldEnumIdForApartmentImport($sectionEntityId, "UF_NODE_TYPE", "project");
$entranceNodeTypeId = getUserFieldEnumIdForApartmentImport($sectionEntityId, "UF_NODE_TYPE", "entrance");
$floorNodeTypeId = getUserFieldEnumIdForApartmentImport($sectionEntityId, "UF_NODE_TYPE", "floor");
$statusFreeId = getPropertyEnumIdForApartmentImport($apartmentsIblockId, "STATUS", "free");
$statusBookedId = getPropertyEnumIdForApartmentImport($apartmentsIblockId, "STATUS", "booked");
$statusSoldId = getPropertyEnumIdForApartmentImport($apartmentsIblockId, "STATUS", "sold");

$statusMap = array(
	"free" => $statusFreeId,
	"booked" => $statusBookedId,
	"sold" => $statusSoldId,
);

$roomsMap = array(
	"studio" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "studio"),
	"1k" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "1k"),
	"2k" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "2k"),
	"2e" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "2e"),
	"3k" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "3k"),
	"3e" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "3e"),
	"4k" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "ROOMS", "4k"),
);

$finishMap = array(
	"no_finish" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "FINISH", "no_finish"),
	"whitebox" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "FINISH", "whitebox"),
	"finish" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "FINISH", "finish"),
	"design" => getPropertyEnumIdForApartmentImport($apartmentsIblockId, "FINISH", "design"),
);

if ($projectNodeTypeId <= 0 || $entranceNodeTypeId <= 0 || $floorNodeTypeId <= 0) {
	echo "[ERROR] Required UF_NODE_TYPE enums not found." . PHP_EOL;
	exit(5);
}

if (in_array(0, $roomsMap, true)) {
	echo "[ERROR] Required ROOMS enums not found." . PHP_EOL;
	exit(6);
}

if (in_array(0, $finishMap, true)) {
	echo "[ERROR] Required FINISH enums not found." . PHP_EOL;
	exit(7);
}

foreach ($items as $itemIndex => $item) {
	if (!is_array($item)) {
		continue;
	}

	$projectCode = isset($item["project_code"]) ? trim((string)$item["project_code"]) : "";
	$entrance = isset($item["entrance"]) ? trim((string)$item["entrance"]) : "";
	$floor = isset($item["floor"]) ? (int)$item["floor"] : 0;
	$apartmentNumber = isset($item["apartment_number"]) ? trim((string)$item["apartment_number"]) : "";
	$code = buildApartmentCodeForImport($item);
	$xmlId = buildApartmentXmlIdForImport($item, $code);

	if ($code === "" || $projectCode === "" || $entrance === "" || $floor <= 0 || $apartmentNumber === "") {
		echo "[WARN] Skip item #" . $itemIndex . " due to missing required fields." . PHP_EOL;
		continue;
	}

	echo PHP_EOL . "[ITEM] " . $code . " :: project=" . $projectCode . ", entrance=" . $entrance . ", floor=" . $floor . PHP_EOL;

	$project = findProjectByCodeForApartmentImport($projectsIblockId, $projectCode);
	if (!is_array($project)) {
		echo "[ERROR] Project not found by code: " . $projectCode . PHP_EOL;
		exit(6);
	}
	$projectId = (int)$project["ID"];

	$projectSection = findSectionByCodeAndParentForApartmentImport($apartmentsIblockId, 0, $projectCode);
	if (!is_array($projectSection)) {
		echo "[ERROR] Top project section not found for code: " . $projectCode . PHP_EOL;
		exit(7);
	}
	if ((int)$projectSection["UF_NODE_TYPE"] !== $projectNodeTypeId && !$dryRun) {
		updateSectionIfNeededForApartmentImport((int)$projectSection["ID"], array("UF_NODE_TYPE" => $projectNodeTypeId), false);
	}
	$projectSectionId = (int)$projectSection["ID"];

	$entranceCode = "podezd-" . normalizeSectionCodeForApartmentImport($entrance);
	$entranceName = "Подъезд " . $entrance;
	$entranceFields = array(
		"UF_NODE_TYPE" => $entranceNodeTypeId,
		"UF_ENTRANCE_NUMBER" => $entrance,
		"UF_PIN_X" => isset($item["entrance_pin_x"]) ? trim((string)$item["entrance_pin_x"]) : "",
		"UF_PIN_Y" => isset($item["entrance_pin_y"]) ? trim((string)$item["entrance_pin_y"]) : "",
		"UF_PIN_LABEL" => isset($item["entrance_pin_label"]) ? trim((string)$item["entrance_pin_label"]) : "",
		"SORT" => ((int)$entrance > 0 ? (int)$entrance : 1) * 100,
	);
	$entranceSectionId = ensureSectionForApartmentImport($apartmentsIblockId, $projectSectionId, $entranceCode, $entranceName, array(
		"UF_NODE_TYPE" => $entranceNodeTypeId,
		"UF_ENTRANCE_NUMBER" => $entrance,
		"UF_PIN_X" => $entranceFields["UF_PIN_X"],
		"UF_PIN_Y" => $entranceFields["UF_PIN_Y"],
		"UF_PIN_LABEL" => $entranceFields["UF_PIN_LABEL"],
		"SORT" => $entranceFields["SORT"],
	), $dryRun);
	if ($entranceSectionId === 0) {
		exit(8);
	}

	$floorCode = sprintf("floor-%02d", $floor);
	$floorName = $floor . " этаж";
	$floorSectionFields = array(
		"UF_NODE_TYPE" => $floorNodeTypeId,
		"UF_FLOOR_NUMBER" => $floor,
		"SORT" => $floor * 100,
	);
	$chessSvgFile = makeFileArrayForApartmentImport(isset($item["floor_chess_svg"]) ? $item["floor_chess_svg"] : "");
	if ($chessSvgFile !== false) {
		$floorSectionFields["UF_CHESS_SVG"] = $chessSvgFile;
	}
	$chessImageFile = makeFileArrayForApartmentImport(isset($item["floor_chess_image"]) ? $item["floor_chess_image"] : "");
	if ($chessImageFile !== false) {
		$floorSectionFields["UF_CHESS_IMAGE"] = $chessImageFile;
	}
	$floorSectionId = ensureSectionForApartmentImport($apartmentsIblockId, $entranceSectionId, $floorCode, $floorName, array(
		"UF_NODE_TYPE" => $floorSectionFields["UF_NODE_TYPE"],
		"UF_FLOOR_NUMBER" => $floorSectionFields["UF_FLOOR_NUMBER"],
		"SORT" => $floorSectionFields["SORT"],
	) + $floorSectionFields, $dryRun);
	if ($floorSectionId === 0) {
		exit(9);
	}

	$statusXmlId = isset($item["status"]) ? trim((string)$item["status"]) : "free";
	$statusEnumId = isset($statusMap[$statusXmlId]) ? (int)$statusMap[$statusXmlId] : 0;
	if ($statusEnumId <= 0) {
		echo "[ERROR] Unknown apartment status: " . $statusXmlId . PHP_EOL;
		exit(10);
	}

	$roomsXmlId = normalizeRoomXmlIdForApartmentImport(isset($item["rooms"]) ? $item["rooms"] : "");
	$roomsEnumId = isset($roomsMap[$roomsXmlId]) ? (int)$roomsMap[$roomsXmlId] : 0;
	if ($roomsEnumId <= 0) {
		echo "[ERROR] Unknown apartment rooms value: " . (isset($item["rooms"]) ? (string)$item["rooms"] : "") . PHP_EOL;
		exit(10);
	}

	$finishXmlId = normalizeFinishXmlIdForApartmentImport(isset($item["finish"]) ? $item["finish"] : "");
	$finishEnumId = $finishXmlId !== "" && isset($finishMap[$finishXmlId]) ? (int)$finishMap[$finishXmlId] : 0;
	if ($finishXmlId !== "" && $finishEnumId <= 0) {
		echo "[ERROR] Unknown apartment finish value: " . (isset($item["finish"]) ? (string)$item["finish"] : "") . PHP_EOL;
		exit(10);
	}

	$name = isset($item["name"]) && trim((string)$item["name"]) !== "" ? trim((string)$item["name"]) : ("Квартира №" . $apartmentNumber);
	$fields = array(
		"IBLOCK_ID" => $apartmentsIblockId,
		"IBLOCK_SECTION_ID" => $floorSectionId > 0 ? $floorSectionId : false,
		"ACTIVE" => "Y",
		"NAME" => $name,
		"CODE" => $code,
		"XML_ID" => $xmlId,
		"SORT" => isset($item["sort_in_floor"]) ? (int)$item["sort_in_floor"] : ($floor * 100),
	);

	$planFile = makeFileArrayForApartmentImport(isset($item["plan_image"]) ? $item["plan_image"] : "");
	if ($planFile !== false) {
		$fields["PREVIEW_PICTURE"] = $planFile;
	}

	$propertyValues = array(
		"PROJECT" => $projectId,
		"CORPUS" => isset($item["corpus"]) ? trim((string)$item["corpus"]) : "",
		"ENTRANCE" => $entrance,
		"FLOOR" => $floor,
		"FLOOR_TO" => isset($item["floor_to"]) && (int)$item["floor_to"] > $floor ? (int)$item["floor_to"] : false,
		"HOUSE_FLOORS" => isset($item["house_floors"]) ? (int)$item["house_floors"] : 0,
		"APARTMENT_NUMBER" => $apartmentNumber,
		"ROOMS" => $roomsEnumId,
		"AREA_TOTAL" => isset($item["area_total"]) ? (float)$item["area_total"] : 0,
		"AREA_LIVING" => isset($item["area_living"]) ? (float)$item["area_living"] : 0,
		"AREA_KITCHEN" => isset($item["area_kitchen"]) ? (float)$item["area_kitchen"] : 0,
		"PRICE_TOTAL" => isset($item["price_total"]) ? (float)$item["price_total"] : 0,
		"PRICE_OLD" => isset($item["price_old"]) && (float)$item["price_old"] > 0 ? (float)$item["price_old"] : false,
		"PRICE_M2" => isset($item["price_m2"]) ? (float)$item["price_m2"] : 0,
		"STATUS" => $statusEnumId,
		"DISCOUNT_LABEL" => "",
		"FINISH" => $finishEnumId > 0 ? $finishEnumId : "",
		"CEILING" => isset($item["ceiling"]) ? (float)$item["ceiling"] : 0,
		"VIEW_TEXT" => isset($item["view_text"]) ? trim((string)$item["view_text"]) : "",
		"WINDOW_SIDES" => isset($item["window_sides"]) ? trim((string)$item["window_sides"]) : "",
		"BALCONY_TYPE" => isset($item["balcony_type"]) ? trim((string)$item["balcony_type"]) : "",
		"BATHROOMS" => isset($item["bathrooms"]) ? (int)$item["bathrooms"] : 0,
		"PLAN_TITLE" => isset($item["plan_title"]) ? trim((string)$item["plan_title"]) : "",
		"PLAN_TEXT" => isset($item["plan_text"]) ? trim((string)$item["plan_text"]) : "",
		"PLAN_ALT" => isset($item["plan_alt"]) ? trim((string)$item["plan_alt"]) : "",
		"FLOOR_SLIDE_TITLE" => isset($item["floor_slide_title"]) ? trim((string)$item["floor_slide_title"]) : "",
		"FLOOR_SLIDE_TEXT" => isset($item["floor_slide_text"]) ? trim((string)$item["floor_slide_text"]) : "",
		"FLOOR_SLIDE_ALT" => isset($item["floor_slide_alt"]) ? trim((string)$item["floor_slide_alt"]) : "",
		"BUILDING_SLIDE_TITLE" => isset($item["building_slide_title"]) ? trim((string)$item["building_slide_title"]) : "",
		"BUILDING_SLIDE_TEXT" => isset($item["building_slide_text"]) ? trim((string)$item["building_slide_text"]) : "",
		"BUILDING_SLIDE_ALT" => isset($item["building_slide_alt"]) ? trim((string)$item["building_slide_alt"]) : "",
		"VIEW_SLIDE_TITLE" => isset($item["view_slide_title"]) ? trim((string)$item["view_slide_title"]) : "",
		"VIEW_SLIDE_TEXT" => isset($item["view_slide_text"]) ? trim((string)$item["view_slide_text"]) : "",
		"VIEW_SLIDE_ALT" => isset($item["view_slide_alt"]) ? trim((string)$item["view_slide_alt"]) : "",
		"RENDER_SLIDE_TITLE" => isset($item["render_slide_title"]) ? trim((string)$item["render_slide_title"]) : "",
		"RENDER_SLIDE_TEXT" => isset($item["render_slide_text"]) ? trim((string)$item["render_slide_text"]) : "",
		"RENDER_SLIDE_ALT" => isset($item["render_slide_alt"]) ? trim((string)$item["render_slide_alt"]) : "",
		"SVG_SLOT_ID" => isset($item["svg_slot_id"]) ? trim((string)$item["svg_slot_id"]) : "",
		"SORT_IN_FLOOR" => isset($item["sort_in_floor"]) ? (int)$item["sort_in_floor"] : 0,
	);

	$badges = array();
	if (isset($item["badges"]) && is_array($item["badges"])) {
		$badges = array_values(array_filter(array_map("trim", $item["badges"]), static function ($value) {
			return $value !== "";
		}));
	}
	$propertyValues["BADGES"] = !empty($badges) ? $badges : false;

	if (isset($item["feature_tags"]) && is_array($item["feature_tags"])) {
		$propertyValues["FEATURE_TAGS"] = array_values(array_filter(array_map("trim", $item["feature_tags"]), static function ($value) {
			return $value !== "";
		}));
	}

	if ($planFile !== false) {
		$propertyValues["PLAN_IMAGE"] = $planFile;
	}

	$slideImageMap = array(
		"FLOOR_SLIDE_IMAGE" => isset($item["floor_slide_image"]) ? $item["floor_slide_image"] : "",
		"BUILDING_SLIDE_IMAGE" => isset($item["building_slide_image"]) ? $item["building_slide_image"] : "",
		"VIEW_SLIDE_IMAGE" => isset($item["view_slide_image"]) ? $item["view_slide_image"] : "",
		"RENDER_SLIDE_IMAGE" => isset($item["render_slide_image"]) ? $item["render_slide_image"] : "",
	);
	foreach ($slideImageMap as $propertyCode => $path) {
		$file = makeFileArrayForApartmentImport($path);
		if ($file !== false) {
			$propertyValues[$propertyCode] = $file;
		}
	}

	$identity = array(
		"project_id" => $projectId,
		"entrance" => $entrance,
		"floor" => $floor,
		"apartment_number" => $apartmentNumber,
	);

	if (!upsertApartmentElementForImport($apartmentsIblockId, $xmlId, $code, $identity, $fields, $propertyValues, $dryRun)) {
		exit(11);
	}
}

echo PHP_EOL . "Done." . PHP_EOL;

exit(0);
