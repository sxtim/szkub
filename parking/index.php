<?php
define("PARKING_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Паркинг");
$catalogPageIntro = function_exists("szcubeGetCatalogPage") ? szcubeGetCatalogPage("parking") : array();
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<div class="container parking-page__title-wrap">
  <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
</div>

<?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/catalog-page-intro.php"; ?>

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
