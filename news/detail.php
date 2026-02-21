<?
define("NEWS_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$newsItems = array(
	"example-1" => array(
		"title" => "Пример новости №1",
		"date" => "2026-02-21",
		"text" => "Текст новости (заглушка).",
	),
	"example-2" => array(
		"title" => "Пример новости №2",
		"date" => "2026-02-10",
		"text" => "Текст новости (заглушка).",
	),
);

$code = isset($_REQUEST["code"]) ? (string)$_REQUEST["code"] : "";
$code = trim($code);

$item = $code !== "" && isset($newsItems[$code]) ? $newsItems[$code] : null;
if (!$item) {
	CHTTP::SetStatus("404 Not Found");
	@define("ERROR_404", "Y");
	$APPLICATION->SetTitle("Новость не найдена");
} else {
	$APPLICATION->SetTitle($item["title"]);
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<? if ($item): ?>
	<?
	$articleHero = array(
		"title" => $item["title"],
		"text" => array(
			"Короткий лид/описание (заглушка).",
			"Еще один абзац текста (заглушка).",
		),
		"list" => array(
			"Пункт списка №1 (заглушка).",
			"Пункт списка №2 (заглушка).",
			"Пункт списка №3 (заглушка).",
		),
		"image" => SITE_TEMPLATE_PATH . "/img/projects/div.image-lazy__image.jpg",
	);
	$articleHeroShowCta = false;
	include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/article-hero.php";
	?>
<? endif; ?>

<section class="news-detail">
	<div class="container">
		<? if (!$item): ?>
			<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>
			<p>Новость не найдена.</p>
		<? else: ?>
			<!-- TODO: ниже будет контент детальной новости -->
			<div class="news-detail__meta"><?= htmlspecialcharsbx($item["date"]) ?></div>
			<div class="news-detail__content"><?= htmlspecialcharsbx($item["text"]) ?></div>
		<? endif; ?>
	</div>
</section>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
