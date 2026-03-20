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

function verifyFindUserField($entityId, $fieldName)
{
	$res = CUserTypeEntity::GetList(
		array("ID" => "ASC"),
		array(
			"ENTITY_ID" => (string)$entityId,
			"FIELD_NAME" => (string)$fieldName,
		)
	);

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

function verifyFindSectionByCodeAndParent($iblockId, $parentId, $code)
{
	$filter = array(
		"IBLOCK_ID" => (int)$iblockId,
		"=CODE" => (string)$code,
	);

	$filter["SECTION_ID"] = $parentId > 0 ? (int)$parentId : false;

	$res = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		$filter,
		false,
		array("ID", "NAME", "CODE", "IBLOCK_SECTION_ID", "UF_ENTRANCE_NUMBER", "UF_FLOOR_NUMBER", "UF_PIN_X", "UF_PIN_Y", "UF_PIN_LABEL")
	);

	if ($row = $res->GetNext()) {
		return $row;
	}

	return null;
}

function verifyFindApartmentByXmlId($iblockId, $xmlId)
{
	$res = CIBlockElement::GetList(
		array(),
		array(
			"IBLOCK_ID" => (int)$iblockId,
			"=XML_ID" => (string)$xmlId,
		),
		false,
		false,
		array("ID", "IBLOCK_SECTION_ID", "NAME", "CODE", "XML_ID")
	);

	return $res->GetNextElement();
}

function verifyGetElementPropertyValue($iblockId, $elementId, $propertyCode)
{
	$res = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("SORT" => "ASC", "ID" => "ASC"),
		array("CODE" => (string)$propertyCode)
	);

	if ($row = $res->Fetch()) {
		return isset($row["VALUE"]) ? $row["VALUE"] : null;
	}

	return null;
}

function verifyGetElementPropertyValues($iblockId, $elementId, $propertyCode)
{
	$result = array();
	$res = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("SORT" => "ASC", "ID" => "ASC"),
		array("CODE" => (string)$propertyCode)
	);

	while ($row = $res->Fetch()) {
		$value = isset($row["VALUE"]) ? trim((string)$row["VALUE"]) : "";
		if ($value !== "") {
			$result[] = $value;
		}
	}

	return array_values(array_unique($result));
}

function verifyGetElementPropertyXmlId($iblockId, $elementId, $propertyCode)
{
	$res = CIBlockElement::GetProperty(
		(int)$iblockId,
		(int)$elementId,
		array("SORT" => "ASC", "ID" => "ASC"),
		array("CODE" => (string)$propertyCode)
	);

	if ($row = $res->Fetch()) {
		return isset($row["VALUE_XML_ID"]) ? trim((string)$row["VALUE_XML_ID"]) : "";
	}

	return "";
}

function verifyNormalizeRoomXmlId($value)
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

function verifyNormalizeFinishXmlId($value)
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

function verifyNormalizeApartmentHouseFloors($floor, $floorTo, $houseFloors)
{
	$floor = (int)$floor;
	$floorTo = (int)$floorTo;
	$houseFloors = (int)$houseFloors;
	$upperFloor = $floorTo > $floor ? $floorTo : 0;

	return max($houseFloors, $floor, $upperFloor);
}

function verifyFindApartmentFieldsByCode($iblockId, $code)
{
	$res = CIBlockElement::GetList(
		array(),
		array(
			"IBLOCK_ID" => (int)$iblockId,
			"=CODE" => (string)$code,
		),
		false,
		false,
		array("ID", "IBLOCK_SECTION_ID", "NAME", "CODE", "XML_ID", "ACTIVE")
	);

	if ($row = $res->Fetch()) {
		return $row;
	}

	return null;
}

function verifySectionHasDescendants($iblockId, $sectionId)
{
	$res = CIBlockSection::GetList(
		array("SORT" => "ASC", "ID" => "ASC"),
		array(
			"IBLOCK_ID" => (int)$iblockId,
			"SECTION_ID" => (int)$sectionId,
		),
		false,
		array("ID")
	);

	return (bool)$res->Fetch();
}

$errors = array();
$apartmentsIblock = verifyFindIblockByCode("apartments");
if (!is_array($apartmentsIblock)) {
	$errors[] = "IBlock apartments not found";
} else {
	echo "[OK] IBlock apartments exists (ID=" . (int)$apartmentsIblock["ID"] . ")" . PHP_EOL;
}

$apartmentsIblockId = is_array($apartmentsIblock) ? (int)$apartmentsIblock["ID"] : 0;
$apartmentsSectionEntityId = $apartmentsIblockId > 0 ? ("IBLOCK_" . $apartmentsIblockId . "_SECTION") : "";

