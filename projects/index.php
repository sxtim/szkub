<?php
define("PROJECTS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проекты");

$projectsIblockType = "";
$projectsIblockCode = "projects";
$projectsIblockId = 0;
$projectsStatusPropertyCode = "ABOUT_COMPANY_STATUS";
$projectsFilterRequestKey = "projects_filter";
$projectsFilterParamName = "status";
$projectsStatusDefaults = array(
	"building" => array(
		"label" => "В продаже",
		"checked" => true,
		"sort" => 100,
	),
	"planned" => array(
		"label" => "Скоро в продаже",
		"checked" => true,
		"sort" => 200,
	),
	"completed" => array(
		"label" => "Реализован",
		"checked" => false,
		"sort" => 300,
	),
);

if (!function_exists("projectsCatalogNormalizeStatusCodes")) {
	function projectsCatalogNormalizeStatusCodes($value)
	{
		if (!is_array($value)) {
			$value = $value === null || $value === "" ? array() : array($value);
		}

		$result = array();
		foreach ($value as $item) {
			$item = trim(mb_strtolower((string)$item));
			$item = preg_replace("/[^a-z0-9_-]+/i", "", $item);
			if ($item !== "") {
				$result[] = $item;
			}
		}

		return array_values(array_unique($result));
	}
}

if (!function_exists("projectsCatalogPropertyEnumXmlId")) {
	function projectsCatalogPropertyEnumXmlId(array $properties, $code)
	{
		if (!isset($properties[$code]["VALUE_XML_ID"])) {
			return "";
		}

		$value = $properties[$code]["VALUE_XML_ID"];
		if (is_array($value)) {
			$value = reset($value);
		}

		return trim((string)$value);
	}
}

$projectsStatusOptions = array();
foreach ($projectsStatusDefaults as $statusCode => $statusMeta) {
	$projectsStatusOptions[$statusCode] = array(
		"code" => $statusCode,
		"label" => isset($statusMeta["label"]) ? (string)$statusMeta["label"] : $statusCode,
		"checked" => !empty($statusMeta["checked"]),
		"sort" => isset($statusMeta["sort"]) ? (int)$statusMeta["sort"] : 500,
		"enum_id" => 0,
		"count" => 0,
	);
}

$projectsFilterSubmitted = isset($_GET[$projectsFilterRequestKey]) && trim((string)$_GET[$projectsFilterRequestKey]) === "Y";
$projectsSelectedStatusCodes = array();
$projectsSelectedStatusEnumIds = array();
$projectsIntroHtml = "";
$projectsMapEmbedHtml = function_exists("szcubeGetPageMapEmbedHtml")
	? (string)szcubeGetPageMapEmbedHtml("projects")
	: "";

if (function_exists("szcubeGetSingletonElementPropertiesByCode") && function_exists("szcubeExtractHtmlPropertyText")) {
	$projectsCatalogPageProperties = szcubeGetSingletonElementPropertiesByCode("projects_catalog_page", "catalog");
	if (!empty($projectsCatalogPageProperties) && isset($projectsCatalogPageProperties["INTRO_TEXT"])) {
		$projectsIntroHtml = trim((string)szcubeExtractHtmlPropertyText($projectsCatalogPageProperties["INTRO_TEXT"]));
	}
}

if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
	$iblockRes = CIBlock::GetList(
		array(),
		array(
			"=CODE" => $projectsIblockCode,
			"ACTIVE" => "Y",
		),
		false
	);
	if ($iblock = $iblockRes->Fetch()) {
		$projectsIblockId = (int)$iblock["ID"];
		$projectsIblockType = (string)$iblock["IBLOCK_TYPE_ID"];
	}

	if ($projectsIblockId > 0) {
		$enumRes = CIBlockPropertyEnum::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			array(
				"IBLOCK_ID" => $projectsIblockId,
				"CODE" => $projectsStatusPropertyCode,
			)
		);
		while ($enum = $enumRes->Fetch()) {
			$xmlId = trim((string)$enum["XML_ID"]);
			if ($xmlId === "") {
				continue;
			}

			if (!isset($projectsStatusOptions[$xmlId])) {
				$projectsStatusOptions[$xmlId] = array(
					"code" => $xmlId,
					"label" => trim((string)$enum["VALUE"]),
					"checked" => $xmlId !== "completed",
					"sort" => (int)$enum["SORT"],
					"enum_id" => 0,
					"count" => 0,
				);
			}

			if (trim((string)$enum["VALUE"]) !== "") {
				$projectsStatusOptions[$xmlId]["label"] = trim((string)$enum["VALUE"]);
			}
			$projectsStatusOptions[$xmlId]["enum_id"] = (int)$enum["ID"];
		}

		uasort($projectsStatusOptions, static function ($left, $right) {
			$leftSort = isset($left["sort"]) ? (int)$left["sort"] : 500;
			$rightSort = isset($right["sort"]) ? (int)$right["sort"] : 500;
			if ($leftSort !== $rightSort) {
				return $leftSort <=> $rightSort;
			}

			return strcmp((string)$left["code"], (string)$right["code"]);
		});

		$projectStatsRes = CIBlockElement::GetList(
			array("SORT" => "ASC", "ID" => "ASC"),
			array(
				"IBLOCK_ID" => $projectsIblockId,
				"ACTIVE" => "Y",
			),
			false,
			false,
			array("ID", "IBLOCK_ID")
		);
		while ($projectStatsElement = $projectStatsRes->GetNextElement()) {
			$projectProperties = $projectStatsElement->GetProperties();
			$statusCode = projectsCatalogPropertyEnumXmlId($projectProperties, $projectsStatusPropertyCode);

			if ($statusCode !== "" && isset($projectsStatusOptions[$statusCode])) {
				$projectsStatusOptions[$statusCode]["count"]++;
			}
		}
	}
}

