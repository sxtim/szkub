<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$promoCard = isset($promoCard) && is_array($promoCard) ? $promoCard : array();

$index = isset($promoCard["INDEX"]) ? (int)$promoCard["INDEX"] : 0;
$isRight = ($index % 3) === 1;
$cardClass = $isRight ? "promo-card promo-card--right" : "promo-card promo-card--left";

$href = isset($promoCard["HREF"]) ? (string)$promoCard["HREF"] : "";
$title = isset($promoCard["TITLE"]) ? (string)$promoCard["TITLE"] : "";
$imageSrc = isset($promoCard["IMAGE_SRC"]) ? (string)$promoCard["IMAGE_SRC"] : "";
$dateToLabel = isset($promoCard["DATE_TO_LABEL"]) ? (string)$promoCard["DATE_TO_LABEL"] : "";
$zhkLabel = isset($promoCard["ZHK_LABEL"]) ? (string)$promoCard["ZHK_LABEL"] : "";
$idAttr = isset($promoCard["ID_ATTR"]) ? trim((string)$promoCard["ID_ATTR"]) : "";

$labels = array();
if ($dateToLabel !== "") {
	$labels[] = $dateToLabel;
}
if ($zhkLabel !== "") {
	$labels[] = $zhkLabel;
}
?>

<a class="<?= htmlspecialcharsbx($cardClass) ?>"<?= $idAttr !== "" ? ' id="' . htmlspecialcharsbx($idAttr) . '"' : "" ?> href="<?= htmlspecialcharsbx($href) ?>">
	<? if ($imageSrc !== ""): ?>
		<img src="<?= htmlspecialcharsbx($imageSrc) ?>" alt="<?= htmlspecialcharsbx($title) ?>" loading="lazy" />
	<? endif; ?>

	<? if ($isRight): ?>
		<div class="promo-card__overlay promo-card__overlay--split"></div>
		<div class="promo-card__text promo-card__text--right">
			<p><?= htmlspecialcharsbx($title) ?></p>
			<? if (!empty($labels)): ?>
				<div class="promo-card__labels">
					<? foreach ($labels as $label): ?>
						<span class="promo-card__label-chip"><?= htmlspecialcharsbx($label) ?></span>
					<? endforeach; ?>
				</div>
			<? endif; ?>
		</div>
	<? else: ?>
		<div class="promo-card__overlay promo-card__overlay--full">
			<p><?= htmlspecialcharsbx($title) ?></p>
			<? if (!empty($labels)): ?>
				<div class="promo-card__labels">
					<? foreach ($labels as $label): ?>
						<span class="promo-card__label-chip"><?= htmlspecialcharsbx($label) ?></span>
					<? endforeach; ?>
				</div>
			<? endif; ?>
		</div>
	<? endif; ?>
</a>

