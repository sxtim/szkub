<?php
define("PROJECTS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$projectsIblockType = "";
$projectsIblockCode = "projects";
$projectsIblockId = 0;
$projectAdvantagesIblockCode = "project_advantages";
$projectAdvantagesIblockType = "";
$projectAdvantagesIblockId = 0;
$projectConstructionIblockCode = "project_construction";
$projectConstructionIblockType = "";
$projectConstructionIblockId = 0;
$projectDocumentsIblockCode = "project_documents";
$projectDocumentsIblockType = "";
$projectDocumentsIblockId = 0;

if (!function_exists("szcubeProjectPageNormalizeFilterValues")) {
	function szcubeProjectPageNormalizeFilterValues($value)
	{
		if (is_string($value)) {
			$value = explode(",", $value);
		}

		if (!is_array($value)) {
			return array();
		}

		$result = array();
		foreach ($value as $item) {
			$item = trim((string)$item);
			if ($item !== "") {
				$result[] = $item;
			}
		}

		return array_values(array_unique($result));
	}
}

if (!function_exists("szcubeProjectPageNormalizeFilterNumber")) {
	function szcubeProjectPageNormalizeFilterNumber($value)
	{
		if ($value === null || $value === "") {
			return null;
		}

		$number = (float)$value;
		return is_finite($number) ? $number : null;
	}
}

if (!function_exists("szcubeProjectPageRequestValue")) {
	function szcubeProjectPageRequestValue(array $keys)
	{
		foreach ($keys as $key) {
			if (isset($_GET[$key])) {
				return $_GET[$key];
			}
		}

		return null;
	}
}

if (!function_exists("szcubeProjectPageReadApartmentFilter")) {
	function szcubeProjectPageReadApartmentFilter()
	{
		$legacy = isset($_GET["apartment_filter"]) ? trim((string)$_GET["apartment_filter"]) : "";
		if ($legacy !== "") {
			return $legacy;
		}

		$state = array(
			"projects" => szcubeProjectPageNormalizeFilterValues(szcubeProjectPageRequestValue(array("project", "projects"))),
			"rooms" => szcubeProjectPageNormalizeFilterValues(szcubeProjectPageRequestValue(array("rooms"))),
			"statuses" => szcubeProjectPageNormalizeFilterValues(szcubeProjectPageRequestValue(array("status", "statuses"))),
			"finishes" => szcubeProjectPageNormalizeFilterValues(szcubeProjectPageRequestValue(array("finish", "finishes"))),
			"features" => szcubeProjectPageNormalizeFilterValues(szcubeProjectPageRequestValue(array("feature", "features"))),
			"priceFrom" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("price_from", "priceFrom"))),
			"priceTo" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("price_to", "priceTo"))),
			"floorFrom" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("floor_from", "floorFrom"))),
			"floorTo" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("floor_to", "floorTo"))),
			"areaFrom" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("area_from", "areaFrom"))),
			"areaTo" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("area_to", "areaTo"))),
			"ceilingFrom" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("ceiling_from", "ceilingFrom"))),
			"ceilingTo" => szcubeProjectPageNormalizeFilterNumber(szcubeProjectPageRequestValue(array("ceiling_to", "ceilingTo"))),
		);

		foreach (array("projects", "rooms", "statuses", "finishes", "features") as $key) {
			if (!empty($state[$key])) {
				return $state;
			}
		}

		foreach (array("priceFrom", "priceTo", "floorFrom", "floorTo", "areaFrom", "areaTo", "ceilingFrom", "ceilingTo") as $key) {
			if ($state[$key] !== null) {
				return $state;
			}
		}

		return array();
	}
}

if (!function_exists("szcubeProjectPageReadSelectorContext")) {
	function szcubeProjectPageReadSelectorContext()
	{
		$view = isset($_GET["selector_view"]) ? trim((string)$_GET["selector_view"]) : "";
		$flatCode = isset($_GET["selector_flat"]) ? trim((string)$_GET["selector_flat"]) : "";
		$flatCode = preg_replace("/[^a-z0-9_-]/i", "", $flatCode);

		return array(
			"initial_view" => $view === "board" ? "board" : "",
			"flat_code" => $flatCode,
		);
	}
}

