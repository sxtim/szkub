<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);

$this->AddEditAction($arResult["ID"], $arResult["EDIT_LINK"], CIBlock::GetArrayByID($arResult["IBLOCK_ID"], "ELEMENT_EDIT"));
$this->AddDeleteAction($arResult["ID"], $arResult["DELETE_LINK"], CIBlock::GetArrayByID($arResult["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

$title = isset($arResult["NAME"]) ? (string)$arResult["NAME"] : "";
$previewText = "";
if (isset($arResult["~PREVIEW_TEXT"]) && (string)$arResult["~PREVIEW_TEXT"] !== "") {
	$previewText = trim((string)$arResult["~PREVIEW_TEXT"]);
} elseif (isset($arResult["PREVIEW_TEXT"])) {
	$previewText = trim((string)$arResult["PREVIEW_TEXT"]);
}
$heroText = array();
if ($previewText !== "") {
	$heroText[] = $previewText;
}

$imageSrc = "";
if (isset($arResult["PREVIEW_PICTURE"]["SRC"])) {
	$imageSrc = (string)$arResult["PREVIEW_PICTURE"]["SRC"];
} elseif (isset($arResult["DETAIL_PICTURE"]["SRC"])) {
	$imageSrc = (string)$arResult["DETAIL_PICTURE"]["SRC"];
}

$detailHtml = "";
if (isset($arResult["DETAIL_TEXT"]) && (string)$arResult["DETAIL_TEXT"] !== "") {
	if (isset($arResult["DETAIL_TEXT_TYPE"]) && strtolower((string)$arResult["DETAIL_TEXT_TYPE"]) === "text") {
		$detailHtml = "<p>" . nl2br(htmlspecialcharsbx((string)$arResult["DETAIL_TEXT"])) . "</p>";
	} else {
		$detailHtml = (string)$arResult["DETAIL_TEXT"];
	}
}

ob_start();
?>
<div class="news-detail" id="<?= $this->GetEditAreaId($arResult["ID"]); ?>">
	<? if ($detailHtml !== ""): ?>
		<div class="news-detail__content">
			<?= $detailHtml ?>
		</div>
	<? endif; ?>
</div>
<?
$articleHeroExtra = ob_get_clean();

$articleHero = array(
	"title" => $title,
	"text" => $heroText,
	"list" => array(),
	"image" => $imageSrc,
);
$articleHeroShowCta = false;
?>

<? include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/article-hero.php"; ?>
