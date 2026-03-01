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

		$propertyValue = static function ($props, $code) {
			if (!isset($props[$code]["VALUE"])) {
				return "";
			}
			if (is_array($props[$code]["VALUE"])) {
				return "";
			}
			return trim((string)$props[$code]["VALUE"]);
		};

		$saleRooms = array();
		if (isset($props["ROOMS_IN_SALE"]["VALUE"])) {
			if (is_array($props["ROOMS_IN_SALE"]["VALUE"])) {
				$saleRooms = $props["ROOMS_IN_SALE"]["VALUE"];
			} elseif ((string)$props["ROOMS_IN_SALE"]["VALUE"] !== "") {
				$saleRooms = array((string)$props["ROOMS_IN_SALE"]["VALUE"]);
			}
		}

		$projectCard = array(
			"HREF" => (string)$item["DETAIL_PAGE_URL"],
			"ID_ATTR" => $this->GetEditAreaId($item["ID"]),
			"IMAGE_SRC" => $imageSrc,
			"TITLE" => (string)$item["NAME"],
			"CLASS_LABEL" => $propertyValue($props, "CLASS_LABEL"),
			"TAG_LABEL" => $propertyValue($props, "TAG_LABEL"),
			"ADDRESS" => $propertyValue($props, "ADDRESS"),
			"DELIVERY_TEXT" => $propertyValue($props, "DELIVERY_TEXT"),
			"SALE_ROOMS" => $saleRooms,
			"SALE_COUNT_TEXT" => $propertyValue($props, "SALE_COUNT_TEXT"),
			"PRICE_FROM_TEXT" => $propertyValue($props, "PRICE_FROM_TEXT"),
			"LABEL" => "Жилой комплекс",
		);

		include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/project-card.php";
		?>
	<? endforeach; ?>

	<? if (empty($arResult["ITEMS"])): ?>
		<div class="projects-empty">Проекты не найдены.</div>
	<? endif; ?>
</div>

