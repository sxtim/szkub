<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);

if (!function_exists("projectAdvantagesPreviewToHtml")) {
	function projectAdvantagesPreviewToHtml($text)
	{
		$text = trim((string)$text);
		if ($text === "") {
			return "";
		}

		$chunks = preg_split("/\r\n|\r|\n{2,}/u", $text);
		$chunks = array_values(array_filter(array_map("trim", $chunks), static function ($item) {
			return $item !== "";
		}));

		if (empty($chunks)) {
			return "";
		}

		$html = array();
		foreach ($chunks as $chunk) {
			$html[] = "<p>" . nl2br(htmlspecialcharsbx($chunk)) . "</p>";
		}

		return implode("", $html);
	}
}

if (!function_exists("projectAdvantagesKnownCategories")) {
	function projectAdvantagesKnownCategories()
	{
		return array(
			"finish" => array("title" => "Отделка", "sort" => 100),
			"location" => array("title" => "Локация", "sort" => 200),
			"landscape" => array("title" => "Благоустройство", "sort" => 300),
			"infrastructure" => array("title" => "Инфраструктура", "sort" => 400),
			"facade" => array("title" => "Фасад и материалы", "sort" => 500),
			"layouts" => array("title" => "Планировки", "sort" => 600),
		);
	}
}

if (!function_exists("projectAdvantagesCategoryXmlIdByTitle")) {
	function projectAdvantagesCategoryXmlIdByTitle($title)
	{
		$title = trim((string)$title);
		if ($title === "") {
			return "";
		}

		$known = projectAdvantagesKnownCategories();
		foreach ($known as $xmlId => $meta) {
			if (mb_strtolower($meta["title"]) === mb_strtolower($title)) {
				return $xmlId;
			}
		}

		if (class_exists("CUtil")) {
			$slug = (string)CUtil::translit($title, "ru", array(
				"max_len" => 64,
				"change_case" => "L",
				"replace_space" => "-",
				"replace_other" => "-",
				"delete_repeat_replace" => true,
			));
			$slug = trim($slug, "-");
			if ($slug !== "") {
				return $slug;
			}
		}

		$slug = mb_strtolower($title);
		$slug = preg_replace("/[^a-z0-9а-яё]+/iu", "-", $slug);
		$slug = trim((string)$slug, "-");
		return $slug;
	}
}

if (!function_exists("projectAdvantagesResolveCategoryMeta")) {
	function projectAdvantagesResolveCategoryMeta(array $item, $labelFallback = "")
	{
		$categoryTitle = "";
		$categoryXmlId = "";

		if (isset($item["DISPLAY_PROPERTIES"]["CATEGORY"]["DISPLAY_VALUE"]) && !is_array($item["DISPLAY_PROPERTIES"]["CATEGORY"]["DISPLAY_VALUE"])) {
			$categoryTitle = trim((string)$item["DISPLAY_PROPERTIES"]["CATEGORY"]["DISPLAY_VALUE"]);
		}
		if ($categoryTitle === "" && isset($item["DISPLAY_PROPERTIES"]["CATEGORY"]["VALUE"]) && !is_array($item["DISPLAY_PROPERTIES"]["CATEGORY"]["VALUE"])) {
			$categoryTitle = trim((string)$item["DISPLAY_PROPERTIES"]["CATEGORY"]["VALUE"]);
		}
		if (isset($item["PROPERTIES"]["CATEGORY"]["VALUE_XML_ID"]) && !is_array($item["PROPERTIES"]["CATEGORY"]["VALUE_XML_ID"])) {
			$categoryXmlId = trim((string)$item["PROPERTIES"]["CATEGORY"]["VALUE_XML_ID"]);
		}

		if ($categoryTitle === "" && $labelFallback !== "") {
			$categoryTitle = trim((string)$labelFallback);
		}
		if ($categoryXmlId === "" && $categoryTitle !== "") {
			$categoryXmlId = projectAdvantagesCategoryXmlIdByTitle($categoryTitle);
		}

		$known = projectAdvantagesKnownCategories();
		$categorySort = isset($known[$categoryXmlId]["sort"]) ? (int)$known[$categoryXmlId]["sort"] : 1000;

		return array(
			"title" => $categoryTitle,
			"xml_id" => $categoryXmlId,
			"sort" => $categorySort,
		);
	}
}

$benefitCards = array();
$benefitCategories = array();

