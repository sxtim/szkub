<?php
define("MORTGAGE_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Военная ипотека");
$APPLICATION->SetPageProperty("title", "Военная ипотека — КУБ");

$mortgageHeroPageCode = "military-mortgage";
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/mortgage-hero.php"; ?>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
