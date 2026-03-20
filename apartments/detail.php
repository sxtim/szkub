<?php
define("APARTMENT_DETAIL_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$defaultPlanImage = SITE_TEMPLATE_PATH . "/img/apartments/" . rawurlencode("1 этаж 2е 92.8 с антресолью 1.jpg");
$defaultBuildingImage = SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg";
$defaultViewImage = SITE_TEMPLATE_PATH . "/img/projects/div.image-lazy__image.jpg";
$defaultFloorImage = SITE_TEMPLATE_PATH . "/img/projects/Group.svg";

if (!function_exists("apartmentDetailAppendFact")) {
	function apartmentDetailAppendFact(&$facts, $label, $value, $extra = array())
	{
		$value = is_string($value) ? trim($value) : $value;
		if ($value === "" || $value === null) {
			return;
		}

		$facts[] = array_merge(
			array(
				"label" => (string)$label,
				"value" => $value,
			),
			is_array($extra) ? $extra : array()
		);
	}
}

if (!function_exists("apartmentDetailLoadProperties")) {
	function apartmentDetailLoadProperties($iblockId, $elementId)
	{
		$result = array();
		$res = CIBlockElement::GetProperty(
			(int)$iblockId,
			(int)$elementId,
			array("SORT" => "ASC", "ID" => "ASC"),
			array()
		);
		while ($row = $res->Fetch()) {
			$code = trim((string)$row["CODE"]);
			if ($code === "") {
				continue;
			}

			if (!isset($result[$code])) {
				$result[$code] = array(
					"CODE" => $code,
					"NAME" => isset($row["NAME"]) ? (string)$row["NAME"] : $code,
					"PROPERTY_TYPE" => isset($row["PROPERTY_TYPE"]) ? (string)$row["PROPERTY_TYPE"] : "",
					"MULTIPLE" => isset($row["MULTIPLE"]) ? (string)$row["MULTIPLE"] : "N",
					"VALUE" => $row["MULTIPLE"] === "Y" ? array() : "",
					"VALUE_XML_ID" => $row["MULTIPLE"] === "Y" ? array() : "",
					"VALUE_ENUM" => $row["MULTIPLE"] === "Y" ? array() : "",
					"DESCRIPTION" => $row["MULTIPLE"] === "Y" ? array() : "",
				);
			}

			if ($row["MULTIPLE"] === "Y") {
				$result[$code]["VALUE"][] = $row["VALUE"];
				$result[$code]["VALUE_XML_ID"][] = $row["VALUE_XML_ID"];
				$result[$code]["VALUE_ENUM"][] = $row["VALUE_ENUM"];
				$result[$code]["DESCRIPTION"][] = $row["DESCRIPTION"];
				continue;
			}

			$result[$code]["VALUE"] = $row["VALUE"];
			$result[$code]["VALUE_XML_ID"] = $row["VALUE_XML_ID"];
			$result[$code]["VALUE_ENUM"] = $row["VALUE_ENUM"];
			$result[$code]["DESCRIPTION"] = $row["DESCRIPTION"];
		}

		return $result;
	}
}

if (!function_exists("apartmentDetailPropertyScalar")) {
	function apartmentDetailPropertyScalar($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : "";
		if (is_array($value)) {
			$value = reset($value);
		}

		$value = trim((string)$value);
		return $value !== "" ? $value : (string)$default;
	}
}

if (!function_exists("apartmentDetailPropertyEnumXmlId")) {
	function apartmentDetailPropertyEnumXmlId($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE_XML_ID"]) ? $properties[$code]["VALUE_XML_ID"] : "";
		if (is_array($value)) {
			$value = reset($value);
		}

		$value = trim((string)$value);
		return $value !== "" ? $value : (string)$default;
	}
}

if (!function_exists("apartmentDetailPropertyEnumLabel")) {
	function apartmentDetailPropertyEnumLabel($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE_ENUM"]) ? $properties[$code]["VALUE_ENUM"] : "";
		if (is_array($value)) {
			$value = reset($value);
		}

		$value = trim((string)$value);
		return $value !== "" ? $value : (string)$default;
	}
}

if (!function_exists("apartmentDetailPropertyMultipleScalars")) {
	function apartmentDetailPropertyMultipleScalars($properties, $code)
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return array();
		}

		$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : array();
		if (!is_array($value)) {
			$value = $value !== "" ? array($value) : array();
		}

		return array_values(array_filter(array_map("trim", $value), static function ($item) {
			return $item !== "";
		}));
	}
}

if (!function_exists("apartmentDetailDiscountBadge")) {
	function apartmentDetailDiscountBadge($priceTotal, $priceOld)
	{
		$priceTotal = (float)$priceTotal;
		$priceOld = (float)$priceOld;
		if ($priceOld <= 0 || $priceTotal <= 0 || $priceOld <= $priceTotal) {
			return "";
		}

		$discountPercent = (int)round((($priceOld - $priceTotal) / $priceOld) * 100);
		if ($discountPercent <= 0) {
			return "";
		}

		return "Скидка " . $discountPercent . "%";
	}
}

