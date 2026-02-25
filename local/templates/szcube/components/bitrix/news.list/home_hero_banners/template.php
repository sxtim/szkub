<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);

$slotItems = array(
	"MAIN" => null,
	"ASIDE_TOP" => null,
	"ASIDE_BOTTOM" => null,
	"BOTTOM_LEFT" => null,
	"BOTTOM_RIGHT" => null,
);

foreach ($arResult["ITEMS"] as $item) {
	$slotCode = "";
	if (isset($item["PROPERTIES"]["SLOT"]["VALUE_XML_ID"]) && !is_array($item["PROPERTIES"]["SLOT"]["VALUE_XML_ID"])) {
		$slotCode = strtoupper(trim((string)$item["PROPERTIES"]["SLOT"]["VALUE_XML_ID"]));
	} elseif (isset($item["PROPERTIES"]["SLOT"]["VALUE"]) && !is_array($item["PROPERTIES"]["SLOT"]["VALUE"])) {
		$slotCode = strtoupper(trim((string)$item["PROPERTIES"]["SLOT"]["VALUE"]));
	}

	if ($slotCode === "" || !array_key_exists($slotCode, $slotItems) || $slotItems[$slotCode] !== null) {
		continue;
	}

	$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

	$linkUrl = "#";
	if (isset($item["PROPERTIES"]["LINK_URL"]["VALUE"]) && !is_array($item["PROPERTIES"]["LINK_URL"]["VALUE"])) {
		$linkUrl = trim((string)$item["PROPERTIES"]["LINK_URL"]["VALUE"]);
	}
	if ($linkUrl === "") {
		$linkUrl = "#";
	}

	$linkTarget = "_self";
	if (isset($item["PROPERTIES"]["LINK_TARGET"]["VALUE"]) && !is_array($item["PROPERTIES"]["LINK_TARGET"]["VALUE"])) {
		$value = trim((string)$item["PROPERTIES"]["LINK_TARGET"]["VALUE"]);
		if ($value !== "") {
			$linkTarget = $value;
		}
	}

	$slotItems[$slotCode] = array(
		"ID_ATTR" => $this->GetEditAreaId($item["ID"]),
		"HREF" => $linkUrl,
		"TARGET" => $linkTarget,
		"TITLE" => isset($item["~NAME"]) ? (string)$item["~NAME"] : (isset($item["NAME"]) ? (string)$item["NAME"] : ""),
		"TEXT" => isset($item["~PREVIEW_TEXT"]) ? trim((string)$item["~PREVIEW_TEXT"]) : (isset($item["PREVIEW_TEXT"]) ? trim((string)$item["PREVIEW_TEXT"]) : ""),
		"IMAGE_SRC" => isset($item["PREVIEW_PICTURE"]["SRC"]) ? (string)$item["PREVIEW_PICTURE"]["SRC"] : "",
	);
}

$main = $slotItems["MAIN"];
$asideTop = $slotItems["ASIDE_TOP"];
$asideBottom = $slotItems["ASIDE_BOTTOM"];
$bottomLeft = $slotItems["BOTTOM_LEFT"];
$bottomRight = $slotItems["BOTTOM_RIGHT"];
?>

