<?php
/**
 * Проверяет квартирный rollout:
 * - ИБ apartments существует
 * - ключевые свойства созданы
 * - элементы из seed существуют и имеют ожидаемые CODE/XML_ID
 * - опционально проверяет HTTP-страницы деталей
 *
 * CLI:
 *   php local/tools/verify_apartments_rollout.php
 *   php local/tools/verify_apartments_rollout.php --base-url=https://szcube.ru
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
		"base-url::",
		"help::",
	));

	if (isset($options["help"])) {
		echo "Usage: php local/tools/verify_apartments_rollout.php [--source=/local/tools/data/apartments-seed.php] [--base-url=https://szcube.ru]\n";
		exit(0);
	}

	foreach ($options as $key => $value) {
		$_REQUEST[str_replace("-", "_", $key)] = $value;
	}
}

$prologPath = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
if (!is_file($prologPath)) {
	echo "[ERROR] Bitrix bootstrap not found: " . $prologPath . PHP_EOL;
	exit(1);
}

require $prologPath;

if (!class_exists("\\Bitrix\\Main\\Loader") || !\Bitrix\Main\Loader::includeModule("iblock")) {
	echo "[ERROR] Failed to load iblock module" . PHP_EOL;
	exit(1);
}

$sourceRel = isset($_REQUEST["source"]) && $_REQUEST["source"] !== "" ? (string)$_REQUEST["source"] : "/local/tools/data/apartments-seed.php";
$sourceFile = strpos($sourceRel, "/") === 0 ? $_SERVER["DOCUMENT_ROOT"] . $sourceRel : $_SERVER["DOCUMENT_ROOT"] . "/" . ltrim($sourceRel, "/");
$baseUrl = isset($_REQUEST["base_url"]) ? rtrim((string)$_REQUEST["base_url"], "/") : "";

echo "Source: " . $sourceFile . PHP_EOL;
echo "Base URL: " . ($baseUrl !== "" ? $baseUrl : "(skip http)") . PHP_EOL;

if (!is_file($sourceFile)) {
	echo "[ERROR] Source file not found: " . $sourceFile . PHP_EOL;
	exit(2);
}

$items = require $sourceFile;
if (!is_array($items)) {
	echo "[ERROR] Source file returned invalid data" . PHP_EOL;
	exit(2);
}

function verifyFindIblockByCode($code)
{
	$res = CIBlock::GetList(array(), array("=CODE" => $code), false);
	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function verifyNormalizeApartmentCodePart($value)
{
	$value = trim((string)$value);
	$value = mb_strtolower($value);
	$value = preg_replace("/[^a-z0-9_-]+/u", "-", $value);
	$value = preg_replace("/-+/u", "-", $value);
	return trim((string)$value, "-");
}

function verifyBuildApartmentCode(array $item)
{
	$projectCode = isset($item["project_code"]) ? verifyNormalizeApartmentCodePart($item["project_code"]) : "";
	$apartmentNumber = isset($item["apartment_number"]) ? verifyNormalizeApartmentCodePart($item["apartment_number"]) : "";
	$corpus = isset($item["corpus"]) ? verifyNormalizeApartmentCodePart($item["corpus"]) : "";

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

function verifyGetHttpStatus($url)
{
	if (function_exists("curl_init")) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $status;
	}

	$headers = @get_headers($url);
	if (!is_array($headers) || !isset($headers[0])) {
		return 0;
	}

	if (preg_match("/\\s(\\d{3})\\s/", (string)$headers[0], $matches)) {
		return (int)$matches[1];
	}

	return 0;
}

$errors = array();
$apartmentsIblock = verifyFindIblockByCode("apartments");
if (!is_array($apartmentsIblock)) {
	$errors[] = "IBlock apartments not found";
} else {
	echo "[OK] IBlock apartments exists (ID=" . (int)$apartmentsIblock["ID"] . ")" . PHP_EOL;
}

$apartmentsIblockId = is_array($apartmentsIblock) ? (int)$apartmentsIblock["ID"] : 0;

$requiredProperties = array(
	"PROJECT",
	"STATUS",
	"PLAN_IMAGE",
	"PLAN_ALT",
	"FLOOR_SLIDE_IMAGE",
	"VIEW_SLIDE_IMAGE",
	"RENDER_SLIDE_IMAGE",
	"SVG_SLOT_ID",
);

if ($apartmentsIblockId > 0) {
	foreach ($requiredProperties as $propertyCode) {
		$res = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $apartmentsIblockId, "CODE" => $propertyCode));
		if ($row = $res->Fetch()) {
			echo "[OK] Property exists: " . $propertyCode . " (ID=" . (int)$row["ID"] . ")" . PHP_EOL;
			continue;
		}

		$errors[] = "Property not found: " . $propertyCode;
	}
}

if ($apartmentsIblockId > 0) {
	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}

		$expectedXmlId = isset($item["xml_id"]) && trim((string)$item["xml_id"]) !== ""
			? trim((string)$item["xml_id"])
			: verifyBuildApartmentCode($item);
		$expectedCode = verifyBuildApartmentCode($item);

		$res = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $apartmentsIblockId,
				"=XML_ID" => $expectedXmlId,
			),
			false,
			false,
			array("ID", "NAME", "CODE", "XML_ID")
		);

		if (!($row = $res->Fetch())) {
			$errors[] = "Apartment not found by XML_ID: " . $expectedXmlId;
			continue;
		}

		echo "[OK] Apartment exists: XML_ID=" . $expectedXmlId . ", CODE=" . (string)$row["CODE"] . PHP_EOL;
		if (trim((string)$row["CODE"]) !== $expectedCode) {
			$errors[] = "Apartment CODE mismatch for XML_ID=" . $expectedXmlId . ": expected " . $expectedCode . ", got " . (string)$row["CODE"];
			continue;
		}

		if ($baseUrl !== "") {
			$status = verifyGetHttpStatus($baseUrl . "/apartments/" . rawurlencode($expectedCode) . "/");
			if ($status !== 200) {
				$errors[] = "HTTP status for /apartments/" . $expectedCode . "/ is " . $status;
			} else {
				echo "[OK] HTTP 200: /apartments/" . $expectedCode . "/" . PHP_EOL;
			}
		}
	}
}

if (!empty($errors)) {
	echo PHP_EOL . "[FAIL]" . PHP_EOL;
	foreach ($errors as $error) {
		echo "- " . $error . PHP_EOL;
	}
	exit(3);
}

echo PHP_EOL . "[OK] Apartment rollout verified." . PHP_EOL;
exit(0);
