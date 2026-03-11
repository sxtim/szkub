<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);

$items = isset($arResult["ITEMS"]) && is_array($arResult["ITEMS"]) ? $arResult["ITEMS"] : array();
if (empty($items)) {
	return;
}
?>

<section class="projects-docs" aria-label="Документация">
	<div class="container">
		<ul class="projects-docs__list">
			<? foreach ($items as $item): ?>
				<?
				$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
				$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

				$title = trim((string)$item["NAME"]);
				if ($title === "") {
					$title = "Документы";
				}
				$subtitle = trim((string)$item["PREVIEW_TEXT"]);

				$fileSrc = "";
				if (isset($item["DISPLAY_PROPERTIES"]["FILE"]["FILE_VALUE"]["SRC"])) {
					$fileSrc = trim((string)$item["DISPLAY_PROPERTIES"]["FILE"]["FILE_VALUE"]["SRC"]);
				}

				$linkUrl = "";
				if (isset($item["DISPLAY_PROPERTIES"]["LINK_URL"]["VALUE"]) && !is_array($item["DISPLAY_PROPERTIES"]["LINK_URL"]["VALUE"])) {
					$linkUrl = trim((string)$item["DISPLAY_PROPERTIES"]["LINK_URL"]["VALUE"]);
				}

				$url = $fileSrc !== "" ? $fileSrc : $linkUrl;
				$isExternal = $fileSrc === "" && preg_match("~^https?://~i", $url);

				$target = "_self";
				$linkTargetValue = isset($item["DISPLAY_PROPERTIES"]["LINK_TARGET"]["VALUE_XML_ID"]) ? trim((string)$item["DISPLAY_PROPERTIES"]["LINK_TARGET"]["VALUE_XML_ID"]) : "";
				if ($linkTargetValue === "_blank") {
					$target = "_blank";
				}
				?>
				<li class="projects-docs__card" id="<?= $this->GetEditAreaId($item["ID"]) ?>">
					<? if ($url !== ""): ?>
						<a
							class="projects-docs__cardLink"
							href="<?= htmlspecialcharsbx($url) ?>"
							target="<?= htmlspecialcharsbx($target) ?>"
							<?= $isExternal ? 'rel="noopener noreferrer"' : '' ?>
						>
					<? else: ?>
						<div class="projects-docs__cardLink">
					<? endif; ?>
						<div class="projects-docs__cardText">
							<h3 class="projects-docs__cardTitle"><?= htmlspecialcharsbx($title) ?></h3>
							<? if ($subtitle !== ""): ?>
								<p class="projects-docs__cardSubtitle"><?= htmlspecialcharsbx($subtitle) ?></p>
							<? endif; ?>
						</div>
						<div class="projects-docs__cardIcon" aria-hidden="true">
							<svg class="projects-docs__iconItem" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4 21.4V2.6C4 2.26863 4.26863 2 4.6 2H16.2515C16.4106 2 16.5632 2.06321 16.6757 2.17574L19.8243 5.32426C19.9368 5.43679 20 5.5894 20 5.74853V21.4C20 21.7314 19.7314 22 19.4 22H4.6C4.26863 22 4 21.7314 4 21.4Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
								<path d="M8 10L16 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
								<path d="M8 18L16 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
								<path d="M8 14L12 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
								<path d="M16 2V5.4C16 5.73137 16.2686 6 16.6 6H20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
							</svg>
							<svg class="projects-docs__iconItem projects-docs__iconItemMobile" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
							</svg>
						</div>
					<? if ($url !== ""): ?>
						</a>
					<? else: ?>
						</div>
					<? endif; ?>
				</li>
			<? endforeach; ?>
		</ul>
	</div>
</section>
