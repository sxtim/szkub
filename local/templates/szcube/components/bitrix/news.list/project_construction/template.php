<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);

$items = isset($arResult["ITEMS"]) && is_array($arResult["ITEMS"]) ? $arResult["ITEMS"] : array();
if (empty($items)) {
	?>
	<div class="projects-empty">Данные о ходе строительства пока не добавлены.</div>
	<?
	return;
}
?>

<div class="construction__slider">
	<div class="construction__swiper swiper" data-construction-swiper>
		<div class="swiper-wrapper">
			<? foreach ($items as $item): ?>
				<?
				$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
				$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

				$month = trim((string)$item["NAME"]);
				$description = trim((string)$item["PREVIEW_TEXT"]);
				$description = strip_tags(
					$description,
					"<br><p><ul><ol><li><strong><b><em><i>"
				);
				$dateText = "";
				if (isset($item["DISPLAY_PROPERTIES"]["DATE_TEXT"]["VALUE"]) && !is_array($item["DISPLAY_PROPERTIES"]["DATE_TEXT"]["VALUE"])) {
					$dateText = trim((string)$item["DISPLAY_PROPERTIES"]["DATE_TEXT"]["VALUE"]);
				}
				if ($dateText === "" && !empty($item["ACTIVE_FROM"])) {
					$dateText = (string)$item["ACTIVE_FROM"];
				}

				$image = "";
				if (isset($item["PREVIEW_PICTURE"]["SRC"])) {
					$image = (string)$item["PREVIEW_PICTURE"]["SRC"];
				}

				$images = array();
				$galleryFiles = isset($item["DISPLAY_PROPERTIES"]["GALLERY"]["FILE_VALUE"]) ? $item["DISPLAY_PROPERTIES"]["GALLERY"]["FILE_VALUE"] : null;
				if (is_array($galleryFiles)) {
					if (isset($galleryFiles["SRC"])) {
						$images[] = (string)$galleryFiles["SRC"];
					} else {
						foreach ($galleryFiles as $galleryFile) {
							if (is_array($galleryFile) && isset($galleryFile["SRC"]) && trim((string)$galleryFile["SRC"]) !== "") {
								$images[] = (string)$galleryFile["SRC"];
							}
						}
					}
				}
				if (empty($images) && $image !== "") {
					$images[] = $image;
				}
				if ($image === "" && !empty($images)) {
					$image = (string)$images[0];
				}

				$payload = array(
					"month" => $month,
					"date" => $dateText,
					"description" => $description,
					"images" => $images,
				);
				?>
				<div class="swiper-slide" id="<?= $this->GetEditAreaId($item["ID"]) ?>">
					<article
						class="construction-card"
						data-construction="<?= htmlspecialchars(json_encode($payload, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>"
					>
						<div
							class="construction-card__image"
							style="background-image: url('<?= htmlspecialchars($image, ENT_QUOTES) ?>');"
							aria-hidden="true"
						></div>

						<div class="construction-card__info">
							<div class="construction-card__meta">
								<h3 class="construction-card__month"><?= htmlspecialcharsbx($month) ?></h3>
								<p class="construction-card__count"><?= htmlspecialcharsbx(count($images) . " фото") ?></p>
							</div>

							<button class="construction-card__more" type="button" aria-label="Открыть фото">
								<svg class="construction-card__more-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
									<path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
								</svg>
							</button>
						</div>
					</article>
				</div>
			<? endforeach; ?>
		</div>
	</div>

	<div class="construction__nav" aria-label="Навигация по ходу строительства">
		<div class="construction__controls" role="group" aria-label="Переключение месяцев">
			<button class="construction__navBtn" type="button" aria-label="Предыдущее" data-construction-prev>
				<svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M4.00049 6.00024L7.00049 3.00024V9.00024L4.00049 6.00024Z" fill="currentColor"></path>
				</svg>
			</button>
			<button class="construction__navBtn" type="button" aria-label="Следующее" data-construction-next>
				<svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
				</svg>
			</button>
		</div>

		<div class="construction__pagination" data-construction-pagination>1 / <?= (int)count($items) ?></div>
	</div>
</div>
