<?php
/**
 * Одноразовый импорт новостей из local/tools/data/news-hardcode-source.php в инфоблок.
 *
 * Запуск (CLI):
 *   php local/tools/import_news_from_hardcode.php --iblock-id=123 --dry-run=1
 *   php local/tools/import_news_from_hardcode.php --iblock-id=123 --dry-run=0
 *
 * Запуск (web, только под админом):
 *   /local/tools/import_news_from_hardcode.php?iblock_id=123&dry_run=1
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
	$options = getopt("", array("iblock-id:", "dry-run::", "help::"));
	if (isset($options["help"])) {
		echo "Usage: php local/tools/import_news_from_hardcode.php --iblock-id=123 [--dry-run=1]\n";
		exit(0);
	}
	if (isset($options["iblock-id"])) {
		$_REQUEST["iblock_id"] = $options["iblock-id"];
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

$iblockId = isset($_REQUEST["iblock_id"]) ? (int)$_REQUEST["iblock_id"] : 0;
$dryRun = !isset($_REQUEST["dry_run"]) || (string)$_REQUEST["dry_run"] === "" || (string)$_REQUEST["dry_run"] === "1" || strtolower((string)$_REQUEST["dry_run"]) === "y";

if ($iblockId <= 0) {
	echo "Parameter iblock_id is required (target News iblock ID)" . PHP_EOL;
	exit(1);
}

if (!defined("SITE_TEMPLATE_PATH")) {
	define("SITE_TEMPLATE_PATH", "/local/templates/szcube");
}

$dataFile = $_SERVER["DOCUMENT_ROOT"] . "/local/tools/data/news-hardcode-source.php";
if (!is_file($dataFile)) {
	echo "Source file not found: " . $dataFile . PHP_EOL;
	exit(1);
}

$newsItems = require $dataFile;
if (!is_array($newsItems)) {
	echo "Source file returned unexpected data type" . PHP_EOL;
	exit(1);
}

function importNewsRenderDetailHtml(array $blocks)
{
	$html = "";

	foreach ($blocks as $block) {
		if (!is_array($block)) {
			continue;
		}

		$type = isset($block["type"]) ? (string)$block["type"] : "";
		$text = isset($block["text"]) ? (string)$block["text"] : "";
		$items = isset($block["items"]) && is_array($block["items"]) ? $block["items"] : array();

		if ($type === "h3") {
			$html .= "<h3>" . htmlspecialcharsbx($text) . "</h3>\n";
			continue;
		}

		if ($type === "p") {
			$html .= "<p>" . htmlspecialcharsbx($text) . "</p>\n";
			continue;
		}

		if ($type === "ul" || $type === "ol") {
			$tag = $type;
			$html .= "<" . $tag . ">\n";
			foreach ($items as $li) {
				$html .= "\t<li>" . htmlspecialcharsbx((string)$li) . "</li>\n";
			}
			$html .= "</" . $tag . ">\n";
		}
	}

	return trim($html);
}

function importNewsDateToBitrixFormat($dateRaw)
{
	$dateRaw = trim((string)$dateRaw);
	if ($dateRaw === "") {
		return "";
	}

	$formats = array("Y-m-d H:i:s", "Y-m-d");
	foreach ($formats as $format) {
		$dt = \DateTime::createFromFormat($format, $dateRaw);
		if ($dt instanceof \DateTime) {
			return $dt->format("d.m.Y H:i:s");
		}
	}

	$timestamp = strtotime($dateRaw);
	if ($timestamp) {
		return date("d.m.Y H:i:s", $timestamp);
	}

	return "";
}

function importNewsMakeFileArray($imagePath)
{
	$imagePath = trim((string)$imagePath);
	if ($imagePath === "") {
		return false;
	}

	$imagePath = str_replace("\\", "/", $imagePath);
	$templatePrefix = rtrim((string)SITE_TEMPLATE_PATH, "/");
	if ($templatePrefix !== "" && strpos($imagePath, $templatePrefix) === 0) {
		$imagePath = substr($imagePath, strlen($templatePrefix));
		$imagePath = $templatePrefix . $imagePath;
	}

	$absPath = $_SERVER["DOCUMENT_ROOT"] . $imagePath;
	if (!is_file($absPath)) {
		return false;
	}

	return CFile::MakeFileArray($absPath);
}

$el = new CIBlockElement();
$created = 0;
$updated = 0;
$skipped = 0;
$errors = array();

echo "Import started. IBlock ID: " . $iblockId . ". dry-run: " . ($dryRun ? "Y" : "N") . PHP_EOL;

foreach ($newsItems as $item) {
	if (!is_array($item)) {
		$skipped++;
		continue;
	}

	$code = isset($item["code"]) ? trim((string)$item["code"]) : "";
	$name = isset($item["title"]) ? trim((string)$item["title"]) : "";

	if ($code === "" || $name === "") {
		$skipped++;
		$errors[] = "Skip item with empty code or title";
		continue;
	}

	$activeFrom = importNewsDateToBitrixFormat(isset($item["date"]) ? $item["date"] : "");
	$previewText = isset($item["preview"]) ? trim((string)$item["preview"]) : "";
	$detailHtml = importNewsRenderDetailHtml(isset($item["detail"]) && is_array($item["detail"]) ? $item["detail"] : array());

	$fields = array(
		"IBLOCK_ID" => $iblockId,
		"ACTIVE" => "Y",
		"NAME" => $name,
		"CODE" => $code,
		"ACTIVE_FROM" => $activeFrom,
		"PREVIEW_TEXT" => $previewText,
		"PREVIEW_TEXT_TYPE" => "text",
		"DETAIL_TEXT" => $detailHtml,
		"DETAIL_TEXT_TYPE" => "html",
	);

	$fileArray = importNewsMakeFileArray(isset($item["image"]) ? $item["image"] : "");
	if ($fileArray !== false) {
		$fields["PREVIEW_PICTURE"] = $fileArray;
	}

	$existingId = 0;
	$res = CIBlockElement::GetList(
		array(),
		array(
			"IBLOCK_ID" => $iblockId,
			"=CODE" => $code,
		),
		false,
		false,
		array("ID", "IBLOCK_ID", "CODE")
	);
	if ($row = $res->Fetch()) {
		$existingId = (int)$row["ID"];
	}

	echo ($existingId > 0 ? "[UPDATE]" : "[CREATE]") . " " . $code . " :: " . $name . PHP_EOL;

	if ($dryRun) {
		if ($existingId > 0) {
			$updated++;
		} else {
			$created++;
		}
		continue;
	}

	if ($existingId > 0) {
		if (!$el->Update($existingId, $fields)) {
			$errors[] = "Update failed for " . $code . ": " . $el->LAST_ERROR;
			continue;
		}
		$updated++;
	} else {
		$newId = $el->Add($fields);
		if (!$newId) {
			$errors[] = "Create failed for " . $code . ": " . $el->LAST_ERROR;
			continue;
		}
		$created++;
	}
}

echo PHP_EOL;
echo "Done. Created: " . $created . ", Updated: " . $updated . ", Skipped: " . $skipped . PHP_EOL;
if (!empty($errors)) {
	echo "Errors:" . PHP_EOL;
	foreach ($errors as $error) {
		echo " - " . $error . PHP_EOL;
	}
	exit(2);
}

exit(0);
