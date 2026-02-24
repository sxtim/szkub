<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);
?>

<div class="breadcrumbs-wrap">
	<div class="container">
		<? $APPLICATION->GetNavChain(
			false,
			0,
			SITE_TEMPLATE_PATH . "/components/bitrix/breadcrumb/szcube/template.php",
			true,
			false
		); ?>
	</div>
</div>

<section class="news">
	<div class="container">
		<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>

		<div class="news__cards">
			<? foreach ($arResult["ITEMS"] as $item): ?>
				<?
				$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
				$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

				$dateIso = "";
				$dateText = "";
				if (!empty($item["ACTIVE_FROM"])) {
					$timestamp = MakeTimeStamp($item["ACTIVE_FROM"]);
					if ($timestamp) {
						$dateIso = date("Y-m-d", $timestamp);
						$dateText = date("d.m.Y", $timestamp);
					}
				}
				if ($dateText === "" && !empty($item["DISPLAY_ACTIVE_FROM"])) {
					$dateText = (string)$item["DISPLAY_ACTIVE_FROM"];
				}

				$imageSrc = "";
				if (isset($item["PREVIEW_PICTURE"]["SRC"])) {
					$imageSrc = (string)$item["PREVIEW_PICTURE"]["SRC"];
				}
				?>
				<?
				$newsCard = array(
					"HREF" => (string)$item["DETAIL_PAGE_URL"],
					"TITLE" => (string)$item["NAME"],
					"IMAGE_SRC" => $imageSrc,
					"DATE_ISO" => $dateIso,
					"DATE_TEXT" => $dateText,
					"PREVIEW" => !empty($item["PREVIEW_TEXT"]) ? (string)$item["PREVIEW_TEXT"] : "",
					"ID_ATTR" => $this->GetEditAreaId($item["ID"]),
				);
				include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/news-card.php";
				?>
			<? endforeach; ?>

			<? if (empty($arResult["ITEMS"])): ?>
				<div class="news-empty">Новости не найдены.</div>
			<? endif; ?>
		</div>

		<? if (!empty($arResult["NAV_STRING"])): ?>
			<?= $arResult["NAV_STRING"] ?>
		<? endif; ?>
	</div>
</section>