$requiredProperties = array(
	"PROJECT",
	"FLOOR_TO",
	"ROOMS",
	"STATUS",
	"DISCOUNT_MODE",
	"DISCOUNT_PERCENT",
	"DISCOUNT_AMOUNT",
	"BADGES",
	"FINISH",
	"PLAN_IMAGE",
	"PLAN_ALT",
	"FLOOR_SLIDE_IMAGE",
	"VIEW_SLIDE_IMAGE",
	"RENDER_SLIDE_IMAGE",
	"SVG_SLOT_ID",
);

if ($apartmentsIblockId > 0) {
	foreach ($requiredProperties as $propertyCode) {
		$res = CIBlockProperty::GetList(array(), array("IBLOCK_ID" => $apartmentsIblockId, "=CODE" => $propertyCode));
		if ($row = $res->Fetch()) {
			echo "[OK] Property exists: " . $propertyCode . " (ID=" . (int)$row["ID"] . ")" . PHP_EOL;
			continue;
		}

		$errors[] = "Property not found: " . $propertyCode;
	}
}

$requiredSectionUserFields = array(
	"UF_NODE_TYPE",
	"UF_ENTRANCE_NUMBER",
	"UF_FLOOR_NUMBER",
	"UF_CHESS_SVG",
	"UF_CHESS_IMAGE",
	"UF_PIN_X",
	"UF_PIN_Y",
	"UF_PIN_LABEL",
);

if ($apartmentsIblockId > 0 && $apartmentsSectionEntityId !== "") {
	foreach ($requiredSectionUserFields as $fieldName) {
		$field = verifyFindUserField($apartmentsSectionEntityId, $fieldName);
		if (is_array($field)) {
			echo "[OK] Section user field exists: " . $fieldName . " (ID=" . (int)$field["ID"] . ")" . PHP_EOL;
			continue;
		}

		$errors[] = "Section user field not found: " . $fieldName;
	}
}

