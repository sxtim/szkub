<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

$this->setFrameMode(true);
?>

<section class="news" id="news">
	<div class="container">
		<h2 class="section-title">Новости</h2>

		<div class="news__cards">
			<? foreach ($arResult["ITEMS"] as $item): ?>
				<?
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

				$newsCard = array(
					"HREF" => (string)$item["DETAIL_PAGE_URL"],
					"TITLE" => (string)$item["NAME"],
					"IMAGE_SRC" => $imageSrc,
					"DATE_ISO" => $dateIso,
					"DATE_TEXT" => $dateText,
					"PREVIEW" => !empty($item["PREVIEW_TEXT"]) ? (string)$item["PREVIEW_TEXT"] : "",
				);
				include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/news-card.php";
				?>
			<? endforeach; ?>
		</div>

		<div class="news__more">
			<a class="news__more-link" href="/news/">
				<span>Все новости</span>
				<span class="news__more-arrow" aria-hidden="true">⟶</span>
			</a>
		</div>
	</div>
</section>

