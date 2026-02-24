<?
define("NEWS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Новости");
// Предпочтительно определять инфоблок по TYPE+CODE (ID может отличаться на локалке/проде).
$newsIblockType = "content";
$newsIblockCode = "news";
$newsIblockId = 0;

if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
	$iblockRes = CIBlock::GetList(
		array(),
		array(
			"TYPE" => $newsIblockType,
			"=CODE" => $newsIblockCode,
			"ACTIVE" => "Y",
		),
		false
	);
	if ($iblock = $iblockRes->Fetch()) {
		$newsIblockId = (int)$iblock["ID"];
	}
}
?>

<? if ($newsIblockId > 0): ?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:news",
		"news",
		array(
			"IBLOCK_TYPE" => $newsIblockType,
			"IBLOCK_ID" => $newsIblockId,
			"NEWS_COUNT" => "12",
			"USE_SEARCH" => "N",
			"USE_RSS" => "N",
			"USE_RATING" => "N",
			"USE_CATEGORIES" => "N",
			"USE_REVIEW" => "N",
			"USE_FILTER" => "N",
			"SORT_BY1" => "ACTIVE_FROM",
			"SORT_ORDER1" => "DESC",
			"SORT_BY2" => "SORT",
			"SORT_ORDER2" => "ASC",
			"CHECK_DATES" => "Y",
			"SEF_MODE" => "Y",
			"SEF_FOLDER" => "/news/",
			"SEF_URL_TEMPLATES" => array(
				"news" => "",
				"section" => "",
				"detail" => "#ELEMENT_CODE#/",
			),
			"VARIABLE_ALIASES" => array(
				"news" => array(),
				"section" => array(),
				"detail" => array(),
			),
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => "36000000",
			"CACHE_FILTER" => "N",
			"CACHE_GROUPS" => "Y",
			"SET_TITLE" => "Y",
			"SET_LAST_MODIFIED" => "N",
			"ADD_SECTIONS_CHAIN" => "N",
			"ADD_ELEMENT_CHAIN" => "Y",
			"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
			"SET_STATUS_404" => "Y",
			"SHOW_404" => "N",
			"FILE_404" => "",
			"MESSAGE_404" => "",
			"STRICT_SECTION_CHECK" => "N",
			"LIST_ACTIVE_DATE_FORMAT" => "d.m.Y",
			"LIST_FIELD_CODE" => array(
				0 => "NAME",
				1 => "PREVIEW_TEXT",
				2 => "PREVIEW_PICTURE",
				3 => "DATE_ACTIVE_FROM",
				4 => "",
			),
			"LIST_PROPERTY_CODE" => array(),
			"HIDE_LINK_WHEN_NO_DETAIL" => "N",
			"DISPLAY_TOP_PAGER" => "N",
			"DISPLAY_BOTTOM_PAGER" => "Y",
			"PAGER_TITLE" => "Новости",
			"PAGER_SHOW_ALWAYS" => "N",
			"PAGER_TEMPLATE" => "",
			"PAGER_DESC_NUMBERING" => "N",
			"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
			"PAGER_SHOW_ALL" => "N",
			"DETAIL_ACTIVE_DATE_FORMAT" => "d.m.Y",
			"DETAIL_FIELD_CODE" => array(
				0 => "NAME",
				1 => "PREVIEW_TEXT",
				2 => "PREVIEW_PICTURE",
				3 => "DETAIL_TEXT",
				4 => "DATE_ACTIVE_FROM",
				5 => "",
			),
			"DETAIL_PROPERTY_CODE" => array(),
			"DETAIL_DISPLAY_TOP_PAGER" => "N",
			"DETAIL_DISPLAY_BOTTOM_PAGER" => "N",
			"DETAIL_PAGER_TITLE" => "Страница",
			"DETAIL_SET_CANONICAL_URL" => "N",
			"SET_BROWSER_TITLE" => "N",
			"SET_META_KEYWORDS" => "N",
			"SET_META_DESCRIPTION" => "N",
			"BROWSER_TITLE" => "-",
			"META_KEYWORDS" => "-",
			"META_DESCRIPTION" => "-",
			"DISPLAY_DATE" => "Y",
			"DISPLAY_NAME" => "Y",
			"DISPLAY_PICTURE" => "Y",
			"DISPLAY_PREVIEW_TEXT" => "Y",
		),
		false
	);?>
<? else: ?>
	<div class="breadcrumbs-wrap">
		<div class="container">
			<?$APPLICATION->IncludeComponent(
				"bitrix:breadcrumb",
				"szcube",
				array(
					"PATH" => "",
					"SITE_ID" => SITE_ID,
					"START_FROM" => "0",
				)
			);?>
		</div>
	</div>
	<section class="news">
		<div class="container">
			<h1 class="section-title"><? $APPLICATION->ShowTitle(false); ?></h1>
				<p>Раздел новостей подготовлен для вывода через инфоблок Bitrix. Создайте инфоблок "Новости" (или запустите скрипт создания инфоблока).</p>
				<p><small>Ожидаемый инфоблок: TYPE=`<?= htmlspecialcharsbx($newsIblockType) ?>`, CODE=`<?= htmlspecialcharsbx($newsIblockCode) ?>`.</small></p>
			</div>
		</section>
	<? endif; ?>

<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