if (!function_exists("apartmentDetailNormalizeUpperFloor")) {
	function apartmentDetailNormalizeUpperFloor($floor, $floorTo)
	{
		$floor = (int)$floor;
		$floorTo = (int)$floorTo;
		return $floorTo > $floor ? $floorTo : 0;
	}
}

if (!function_exists("apartmentDetailIsDuplex")) {
	function apartmentDetailIsDuplex($floor, $floorTo)
	{
		return apartmentDetailNormalizeUpperFloor($floor, $floorTo) > 0;
	}
}

if (!function_exists("apartmentDetailNormalizeHouseFloors")) {
	function apartmentDetailNormalizeHouseFloors($floor, $floorTo, $houseFloors)
	{
		$floorNumber = (int)$floor;
		$houseFloorsNumber = (int)$houseFloors;
		$upperFloor = apartmentDetailNormalizeUpperFloor($floorNumber, $floorTo);

		return max($houseFloorsNumber, $floorNumber, $upperFloor);
	}
}

if (!function_exists("apartmentDetailFloorDisplay")) {
	function apartmentDetailFloorDisplay($floor, $floorTo, $houseFloors)
	{
		$floor = trim((string)$floor);
		$floorNumber = (int)$floor;
		$houseFloors = (string)apartmentDetailNormalizeHouseFloors($floorNumber, $floorTo, $houseFloors);
		$upperFloor = apartmentDetailNormalizeUpperFloor($floorNumber, $floorTo);
		if ($upperFloor > 0 && $floorNumber > 0) {
			return $floorNumber . "-" . $upperFloor . " этаж";
		}

		if ($floor !== "" && $houseFloors !== "") {
			return $floor . " из " . $houseFloors;
		}

		return $floor !== "" ? $floor : $houseFloors;
	}
}

if (!function_exists("apartmentDetailBuildBadges")) {
	function apartmentDetailBuildBadges(array $manualBadges, $floor, $floorTo)
	{
		$badges = array();
		foreach ($manualBadges as $badge) {
			$badge = trim((string)$badge);
			if ($badge !== "") {
				$badges[] = $badge;
			}
		}

		if (apartmentDetailIsDuplex($floor, $floorTo) && !in_array("Двухуровневая", $badges, true)) {
			$badges[] = "Двухуровневая";
		}

		return array_values(array_unique($badges));
	}
}

if (!function_exists("apartmentDetailPropertyFileUrl")) {
	function apartmentDetailPropertyFileUrl($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : 0;
		if (is_array($value)) {
			$value = reset($value);
		}

		$fileId = (int)$value;
		if ($fileId <= 0) {
			return (string)$default;
		}

		$filePath = CFile::GetPath($fileId);
		return $filePath ? (string)$filePath : (string)$default;
	}
}

if (!function_exists("apartmentDetailFormatMoney")) {
	function apartmentDetailFormatMoney($value)
	{
		$value = (float)$value;
		if ($value <= 0) {
			return "";
		}

		return number_format($value, 0, ".", " ") . " ₽";
	}
}

if (!function_exists("apartmentDetailFormatFloat")) {
	function apartmentDetailFormatFloat($value, $precision = 1)
	{
		$value = trim((string)$value);
		if ($value === "") {
			return "";
		}

		$number = (float)$value;
		$isInteger = abs($number - round($number)) < 0.00001;
		return $isInteger
			? (string)(int)round($number)
			: number_format($number, (int)$precision, ".", "");
	}
}

if (!function_exists("apartmentDetailFormatArea")) {
	function apartmentDetailFormatArea($value)
	{
		$formatted = apartmentDetailFormatFloat($value, 1);
		return $formatted !== "" ? $formatted . " м²" : "";
	}
}

if (!function_exists("apartmentDetailFormatCeiling")) {
	function apartmentDetailFormatCeiling($value)
	{
		$formatted = apartmentDetailFormatFloat($value, 2);
		return $formatted !== "" ? $formatted . " м" : "";
	}
}

if (!function_exists("apartmentDetailRoomsLabel")) {
	function apartmentDetailRoomsLabel($rooms)
	{
		$rooms = trim((string)$rooms);
		if ($rooms === "") {
			return "";
		}

		if (preg_match("/^studio|студ/i", $rooms)) {
			return "Студия";
		}

		if (preg_match("/евро\\s*дв|евродв|\\b2\\s*[еe]\\b|\\b2e\\b/iu", $rooms)) {
			return "Евродвушка";
		}

		if (preg_match("/евро\\s*тр|евротр|\\b3\\s*[еe]\\b|\\b3e\\b/iu", $rooms)) {
			return "Евротрешка";
		}

		if (preg_match("/^(\d+)/", $rooms, $matches)) {
			$number = (int)$matches[1];
			if ($number >= 4) {
				return "4-комнатная";
			}

			return $number . "-комнатная";
		}

		return $rooms;
	}
}

