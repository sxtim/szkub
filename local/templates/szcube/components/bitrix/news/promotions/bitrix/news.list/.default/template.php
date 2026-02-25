<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);
$activeZhk = isset($_GET["zhk"]) ? preg_replace("/[^a-z0-9_-]/i", "", trim((string)$_GET["zhk"])) : "";
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

<section class="promotions-page">
	<div class="container">
		<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>

		<? if ($activeZhk !== ""): ?>
			<div class="promotions-filter">
				<span class="promotions-filter__label">Фильтр по ЖК:</span>
				<span class="promotions-filter__value"><?= htmlspecialcharsbx($activeZhk) ?></span>
				<a class="promotions-filter__clear" href="/promotions/">Сбросить</a>
			</div>
		<? endif; ?>

		<div class="promo__cards">
			<? foreach ($arResult["ITEMS"] as $index => $item): ?>
				<?
				$this->AddEditAction($item["ID"], $item["EDIT_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_EDIT"));
				$this->AddDeleteAction($item["ID"], $item["DELETE_LINK"], CIBlock::GetArrayByID($item["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));

				$imageSrc = isset($item["PREVIEW_PICTURE"]["SRC"]) ? (string)$item["PREVIEW_PICTURE"]["SRC"] : "";

				$dateToLabel = "";
				$activeToRaw = "";
				if (!empty($item["ACTIVE_TO"])) {
					$activeToRaw = (string)$item["ACTIVE_TO"];
				} elseif (!empty($item["FIELDS"]["DATE_ACTIVE_TO"])) {
					$activeToRaw = (string)$item["FIELDS"]["DATE_ACTIVE_TO"];
				}
				if ($activeToRaw !== "") {
					$timestamp = MakeTimeStamp($activeToRaw);
					if ($timestamp) {
						$dateToLabel = "До " . date("d.m.Y", $timestamp);
					}
				}

				$zhkLabel = "";
				if (isset($item["PROPERTIES"]["ZHK_LABEL"]["VALUE"]) && !is_array($item["PROPERTIES"]["ZHK_LABEL"]["VALUE"])) {
					$zhkLabel = trim((string)$item["PROPERTIES"]["ZHK_LABEL"]["VALUE"]);
				}

				$promoCard = array(
					"INDEX" => (int)$index,
					"HREF" => (string)$item["DETAIL_PAGE_URL"],
					"TITLE" => (string)$item["NAME"],
					"IMAGE_SRC" => $imageSrc,
					"DATE_TO_LABEL" => $dateToLabel,
					"ZHK_LABEL" => $zhkLabel,
					"ID_ATTR" => $this->GetEditAreaId($item["ID"]),
				);
				include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/promo-card.php";
				?>
			<? endforeach; ?>

			<? if (empty($arResult["ITEMS"])): ?>
				<div class="promo-empty">Акции не найдены.</div>
			<? endif; ?>
		</div>

		<? if (!empty($arResult["NAV_STRING"])): ?>
			<?= $arResult["NAV_STRING"] ?>
		<? endif; ?>
	</div>
</section>