$code = isset($_REQUEST["code"]) ? trim((string)$_REQUEST["code"]) : "";
$code = preg_replace("/[^a-z0-9_-]/i", "", $code);
$apartmentFilterRaw = szcubeProjectPageReadApartmentFilter();
$selectorContext = szcubeProjectPageReadSelectorContext();
$project = null;

if ($code !== "" && class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
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

	$advantagesIblockRes = CIBlock::GetList(
		array(),
		array(
			"=CODE" => $projectAdvantagesIblockCode,
			"ACTIVE" => "Y",
		),
		false
	);
	if ($advantagesIblock = $advantagesIblockRes->Fetch()) {
		$projectAdvantagesIblockId = (int)$advantagesIblock["ID"];
		$projectAdvantagesIblockType = (string)$advantagesIblock["IBLOCK_TYPE_ID"];
	}

	$constructionIblockRes = CIBlock::GetList(
		array(),
		array(
			"=CODE" => $projectConstructionIblockCode,
			"ACTIVE" => "Y",
		),
		false
	);
	if ($constructionIblock = $constructionIblockRes->Fetch()) {
		$projectConstructionIblockId = (int)$constructionIblock["ID"];
		$projectConstructionIblockType = (string)$constructionIblock["IBLOCK_TYPE_ID"];
	}

	$documentsIblockRes = CIBlock::GetList(
		array(),
		array(
			"=CODE" => $projectDocumentsIblockCode,
			"ACTIVE" => "Y",
		),
		false
	);
	if ($documentsIblock = $documentsIblockRes->Fetch()) {
		$projectDocumentsIblockId = (int)$documentsIblock["ID"];
		$projectDocumentsIblockType = (string)$documentsIblock["IBLOCK_TYPE_ID"];
	}

	if ($projectsIblockId > 0) {
		$projectRes = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => $projectsIblockId,
				"=CODE" => $code,
				"ACTIVE" => "Y",
			),
			false,
			false,
			array(
				"ID",
				"IBLOCK_ID",
				"NAME",
				"CODE",
				"DETAIL_TEXT",
				"PREVIEW_PICTURE",
				"DETAIL_PICTURE",
			)
		);
		if ($projectElement = $projectRes->GetNextElement()) {
			$projectFields = $projectElement->GetFields();
			$projectProperties = $projectElement->GetProperties();
			$project = array(
				"id" => (int)$projectFields["ID"],
				"name" => (string)$projectFields["NAME"],
				"code" => (string)$projectFields["CODE"],
				"fields" => $projectFields,
				"properties" => $projectProperties,
			);
		}
	}
}

if (!$project) {
  CHTTP::SetStatus("404 Not Found");
  @define("ERROR_404", "Y");
  $APPLICATION->SetTitle("Проект не найден");
} else {
  $APPLICATION->SetTitle("ЖК «" . $project["name"] . "»");
  $APPLICATION->SetPageProperty("title", "ЖК «" . $project["name"] . "» — КУБ");
}

if (!function_exists("projectDetailPropertyScalar")) {
	function projectDetailPropertyScalar($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : "";
		if (is_array($value)) {
			$value = reset($value);
		}

		$value = trim((string)$value);
		return $value !== "" ? $value : (string)$default;
	}
}

if (!function_exists("projectDetailPropertyEnumXmlId")) {
	function projectDetailPropertyEnumXmlId($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE_XML_ID"]) ? $properties[$code]["VALUE_XML_ID"] : "";
		if (is_array($value)) {
			$value = reset($value);
		}

		$value = trim((string)$value);
		return $value !== "" ? $value : (string)$default;
	}
}

if (!function_exists("projectDetailPropertyFileUrl")) {
	function projectDetailPropertyFileUrl($properties, $code, $default = "")
	{
		if (!is_array($properties) || !isset($properties[$code])) {
			return (string)$default;
		}

		$value = isset($properties[$code]["VALUE"]) ? $properties[$code]["VALUE"] : 0;
		if (is_array($value)) {
			$value = reset($value);
		}

		$fileId = (int)$value;
		if ($fileId <= 0) {
			return (string)$default;
		}

		$filePath = CFile::GetPath($fileId);
		return $filePath ? (string)$filePath : (string)$default;
	}
}