if ($apartmentsIblockId > 0) {
	$checkedProjectSections = array();
	$checkedEntranceSections = array();
	$checkedFloorSections = array();

	foreach ($items as $item) {
		if (!is_array($item)) {
			continue;
		}

		$projectCode = isset($item["project_code"]) ? trim((string)$item["project_code"]) : "";
		$entrance = isset($item["entrance"]) ? trim((string)$item["entrance"]) : "";
		$floor = isset($item["floor"]) ? (int)$item["floor"] : 0;
		$floorTo = isset($item["floor_to"]) ? (int)$item["floor_to"] : 0;
		$houseFloors = verifyNormalizeApartmentHouseFloors(
			$floor,
			$floorTo,
			isset($item["house_floors"]) ? (int)$item["house_floors"] : 0
		);
		$apartmentNumber = isset($item["apartment_number"]) ? trim((string)$item["apartment_number"]) : "";
		$expectedXmlId = isset($item["xml_id"]) && trim((string)$item["xml_id"]) !== ""
			? trim((string)$item["xml_id"])
			: verifyBuildApartmentCode($item);
		$expectedCode = verifyBuildApartmentCode($item);
		$expectedRoomsXmlId = verifyNormalizeRoomXmlId(isset($item["rooms"]) ? $item["rooms"] : "");
		$expectedFinishXmlId = verifyNormalizeFinishXmlId(isset($item["finish"]) ? $item["finish"] : "");
		$expectedPriceOld = isset($item["price_old"]) ? (float)$item["price_old"] : 0;
		$expectedBadges = array();
		if (isset($item["badges"]) && is_array($item["badges"])) {
			$expectedBadges = array_values(array_unique(array_filter(array_map("trim", $item["badges"]), static function ($value) {
				return $value !== "";
			})));
			sort($expectedBadges);
		}

		$projectSection = null;
		if ($projectCode !== "") {
			if (!array_key_exists($projectCode, $checkedProjectSections)) {
				$checkedProjectSections[$projectCode] = verifyFindSectionByCodeAndParent($apartmentsIblockId, 0, $projectCode);
				if (is_array($checkedProjectSections[$projectCode])) {
					echo "[OK] Project section exists: " . $projectCode . " (ID=" . (int)$checkedProjectSections[$projectCode]["ID"] . ")" . PHP_EOL;
				} else {
					$errors[] = "Project section not found: " . $projectCode;
				}
			}

			$projectSection = $checkedProjectSections[$projectCode];
		}

		$entranceSection = null;
		$entranceSectionKey = $projectCode . "|" . $entrance;
		if (is_array($projectSection) && $entrance !== "") {
			$entranceCode = "podezd-" . verifyNormalizeApartmentCodePart($entrance);
			if (!array_key_exists($entranceSectionKey, $checkedEntranceSections)) {
				$checkedEntranceSections[$entranceSectionKey] = verifyFindSectionByCodeAndParent($apartmentsIblockId, (int)$projectSection["ID"], $entranceCode);
				if (is_array($checkedEntranceSections[$entranceSectionKey])) {
					echo "[OK] Entrance section exists: " . $entranceCode . " (ID=" . (int)$checkedEntranceSections[$entranceSectionKey]["ID"] . ")" . PHP_EOL;
				} else {
					$errors[] = "Entrance section not found: " . $entranceCode . " under " . $projectCode;
				}
			}

			$entranceSection = $checkedEntranceSections[$entranceSectionKey];
			if (is_array($entranceSection)) {
				$expectedPinX = isset($item["entrance_pin_x"]) ? trim((string)$item["entrance_pin_x"]) : "";
				$expectedPinY = isset($item["entrance_pin_y"]) ? trim((string)$item["entrance_pin_y"]) : "";
				$expectedPinLabel = isset($item["entrance_pin_label"]) ? trim((string)$item["entrance_pin_label"]) : "";

				if ($expectedPinX !== "" && trim((string)$entranceSection["UF_PIN_X"]) !== $expectedPinX) {
					$errors[] = "Entrance UF_PIN_X mismatch for " . $entranceCode . ": expected " . $expectedPinX . ", got " . trim((string)$entranceSection["UF_PIN_X"]);
				}
				if ($expectedPinY !== "" && trim((string)$entranceSection["UF_PIN_Y"]) !== $expectedPinY) {
					$errors[] = "Entrance UF_PIN_Y mismatch for " . $entranceCode . ": expected " . $expectedPinY . ", got " . trim((string)$entranceSection["UF_PIN_Y"]);
				}
				if ($expectedPinLabel !== "" && trim((string)$entranceSection["UF_PIN_LABEL"]) !== $expectedPinLabel) {
					$errors[] = "Entrance UF_PIN_LABEL mismatch for " . $entranceCode . ": expected " . $expectedPinLabel . ", got " . trim((string)$entranceSection["UF_PIN_LABEL"]);
				}
			}
		}

		$floorSection = null;
		$floorSectionKey = $projectCode . "|" . $entrance . "|" . $floor;
		if (is_array($entranceSection) && $floor > 0) {
			$floorCode = sprintf("floor-%02d", $floor);
			if (!array_key_exists($floorSectionKey, $checkedFloorSections)) {
				$checkedFloorSections[$floorSectionKey] = verifyFindSectionByCodeAndParent($apartmentsIblockId, (int)$entranceSection["ID"], $floorCode);
				if (is_array($checkedFloorSections[$floorSectionKey])) {
					echo "[OK] Floor section exists: " . $floorCode . " (ID=" . (int)$checkedFloorSections[$floorSectionKey]["ID"] . ")" . PHP_EOL;
				} else {
					$errors[] = "Floor section not found: " . $floorCode . " under entrance " . $entrance;
				}
			}

			$floorSection = $checkedFloorSections[$floorSectionKey];
			if (is_array($floorSection) && (int)$floorSection["UF_FLOOR_NUMBER"] !== $floor) {
				$errors[] = "Floor section UF_FLOOR_NUMBER mismatch for " . $floorCode . ": expected " . $floor . ", got " . (int)$floorSection["UF_FLOOR_NUMBER"];
			}
		}

		$element = verifyFindApartmentByXmlId($apartmentsIblockId, $expectedXmlId);
		if (!($element instanceof _CIBElement)) {
			$errors[] = "Apartment not found by XML_ID: " . $expectedXmlId;
			continue;
		}

		$row = $element->GetFields();

		echo "[OK] Apartment exists: XML_ID=" . $expectedXmlId . ", CODE=" . (string)$row["CODE"] . PHP_EOL;
		if (trim((string)$row["CODE"]) !== $expectedCode) {
			$errors[] = "Apartment CODE mismatch for XML_ID=" . $expectedXmlId . ": expected " . $expectedCode . ", got " . (string)$row["CODE"];
			continue;
		}

		if (is_array($floorSection) && (int)$row["IBLOCK_SECTION_ID"] !== (int)$floorSection["ID"]) {
			$errors[] = "Apartment section mismatch for XML_ID=" . $expectedXmlId . ": expected section ID " . (int)$floorSection["ID"] . ", got " . (int)$row["IBLOCK_SECTION_ID"];
			continue;
		}

		$actualEntrance = trim((string)verifyGetElementPropertyValue($apartmentsIblockId, (int)$row["ID"], "ENTRANCE"));
		$actualFloor = (int)verifyGetElementPropertyValue($apartmentsIblockId, (int)$row["ID"], "FLOOR");
		$actualFloorTo = (int)verifyGetElementPropertyValue($apartmentsIblockId, (int)$row["ID"], "FLOOR_TO");
		$actualHouseFloors = (int)verifyGetElementPropertyValue($apartmentsIblockId, (int)$row["ID"], "HOUSE_FLOORS");
		$actualApartmentNumber = trim((string)verifyGetElementPropertyValue($apartmentsIblockId, (int)$row["ID"], "APARTMENT_NUMBER"));
		$actualPriceOld = (float)verifyGetElementPropertyValue($apartmentsIblockId, (int)$row["ID"], "PRICE_OLD");
		$actualRoomsXmlId = verifyGetElementPropertyXmlId($apartmentsIblockId, (int)$row["ID"], "ROOMS");
		$actualFinishXmlId = verifyGetElementPropertyXmlId($apartmentsIblockId, (int)$row["ID"], "FINISH");
		$actualBadges = verifyGetElementPropertyValues($apartmentsIblockId, (int)$row["ID"], "BADGES");
		sort($actualBadges);

		if ($entrance !== "" && $actualEntrance !== $entrance) {
			$errors[] = "Apartment entrance mismatch for XML_ID=" . $expectedXmlId . ": expected " . $entrance . ", got " . $actualEntrance;
			continue;
		}
		if ($floor > 0 && $actualFloor !== $floor) {
			$errors[] = "Apartment floor mismatch for XML_ID=" . $expectedXmlId . ": expected " . $floor . ", got " . $actualFloor;
			continue;
		}
		if ($floorTo > 0 && $actualFloorTo !== $floorTo) {
			$errors[] = "Apartment floor_to mismatch for XML_ID=" . $expectedXmlId . ": expected " . $floorTo . ", got " . $actualFloorTo;
			continue;
		}
		if ($floorTo <= 0 && $actualFloorTo > 0) {
			$errors[] = "Apartment floor_to mismatch for XML_ID=" . $expectedXmlId . ": expected empty, got " . $actualFloorTo;
			continue;
		}
		if ($houseFloors > 0 && $actualHouseFloors !== $houseFloors) {
			$errors[] = "Apartment house_floors mismatch for XML_ID=" . $expectedXmlId . ": expected " . $houseFloors . ", got " . $actualHouseFloors;
			continue;
		}
		if ($apartmentNumber !== "" && $actualApartmentNumber !== $apartmentNumber) {
			$errors[] = "Apartment number mismatch for XML_ID=" . $expectedXmlId . ": expected " . $apartmentNumber . ", got " . $actualApartmentNumber;
			continue;
		}
		if (abs($actualPriceOld - $expectedPriceOld) > 0.0001) {
			$errors[] = "Apartment price_old mismatch for XML_ID=" . $expectedXmlId . ": expected " . $expectedPriceOld . ", got " . $actualPriceOld;
			continue;
		}
		if ($expectedRoomsXmlId !== "" && $actualRoomsXmlId !== $expectedRoomsXmlId) {
			$errors[] = "Apartment rooms mismatch for XML_ID=" . $expectedXmlId . ": expected " . $expectedRoomsXmlId . ", got " . $actualRoomsXmlId;
			continue;
		}
		if ($expectedFinishXmlId !== "" && $actualFinishXmlId !== $expectedFinishXmlId) {
			$errors[] = "Apartment finish mismatch for XML_ID=" . $expectedXmlId . ": expected " . $expectedFinishXmlId . ", got " . $actualFinishXmlId;
			continue;
		}
		if ($actualBadges !== $expectedBadges) {
			$errors[] = "Apartment badges mismatch for XML_ID=" . $expectedXmlId . ": expected [" . implode(", ", $expectedBadges) . "], got [" . implode(", ", $actualBadges) . "]";
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

if ($apartmentsIblockId > 0) {
	foreach (array(
		"vertical-235",
		"vertical-236",
		"301",
	) as $legacyCode) {
		$legacyElement = verifyFindApartmentFieldsByCode($apartmentsIblockId, $legacyCode);
		if (is_array($legacyElement)) {
			$errors[] = "Legacy apartment still exists: CODE=" . $legacyCode . ", ID=" . (int)$legacyElement["ID"];
		}
	}

	$verticalSection = verifyFindSectionByCodeAndParent($apartmentsIblockId, 0, "vertical");
	if (is_array($verticalSection) && verifySectionHasDescendants($apartmentsIblockId, (int)$verticalSection["ID"])) {
		$errors[] = "Vertical section still has child sections";
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
