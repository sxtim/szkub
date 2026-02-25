<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
	die();
}

ob_start();
$APPLICATION->IncludeComponent(
	"bitrix:news.detail",
	"",
	array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"ELEMENT_ID" => isset($arResult["VARIABLES"]["ELEMENT_ID"]) ? $arResult["VARIABLES"]["ELEMENT_ID"] : "",
		"ELEMENT_CODE" => isset($arResult["VARIABLES"]["ELEMENT_CODE"]) ? $arResult["VARIABLES"]["ELEMENT_CODE"] : "",
		"FIELD_CODE" => $arParams["DETAIL_FIELD_CODE"],
		"PROPERTY_CODE" => $arParams["DETAIL_PROPERTY_CODE"],
		"CHECK_DATES" => $arParams["CHECK_DATES"],
		"ACTIVE_DATE_FORMAT" => $arParams["DETAIL_ACTIVE_DATE_FORMAT"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_BROWSER_TITLE" => $arParams["SET_BROWSER_TITLE"],
		"SET_META_KEYWORDS" => $arParams["SET_META_KEYWORDS"],
		"SET_META_DESCRIPTION" => $arParams["SET_META_DESCRIPTION"],
		"SET_LAST_MODIFIED" => $arParams["SET_LAST_MODIFIED"],
		"BROWSER_TITLE" => $arParams["BROWSER_TITLE"],
		"META_KEYWORDS" => $arParams["META_KEYWORDS"],
		"META_DESCRIPTION" => $arParams["META_DESCRIPTION"],
		"SET_CANONICAL_URL" => $arParams["DETAIL_SET_CANONICAL_URL"],
		"DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
		"DISPLAY_NAME" => $arParams["DISPLAY_NAME"],
		"DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
		"USE_PERMISSIONS" => "N",
		"IBLOCK_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["news"],
		"DETAIL_URL" => $arResult["FOLDER"] . $arResult["URL_TEMPLATES"]["detail"],
		"ADD_ELEMENT_CHAIN" => $arParams["ADD_ELEMENT_CHAIN"],
		"INCLUDE_IBLOCK_INTO_CHAIN" => $arParams["INCLUDE_IBLOCK_INTO_CHAIN"],
		"DISPLAY_TOP_PAGER" => $arParams["DETAIL_DISPLAY_TOP_PAGER"],
		"DISPLAY_BOTTOM_PAGER" => $arParams["DETAIL_DISPLAY_BOTTOM_PAGER"],
		"PAGER_TITLE" => $arParams["DETAIL_PAGER_TITLE"],
		"PAGER_TEMPLATE" => $arParams["PAGER_TEMPLATE"],
		"SET_STATUS_404" => $arParams["SET_STATUS_404"],
		"SHOW_404" => $arParams["SHOW_404"],
		"FILE_404" => $arParams["FILE_404"],
		"MESSAGE_404" => $arParams["MESSAGE_404"],
		"STRICT_SECTION_CHECK" => $arParams["STRICT_SECTION_CHECK"],
	),
	$component
);
$detailContent = ob_get_clean();
?>

<div class="breadcrumbs-wrap">
	<div class="container">
		<? $APPLICATION->GetNavChain(
			false,
			0,
			SITE_TEMPLATE_PATH . "/components/bitrix/breadcrumb/szcube/template.php",
			true,
			false
		); ?>
	</div>
</div>

<?= $detailContent ?>

