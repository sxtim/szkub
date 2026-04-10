<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);
?>

<div class="projects__cards">
	<? foreach ($arResult["ITEMS"] as $item): ?>
		<?
		$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
		$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

		$imageSrc = isset($item["PREVIEW_PICTURE"]["SRC"]) ? (string)$item["PREVIEW_PICTURE"]["SRC"] : "";
		$props = isset($item["PROPERTIES"]) && is_array($item["PROPERTIES"]) ? $item["PROPERTIES"] : array();
		$autoProjectCard = isset($item["AUTO_PROJECT_CARD"]) && is_array($item["AUTO_PROJECT_CARD"]) ? $item["AUTO_PROJECT_CARD"] : array();

			$propertyValue = static function ($props, $code) {
				if (!isset($props[$code]["VALUE"])) {
					return "";
				}
			if (is_array($props[$code]["VALUE"])) {
				return "";
			}
				return trim((string)$props[$code]["VALUE"]);
			};

			$propertyEnumXmlId = static function ($props, $code) {
				if (!isset($props[$code]["VALUE_XML_ID"])) {
					return "";
				}
				$value = $props[$code]["VALUE_XML_ID"];
				if (is_array($value)) {
					$value = reset($value);
				}
				return trim((string)$value);
			};

			$statusCode = $propertyEnumXmlId($props, "ABOUT_COMPANY_STATUS");
			$statusBadgeText = "";
			$classLabel = $propertyValue($props, "CLASS_LABEL");
			$tagLabel = $propertyValue($props, "TAG_LABEL");

			$manualSaleRooms = array();
			if (isset($props["ROOMS_IN_SALE"]["VALUE"])) {
				if (is_array($props["ROOMS_IN_SALE"]["VALUE"])) {
					$manualSaleRooms = array_values(array_filter(array_map("trim", $props["ROOMS_IN_SALE"]["VALUE"])));
				} elseif ((string)$props["ROOMS_IN_SALE"]["VALUE"] !== "") {
					$manualSaleRooms = array(trim((string)$props["ROOMS_IN_SALE"]["VALUE"]));
				}
			}

			$autoSaleRooms = array();
			if (isset($autoProjectCard["SALE_ROOMS"]) && is_array($autoProjectCard["SALE_ROOMS"])) {
				$autoSaleRooms = array_values(array_filter(array_map("trim", $autoProjectCard["SALE_ROOMS"])));
			}

			$manualSaleCountText = $propertyValue($props, "SALE_COUNT_TEXT");
			$autoSaleCountText = "";
			if (isset($autoProjectCard["SALE_COUNT_TEXT"]) && trim((string)$autoProjectCard["SALE_COUNT_TEXT"]) !== "") {
				$autoSaleCountText = trim((string)$autoProjectCard["SALE_COUNT_TEXT"]);
			}

			$manualPriceFromText = $propertyValue($props, "PRICE_FROM_TEXT");
			$autoPriceFromText = "";
			if (isset($autoProjectCard["PRICE_FROM_TEXT"]) && trim((string)$autoProjectCard["PRICE_FROM_TEXT"]) !== "") {
				$autoPriceFromText = trim((string)$autoProjectCard["PRICE_FROM_TEXT"]);
			}

			$saleRooms = array();
			$saleRoomsLabel = "";
			$saleCountText = $manualSaleCountText;
			$priceFromText = $manualPriceFromText;

			if ($statusCode === "planned") {
				$saleRooms = $manualSaleRooms;
				if (!empty($saleRooms)) {
					$saleRoomsLabel = "Скоро в продаже";
				}
			} elseif ($statusCode === "building") {
				$saleRooms = !empty($autoSaleRooms) ? $autoSaleRooms : $manualSaleRooms;
				if (!empty($saleRooms)) {
					$saleRoomsLabel = "В продаже:";
				}
				if ($autoSaleCountText !== "") {
					$saleCountText = $autoSaleCountText;
				}
				if ($autoPriceFromText !== "") {
					$priceFromText = $autoPriceFromText;
				}
			} elseif ($statusCode === "completed") {
				$saleRooms = array();
				$saleRoomsLabel = "";
				$classLabel = "";
				$tagLabel = "";
				$statusBadgeText = "Продан";
			} else {
				$saleRooms = !empty($autoSaleRooms) ? $autoSaleRooms : $manualSaleRooms;
				if (!empty($autoSaleRooms)) {
					$saleRoomsLabel = "В продаже:";
					if ($autoSaleCountText !== "") {
						$saleCountText = $autoSaleCountText;
					}
					if ($autoPriceFromText !== "") {
						$priceFromText = $autoPriceFromText;
					}
				} elseif (!empty($manualSaleRooms)) {
					$saleRoomsLabel = "Скоро в продаже";
				}
			}

			$projectCard = array(
			"HREF" => (string)$item["DETAIL_PAGE_URL"],
			"ID_ATTR" => $this->GetEditAreaId($item["ID"]),
			"IMAGE_SRC" => $imageSrc,
			"TITLE" => (string)$item["NAME"],
			"CLASS_LABEL" => $classLabel,
			"TAG_LABEL" => $tagLabel,
			"ADDRESS" => $propertyValue($props, "ADDRESS"),
			"DELIVERY_TEXT" => $propertyValue($props, "DELIVERY_TEXT"),
			"SALE_ROOMS" => $saleRooms,
			"SALE_ROOMS_LABEL" => $saleRoomsLabel,
			"SALE_COUNT_TEXT" => $saleCountText,
			"PRICE_FROM_TEXT" => $priceFromText,
			"STATUS_CODE" => $statusCode,
			"STATUS_BADGE_TEXT" => $statusBadgeText,
			"LABEL" => "Жилой комплекс",
		);

		include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/project-card.php";
		?>
	<? endforeach; ?>

	<? if (empty($arResult["ITEMS"])): ?>
		<div class="projects-empty">Проекты не найдены.</div>
	<? endif; ?>
</div>
