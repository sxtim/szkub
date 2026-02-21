<?
define("PROMOTIONS_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$promotions = array(
	"discount-15" => array(
		"title" => "Скидки до 15%",
		"text" => "Текст акции (заглушка).",
		"zhk" => "kollekciya",
	),
	"installment-1y" => array(
		"title" => "Рассрочка на 1 год",
		"text" => "Текст акции (заглушка).",
		"zhk" => "vertical",
	),
	"common-promo" => array(
		"title" => "Общая акция (без привязки к ЖК)",
		"text" => "Текст акции (заглушка).",
		"zhk" => "",
	),
);

$code = isset($_REQUEST["code"]) ? (string)$_REQUEST["code"] : "";
$code = trim($code);

$item = $code !== "" && isset($promotions[$code]) ? $promotions[$code] : null;
if (!$item) {
	CHTTP::SetStatus("404 Not Found");
	@define("ERROR_404", "Y");
	$APPLICATION->SetTitle("Акция не найдена");
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
	$articleHeroShowCta = true;
	include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/article-hero.php";
	?>
<? endif; ?>

<section class="promo-detail">
	<div class="container">
		<? if (!$item): ?>
			<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>
			<p>Акция не найдена.</p>
		<? else: ?>
			<!-- TODO: ниже будет контент детальной акции -->
			<div class="promo-detail__content"><?= htmlspecialcharsbx($item["text"]) ?></div>
		<? endif; ?>
	</div>
</section>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