if (!function_exists("apartmentDetailNormalizeSlideKind")) {
	function apartmentDetailNormalizeSlideKind($kind)
	{
		$kind = trim((string)$kind);
		$map = array(
			"plan" => "plan",
			"floor" => "scheme",
			"scheme" => "scheme",
			"building" => "render",
			"render" => "render",
			"view" => "photo",
			"photo" => "photo",
		);

		return isset($map[$kind]) ? $map[$kind] : "render";
	}
}

if (!function_exists("apartmentDetailLoadSlidesFromIblock")) {
	function apartmentDetailLoadSlidesFromIblock($iblockId, $apartmentId)
	{
		$iblockId = (int)$iblockId;
		$apartmentId = (int)$apartmentId;
		if ($iblockId <= 0 || $apartmentId <= 0) {
			return array();
		}

		$slides = array();
		$res = CIBlockElement::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			array(
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
				"PROPERTY_APARTMENT" => $apartmentId,
			),
			false,
			false,
			array("ID", "IBLOCK_ID", "NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE")
		);
		while ($row = $res->Fetch()) {
			$properties = apartmentDetailLoadProperties((int)$row["IBLOCK_ID"], (int)$row["ID"]);
			$image = "";
			if ((int)$row["PREVIEW_PICTURE"] > 0) {
				$image = (string)CFile::GetPath((int)$row["PREVIEW_PICTURE"]);
			}
			if ($image === "") {
				continue;
			}

			$label = apartmentDetailPropertyScalar($properties, "LABEL", (string)$row["NAME"]);
			$mediaType = apartmentDetailPropertyEnumXmlId($properties, "MEDIA_TYPE", "render");
			$slides[] = array(
				"label" => $label,
				"title" => trim((string)$row["NAME"]),
				"description" => trim((string)$row["PREVIEW_TEXT"]),
				"image" => $image,
				"alt" => apartmentDetailPropertyScalar($properties, "ALT_TEXT", trim((string)$row["NAME"])),
				"bearing" => (int)apartmentDetailPropertyScalar($properties, "BEARING", 214),
				"kind" => apartmentDetailNormalizeSlideKind($mediaType),
			);
		}

		return $slides;
	}
}

if (!function_exists("apartmentDetailBuildSlides")) {
	function apartmentDetailBuildSlides($lotCode, $building, $projectName, $planImage, $floorImage, $buildingImage, $viewImage)
	{
		$buildingTitle = trim((string)$building) !== "" ? "Корпус " . $building : "В корпусе";
		$buildingAlt = trim((string)$building) !== ""
			? "Корпус " . $building . (trim((string)$projectName) !== "" ? " " . $projectName : "")
			: "Корпус проекта" . (trim((string)$projectName) !== "" ? " " . $projectName : "");

		return array(
			array(
				"label" => "Планировка",
				"title" => "Планировка 2Е",
				"description" => "Рациональная кухня-гостиная, отдельная спальня и большая антресольная зона хранения.",
				"image" => $planImage,
				"alt" => "Планировка квартиры " . $lotCode,
				"bearing" => 214,
				"kind" => "plan",
			),
			array(
				"label" => "На этаже",
				"title" => "Положение на этаже",
				"description" => "Квартира расположена в торцевой части этажа, дальше от лифтового узла.",
				"image" => $floorImage,
				"alt" => "Схема расположения квартиры на этаже",
				"bearing" => 228,
				"kind" => "scheme",
			),
			array(
				"label" => "В корпусе",
				"title" => $buildingTitle,
				"description" => "Выше линии двора, с открытым видом и быстрым выходом к приватной инфраструктуре проекта.",
				"image" => $buildingImage,
				"alt" => $buildingAlt,
				"bearing" => 206,
				"kind" => "render",
			),
			array(
				"label" => "Вид из окна",
				"title" => "Вид из окна",
				"description" => "Окна ориентированы на воду и городскую панораму, с вечерним западным светом.",
				"image" => $viewImage,
				"alt" => "Вид из окна квартиры",
				"bearing" => 247,
				"kind" => "photo",
			),
			array(
				"label" => "Визуализация",
				"title" => "Пространство в интерьере",
				"description" => "Потенциал комнаты и кухни-гостиной в спокойной современной палитре материалов.",
				"image" => $buildingImage,
				"alt" => "Визуализация квартиры",
				"bearing" => 214,
				"kind" => "render",
			),
		);
	}
}

