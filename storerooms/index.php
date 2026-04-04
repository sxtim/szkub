<?php
define("STOREROOMS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Кладовые");
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
          <p>Каталог кладовых помещений по нашим жилым комплексам. Выбирайте подходящий вариант в том же контуре, что и квартиры и паркинг.</p>
          <p>В выдаче используем тот же строчный формат карточки: номер кладовки, ЖК, цена, статус и лейблы без лишних параметров.</p>
        </div>
      </div>
      <div class="parking-intro__media" aria-hidden="true">
        <img src="<?= SITE_TEMPLATE_PATH ?>/img/figma-962f733c-d79a-402f-b82c-1e5b010739c3.png" alt="" loading="lazy">
      </div>
    </div>
  </div>
</section>

<?php
$APPLICATION->IncludeComponent(
    "szcube:storeroom.filter",
    "catalog",
    array(
        "CACHE_TIME" => "36000000",
        "CATALOG_PAGE_URL" => "/storerooms/",
        "PAGE_SIZE" => "12",
    ),
    false
);
?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
