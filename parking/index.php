<?php
define("PARKING_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Паркинг");
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
          <p>Каталог парковочных мест по нашим жилым комплексам. Выбирайте подходящий уровень, тип места и бюджет в том же контуре, что и квартиры.</p>
          <p>Сейчас в выдаче используем компактную строчную карточку: номер места, ЖК, тип, уровень, площадь, цена и статус.</p>
        </div>
      </div>
      <div class="parking-intro__media" aria-hidden="true">
        <img src="<?= SITE_TEMPLATE_PATH ?>/img/figma-683b8703-3ea0-4192-baac-c2b5ed21c8ba.png" alt="" loading="lazy">
      </div>
    </div>
  </div>
</section>

<?php
$APPLICATION->IncludeComponent(
    "szcube:parking.filter",
    "catalog",
    array(
        "CACHE_TIME" => "36000000",
        "CATALOG_PAGE_URL" => "/parking/",
        "PAGE_SIZE" => "12",
    ),
    false
);
?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