if (!function_exists("apartmentDetailBuildFixedSlidesFromProperties")) {
	function apartmentDetailBuildFixedSlidesFromProperties(array $apartmentFields, array $properties)
	{
		$previewImage = (int)$apartmentFields["PREVIEW_PICTURE"] > 0 ? (string)CFile::GetPath((int)$apartmentFields["PREVIEW_PICTURE"]) : "";
		$lotCode = trim((string)$apartmentFields["CODE"]);

		$definitions = array(
			array(
				"label" => "Планировка",
				"kind" => "plan",
				"bearing" => 214,
				"image" => apartmentDetailPropertyFileUrl($properties, "PLAN_IMAGE", $previewImage),
				"title_code" => "PLAN_TITLE",
				"text_code" => "PLAN_TEXT",
				"alt_code" => "PLAN_ALT",
				"default_title" => "Планировка",
				"default_alt" => $lotCode !== "" ? "Планировка квартиры " . $lotCode : "Планировка квартиры",
			),
			array(
				"label" => "На этаже",
				"kind" => "scheme",
				"bearing" => 228,
				"image" => apartmentDetailPropertyFileUrl($properties, "FLOOR_SLIDE_IMAGE", ""),
				"title_code" => "FLOOR_SLIDE_TITLE",
				"text_code" => "FLOOR_SLIDE_TEXT",
				"alt_code" => "FLOOR_SLIDE_ALT",
				"default_title" => "На этаже",
				"default_alt" => "Схема расположения квартиры на этаже",
			),
			array(
				"label" => "В корпусе",
				"kind" => "render",
				"bearing" => 206,
				"image" => apartmentDetailPropertyFileUrl($properties, "BUILDING_SLIDE_IMAGE", ""),
				"title_code" => "BUILDING_SLIDE_TITLE",
				"text_code" => "BUILDING_SLIDE_TEXT",
				"alt_code" => "BUILDING_SLIDE_ALT",
				"default_title" => "В корпусе",
				"default_alt" => "Положение квартиры в корпусе",
			),
			array(
				"label" => "Вид из окна",
				"kind" => "photo",
				"bearing" => 247,
				"image" => apartmentDetailPropertyFileUrl($properties, "VIEW_SLIDE_IMAGE", ""),
				"title_code" => "VIEW_SLIDE_TITLE",
				"text_code" => "VIEW_SLIDE_TEXT",
				"alt_code" => "VIEW_SLIDE_ALT",
				"default_title" => "Вид из окна",
				"default_alt" => "Вид из окна квартиры",
			),
			array(
				"label" => "Визуализация",
				"kind" => "render",
				"bearing" => 214,
				"image" => apartmentDetailPropertyFileUrl($properties, "RENDER_SLIDE_IMAGE", ""),
				"title_code" => "RENDER_SLIDE_TITLE",
				"text_code" => "RENDER_SLIDE_TEXT",
				"alt_code" => "RENDER_SLIDE_ALT",
				"default_title" => "Визуализация",
				"default_alt" => "Визуализация квартиры",
			),
		);

		$slides = array();
		foreach ($definitions as $definition) {
			$image = trim((string)$definition["image"]);
			if ($image === "") {
				continue;
			}

			$title = apartmentDetailPropertyScalar($properties, $definition["title_code"], $definition["default_title"]);
			$description = apartmentDetailPropertyScalar($properties, $definition["text_code"], "");
			$alt = apartmentDetailPropertyScalar($properties, $definition["alt_code"], $definition["default_alt"]);
			$slides[] = array(
				"label" => $definition["label"],
				"title" => $title,
				"description" => $description,
				"image" => $image,
				"alt" => $alt,
				"bearing" => (int)$definition["bearing"],
				"kind" => $definition["kind"],
			);
		}

		return $slides;
	}
}

