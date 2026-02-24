<?
define("NEWS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Новости");

$newsItems = require $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/news-data.php";

usort($newsItems, function ($a, $b) {
	return strcmp((string)$b["date"], (string)$a["date"]);
});
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="news">
	<div class="container">
		<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>

		<div class="news__cards">
			<? foreach ($newsItems as $item): ?>
				<?
				$dateIso = isset($item["date"]) ? (string)$item["date"] : "";
				$dateText = $dateIso !== "" ? date("d.m.Y", strtotime($dateIso)) : "";
				?>
				<a class="news-card" href="/news/<?= htmlspecialcharsbx($item["code"]) ?>/">
					<img src="<?= htmlspecialcharsbx($item["image"]) ?>" alt="<?= htmlspecialcharsbx($item["title"]) ?>" loading="lazy" />
					<time datetime="<?= htmlspecialcharsbx($dateIso) ?>"><?= htmlspecialcharsbx($dateText) ?></time>
					<h3><?= htmlspecialcharsbx($item["title"]) ?></h3>
					<p><?= htmlspecialcharsbx($item["preview"]) ?></p>
				</a>
			<? endforeach; ?>
		</div>
	</div>
</section>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