if (!function_exists("projectDetailHasLinkedElements")) {
	function projectDetailHasLinkedElements($iblockId, $projectId)
	{
		$iblockId = (int)$iblockId;
		$projectId = (int)$projectId;
		if ($iblockId <= 0 || $projectId <= 0) {
			return false;
		}

		$elementRes = CIBlockElement::GetList(
			array("ID" => "ASC"),
			array(
				"IBLOCK_ID" => $iblockId,
				"ACTIVE" => "Y",
				"PROPERTY_PROJECT" => $projectId,
			),
			false,
			array("nTopCount" => 1),
			array("ID")
		);

		return (bool)$elementRes->Fetch();
	}
}

$projectDetail = array();
$projectStatusCode = "";
if ($project) {
	$projectProperties = isset($project["properties"]) && is_array($project["properties"]) ? $project["properties"] : array();
	$projectStatusCode = projectDetailPropertyEnumXmlId($projectProperties, "ABOUT_COMPANY_STATUS", "");

	$projectSeoDescriptions = array(
		"kollekciya" => "ЖК «Коллекция» — дом бизнес-класса в историческом центре Воронежа. Проект сочетает приватный формат, продуманные планировки и удобное расположение рядом с городской инфраструктурой. Выберите квартиру в новостройке от девелопера «КУБ» по приятной цене.",
	);

	$projectDescription = isset($projectSeoDescriptions[$project["code"]])
		? trim((string)$projectSeoDescriptions[$project["code"]])
		: projectDetailPropertyScalar($projectProperties, "ABOUT_TEXT_1", "");

	if ($projectDescription === "") {
		$projectDescription = "ЖК «" . $project["name"] . "» — проект девелопера «КУБ» в Воронеже. Планировки, сроки сдачи и условия покупки на официальном сайте.";
	}

	$APPLICATION->SetPageProperty("description", $projectDescription);

	$projectDetail["about"] = array(
		"image" => projectDetailPropertyFileUrl($projectProperties, "ABOUT_IMAGE", ""),
		"title_suffix" => projectDetailPropertyScalar($projectProperties, "ABOUT_TITLE_SUFFIX", ""),
		"text" => array_filter(array(
			projectDetailPropertyScalar($projectProperties, "ABOUT_TEXT_1", ""),
			projectDetailPropertyScalar($projectProperties, "ABOUT_TEXT_2", ""),
			projectDetailPropertyScalar($projectProperties, "ABOUT_TEXT_3", ""),
		), static function ($item) {
			return trim((string)$item) !== "";
		}),
		"features" => array(),
	);

	for ($featureIndex = 1; $featureIndex <= 4; $featureIndex++) {
		$featureLabel = projectDetailPropertyScalar($projectProperties, "ABOUT_F" . $featureIndex . "_LABEL", "");
		$featureValue = projectDetailPropertyScalar($projectProperties, "ABOUT_F" . $featureIndex . "_VALUE", "");
		if ($featureLabel === "" && $featureValue === "") {
			continue;
		}

		$projectDetail["about"]["features"][] = array(
			"label" => $featureLabel,
			"value" => $featureValue,
		);
	}

	$projectDetail["extra"] = szcubeGetExtraCards("project", isset($project["code"]) ? $project["code"] : "");
	$projectDetail["has_advantages"] = projectDetailHasLinkedElements($projectAdvantagesIblockId, $project["id"]);
	$projectDetail["has_construction"] = projectDetailHasLinkedElements($projectConstructionIblockId, $project["id"]);
	$projectDetail["show_purchase"] = $projectStatusCode !== "completed";

	$projectDetail["construction_subtitle"] = projectDetailPropertyScalar($projectProperties, "CONSTRUCTION_SUBTITLE", "Сдача в IV кв. 2026");

	$selectorSceneConfig = function_exists("szcubeGetProjectSelectorSceneConfig")
		? szcubeGetProjectSelectorSceneConfig($project["code"])
		: array();
	$projectSelectorMapEmbedHtml = function_exists("szcubeGetProjectMapEmbedHtml")
		? trim((string)szcubeGetProjectMapEmbedHtml($project["code"]))
		: "";
	$projectDetail["selector"] = array(
		"enabled" => !empty($selectorSceneConfig),
		"scene_mode" => isset($selectorSceneConfig["scene_mode"]) ? (string)$selectorSceneConfig["scene_mode"] : "single_building",
		"data_project_code" => isset($selectorSceneConfig["data_project_code"]) && trim((string)$selectorSceneConfig["data_project_code"]) !== "" ? (string)$selectorSceneConfig["data_project_code"] : $project["code"],
		"scene_image" => isset($selectorSceneConfig["scene_image"]) && trim((string)$selectorSceneConfig["scene_image"]) !== "" ? (string)$selectorSceneConfig["scene_image"] : SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg",
		"scene_svg_path" => isset($selectorSceneConfig["scene_svg_path"]) && trim((string)$selectorSceneConfig["scene_svg_path"]) !== "" ? (string)$selectorSceneConfig["scene_svg_path"] : SITE_TEMPLATE_PATH . "/img/projects/Group.svg",
		"map_embed_html" => $projectSelectorMapEmbedHtml,
		"scene_config" => $selectorSceneConfig,
	);
}
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<?php if (!$project): ?>
  <section class="projects-page">
    <div class="container">
      <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>
      <p>Проект не найден.</p>
    </div>
  </section>
