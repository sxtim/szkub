<?php
define("COMMERCIAL_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Коммерческие помещения");
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<div class="container parking-page__title-wrap">
  <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
</div>

<section class="parking-intro">
  <div class="container">
    <div class="parking-intro__card">
      <div class="parking-intro__copy">
        <div class="parking-intro__text">
          <p>Каталог коммерческих помещений по нашим жилым комплексам. Выбирайте формат, площадь и бюджет в том же контуре, что и квартиры, паркинг и кладовые.</p>
          <p>В выдаче используем карточки по паттерну квартир: ЖК, срок сдачи, план, тип помещения, площадь, этаж, цена и статус.</p>
        </div>
      </div>
      <div class="parking-intro__media" aria-hidden="true">
        <img src="<?= SITE_TEMPLATE_PATH ?>/img/figma-d19d0bcf-14ae-4fb3-a3dc-4363edabe21a.png" alt="" loading="lazy">
      </div>
    </div>
  </div>
</section>

<?php
$APPLICATION->IncludeComponent(
    "szcube:commercial.filter",
    "catalog",
    array(
        "CACHE_TIME" => "36000000",
        "CATALOG_PAGE_URL" => "/commerce/",
    ),
    false
);
?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>