<section class="hero">
	<div class="container">
		<div class="hero__top">
			<? if ($main !== null): ?>
				<a class="hero-main" id="<?= htmlspecialcharsbx($main["ID_ATTR"]) ?>" href="<?= htmlspecialcharsbx($main["HREF"]) ?>"<?= $main["TARGET"] !== "" && $main["TARGET"] !== "_self" ? ' target="' . htmlspecialcharsbx($main["TARGET"]) . '" rel="noopener"' : "" ?>>
					<? if ($main["IMAGE_SRC"] !== ""): ?>
						<img src="<?= htmlspecialcharsbx($main["IMAGE_SRC"]) ?>" alt="<?= htmlspecialcharsbx($main["TITLE"]) ?>" loading="lazy" />
					<? endif; ?>
					<? if ($main["TITLE"] !== "" || $main["TEXT"] !== ""): ?>
						<div class="hero-main__overlay">
							<? if ($main["TITLE"] !== ""): ?><p class="hero-main__title"><?= htmlspecialcharsbx($main["TITLE"]) ?></p><? endif; ?>
							<? if ($main["TEXT"] !== ""): ?><p class="hero-main__subtitle"><?= htmlspecialcharsbx($main["TEXT"]) ?></p><? endif; ?>
						</div>
					<? endif; ?>
				</a>
			<? endif; ?>

			<div class="hero-aside">
				<? if ($asideTop !== null): ?>
					<a class="hero-card" id="<?= htmlspecialcharsbx($asideTop["ID_ATTR"]) ?>" href="<?= htmlspecialcharsbx($asideTop["HREF"]) ?>"<?= $asideTop["TARGET"] !== "" && $asideTop["TARGET"] !== "_self" ? ' target="' . htmlspecialcharsbx($asideTop["TARGET"]) . '" rel="noopener"' : "" ?>>
						<? if ($asideTop["IMAGE_SRC"] !== ""): ?>
							<img src="<?= htmlspecialcharsbx($asideTop["IMAGE_SRC"]) ?>" alt="<?= htmlspecialcharsbx($asideTop["TITLE"]) ?>" loading="lazy" />
						<? endif; ?>
						<div class="hero-card__label">
							<?= htmlspecialcharsbx($asideTop["TITLE"]) ?>
							<? if ($asideTop["TEXT"] !== ""): ?><br /><?= nl2br(htmlspecialcharsbx($asideTop["TEXT"])) ?><? endif; ?>
						</div>
					</a>
				<? endif; ?>

				<? if ($asideBottom !== null): ?>
					<a class="hero-card" id="<?= htmlspecialcharsbx($asideBottom["ID_ATTR"]) ?>" href="<?= htmlspecialcharsbx($asideBottom["HREF"]) ?>"<?= $asideBottom["TARGET"] !== "" && $asideBottom["TARGET"] !== "_self" ? ' target="' . htmlspecialcharsbx($asideBottom["TARGET"]) . '" rel="noopener"' : "" ?>>
						<? if ($asideBottom["IMAGE_SRC"] !== ""): ?>
							<img src="<?= htmlspecialcharsbx($asideBottom["IMAGE_SRC"]) ?>" alt="<?= htmlspecialcharsbx($asideBottom["TITLE"]) ?>" loading="lazy" />
						<? endif; ?>
						<div class="hero-card__label hero-card__label--tall">
							<?= htmlspecialcharsbx($asideBottom["TITLE"]) ?>
							<? if ($asideBottom["TEXT"] !== ""): ?><br /><?= nl2br(htmlspecialcharsbx($asideBottom["TEXT"])) ?><? endif; ?>
						</div>
					</a>
				<? endif; ?>
			</div>
		</div>

		<div class="hero__bottom">
			<? if ($bottomLeft !== null): ?>
				<a class="hero-banner" id="<?= htmlspecialcharsbx($bottomLeft["ID_ATTR"]) ?>" href="<?= htmlspecialcharsbx($bottomLeft["HREF"]) ?>"<?= $bottomLeft["TARGET"] !== "" && $bottomLeft["TARGET"] !== "_self" ? ' target="' . htmlspecialcharsbx($bottomLeft["TARGET"]) . '" rel="noopener"' : "" ?>>
					<? if ($bottomLeft["IMAGE_SRC"] !== ""): ?>
						<img src="<?= htmlspecialcharsbx($bottomLeft["IMAGE_SRC"]) ?>" alt="<?= htmlspecialcharsbx($bottomLeft["TITLE"]) ?>" loading="lazy" />
					<? endif; ?>
					<div class="hero-banner__label">
						<?= htmlspecialcharsbx($bottomLeft["TITLE"]) ?>
						<? if ($bottomLeft["TEXT"] !== ""): ?><br /><?= nl2br(htmlspecialcharsbx($bottomLeft["TEXT"])) ?><? endif; ?>
					</div>
				</a>
			<? endif; ?>

			<? if ($bottomRight !== null): ?>
				<a class="hero-banner" id="<?= htmlspecialcharsbx($bottomRight["ID_ATTR"]) ?>" href="<?= htmlspecialcharsbx($bottomRight["HREF"]) ?>"<?= $bottomRight["TARGET"] !== "" && $bottomRight["TARGET"] !== "_self" ? ' target="' . htmlspecialcharsbx($bottomRight["TARGET"]) . '" rel="noopener"' : "" ?>>
					<? if ($bottomRight["IMAGE_SRC"] !== ""): ?>
						<img src="<?= htmlspecialcharsbx($bottomRight["IMAGE_SRC"]) ?>" alt="<?= htmlspecialcharsbx($bottomRight["TITLE"]) ?>" loading="lazy" />
					<? endif; ?>
					<div class="hero-banner__label">
						<?= htmlspecialcharsbx($bottomRight["TITLE"]) ?>
						<? if ($bottomRight["TEXT"] !== ""): ?><br /><?= nl2br(htmlspecialcharsbx($bottomRight["TEXT"])) ?><? endif; ?>
					</div>
				</a>
			<? endif; ?>
		</div>
	</div>
</section>
