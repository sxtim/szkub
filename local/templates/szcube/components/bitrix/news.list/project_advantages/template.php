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
?>

<div class="projects-benefits__body" data-benefits-body>
	<ul class="projects-benefits__list">
		<? foreach ($arResult["ITEMS"] as $index => $item): ?>
			<?
			$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

			$label = "";
			if (isset($item["DISPLAY_PROPERTIES"]["LABEL"]["VALUE"]) && !is_array($item["DISPLAY_PROPERTIES"]["LABEL"]["VALUE"])) {
				$label = trim((string)$item["DISPLAY_PROPERTIES"]["LABEL"]["VALUE"]);
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
			?>
			<li class="projects-benefits__item" data-benefit-category="all" id="<?= $this->GetEditAreaId($item["ID"]) ?>">
				<article
					class="projects-benefit-card"
					data-benefit="<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>"
				>
					<div class="projects-benefit-card__tags">
						<? if ($label !== ""): ?>
							<span class="projects-benefit-card__tag"><?= htmlspecialcharsbx($label) ?></span>
						<? endif; ?>
					</div>

					<div class="projects-benefit-card__image" style="background-image: url('<?= htmlspecialchars($image, ENT_QUOTES) ?>');"></div>

					<div class="projects-benefit-card__info">
						<div class="projects-benefit-card__text">
							<h3 class="projects-benefit-card__title"><?= htmlspecialcharsbx($title) ?></h3>
							<p class="projects-benefit-card__description"><?= htmlspecialcharsbx($description) ?></p>
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
