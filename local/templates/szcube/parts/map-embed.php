<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$mapEmbedHtml = isset($mapEmbedHtml) ? trim((string)$mapEmbedHtml) : "";
$mapClass = isset($mapClass) && trim((string)$mapClass) !== "" ? trim((string)$mapClass) : "szcube-map";
$mapPlaceholderTitle = isset($mapPlaceholderTitle) && trim((string)$mapPlaceholderTitle) !== ""
	? trim((string)$mapPlaceholderTitle)
	: "Здесь будет карта";
$mapPlaceholderText = isset($mapPlaceholderText) && trim((string)$mapPlaceholderText) !== ""
	? trim((string)$mapPlaceholderText)
	: "Карта пока не добавлена.";
?>
<div class="<?= htmlspecialcharsbx($mapClass) ?>">
	<div class="szcube-map__embed<?= $mapEmbedHtml === "" ? " is-empty" : "" ?>">
		<?php if ($mapEmbedHtml !== ""): ?>
			<?= $mapEmbedHtml ?>
		<?php else: ?>
			<div class="szcube-map__placeholder">
				<div class="szcube-map__placeholder-badge">Заглушка</div>
				<h3 class="szcube-map__placeholder-title"><?= htmlspecialcharsbx($mapPlaceholderTitle) ?></h3>
				<p class="szcube-map__placeholder-text"><?= htmlspecialcharsbx($mapPlaceholderText) ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
