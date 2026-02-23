<?
define("PROMOTIONS_PAGE", true);
define("FOOTER_FLAT", true);
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

		<div class="promo__cards">
			<? foreach ($promotions as $index => $promo): ?>
				<?php
				$isRight = ($index % 3) === 1;
				$cardClass = $isRight ? "promo-card promo-card--right" : "promo-card promo-card--left";
				$image = $isRight
					? SITE_TEMPLATE_PATH . "/img/figma-683b8703-3ea0-4192-baac-c2b5ed21c8ba.png"
					: SITE_TEMPLATE_PATH . "/img/figma-8964cdee-e9c9-4b1f-9979-9cd074589984.png";
				?>
				<a class="<?= $cardClass ?>" href="/promotions/<?= htmlspecialcharsbx($promo["code"]) ?>/">
					<img src="<?= htmlspecialcharsbx($image) ?>" alt="<?= htmlspecialcharsbx($promo["title"]) ?>" />

					<? if ($isRight): ?>
						<div class="promo-card__overlay promo-card__overlay--split"></div>
						<div class="promo-card__text promo-card__text--right">
							<p><?= htmlspecialcharsbx($promo["title"]) ?></p>
						</div>
					<? else: ?>
						<div class="promo-card__overlay promo-card__overlay--full">
							<p><?= htmlspecialcharsbx($promo["title"]) ?></p>
						</div>
					<? endif; ?>
				</a>
			<? endforeach; ?>

			<? if (count($promotions) === 0): ?>
				<div class="promo-empty">Акции не найдены.</div>
			<? endif; ?>
		</div>
	</div>
</section>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
