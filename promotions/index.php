<?
define("PROMOTIONS_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Акции");

$promotions = array(
	array(
		"code" => "discount-15",
		"title" => "Скидки до 15%",
		"preview" => "Описание акции (заглушка).",
		"zhk" => "kollekciya",
	),
	array(
		"code" => "installment-1y",
		"title" => "Рассрочка на 1 год",
		"preview" => "Описание акции (заглушка).",
		"zhk" => "vertical",
	),
	array(
		"code" => "common-promo",
		"title" => "Общая акция (без привязки к ЖК)",
		"preview" => "Описание акции (заглушка).",
		"zhk" => "",
	),
);

$activeZhk = isset($_GET["zhk"]) ? trim((string)$_GET["zhk"]) : "";

if ($activeZhk !== "") {
	$promotions = array_values(array_filter($promotions, function ($promo) use ($activeZhk) {
		return isset($promo["zhk"]) && $promo["zhk"] === $activeZhk;
	}));
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
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

		<!-- TODO: сюда вставим верстку списка акций -->
		<ul class="promotions-list">
			<? foreach ($promotions as $promo): ?>
				<li class="promo-card">
					<a class="promo-card__link" href="/promotions/<?= htmlspecialcharsbx($promo["code"]) ?>/">
						<div class="promo-card__title"><?= htmlspecialcharsbx($promo["title"]) ?></div>
						<div class="promo-card__preview"><?= htmlspecialcharsbx($promo["preview"]) ?></div>
					</a>
				</li>
			<? endforeach; ?>

			<? if (count($promotions) === 0): ?>
				<li class="promo-empty">Акции не найдены.</li>
			<? endif; ?>
		</ul>
	</div>
</section>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
