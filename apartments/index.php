<?php
define("CATALOG_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Выбор квартиры");
?>

<?php
$APPLICATION->IncludeComponent(
    "szcube:apartment.filter",
    "catalog",
    array(
        "PROJECTS_PAGE_URL" => "/projects/",
        "CATALOG_PAGE_URL" => "/apartments/",
        "ENABLE_SERVER_PAGINATION" => "Y",
        "PAGE_SIZE" => "12",
        "CACHE_TIME" => "36000000",
    ),
    false
);
?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