if (!function_exists("apartmentDetailBuildPrototype")) {
	function apartmentDetailBuildPrototype($overrides, $planImage, $floorImage, $buildingImage, $viewImage)
	{
		$base = array(
			"title" => "",
			"title_line_1" => "",
			"title_line_2" => "",
			"project" => "",
			"project_url" => "",
			"building" => "",
			"floor" => "",
			"floor_to" => "",
			"house_floors" => "",
			"handover" => "",
			"lot" => "",
			"apartment_number" => "",
			"price_meter" => "",
			"price_total" => "",
			"price_old" => "",
			"finish" => "",
			"ceiling" => "",
			"street" => "",
			"entrance" => "",
			"view" => "",
			"window_sides" => "",
			"discount" => "",
			"badges" => array(),
			"availability_status" => "",
			"availability_label" => "",
			"availability_badges" => array(),
			"rooms" => "",
			"area_total" => "",
			"area_living" => "",
			"area_kitchen" => "",
			"balcony_type" => "",
			"bathrooms" => "",
			"house_type" => "",
			"feature_flags" => array(),
			"feature_tags" => array(),
		);

			$data = array_replace($base, is_array($overrides) ? $overrides : array());
		$data["floor_display"] = apartmentDetailFloorDisplay(
			isset($data["floor"]) ? $data["floor"] : "",
			isset($data["floor_to"]) ? $data["floor_to"] : "",
			isset($data["house_floors"]) ? $data["house_floors"] : ""
		);

			$availabilityBadges = array();
			if (isset($data["availability_badges"]) && is_array($data["availability_badges"])) {
				foreach ($data["availability_badges"] as $badge) {
					$status = isset($badge["status"]) ? trim((string)$badge["status"]) : "";
					$label = isset($badge["label"]) ? trim((string)$badge["label"]) : "";
					if ($status !== "" && $label !== "") {
						$availabilityBadges[] = array(
							"status" => $status,
							"label" => $label,
						);
					}
				}
			}
			if (empty($availabilityBadges) && trim((string)$data["availability_status"]) !== "" && trim((string)$data["availability_label"]) !== "") {
				$availabilityBadges[] = array(
					"status" => trim((string)$data["availability_status"]),
					"label" => trim((string)$data["availability_label"]),
				);
			}
			$data["availability_badges"] = $availabilityBadges;

			$badges = array();
			if (isset($data["badges"]) && is_array($data["badges"])) {
				foreach ($data["badges"] as $badge) {
					$badge = trim((string)$badge);
					if ($badge !== "") {
						$badges[] = $badge;
					}
				}
			}
			$data["badges"] = apartmentDetailBuildBadges(
				$badges,
				isset($data["floor"]) ? $data["floor"] : "",
				isset($data["floor_to"]) ? $data["floor_to"] : ""
			);

			$data["slides"] = apartmentDetailBuildSlides(
			(string)$data["lot"],
			(string)$data["building"],
			(string)$data["project"],
			$planImage,
			$floorImage,
			$buildingImage,
			$viewImage
		);

		$primaryFacts = array();
		apartmentDetailAppendFact($primaryFacts, "ЖК", $data["project"], array("url" => $data["project_url"]));
		apartmentDetailAppendFact($primaryFacts, "Улица", $data["street"]);
		apartmentDetailAppendFact($primaryFacts, "Подъезд", $data["entrance"]);
		apartmentDetailAppendFact($primaryFacts, "Этаж", $data["floor_display"]);
		apartmentDetailAppendFact($primaryFacts, "Общая площадь", $data["area_total"]);
		apartmentDetailAppendFact($primaryFacts, "Жилая площадь", $data["area_living"]);
		apartmentDetailAppendFact($primaryFacts, "Тип дома", $data["house_type"]);
		apartmentDetailAppendFact($primaryFacts, "Срок сдачи", $data["handover"]);
		$data["primary_facts"] = $primaryFacts;

		$detailFacts = array();
		apartmentDetailAppendFact($detailFacts, "Площадь кухни", $data["area_kitchen"]);
		apartmentDetailAppendFact($detailFacts, "Высота потолков", $data["ceiling"]);
		apartmentDetailAppendFact($detailFacts, "Кол-во санузлов", $data["bathrooms"]);
		apartmentDetailAppendFact($detailFacts, "Балкон / лоджия", $data["balcony_type"]);
		apartmentDetailAppendFact($detailFacts, "Вид из окна", $data["view"]);
		apartmentDetailAppendFact($detailFacts, "Окна по сторонам", $data["window_sides"]);
		$data["detail_facts"] = $detailFacts;

		$featureTags = array();
		if (isset($data["feature_flags"]) && is_array($data["feature_flags"])) {
			foreach ($data["feature_flags"] as $featureFlag) {
				$label = isset($featureFlag["label"]) ? trim((string)$featureFlag["label"]) : "";
				$enabled = !empty($featureFlag["enabled"]);
				if ($enabled && $label !== "") {
					$featureTags[] = $label;
				}
			}
		}
		if (isset($data["feature_tags"]) && is_array($data["feature_tags"])) {
			foreach ($data["feature_tags"] as $featureTag) {
				$featureTag = trim((string)$featureTag);
				if ($featureTag !== "") {
					$featureTags[] = $featureTag;
				}
			}
		}
		$data["feature_tags"] = array_values(array_unique($featureTags));

		return $data;
	}
}

$apartmentsIblockCode = "apartments";
$projectsIblockCode = "projects";
$code = isset($_REQUEST["code"]) ? trim((string)$_REQUEST["code"]) : "";
$code = preg_replace("/[^a-z0-9_-]/i", "", $code);
$apartment = null;