$projectsDefaultStatusCodes = array_values(array_map(
	static function ($option) {
		return isset($option["code"]) ? (string)$option["code"] : "";
	},
	array_filter($projectsStatusOptions, static function ($option) {
		return !empty($option["checked"]);
	})
));

if ($projectsFilterSubmitted) {
	$projectsSelectedStatusCodes = projectsCatalogNormalizeStatusCodes(
		isset($_GET[$projectsFilterParamName]) ? $_GET[$projectsFilterParamName] : array()
	);
	$projectsSelectedStatusCodes = array_values(array_intersect(
		$projectsSelectedStatusCodes,
		array_keys($projectsStatusOptions)
	));
} else {
	$projectsSelectedStatusCodes = $projectsDefaultStatusCodes;
}

foreach ($projectsSelectedStatusCodes as $statusCode) {
	$enumId = isset($projectsStatusOptions[$statusCode]["enum_id"]) ? (int)$projectsStatusOptions[$statusCode]["enum_id"] : 0;
	if ($enumId <= 0 && function_exists("szcubeGetPropertyEnumIdByXmlId")) {
		$enumId = (int)szcubeGetPropertyEnumIdByXmlId($projectsIblockId, $projectsStatusPropertyCode, $statusCode);
	}
	if ($enumId > 0) {
		$projectsSelectedStatusEnumIds[] = $enumId;
	}
}
$projectsSelectedStatusEnumIds = array_values(array_unique($projectsSelectedStatusEnumIds));

