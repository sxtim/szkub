<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$projectCard = isset($projectCard) && is_array($projectCard) ? $projectCard : array();

$href = isset($projectCard["HREF"]) ? (string)$projectCard["HREF"] : "";
$idAttr = isset($projectCard["ID_ATTR"]) ? trim((string)$projectCard["ID_ATTR"]) : "";
$imageSrc = isset($projectCard["IMAGE_SRC"]) ? (string)$projectCard["IMAGE_SRC"] : "";
$title = isset($projectCard["TITLE"]) ? (string)$projectCard["TITLE"] : "";
$classLabel = isset($projectCard["CLASS_LABEL"]) ? (string)$projectCard["CLASS_LABEL"] : "";
$tagLabel = isset($projectCard["TAG_LABEL"]) ? (string)$projectCard["TAG_LABEL"] : "";
$address = isset($projectCard["ADDRESS"]) ? (string)$projectCard["ADDRESS"] : "";
$deliveryText = isset($projectCard["DELIVERY_TEXT"]) ? (string)$projectCard["DELIVERY_TEXT"] : "";
$saleRooms = isset($projectCard["SALE_ROOMS"]) && is_array($projectCard["SALE_ROOMS"]) ? $projectCard["SALE_ROOMS"] : array();
$saleCountText = isset($projectCard["SALE_COUNT_TEXT"]) ? (string)$projectCard["SALE_COUNT_TEXT"] : "";
$priceFromText = isset($projectCard["PRICE_FROM_TEXT"]) ? (string)$projectCard["PRICE_FROM_TEXT"] : "";
$label = isset($projectCard["LABEL"]) ? (string)$projectCard["LABEL"] : "Жилой комплекс";
?>

<a class="project-card" href="<?= htmlspecialcharsbx($href) ?>"<?= $idAttr !== "" ? ' id="' . htmlspecialcharsbx($idAttr) . '"' : "" ?>>
	<div class="project-card__image">
		<? if ($imageSrc !== ""): ?>
			<img src="<?= htmlspecialcharsbx($imageSrc) ?>" alt="<?= htmlspecialcharsbx($title) ?>" loading="lazy" />
		<? endif; ?>

		<? if ($classLabel !== "" || $tagLabel !== ""): ?>
			<div class="project-card__tags">
				<? if ($classLabel !== ""): ?>
					<span class="tag tag--solid"><?= htmlspecialcharsbx($classLabel) ?></span>
				<? endif; ?>
				<? if ($tagLabel !== ""): ?>
					<span class="tag tag--outline"><?= htmlspecialcharsbx($tagLabel) ?></span>
				<? endif; ?>
			</div>
		<? endif; ?>
	</div>

	<div class="project-card__content">
		<div class="project-card__details">
			<span class="project-card__label"><?= htmlspecialcharsbx($label) ?></span>
			<h3 class="project-card__title"><?= htmlspecialcharsbx($title) ?></h3>

			<? if ($address !== ""): ?>
				<span class="project-card__meta"><?= htmlspecialcharsbx($address) ?></span>
			<? endif; ?>

			<? if ($deliveryText !== ""): ?>
				<span class="project-card__meta">Срок сдачи <strong><?= htmlspecialcharsbx($deliveryText) ?></strong></span>
			<? endif; ?>

			<? if (!empty($saleRooms)): ?>
				<div class="project-card__sale">
					<span class="project-card__sale-label">В продаже:</span>
					<div class="project-card__rooms">
						<? foreach ($saleRooms as $room): ?>
							<? $room = trim((string)$room); ?>
							<? if ($room === "") { continue; } ?>
							<span class="project-card__room"><?= htmlspecialcharsbx($room) ?></span>
						<? endforeach; ?>
					</div>
				</div>
			<? endif; ?>
		</div>

		<div class="project-card__footer">
			<? if ($saleCountText !== ""): ?>
				<span class="project-card__sale-count"><?= htmlspecialcharsbx($saleCountText) ?></span>
			<? endif; ?>
			<? if ($priceFromText !== ""): ?>
				<span class="project-card__price"><?= htmlspecialcharsbx($priceFromText) ?></span>
			<? endif; ?>
		</div>
	</div>
</a>

