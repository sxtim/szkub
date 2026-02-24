<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$newsCard = isset($newsCard) && is_array($newsCard) ? $newsCard : array();

$href = isset($newsCard["HREF"]) ? (string)$newsCard["HREF"] : "";
$title = isset($newsCard["TITLE"]) ? (string)$newsCard["TITLE"] : "";
$imageSrc = isset($newsCard["IMAGE_SRC"]) ? (string)$newsCard["IMAGE_SRC"] : "";
$dateIso = isset($newsCard["DATE_ISO"]) ? (string)$newsCard["DATE_ISO"] : "";
$dateText = isset($newsCard["DATE_TEXT"]) ? (string)$newsCard["DATE_TEXT"] : "";
$preview = isset($newsCard["PREVIEW"]) ? (string)$newsCard["PREVIEW"] : "";
$idAttr = isset($newsCard["ID_ATTR"]) ? trim((string)$newsCard["ID_ATTR"]) : "";
?>

<a class="news-card"<?= $idAttr !== "" ? ' id="' . htmlspecialcharsbx($idAttr) . '"' : "" ?> href="<?= htmlspecialcharsbx($href) ?>">
	<? if ($imageSrc !== ""): ?>
		<img src="<?= htmlspecialcharsbx($imageSrc) ?>" alt="<?= htmlspecialcharsbx($title) ?>" loading="lazy" />
	<? endif; ?>
	<? if ($dateText !== ""): ?>
		<time datetime="<?= htmlspecialcharsbx($dateIso) ?>"><?= htmlspecialcharsbx($dateText) ?></time>
	<? endif; ?>
	<h3><?= htmlspecialcharsbx($title) ?></h3>
	<? if ($preview !== ""): ?>
		<p><?= htmlspecialcharsbx($preview) ?></p>
	<? endif; ?>
</a>

