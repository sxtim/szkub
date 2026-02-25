<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);
?>

<section class="promo" id="promo">
	<div class="container">
		<h2 class="section-title">Акции</h2>

		<div class="promo__cards">
			<? foreach ($arResult["ITEMS"] as $index => $item): ?>
				<?
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
				);
				include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/promo-card.php";
				?>
			<? endforeach; ?>
		</div>

		<div class="promo__more">
			<a class="promo__more-link" href="/promotions/">
				<span>Все акции</span>
				<span class="promo__more-arrow" aria-hidden="true">⟶</span>
			</a>
		</div>
	</div>
</section>