if ($code !== "" && class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
	$apartmentsIblock = CIBlock::GetList(array(), array("=CODE" => $apartmentsIblockCode, "ACTIVE" => "Y"), false)->Fetch();
	$projectsIblock = CIBlock::GetList(array(), array("=CODE" => $projectsIblockCode, "ACTIVE" => "Y"), false)->Fetch();

	$apartmentsIblockId = is_array($apartmentsIblock) ? (int)$apartmentsIblock["ID"] : 0;
	$projectsIblockId = is_array($projectsIblock) ? (int)$projectsIblock["ID"] : 0;

	if ($apartmentsIblockId > 0) {
		$apartmentRes = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $apartmentsIblockId,
				"=CODE" => $code,
				"ACTIVE" => "Y",
			),
			false,
			false,
			array("ID", "IBLOCK_ID", "NAME", "CODE", "IBLOCK_SECTION_ID", "PREVIEW_PICTURE")
		);

		if ($apartmentFields = $apartmentRes->Fetch()) {
			$apartmentId = (int)$apartmentFields["ID"];
			$apartmentProperties = apartmentDetailLoadProperties($apartmentsIblockId, $apartmentId);

			$projectId = (int)apartmentDetailPropertyScalar($apartmentProperties, "PROJECT", 0);
			$projectName = "";
			$projectUrl = "";
			$street = "";
			$handover = "";
			$buildingImage = $defaultBuildingImage;

			if ($projectsIblockId > 0 && $projectId > 0) {
				$projectRes = CIBlockElement::GetList(
					array(),
					array("IBLOCK_ID" => $projectsIblockId, "ID" => $projectId, "ACTIVE" => "Y"),
					false,
					false,
					array("ID", "IBLOCK_ID", "NAME", "CODE", "PREVIEW_PICTURE")
				);
				if ($projectFields = $projectRes->Fetch()) {
					$projectProperties = apartmentDetailLoadProperties($projectsIblockId, (int)$projectFields["ID"]);
					$projectName = trim((string)$projectFields["NAME"]);
					$projectCode = trim((string)$projectFields["CODE"]);
					$projectUrl = $projectCode !== "" ? "/projects/" . $projectCode . "/" : "";
					$street = apartmentDetailPropertyScalar($projectProperties, "ADDRESS", "");
					$handover = apartmentDetailPropertyScalar($projectProperties, "DELIVERY_TEXT", "");
				}
			}

			$rooms = apartmentDetailPropertyEnumLabel($apartmentProperties, "ROOMS", "");
			$areaTotal = apartmentDetailFormatArea(apartmentDetailPropertyScalar($apartmentProperties, "AREA_TOTAL", ""));
			$titleLine1 = apartmentDetailRoomsLabel($rooms);
			$titleLine2 = $areaTotal;
			$title = trim($titleLine1 . ($titleLine1 !== "" && $titleLine2 !== "" ? ", " : "") . $titleLine2);
			if ($title === "") {
				$title = trim((string)$apartmentFields["NAME"]);
			}

			$priceTotalRaw = (float)apartmentDetailPropertyScalar($apartmentProperties, "PRICE_TOTAL", 0);
			$priceOldRaw = (float)apartmentDetailPropertyScalar($apartmentProperties, "PRICE_OLD", 0);

			$overrides = array(
				"title" => $title,
				"title_line_1" => $titleLine1,
				"title_line_2" => $titleLine2,
				"project" => $projectName,
				"project_url" => $projectUrl,
				"building" => apartmentDetailPropertyScalar($apartmentProperties, "CORPUS", ""),
				"floor" => apartmentDetailPropertyScalar($apartmentProperties, "FLOOR", ""),
				"floor_to" => apartmentDetailPropertyScalar($apartmentProperties, "FLOOR_TO", ""),
				"house_floors" => apartmentDetailNormalizeHouseFloors(
					apartmentDetailPropertyScalar($apartmentProperties, "FLOOR", ""),
					apartmentDetailPropertyScalar($apartmentProperties, "FLOOR_TO", ""),
					apartmentDetailPropertyScalar($apartmentProperties, "HOUSE_FLOORS", "")
				),
				"handover" => $handover,
				"lot" => trim((string)$apartmentFields["CODE"]),
				"apartment_number" => apartmentDetailPropertyScalar($apartmentProperties, "APARTMENT_NUMBER", ""),
				"price_meter" => apartmentDetailFormatMoney(apartmentDetailPropertyScalar($apartmentProperties, "PRICE_M2", "")),
				"price_total" => apartmentDetailFormatMoney($priceTotalRaw),
				"price_old" => apartmentDetailFormatMoney($priceOldRaw),
				"finish" => apartmentDetailPropertyEnumLabel($apartmentProperties, "FINISH", ""),
				"ceiling" => apartmentDetailFormatCeiling(apartmentDetailPropertyScalar($apartmentProperties, "CEILING", "")),
				"street" => $street,
				"entrance" => apartmentDetailPropertyScalar($apartmentProperties, "ENTRANCE", ""),
				"view" => apartmentDetailPropertyScalar($apartmentProperties, "VIEW_TEXT", ""),
				"window_sides" => apartmentDetailPropertyScalar($apartmentProperties, "WINDOW_SIDES", ""),
				"discount" => apartmentDetailDiscountBadge($priceTotalRaw, $priceOldRaw),
				"badges" => apartmentDetailPropertyMultipleScalars($apartmentProperties, "BADGES"),
				"availability_status" => apartmentDetailPropertyEnumXmlId($apartmentProperties, "STATUS", ""),
				"availability_label" => apartmentDetailPropertyEnumLabel($apartmentProperties, "STATUS", ""),
				"rooms" => $rooms,
				"area_total" => $areaTotal,
				"area_living" => apartmentDetailFormatArea(apartmentDetailPropertyScalar($apartmentProperties, "AREA_LIVING", "")),
				"area_kitchen" => apartmentDetailFormatArea(apartmentDetailPropertyScalar($apartmentProperties, "AREA_KITCHEN", "")),
				"balcony_type" => apartmentDetailPropertyScalar($apartmentProperties, "BALCONY_TYPE", ""),
				"bathrooms" => apartmentDetailPropertyScalar($apartmentProperties, "BATHROOMS", ""),
				"house_type" => "",
				"feature_flags" => array(),
				"feature_tags" => apartmentDetailPropertyMultipleScalars($apartmentProperties, "FEATURE_TAGS"),
			);

			$apartment = apartmentDetailBuildPrototype(
				$overrides,
				"",
				"",
				"",
				""
			);
			$apartment["slides"] = apartmentDetailBuildFixedSlidesFromProperties($apartmentFields, $apartmentProperties);
		}
	}
}