<?php else: ?>

<section class="projects-page">
  <div class="container">
    <?php
    $activeProjectId = isset($project["id"]) ? (int)$project["id"] : 0;

    global $arrProjectAdvantagesFilter, $arrProjectConstructionFilter;
    $arrProjectAdvantagesFilter = array();
    $arrProjectConstructionFilter = array();
    $arrProjectDocumentsFilter = array();
    if ($activeProjectId > 0) {
      $arrProjectAdvantagesFilter["PROPERTY_PROJECT"] = $activeProjectId;
      $arrProjectConstructionFilter["PROPERTY_PROJECT"] = $activeProjectId;
      $arrProjectDocumentsFilter["PROPERTY_PROJECT"] = $activeProjectId;
    }
    ?>

    <div class="projects-about">
      <div class="projects-about__grid">
        <?php if ($projectDetail["about"]["image"] !== ""): ?>
          <div class="projects-about__media">
            <button
              class="projects-about__zoom"
              type="button"
              data-project-about-zoom
              aria-label="Увеличить изображение проекта"
            >
              <img
                src="<?= htmlspecialcharsbx($projectDetail["about"]["image"]) ?>"
                alt="ЖК «<?= htmlspecialcharsbx($project["name"]) ?>»"
                loading="lazy"
              />
            </button>
          </div>

          <div class="projects-about__lightbox" data-project-about-lightbox hidden>
            <button
              class="projects-about__lightbox-backdrop"
              type="button"
              data-project-about-lightbox-close
              aria-label="Закрыть просмотр"
            ></button>
            <div
              class="projects-about__lightbox-dialog"
              role="dialog"
              aria-modal="true"
              aria-label="Просмотр изображения проекта"
            >
              <figure class="projects-about__lightbox-figure">
                <div class="projects-about__lightbox-media">
                  <button
                    class="projects-about__lightbox-close"
                    type="button"
                    data-project-about-lightbox-close
                    aria-label="Закрыть"
                  >
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M5 5L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                      <path d="M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                  </button>
                  <img
                    class="projects-about__lightbox-image"
                    data-project-about-lightbox-image
                    src=""
                    alt=""
                  />
                </div>
                <figcaption
                  class="projects-about__lightbox-caption"
                  data-project-about-lightbox-caption
                  hidden
                ></figcaption>
              </figure>
            </div>
          </div>
        <?php endif; ?>

        <h1 class="projects-about__title">
          <span class="projects-about__title-accent">ЖК «<?= htmlspecialcharsbx($project["name"]) ?>»</span><?php if ($projectDetail["about"]["title_suffix"] !== ""): ?>
            <?= htmlspecialcharsbx($projectDetail["about"]["title_suffix"]) ?>
          <?php endif; ?>
        </h1>

        <div class="projects-about__content">
          <?php if (!empty($projectDetail["about"]["text"])): ?>
            <div class="projects-about__text">
              <?php foreach ($projectDetail["about"]["text"] as $aboutText): ?>
                <p><?= nl2br(htmlspecialcharsbx($aboutText)) ?></p>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($projectDetail["about"]["features"])): ?>
            <ul class="projects-about__features">
              <?php foreach ($projectDetail["about"]["features"] as $feature): ?>
                <li class="projects-about__feature">
                  <?php if ($feature["label"] !== ""): ?>
                    <div class="projects-about__feature-label"><?= htmlspecialcharsbx($feature["label"]) ?></div>
                  <?php endif; ?>
                  <?php if ($feature["value"] !== ""): ?>
                    <div class="projects-about__feature-value"><?= htmlspecialcharsbx($feature["value"]) ?></div>
                  <?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php
    if (!empty($projectDetail["selector"]["enabled"])) {
      $APPLICATION->IncludeComponent(
        "szcube:project.apartment.selector",
        ".default",
        array(
          "PROJECT_ID" => $project["id"],
          "PROJECT_CODE" => $project["code"],
          "DATA_PROJECT_CODE" => $projectDetail["selector"]["data_project_code"],
          "PROJECT_NAME" => $project["name"],
          "SCENE_MODE" => $projectDetail["selector"]["scene_mode"],
          "SCENE_IMAGE" => $projectDetail["selector"]["scene_image"],
          "SCENE_SVG_PATH" => $projectDetail["selector"]["scene_svg_path"],
          "MAP_EMBED_HTML" => $projectDetail["selector"]["map_embed_html"],
          "SCENE_CONFIG" => $projectDetail["selector"]["scene_config"],
          "APARTMENT_FILTER" => $apartmentFilterRaw,
          "INITIAL_VIEW" => isset($selectorContext["initial_view"]) ? $selectorContext["initial_view"] : "",
          "TARGET_FLAT_CODE" => isset($selectorContext["flat_code"]) ? $selectorContext["flat_code"] : "",
          "CONSTRUCTION_SUBTITLE" => $projectDetail["construction_subtitle"],
          "CACHE_TIME" => "36000000",
        ),
        false
      );
    }
    ?>

    <?php if (!empty($projectDetail["has_advantages"])): ?>
      <section class="projects-benefits" aria-label="Преимущества проекта">
        <h2 class="projects-benefits__title">Преимущества</h2>
        <?php
        $APPLICATION->IncludeComponent(
          "bitrix:news.list",
          "project_advantages",
          array(
            "IBLOCK_TYPE" => $projectAdvantagesIblockType !== "" ? $projectAdvantagesIblockType : $projectsIblockType,
            "IBLOCK_ID" => $projectAdvantagesIblockId,
            "NEWS_COUNT" => "200",
            "SORT_BY1" => "SORT",
            "SORT_ORDER1" => "ASC",
            "SORT_BY2" => "ID",
            "SORT_ORDER2" => "ASC",
            "FILTER_NAME" => "arrProjectAdvantagesFilter",
            "FIELD_CODE" => array(
              0 => "NAME",
              1 => "PREVIEW_TEXT",
              2 => "PREVIEW_PICTURE",
              3 => "",
            ),
            "PROPERTY_CODE" => array(
              0 => "PROJECT",
              1 => "LABEL",
              2 => "CATEGORY",
              3 => "",
            ),
            "CHECK_DATES" => "N",
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
            "DISPLAY_DATE" => "N",
            "DISPLAY_NAME" => "Y",
            "DISPLAY_PICTURE" => "Y",
            "DISPLAY_PREVIEW_TEXT" => "Y",
            "PARENT_SECTION" => "",
            "PARENT_SECTION_CODE" => "",
            "STRICT_SECTION_CHECK" => "N",
            "DISPLAY_TOP_PAGER" => "N",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "PAGER_SHOW_ALWAYS" => "N",
            "PAGER_TEMPLATE" => "",
          ),
          false
        );
        ?>
      </section>
    <?php endif; ?>
  </div>
