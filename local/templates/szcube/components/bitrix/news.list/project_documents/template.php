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
							<svg class="projects-docs__iconItem" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M4.66667 4.00016V2.00016C4.66667 1.82335 4.7369 1.65378 4.86193 1.52876C4.98695 1.40373 5.15652 1.3335 5.33333 1.3335H13.3333C13.5101 1.3335 13.6797 1.40373 13.8047 1.52876C13.9298 1.65378 14 1.82335 14 2.00016V11.3335C14 11.5103 13.9298 11.6799 13.8047 11.8049C13.6797 11.9299 13.5101 12.0002 13.3333 12.0002H11.3333V14.0002C11.3333 14.3682 11.0333 14.6668 10.662 14.6668H2.67133C2.58342 14.6674 2.49626 14.6505 2.41488 14.6172C2.3335 14.584 2.25949 14.535 2.19711 14.473C2.13472 14.4111 2.0852 14.3374 2.05137 14.2563C2.01754 14.1751 2.00009 14.0881 2 14.0002L2.002 4.66683C2.002 4.29883 2.302 4.00016 2.67333 4.00016H4.66667ZM6 4.00016H11.3333V10.6668H12.6667V2.66683H6V4.00016ZM4.66667 7.3335V8.66683H8.66667V7.3335H4.66667ZM4.66667 10.0002V11.3335H8.66667V10.0002H4.66667Z" fill="currentColor"></path>
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
