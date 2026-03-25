<?php
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Политика конфиденциальности");
$APPLICATION->SetPageProperty("title", "Политика конфиденциальности — КУБ");
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="legal-page">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
    <div class="legal-page__content">
      <?php
      $APPLICATION->IncludeComponent(
        "bitrix:main.include",
        "",
        array(
          "AREA_FILE_SHOW" => "file",
          "PATH" => SITE_DIR . "include/privacy-policy-content.php",
          "EDIT_TEMPLATE" => "",
        ),
        false
      );
      ?>
    </div>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