</section>

<?php if (!empty($projectDetail["extra"])): ?>
  <section class="extra" id="apartments">
    <div class="container">
      <h2 class="section-title">Кроме квартир</h2>
      <div class="extra__cards">
        <?php foreach ($projectDetail["extra"] as $extraItem): ?>
          <?php if ($extraItem["url"] !== ""): ?>
            <a class="extra-card" href="<?= htmlspecialcharsbx($extraItem["url"]) ?>">
              <img
                src="<?= htmlspecialcharsbx($extraItem["image"]) ?>"
                alt="<?= htmlspecialcharsbx($extraItem["title"]) ?>"
              />
              <h3 class="extra-card__title"><?= htmlspecialcharsbx($extraItem["title"]) ?></h3>
              <div class="extra-card__overlay">
                <div class="extra-card__link">
                  <img
                    src="<?=SITE_TEMPLATE_PATH?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg"
                    alt=""
                  />
                </div>
              </div>
            </a>
          <?php else: ?>
            <article class="extra-card">
              <img
                src="<?= htmlspecialcharsbx($extraItem["image"]) ?>"
                alt="<?= htmlspecialcharsbx($extraItem["title"]) ?>"
              />
              <h3 class="extra-card__title"><?= htmlspecialcharsbx($extraItem["title"]) ?></h3>
              <div class="extra-card__overlay">
                <div class="extra-card__link">
                  <img
                    src="<?=SITE_TEMPLATE_PATH?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg"
                    alt=""
                  />
                </div>
              </div>
            </article>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php