global $arrProjectsCatalogFilter;
$arrProjectsCatalogFilter = array();
if ($projectsFilterSubmitted && empty($projectsSelectedStatusCodes)) {
	$arrProjectsCatalogFilter["ID"] = array(-1);
} elseif (!empty($projectsSelectedStatusEnumIds)) {
	$arrProjectsCatalogFilter["PROPERTY_" . $projectsStatusPropertyCode] = $projectsSelectedStatusEnumIds;
} elseif (!empty($projectsSelectedStatusCodes)) {
	$projectsSelectedStatusValues = array();
	foreach ($projectsSelectedStatusCodes as $statusCode) {
		if (isset($projectsStatusOptions[$statusCode]["label"])) {
			$projectsSelectedStatusValues[] = trim((string)$projectsStatusOptions[$statusCode]["label"]);
		}
	}
	$projectsSelectedStatusValues = array_values(array_unique(array_filter($projectsSelectedStatusValues)));
	if (!empty($projectsSelectedStatusValues)) {
		$arrProjectsCatalogFilter["PROPERTY_" . $projectsStatusPropertyCode . "_VALUE"] = $projectsSelectedStatusValues;
	}
}
$projectsActiveView = isset($_GET["view"]) && trim((string)$_GET["view"]) === "map" ? "map" : "list";
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="projects">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
    <?php if ($projectsIntroHtml !== ""): ?>
      <div class="projects-intro"><?= htmlspecialcharsback($projectsIntroHtml) ?></div>
    <?php endif; ?>

    <?php if ($projectsIblockId > 0): ?>
      <div class="projects-catalog">
        <div class="projects-catalog-toolbar<?= $projectsActiveView === "map" ? " is-map-view" : "" ?>">
          <section class="projects-catalog-filter" aria-label="Фильтр проектов">
            <form class="filters projects-catalog-filters" method="get" action="/projects/" data-projects-status-filter>
              <input type="hidden" name="<?= htmlspecialcharsbx($projectsFilterRequestKey) ?>" value="Y" />
              <input type="hidden" name="view" value="<?= htmlspecialcharsbx($projectsActiveView) ?>" data-projects-view-input />

              <div class="filters__controls">
                <div class="filter filter--project-status">
                  <span class="filter__label">Статус проекта</span>
                  <div class="filter__checkboxes">
                    <?php foreach ($projectsStatusOptions as $statusOption): ?>
                      <?php
                      $statusCode = isset($statusOption["code"]) ? (string)$statusOption["code"] : "";
                      if ($statusCode === "") {
                        continue;
                      }
                      $isChecked = in_array($statusCode, $projectsSelectedStatusCodes, true);
                      ?>
                      <label class="filter__checkbox projects-catalog-filters__checkbox">
                        <input
                          class="custom-checkbox"
                          type="checkbox"
                          name="<?= htmlspecialcharsbx($projectsFilterParamName) ?>[]"
                          value="<?= htmlspecialcharsbx($statusCode) ?>"
                          <?= $isChecked ? "checked" : "" ?>
                        />
                        <span class="projects-catalog-filters__checkbox-text"><?= htmlspecialcharsbx((string)$statusOption["label"]) ?></span>
                        <span class="projects-catalog-filters__checkbox-count"><?= (int)$statusOption["count"] ?></span>
                      </label>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </form>
          </section>

          <div class="projects-view-switch" role="tablist" aria-label="Вид отображения проектов" data-projects-view-root>
            <button
              class="projects-view-switch__button<?= $projectsActiveView === "list" ? " is-active" : "" ?>"
              type="button"
              role="tab"
              id="projects-view-tab-list"
              aria-controls="projects-view-panel-list"
              aria-selected="<?= $projectsActiveView === "list" ? "true" : "false" ?>"
              data-projects-view-tab="list"
            >Списком</button>
            <button
              class="projects-view-switch__button<?= $projectsActiveView === "map" ? " is-active" : "" ?>"
              type="button"
              role="tab"
              id="projects-view-tab-map"
              aria-controls="projects-view-panel-map"
              aria-selected="<?= $projectsActiveView === "map" ? "true" : "false" ?>"
              aria-disabled="true"
              disabled
              data-projects-view-tab="map"
            >На карте</button>
          </div>
        </div>

        <div class="projects-view">
          <div
            class="projects-view__panel<?= $projectsActiveView === "list" ? " is-active" : "" ?>"
            id="projects-view-panel-list"
            role="tabpanel"
            aria-labelledby="projects-view-tab-list"
            data-projects-view-panel="list"
            <?= $projectsActiveView === "list" ? "" : "hidden" ?>
          >
            <?php
            $APPLICATION->IncludeComponent(
              "bitrix:news.list",
              "projects_list",
              array(
                "IBLOCK_TYPE" => $projectsIblockType,
                "IBLOCK_ID" => $projectsIblockId,
                "FILTER_NAME" => "arrProjectsCatalogFilter",
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
                  7 => "ABOUT_COMPANY_STATUS",
                  8 => "",
                ),
                "CHECK_DATES" => "N",
                "DETAIL_URL" => "/projects/#ELEMENT_CODE#/",
                "ACTIVE_DATE_FORMAT" => "d.m.Y",
                "CACHE_TYPE" => "A",
                "CACHE_TIME" => "36000000",
                "CACHE_FILTER" => "Y",
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
          </div>

          <div
            class="projects-view__panel<?= $projectsActiveView === "map" ? " is-active" : "" ?>"
            id="projects-view-panel-map"
            role="tabpanel"
            aria-labelledby="projects-view-tab-map"
            data-projects-view-panel="map"
            <?= $projectsActiveView === "map" ? "" : "hidden" ?>
          >
            <?php
            $mapEmbedHtml = $projectsMapEmbedHtml;
            $mapClass = "projects-map__frame szcube-map";
            $mapPlaceholderTitle = "Здесь будет карта проектов";
            $mapPlaceholderText = "Добавьте код карты в админке элемента «Проекты» инфоблока «Карты: страницы».";
            include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/map-embed.php";
            ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <p>Раздел проектов подготовлен для вывода через инфоблок Bitrix.</p>
      <p><small>Ожидаемый инфоблок: TYPE=`<?= htmlspecialcharsbx($projectsIblockType) ?>`, CODE=`<?= htmlspecialcharsbx($projectsIblockCode) ?>`.</small></p>
    <?php endif; ?>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
