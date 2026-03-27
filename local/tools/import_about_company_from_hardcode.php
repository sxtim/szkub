<?php
/**
 * Импортирует текущий хардкод страницы /about-company/:
 * - singleton-элемент ИБ about_company_page
 * - элементы ИБ about_company_social_gallery
 * - новые свойства в ИБ projects для блока "Наши проекты"
 *
 * CLI:
 *   php local/tools/import_about_company_from_hardcode.php --dry-run=1
 *   php local/tools/import_about_company_from_hardcode.php --dry-run=0
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
	$options = getopt("", array("dry-run::", "help::"));
	if (isset($options["help"])) {
		echo "Usage: php local/tools/import_about_company_from_hardcode.php [--dry-run=1]\n";
		exit(0);
	}
	if (isset($options["dry-run"])) {
		$_REQUEST["dry_run"] = $options["dry-run"];
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

$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";
echo "dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

if (!defined("SITE_TEMPLATE_PATH")) {
	define("SITE_TEMPLATE_PATH", "/local/templates/szcube");
}

$dataFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/about-company-hardcode-source.php";
if (!is_file($dataFile)) {
	echo "Source file not found: " . $dataFile . PHP_EOL;
	exit(1);
}

$data = require $dataFile;
if (!is_array($data)) {
	echo "Source file returned unexpected data type" . PHP_EOL;
	exit(1);
}

function aboutCompanyImportFindIblock($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => (string)$code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function aboutCompanyImportFindElementId($iblockId, $code)
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

function aboutCompanyImportMakeFileArray($path)
{
	$path = trim((string)$path);
	if ($path === "") {
		return false;
	}

	$absPath = $_SERVER["DOCUMENT_ROOT"] . str_replace("\\", "/", $path);
	if (!is_file($absPath)) {
		return false;
	}

	static $tempFiles = null;
	if ($tempFiles === null) {
		$tempFiles = array();
		register_shutdown_function(function () use (&$tempFiles) {
			foreach ($tempFiles as $tempFile) {
				if (is_file($tempFile)) {
					@unlink($tempFile);
				}
			}
		});
	}

	$extension = pathinfo($absPath, PATHINFO_EXTENSION);
	$uniqueName = "about-company-" . md5($absPath . microtime(true) . mt_rand()) . ($extension !== "" ? "." . $extension : "");
	$tempPath = rtrim((string)sys_get_temp_dir(), "/") . "/" . $uniqueName;

	if (!@copy($absPath, $tempPath)) {
		return CFile::MakeFileArray($absPath);
	}

	$tempFiles[] = $tempPath;

	return CFile::MakeFileArray($tempPath);
}

function aboutCompanyImportGetSourceAbsolutePath($path)
{
	$path = trim((string)$path);
	if ($path === "") {
		return "";
	}

	$absPath = $_SERVER["DOCUMENT_ROOT"] . str_replace("\\", "/", $path);
	return is_file($absPath) ? $absPath : "";
}

function aboutCompanyImportEnsureFileStoredById($fileId, $sourcePath)
{
	$fileId = (int)$fileId;
	if ($fileId <= 0) {
		return;
	}

	$sourceAbsPath = aboutCompanyImportGetSourceAbsolutePath($sourcePath);
	if ($sourceAbsPath === "") {
		return;
	}

	$file = CFile::GetFileArray($fileId);
	if (!is_array($file) || empty($file["SRC"])) {
		return;
	}

	$targetAbsPath = $_SERVER["DOCUMENT_ROOT"] . (string)$file["SRC"];
	if (is_file($targetAbsPath)) {
		return;
	}

	$targetDir = dirname($targetAbsPath);
	if (!is_dir($targetDir)) {
		@mkdir($targetDir, BX_DIR_PERMISSIONS, true);
	}

	@copy($sourceAbsPath, $targetAbsPath);
}

function aboutCompanyImportEnsureElementFileProperty($iblockId, $elementId, $propertyCode, $sourcePath)
{
	$property = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("sort" => "asc"),
		array("CODE" => (string)$propertyCode)
	)->Fetch();

	if (is_array($property) && !empty($property["VALUE"])) {
		aboutCompanyImportEnsureFileStoredById((int)$property["VALUE"], $sourcePath);
	}
}

function aboutCompanyImportEnsureElementPreviewPicture($elementId, $sourcePath)
{
	$element = CIBlockElement::GetList(
		array(),
		array("ID" => (int)$elementId),
		false,
		false,
		array("ID", "PREVIEW_PICTURE")
	)->GetNext();

	if (is_array($element) && !empty($element["PREVIEW_PICTURE"])) {
		aboutCompanyImportEnsureFileStoredById((int)$element["PREVIEW_PICTURE"], $sourcePath);
	}
}

function aboutCompanyImportFindProperty($iblockId, $code)
{
	$res = CIBlockProperty::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("IBLOCK_ID" => (int)$iblockId, "CODE" => (string)$code)
	);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function aboutCompanyImportResolveEnumId($iblockId, $propertyCode, $xmlId, $fallbackValue = "")
{
	$property = aboutCompanyImportFindProperty($iblockId, $propertyCode);
	if (!is_array($property)) {
		return false;
	}

	$propertyId = (int)$property["ID"];
	$res = CIBlockPropertyEnum::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array("PROPERTY_ID" => $propertyId)
	);
	while ($row = $res->Fetch()) {
		$rowXmlId = isset($row["XML_ID"]) ? trim((string)$row["XML_ID"]) : "";
		$rowValue = isset($row["VALUE"]) ? trim((string)$row["VALUE"]) : "";
		if ($rowXmlId !== "" && $rowXmlId === (string)$xmlId) {
			return (int)$row["ID"];
		}
		if ($fallbackValue !== "" && $rowValue === (string)$fallbackValue) {
			return (int)$row["ID"];
		}
	}

	return false;
}

function aboutCompanyImportResolveYesNoEnumId($iblockId, $propertyCode, $value)
{
	$normalized = strtoupper(trim((string)$value));
	$xmlId = in_array($normalized, array("Y", "YES", "1", "TRUE", "ДА"), true) ? "Y" : "N";
	$fallbackValue = $xmlId === "Y" ? "Да" : "Нет";

	$enumId = aboutCompanyImportResolveEnumId($iblockId, $propertyCode, $xmlId, $fallbackValue);
	return $enumId !== false ? $enumId : false;
}

function aboutCompanyImportNormalizePropertyValue($iblockId, $propertyCode, $value)
{
	if (is_array($value) && isset($value["VALUE"])) {
		return $value;
	}

	$property = aboutCompanyImportFindProperty($iblockId, $propertyCode);
	if (!is_array($property)) {
		return $value;
	}

	$type = isset($property["PROPERTY_TYPE"]) ? (string)$property["PROPERTY_TYPE"] : "S";
	if ($type === "F") {
		$file = aboutCompanyImportMakeFileArray($value);
		return $file !== false ? $file : false;
	}

	if ($type === "L") {
		$fallbackValue = "";
		if ($propertyCode === "ABOUT_COMPANY_SHOW" || $propertyCode === "ABOUT_COMPANY_SALE_SHOW") {
			$fallbackValue = strtoupper((string)$value) === "Y" ? "Да" : "Нет";
		} elseif ($propertyCode === "ABOUT_COMPANY_STATUS") {
			$statusMap = array(
				"building" => "Строится",
				"planned" => "В проекте",
				"completed" => "Реализован",
			);
			$fallbackValue = isset($statusMap[$value]) ? $statusMap[$value] : "";
		} elseif ($propertyCode === "COLUMN") {
			$columnMap = array(
				"left" => "Левая",
				"right" => "Правая",
			);
			$fallbackValue = isset($columnMap[$value]) ? $columnMap[$value] : "";
		}
		$enumId = aboutCompanyImportResolveEnumId($iblockId, $propertyCode, (string)$value, $fallbackValue);
		return $enumId !== false ? $enumId : false;
	}

	if ($type === "N") {
		return (string)((int)$value);
	}

	return (string)$value;
}

function aboutCompanyImportBuildPropertyValues($iblockId, array $propertyMap)
{
	$result = array();
	foreach ($propertyMap as $code => $rawValue) {
		$result[$code] = aboutCompanyImportNormalizePropertyValue($iblockId, $code, $rawValue);
	}

	return $result;
}

$pageIblock = aboutCompanyImportFindIblock("about_company_page");
$galleryIblock = aboutCompanyImportFindIblock("about_company_social_gallery");
$projectsIblock = aboutCompanyImportFindIblock("projects");

if (!is_array($pageIblock) || !is_array($galleryIblock) || !is_array($projectsIblock)) {
	echo "Required iblocks are missing. Run create scripts first." . PHP_EOL;
	exit(2);
}

$pageIblockId = (int)$pageIblock["ID"];
$galleryIblockId = (int)$galleryIblock["ID"];
$projectsIblockId = (int)$projectsIblock["ID"];

$elementApi = new CIBlockElement();
$saleShowEnumMap = array();
if ($projectsIblockId > 0) {
	$saleShowProperty = aboutCompanyImportFindProperty($projectsIblockId, "ABOUT_COMPANY_SALE_SHOW");
	if (is_array($saleShowProperty)) {
		$enumRes = CIBlockPropertyEnum::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			array("PROPERTY_ID" => (int)$saleShowProperty["ID"])
		);
		while ($enum = $enumRes->Fetch()) {
			$xmlId = isset($enum["XML_ID"]) ? strtoupper(trim((string)$enum["XML_ID"])) : "";
			if ($xmlId !== "") {
				$saleShowEnumMap[$xmlId] = (int)$enum["ID"];
			}
		}
	}
}

$pageData = isset($data["page"]) && is_array($data["page"]) ? $data["page"] : array();
$pageCode = isset($pageData["code"]) ? (string)$pageData["code"] : "main";
$pageName = isset($pageData["name"]) ? (string)$pageData["name"] : "О компании";
$pageElementId = aboutCompanyImportFindElementId($pageIblockId, $pageCode);
$pageFields = array(
	"IBLOCK_ID" => $pageIblockId,
	"ACTIVE" => "Y",
	"NAME" => $pageName,
	"CODE" => $pageCode,
	"SORT" => 100,
);
$pageProperties = aboutCompanyImportBuildPropertyValues(
	$pageIblockId,
	isset($pageData["properties"]) && is_array($pageData["properties"]) ? $pageData["properties"] : array()
);

echo ($pageElementId > 0 ? "[UPDATE]" : "[CREATE]") . " about_company_page :: " . $pageCode . PHP_EOL;
if (!$dryRun) {
	if ($pageElementId > 0) {
		if (!$elementApi->Update($pageElementId, $pageFields)) {
			echo "[ERROR] Failed to update page element: " . $elementApi->LAST_ERROR . PHP_EOL;
			exit(3);
		}
		CIBlockElement::SetPropertyValuesEx($pageElementId, $pageIblockId, $pageProperties);
	} else {
		$pageElementId = (int)$elementApi->Add($pageFields);
		if ($pageElementId <= 0) {
			echo "[ERROR] Failed to create page element: " . $elementApi->LAST_ERROR . PHP_EOL;
			exit(3);
		}
		CIBlockElement::SetPropertyValuesEx($pageElementId, $pageIblockId, $pageProperties);
	}

	$pageFileProperties = array(
		"HERO_IMAGE",
		"SOCIAL_METRIC_IMAGE",
		"SOCIAL_MATERIAL_IMAGE",
		"SALE_BACKGROUND_IMAGE",
	);
	foreach ($pageFileProperties as $propertyCode) {
		if (!empty($pageData["properties"][$propertyCode])) {
			aboutCompanyImportEnsureElementFileProperty($pageIblockId, $pageElementId, $propertyCode, $pageData["properties"][$propertyCode]);
		}
	}
}

$galleryItems = isset($data["gallery"]) && is_array($data["gallery"]) ? $data["gallery"] : array();
foreach ($galleryItems as $galleryItem) {
	if (!is_array($galleryItem)) {
		continue;
	}

	$galleryCode = isset($galleryItem["code"]) ? (string)$galleryItem["code"] : "";
	$galleryName = isset($galleryItem["name"]) ? (string)$galleryItem["name"] : $galleryCode;
	if ($galleryCode === "") {
		continue;
	}

	$galleryElementId = aboutCompanyImportFindElementId($galleryIblockId, $galleryCode);
	$galleryFields = array(
		"IBLOCK_ID" => $galleryIblockId,
		"ACTIVE" => "Y",
		"NAME" => $galleryName,
		"CODE" => $galleryCode,
		"SORT" => 100,
	);
	$galleryPreview = aboutCompanyImportMakeFileArray(isset($galleryItem["image"]) ? $galleryItem["image"] : "");
	if ($galleryPreview !== false) {
		$galleryFields["PREVIEW_PICTURE"] = $galleryPreview;
	}

	$galleryProperties = aboutCompanyImportBuildPropertyValues($galleryIblockId, array(
		"COLUMN" => isset($galleryItem["column"]) ? $galleryItem["column"] : "",
		"LABEL" => isset($galleryItem["label"]) ? $galleryItem["label"] : "",
		"ALT" => isset($galleryItem["alt"]) ? $galleryItem["alt"] : "",
		"ITEM_HEIGHT" => isset($galleryItem["height"]) ? $galleryItem["height"] : 0,
	));

	echo ($galleryElementId > 0 ? "[UPDATE]" : "[CREATE]") . " about_company_social_gallery :: " . $galleryCode . PHP_EOL;
	if ($dryRun) {
		continue;
	}

	if ($galleryElementId > 0) {
		if (!$elementApi->Update($galleryElementId, $galleryFields)) {
			echo "[ERROR] Failed to update gallery element {$galleryCode}: " . $elementApi->LAST_ERROR . PHP_EOL;
			exit(4);
		}
		CIBlockElement::SetPropertyValuesEx($galleryElementId, $galleryIblockId, $galleryProperties);
	} else {
		$galleryElementId = (int)$elementApi->Add($galleryFields);
		if ($galleryElementId <= 0) {
			echo "[ERROR] Failed to create gallery element {$galleryCode}: " . $elementApi->LAST_ERROR . PHP_EOL;
			exit(4);
		}
		CIBlockElement::SetPropertyValuesEx($galleryElementId, $galleryIblockId, $galleryProperties);
	}

	$galleryElement = CIBlockElement::GetList(
		array(),
		array("ID" => $galleryElementId),
		false,
		false,
		array("ID", "PREVIEW_PICTURE")
	)->GetNext();
	if (is_array($galleryElement) && !empty($galleryItem["image"])) {
		aboutCompanyImportEnsureFileStoredById((int)$galleryElement["PREVIEW_PICTURE"], $galleryItem["image"]);
	}
}

$projectItems = isset($data["projects"]) && is_array($data["projects"]) ? $data["projects"] : array();
foreach ($projectItems as $projectItem) {
	if (!is_array($projectItem) || empty($projectItem["code"])) {
		continue;
	}

	$projectCode = (string)$projectItem["code"];
	$projectElementId = aboutCompanyImportFindElementId($projectsIblockId, $projectCode);
	if ($projectElementId <= 0) {
		echo "[WARN] Project not found by code: {$projectCode}" . PHP_EOL;
		continue;
	}

	$projectProperties = aboutCompanyImportBuildPropertyValues($projectsIblockId, array(
		"ABOUT_COMPANY_SHOW" => isset($projectItem["show"]) ? $projectItem["show"] : "N",
		"ABOUT_COMPANY_SALE_SHOW" => isset($projectItem["sale_show"]) ? $projectItem["sale_show"] : "N",
		"ABOUT_COMPANY_STATUS" => isset($projectItem["status"]) ? $projectItem["status"] : "",
		"ABOUT_COMPANY_IMAGE" => isset($projectItem["image"]) ? $projectItem["image"] : "",
		"ABOUT_COMPANY_TEXT_1" => isset($projectItem["text_1"]) ? $projectItem["text_1"] : "",
		"ABOUT_COMPANY_TEXT_2" => isset($projectItem["text_2"]) ? $projectItem["text_2"] : "",
	));

	echo "[UPDATE] projects :: {$projectCode}" . PHP_EOL;
	if (!$dryRun) {
		CIBlockElement::SetPropertyValuesEx($projectElementId, $projectsIblockId, $projectProperties);
		if (isset($projectItem["sale_show"])) {
			$rawSaleValue = strtoupper(trim((string)$projectItem["sale_show"]));
			$normalizedSaleValue = in_array($rawSaleValue, array("Y", "YES", "1", "TRUE", "ДА"), true) ? "Y" : "N";
			if (isset($saleShowEnumMap[$normalizedSaleValue])) {
				CIBlockElement::SetPropertyValuesEx($projectElementId, $projectsIblockId, array(
					"ABOUT_COMPANY_SALE_SHOW" => $saleShowEnumMap[$normalizedSaleValue],
				));
			}
		}
		if (!empty($projectItem["image"])) {
			aboutCompanyImportEnsureElementFileProperty($projectsIblockId, $projectElementId, "ABOUT_COMPANY_IMAGE", $projectItem["image"]);
		}
	}
}

if (!$dryRun) {
	$pageRepairMap = array(
		"HERO_IMAGE",
		"SOCIAL_METRIC_IMAGE",
		"SOCIAL_MATERIAL_IMAGE",
		"SALE_BACKGROUND_IMAGE",
	);
	foreach ($pageRepairMap as $propertyCode) {
		if (!empty($pageData["properties"][$propertyCode])) {
			aboutCompanyImportEnsureElementFileProperty($pageIblockId, $pageElementId, $propertyCode, $pageData["properties"][$propertyCode]);
		}
	}

	foreach ($galleryItems as $galleryItem) {
		if (!is_array($galleryItem) || empty($galleryItem["code"]) || empty($galleryItem["image"])) {
			continue;
		}

		$galleryElementId = aboutCompanyImportFindElementId($galleryIblockId, (string)$galleryItem["code"]);
		if ($galleryElementId > 0) {
			aboutCompanyImportEnsureElementPreviewPicture($galleryElementId, $galleryItem["image"]);
		}
	}

	foreach ($projectItems as $projectItem) {
		if (!is_array($projectItem) || empty($projectItem["code"]) || empty($projectItem["image"])) {
			continue;
		}

		$projectElementId = aboutCompanyImportFindElementId($projectsIblockId, (string)$projectItem["code"]);
		if ($projectElementId > 0) {
			aboutCompanyImportEnsureElementFileProperty($projectsIblockId, $projectElementId, "ABOUT_COMPANY_IMAGE", $projectItem["image"]);
		}
	}
}

echo PHP_EOL . "Done." . PHP_EOL;
exit(0);