$homePromotionsIblockType = "content";
$homePromotionsIblockCode = "promotions";
$homePromotionsIblockId = 0;
if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
  $iblockRes = CIBlock::GetList(
    array(),
    array(
      "TYPE" => $homePromotionsIblockType,
      "=CODE" => $homePromotionsIblockCode,
      "ACTIVE" => "Y",
    ),
    false
  );
  if ($iblock = $iblockRes->Fetch()) {
    $homePromotionsIblockId = (int)$iblock["ID"];
  }
}

$activeProjectCode = isset($project["code"]) ? trim((string)$project["code"]) : "";
$activeProjectCode = preg_replace("/[^a-z0-9_-]/i", "", $activeProjectCode);

global $arrProjectPromotionsFilter;
$arrProjectPromotionsFilter = array();
if ($activeProjectCode !== "") {
	$arrProjectPromotionsFilter["PROPERTY_ZHK_CODE"] = $activeProjectCode;
}
?>
<?php if ($homePromotionsIblockId > 0): ?>
  <?php
  $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "home_promotions",
    array(
      "IBLOCK_TYPE" => $homePromotionsIblockType,
      "IBLOCK_ID" => $homePromotionsIblockId,
      "NEWS_COUNT" => "3",
      "SORT_BY1" => "ACTIVE_FROM",
      "SORT_ORDER1" => "DESC",
      "SORT_BY2" => "SORT",
      "SORT_ORDER2" => "ASC",
      "FILTER_NAME" => "arrProjectPromotionsFilter",
      "FIELD_CODE" => array(
        0 => "NAME",
        1 => "PREVIEW_PICTURE",
        2 => "DATE_ACTIVE_TO",
        3 => "",
      ),
      "PROPERTY_CODE" => array(
        0 => "ZHK_CODE",
        1 => "ZHK_LABEL",
        2 => "",
      ),
      "CHECK_DATES" => "Y",
      "DETAIL_URL" => "/promotions/#ELEMENT_CODE#/",
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
      "DISPLAY_DATE" => "N",
      "DISPLAY_NAME" => "Y",
      "DISPLAY_PICTURE" => "Y",
      "DISPLAY_PREVIEW_TEXT" => "N",
      "PARENT_SECTION" => "",
      "PARENT_SECTION_CODE" => "",
      "STRICT_SECTION_CHECK" => "N",
      "DISPLAY_TOP_PAGER" => "N",
      "DISPLAY_BOTTOM_PAGER" => "N",
      "PAGER_SHOW_ALWAYS" => "N",
      "PAGER_TEMPLATE" => "",
    ),
    false
  );
  ?>
<?php endif; ?>