foreach ($arResult["ITEMS"] as $index => $item) {
	$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

	$label = "";
	if (isset($item["DISPLAY_PROPERTIES"]["LABEL"]["VALUE"]) && !is_array($item["DISPLAY_PROPERTIES"]["LABEL"]["VALUE"])) {
		$label = trim((string)$item["DISPLAY_PROPERTIES"]["LABEL"]["VALUE"]);
	}

	$categoryMeta = projectAdvantagesResolveCategoryMeta($item, $label);
	if ($categoryMeta["xml_id"] !== "" && $categoryMeta["title"] !== "") {
		$benefitCategories[$categoryMeta["xml_id"]] = array(
			"xml_id" => $categoryMeta["xml_id"],
			"title" => $categoryMeta["title"],
			"sort" => $categoryMeta["sort"],
		);
	}

	$title = trim((string)$item["NAME"]);
	$description = trim((string)$item["PREVIEW_TEXT"]);
	$image = "";
	if (isset($item["PREVIEW_PICTURE"]["SRC"])) {
		$image = (string)$item["PREVIEW_PICTURE"]["SRC"];
	}

	$payload = array(
		"id" => (int)$index,
		"label" => $label,
		"title" => $title,
		"description" => $description,
		"content" => projectAdvantagesPreviewToHtml($description),
		"image" => $image,
	);

	$benefitCards[] = array(
		"edit_area_id" => $this->GetEditAreaId($item["ID"]),
		"category_xml_id" => $categoryMeta["xml_id"] !== "" ? $categoryMeta["xml_id"] : "all",
		"label" => $label,
		"title" => $title,
		"description" => $description,
		"image" => $image,
		"payload" => $payload,
	);
}

if (!empty($benefitCategories)) {
	uasort($benefitCategories, static function ($left, $right) {
		$leftSort = isset($left["sort"]) ? (int)$left["sort"] : 1000;
		$rightSort = isset($right["sort"]) ? (int)$right["sort"] : 1000;
		if ($leftSort !== $rightSort) {
			return $leftSort <=> $rightSort;
		}

		return strcasecmp((string)$left["title"], (string)$right["title"]);
	});
}

$showBenefitTabs = count($benefitCategories) > 1;
?>

<? if ($showBenefitTabs): ?>
<div class="projects-benefits__tabs" role="tablist" aria-label="Категории преимуществ">
	<button class="btn btn--sm projects-benefits__tab is-active" type="button" role="tab" aria-selected="true" data-benefit-tab="all">Все</button>
	<? foreach ($benefitCategories as $category): ?>
		<button
			class="btn btn--sm projects-benefits__tab"
			type="button"
			role="tab"
			aria-selected="false"
			data-benefit-tab="<?= htmlspecialchars($category["xml_id"], ENT_QUOTES) ?>"
		><?= htmlspecialcharsbx($category["title"]) ?></button>
	<? endforeach; ?>
</div>
<? endif; ?>

<div class="projects-benefits__body" data-benefits-body>
	<ul class="projects-benefits__list">
		<? foreach ($benefitCards as $card): ?>
			<li class="projects-benefits__item" data-benefit-category="<?= htmlspecialchars($card["category_xml_id"], ENT_QUOTES) ?>" id="<?= $card["edit_area_id"] ?>">
				<article
					class="projects-benefit-card"
					data-benefit="<?= htmlspecialchars(json_encode($card["payload"], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>"
				>
					<div class="projects-benefit-card__tags">
						<? if ($card["label"] !== ""): ?>
							<span class="projects-benefit-card__tag"><?= htmlspecialcharsbx($card["label"]) ?></span>
						<? endif; ?>
					</div>

					<div class="projects-benefit-card__image" style="background-image: url('<?= htmlspecialchars($card["image"], ENT_QUOTES) ?>');"></div>

					<div class="projects-benefit-card__info">
						<div class="projects-benefit-card__text">
							<h3 class="projects-benefit-card__title"><?= htmlspecialcharsbx($card["title"]) ?></h3>
							<p class="projects-benefit-card__description"><?= htmlspecialcharsbx($card["description"]) ?></p>
						</div>

						<button class="projects-benefit-card__more" type="button" aria-label="Подробнее">
							<svg class="projects-benefit-card__more-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
								<path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
							</svg>
						</button>
					</div>
				</article>
			</li>
		<? endforeach; ?>
	</ul>
</div>

<div class="projects-benefits__actions" data-benefits-actions hidden>
	<button class="projects-benefits__more" type="button" data-benefits-more></button>
</div>
