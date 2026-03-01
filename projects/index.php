<?php
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проекты");

$projectsIblockType = "content";
$projectsIblockCode = "projects";
$projectsIblockId = 0;
if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
	$iblockRes = CIBlock::GetList(
		array(),
		array(
			"TYPE" => $projectsIblockType,
			"=CODE" => $projectsIblockCode,
			"ACTIVE" => "Y",
		),
		false
	);
	if ($iblock = $iblockRes->Fetch()) {
		$projectsIblockId = (int)$iblock["ID"];
	}
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="projects">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
    <?php if ($projectsIblockId > 0): ?>
      <?php
      $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "projects_list",
        array(
          "IBLOCK_TYPE" => $projectsIblockType,
          "IBLOCK_ID" => $projectsIblockId,
          "NEWS_COUNT" => "30",
          "SORT_BY1" => "SORT",
          "SORT_ORDER1" => "ASC",
          "SORT_BY2" => "NAME",
          "SORT_ORDER2" => "ASC",
          "FIELD_CODE" => array(
            0 => "NAME",
            1 => "PREVIEW_PICTURE",
            2 => "",
          ),
          "PROPERTY_CODE" => array(
            0 => "CLASS_LABEL",
            1 => "TAG_LABEL",
            2 => "ADDRESS",
            3 => "DELIVERY_TEXT",
            4 => "ROOMS_IN_SALE",
            5 => "SALE_COUNT_TEXT",
            6 => "PRICE_FROM_TEXT",
            7 => "",
          ),
          "CHECK_DATES" => "N",
          "DETAIL_URL" => "/projects/#ELEMENT_CODE#/",
          "ACTIVE_DATE_FORMAT" => "d.m.Y",
          "CACHE_TYPE" => "A",
          "CACHE_TIME" => "36000000",
          "CACHE_FILTER" => "N",
          "CACHE_GROUPS" => "Y",
          "SET_TITLE" => "N",
          "SET_BROWSER_TITLE" => "N",
          "SET_META_KEYWORDS" => "N",
          "SET_META_DESCRIPTION" => "N",
          "SET_LAST_MODIFIED" => "N",
          "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
          "ADD_SECTIONS_CHAIN" => "N",
          "HIDE_LINK_WHEN_NO_DETAIL" => "N",
          "DISPLAY_TOP_PAGER" => "N",
          "DISPLAY_BOTTOM_PAGER" => "N",
          "PAGER_SHOW_ALWAYS" => "N",
          "PAGER_TEMPLATE" => "",
          "DISPLAY_DATE" => "N",
          "DISPLAY_NAME" => "Y",
          "DISPLAY_PICTURE" => "Y",
          "DISPLAY_PREVIEW_TEXT" => "N",
          "PARENT_SECTION" => "",
          "PARENT_SECTION_CODE" => "",
          "STRICT_SECTION_CHECK" => "N",
        ),
        false
      );
      ?>
    <?php else: ?>
      <p>Раздел проектов подготовлен для вывода через инфоблок Bitrix.</p>
      <p><small>Ожидаемый инфоблок: TYPE=`<?= htmlspecialcharsbx($projectsIblockType) ?>`, CODE=`<?= htmlspecialcharsbx($projectsIblockCode) ?>`.</small></p>
    <?php endif; ?>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