<?php if (!empty($projectDetail["show_purchase"])): ?>
  <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/purchase.php"; ?>
<?php endif; ?>

<?php if (!empty($projectDetail["has_construction"])): ?>
  <section class="construction" id="construction" aria-label="Ход строительства">
    <div class="container">
      <header class="construction__header">
        <h2 class="construction__title">Ход строительства</h2>
        <p class="construction__subtitle"><?= htmlspecialcharsbx($projectDetail["construction_subtitle"]) ?></p>
      </header>
      <?php
      $APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "project_construction",
        array(
          "IBLOCK_TYPE" => $projectConstructionIblockType !== "" ? $projectConstructionIblockType : $projectsIblockType,
          "IBLOCK_ID" => $projectConstructionIblockId,
          "NEWS_COUNT" => "200",
          "SORT_BY1" => "SORT",
          "SORT_ORDER1" => "ASC",
          "SORT_BY2" => "ID",
          "SORT_ORDER2" => "ASC",
          "FILTER_NAME" => "arrProjectConstructionFilter",
          "FIELD_CODE" => array(
            0 => "NAME",
            1 => "PREVIEW_TEXT",
            2 => "PREVIEW_PICTURE",
            3 => "ACTIVE_FROM",
            4 => "",
          ),
          "PROPERTY_CODE" => array(
            0 => "PROJECT",
            1 => "DATE_TEXT",
            2 => "GALLERY",
            3 => "",
          ),
          "CHECK_DATES" => "N",
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
          "DISPLAY_DATE" => "N",
          "DISPLAY_NAME" => "Y",
          "DISPLAY_PICTURE" => "Y",
          "DISPLAY_PREVIEW_TEXT" => "Y",
          "PARENT_SECTION" => "",
          "PARENT_SECTION_CODE" => "",
          "STRICT_SECTION_CHECK" => "N",
          "DISPLAY_TOP_PAGER" => "N",
          "DISPLAY_BOTTOM_PAGER" => "N",
          "PAGER_SHOW_ALWAYS" => "N",
          "PAGER_TEMPLATE" => "",
        ),
        false
      );
      ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($projectDocumentsIblockId > 0): ?>
  <?php
  $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "project_documents",
    array(
      "IBLOCK_TYPE" => $projectDocumentsIblockType !== "" ? $projectDocumentsIblockType : $projectsIblockType,
      "IBLOCK_ID" => $projectDocumentsIblockId,
      "NEWS_COUNT" => "200",
      "SORT_BY1" => "SORT",
      "SORT_ORDER1" => "ASC",
      "SORT_BY2" => "ID",
      "SORT_ORDER2" => "ASC",
      "FILTER_NAME" => "arrProjectDocumentsFilter",
      "FIELD_CODE" => array(
        0 => "NAME",
        1 => "PREVIEW_TEXT",
        2 => "",
      ),
      "PROPERTY_CODE" => array(
        0 => "PROJECT",
        1 => "FILE",
        2 => "LINK_URL",
        3 => "LINK_TARGET",
        4 => "",
      ),
      "CHECK_DATES" => "N",
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
      "DISPLAY_DATE" => "N",
      "DISPLAY_NAME" => "Y",
      "DISPLAY_PICTURE" => "N",
      "DISPLAY_PREVIEW_TEXT" => "Y",
      "PARENT_SECTION" => "",
      "PARENT_SECTION_CODE" => "",
      "STRICT_SECTION_CHECK" => "N",
      "DISPLAY_TOP_PAGER" => "N",
      "DISPLAY_BOTTOM_PAGER" => "N",
      "PAGER_SHOW_ALWAYS" => "N",
      "PAGER_TEMPLATE" => "",
    ),
    false
  );
  ?>
<?php endif; ?>