if (!$apartment) {
	CHTTP::SetStatus("404 Not Found");
	@define("ERROR_404", "Y");
	$APPLICATION->SetTitle("Квартира не найдена");
} else {
	$APPLICATION->SetTitle($apartment["title"]);
	$APPLICATION->SetPageProperty("title", $apartment["title"] . " — КУБ");
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<?php if (!$apartment): ?>
<section class="apartment-detail apartment-detail--empty">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
    <p>Квартира не найдена.</p>
  </div>
</section>
<?php else: ?>
<section class="apartment-detail">
  <div class="container">
	    <div class="apartment-hero">
	      <?php if (!empty($apartment["slides"])): ?>
	      <div class="apartment-hero__media">
	        <div class="apartment-hero__viewer-shell" data-apartment-gallery>
	          <?php if (!empty($apartment["discount"])): ?>
	          <div class="apartment-hero__badge apartment-hero__badge--discount"><?= htmlspecialcharsbx($apartment["discount"]) ?></div>
	          <?php endif; ?>
	          <div class="apartment-hero__rail">
	          <?php if (!empty($apartment["availability_badges"])): ?>
	            <?php $statusBadge = reset($apartment["availability_badges"]); ?>
	            <div class="apartment-hero__badge apartment-hero__badge--status apartment-hero__badge--<?= htmlspecialcharsbx($statusBadge["status"]) ?>">
	              <?= htmlspecialcharsbx($statusBadge["label"]) ?>
	            </div>
	            <?php endif; ?>
	            <?php if (!empty($apartment["badges"])): ?>
	              <?php foreach ($apartment["badges"] as $badge): ?>
	                <div class="apartment-hero__badge apartment-hero__badge--status">
	                  <?= htmlspecialcharsbx($badge) ?>
	                </div>
	              <?php endforeach; ?>
	            <?php endif; ?>
	            <div class="apartment-hero__actions">
	              <button class="apartment-hero__action" type="button" data-apartment-action="zoom" aria-label="Увеличить слайд">
	                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <circle cx="10" cy="10" r="6.25" stroke="currentColor" stroke-width="1.5" />
	                  <path d="M10 7V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                  <path d="M7 10H13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                  <path d="M14.5 14.5L18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                </svg>
	              </button>
	              <button class="apartment-hero__action" type="button" data-apartment-action="favorite" aria-label="Добавить в избранное">
	                <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <path d="M10.9988 18.1463L9.77616 17.0337C5.43468 13.1098 2.56445 10.5081 2.56445 7.31585C2.56445 4.71413 4.59884 2.69336 7.20562 2.69336C8.67789 2.69336 10.0906 3.37417 10.9988 4.44819C11.9071 3.37417 13.3198 2.69336 14.7921 2.69336C17.3989 2.69336 19.4333 4.71413 19.4333 7.31585C19.4333 10.5081 16.563 13.1098 12.2215 17.0412L10.9988 18.1463Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                </svg>
	              </button>
	              <button class="apartment-hero__action" type="button" data-apartment-action="share" aria-label="Поделиться">
	                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <path d="M14.8398 7.16113L7.16035 14.8406" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
	                  <path d="M9.37988 5.68164H16.3199V12.6216" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                </svg>
	              </button>
	              <button class="apartment-hero__action" type="button" data-apartment-action="print" aria-label="Печать">
	                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
	                  <path d="M17 13.01L17.01 12.9989" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                  <path d="M7 17H17M6 10V3.6C6 3.26863 6.26863 3 6.6 3H17.4C17.7314 3 18 3.26863 18 3.6V10M21 20.4V14C21 11.7909 19.2091 10 17 10H7C4.79086 10 3 11.7909 3 14V20.4C3 20.7314 3.26863 21 3.6 21H20.4C20.7314 21 21 20.7314 21 20.4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
	                </svg>
	              </button>
	            </div>
	          </div>

	          <div class="apartment-hero__viewer">
	            <div class="swiper apartment-hero__swiper" data-apartment-swiper>
	              <div class="swiper-wrapper">
	                <?php foreach ($apartment["slides"] as $index => $slide): ?>
	                <div
	                  class="swiper-slide apartment-hero__slide apartment-hero__slide--<?= htmlspecialcharsbx($slide["kind"]) ?>"
	                  data-bearing="<?= (int)$slide["bearing"] ?>"
	                >
	                  <div class="apartment-hero__slide-media">
	                    <img src="<?= htmlspecialcharsbx($slide["image"]) ?>" alt="<?= htmlspecialcharsbx($slide["alt"]) ?>" loading="lazy" />
	                  </div>
	                  <div class="apartment-hero__slide-caption">
	                    <?php if (!empty($slide["title"])): ?>
	                    <div class="apartment-hero__slide-title"><?= htmlspecialcharsbx($slide["title"]) ?></div>
	                    <?php endif; ?>
	                    <?php if (!empty($slide["description"])): ?>
	                    <p><?= htmlspecialcharsbx($slide["description"]) ?></p>
	                    <?php endif; ?>
	                  </div>
	                </div>
	                <?php endforeach; ?>
	              </div>
	            </div>
	          </div>

          <?php if (count($apartment["slides"]) > 1): ?>
          <div class="apartment-hero__tabs-row">
            <div class="apartment-hero__tabs" role="tablist" aria-label="Режимы просмотра квартиры">
              <?php foreach ($apartment["slides"] as $index => $slide): ?>
              <button
                class="apartment-hero__tab<?= $index === 0 ? " is-active" : "" ?>"
                type="button"
                role="tab"
                aria-selected="<?= $index === 0 ? "true" : "false" ?>"
                data-apartment-tab="<?= $index ?>"
              >
                <?= htmlspecialcharsbx($slide["label"]) ?>
              </button>
              <?php endforeach; ?>
            </div>

            <div class="apartment-hero__nav">
              <button class="apartment-hero__nav-btn apartment-hero__nav-btn--prev" type="button" data-apartment-prev aria-label="Предыдущий слайд">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M10.5 4.5L6 9L10.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <button class="apartment-hero__nav-btn apartment-hero__nav-btn--next" type="button" data-apartment-next aria-label="Следующий слайд">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M7.5 4.5L12 9L7.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <aside class="apartment-hero__summary">
        <div class="apartment-hero__eyebrow">№ <?= htmlspecialcharsbx($apartment["apartment_number"]) ?></div>
        <h1 class="apartment-hero__title">
          <span class="apartment-hero__title-line"><?= htmlspecialcharsbx($apartment["title_line_1"]) ?></span>
          <span class="apartment-hero__title-line"><?= htmlspecialcharsbx($apartment["title_line_2"]) ?></span>
        </h1>

        <dl class="apartment-hero__facts">
          <?php foreach ($apartment["primary_facts"] as $fact): ?>
          <div class="apartment-hero__fact">
            <dt><?= htmlspecialcharsbx($fact["label"]) ?></dt>
            <dd>
              <?php if (!empty($fact["url"])): ?>
              <a href="<?= htmlspecialcharsbx($fact["url"]) ?>"><?= htmlspecialcharsbx($fact["value"]) ?></a>
              <?php else: ?>
              <?= htmlspecialcharsbx($fact["value"]) ?>
              <?php endif; ?>
            </dd>
          </div>
          <?php endforeach; ?>
        </dl>

        <?php if (!empty($apartment["feature_tags"])): ?>
        <div class="apartment-hero__traits">
          <div class="apartment-hero__traits-title">Особенности</div>
          <div class="apartment-hero__traits-list">
            <?php foreach ($apartment["feature_tags"] as $featureTag): ?>
            <span class="apartment-hero__trait"><?= htmlspecialcharsbx($featureTag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="apartment-hero__price-card">
          <?php if (!empty($apartment["price_old"])): ?>
          <div class="apartment-hero__price-old"><?= htmlspecialcharsbx($apartment["price_old"]) ?></div>
          <?php endif; ?>
          <div class="apartment-hero__price-current"><?= htmlspecialcharsbx($apartment["price_total"]) ?></div>
          <div class="apartment-hero__price-meta">
            <span>Цена за м²</span>
            <strong><?= htmlspecialcharsbx($apartment["price_meter"]) ?></strong>
          </div>
        </div>

        <button
          class="btn btn--primary apartment-hero__cta"
          type="button"
          data-contact-open="contact"
          data-contact-title="Забронировать квартиру"
          data-contact-type="booking"
          data-contact-source="apartment_detail"
        >
          Забронировать
        </button>

        <div class="apartment-hero__params">
          <button class="apartment-hero__params-toggle" type="button" data-apartment-params-toggle aria-expanded="true">
            Все параметры квартиры
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>

          <div class="apartment-hero__params-body" data-apartment-params>
            <dl class="apartment-hero__params-list">
              <?php foreach ($apartment["detail_facts"] as $param): ?>
              <div class="apartment-hero__fact">
                <dt><?= htmlspecialcharsbx($param["label"]) ?></dt>
                <dd><?= htmlspecialcharsbx($param["value"]) ?></dd>
              </div>
              <?php endforeach; ?>
            </dl>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