<section class="projects-call" aria-label="Связаться">
  <div class="container">
    <div class="projects-call__panel">
      <div class="projects-call__col">
        <button
          class="projects-call__tile projects-call__tile--dark"
          type="button"
          data-contact-open="contact"
          data-contact-title="Заказать обратный звонок"
          data-contact-type="callback"
          data-contact-source="projects_call"
        >
          <div class="projects-call__text">
            <div class="projects-call__title">Получите консультацию</div>
            <div class="projects-call__subtitle">Заказать обратный звонок</div>
          </div>
          <div class="projects-call__btn projects-call__btn--white" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8.9 11.1c1.3 2.5 3.5 4.7 6 6l2-2c.3-.3.8-.4 1.2-.2 1 .4 2.1.7 3.2.8.5.1.8.5.8 1v3.2c0 .5-.4 1-.9 1-10.1 0-18.3-8.2-18.3-18.3 0-.5.4-.9 1-.9H7c.5 0 .9.3 1 .8.1 1.1.4 2.2.8 3.2.2.4.1.9-.2 1.2l-2 2z" fill="currentColor"/>
            </svg>
          </div>
        </button>
      </div>

      <div class="projects-call__col">
        <div class="projects-call__grid projects-call__grid--single">
          <div class="projects-call__tile projects-call__tile--light projects-call__tile--gray">
            <div class="projects-call__text">
              <div class="projects-call__title">Напишите нам</div>
              <div class="projects-call__subtitle">в MAX</div>
            </div>
            <div class="projects-call__btn projects-call__btn--white" aria-hidden="true">
              <svg class="projects-call__icon projects-call__icon--max" viewBox="0 0 720 720" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M350.4 9.6C141.8 20.5 4.1 184.1 12.8 390.4c3.8 90.3 40.1 168 48.7 253.7 2.2 22.2-4.2 49.6 21.4 59.3 31.5 11.9 79.8-8.1 106.2-26.4 9-6.1 17.6-13.2 24.2-22 27.3 18.1 53.2 35.6 85.7 43.4 143.1 34.3 299.9-44.2 369.6-170.3C799.6 291.2 622.5-4.6 350.4 9.6ZM269.4 504c-11.3 8.8-22.2 20.8-34.7 27.7-18.1 9.7-23.7-.4-30.5-16.4-21.4-50.9-24-137.6-11.5-190.9 16.8-72.5 72.9-136.3 150-143.1 78-6.9 150.4 32.7 183.1 104.2 72.4 159.1-112.9 316.2-256.4 218.6Z" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="construction-modal-wrap" data-construction-modal hidden>
  <div class="construction-modal" role="dialog" aria-modal="true" aria-label="Ход строительства">
    <button class="construction-modal__close" type="button" aria-label="Закрыть" data-construction-modal-close>
      <svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M1 1L9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path>
        <path d="M9 1L1 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path>
      </svg>
    </button>

	    <div class="construction-modal__left">
	      <div class="construction-modal__heading">
	        <h4 class="construction-modal__title">
	          Ход строительства<br />
	          <span class="construction-modal__title-muted">ЖК «<?= htmlspecialcharsbx($project["name"]) ?>»</span>
	        </h4>

	        <p class="construction-modal__date" data-construction-modal-date></p>
	      </div>

	      <div class="construction-modal__text" data-construction-modal-text></div>
	    </div>

	    <div class="construction-modal__right">
	      <div class="construction-modal__swiper swiper" data-construction-modal-swiper>
	        <div class="swiper-wrapper" data-construction-modal-wrapper></div>

        <div class="construction-modal__controls" aria-label="Управление галереей">
          <div class="construction-modal__nav" role="group" aria-label="Переключение фотографий">
            <button class="construction-modal__navBtn" type="button" aria-label="Предыдущее фото" data-construction-modal-prev>
              <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4.00049 6.00024L7.00049 3.00024V9.00024L4.00049 6.00024Z" fill="currentColor"></path>
              </svg>
            </button>
            <span class="construction-modal__navSep" aria-hidden="true"></span>
            <button class="construction-modal__navBtn" type="button" aria-label="Следующее фото" data-construction-modal-next>
              <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
              </svg>
            </button>
          </div>

          <div class="construction-modal__pagination" aria-label="Счётчик фотографий">
            <span class="construction-modal__pagination-num" data-construction-modal-current>1</span>
            <span class="construction-modal__pagination-sep" aria-hidden="true"></span>
            <span class="construction-modal__pagination-num" data-construction-modal-total>1</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/project-benefit-modal.php"; ?>

<?php endif; ?>
<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
