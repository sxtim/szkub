<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$projects = isset($arResult["PROJECTS"]) && is_array($arResult["PROJECTS"]) ? $arResult["PROJECTS"] : array();
$rooms = isset($arResult["ROOMS"]) && is_array($arResult["ROOMS"]) ? $arResult["ROOMS"] : array();
$statuses = isset($arResult["STATUSES"]) && is_array($arResult["STATUSES"]) ? $arResult["STATUSES"] : array();
$finishes = isset($arResult["FINISHES"]) && is_array($arResult["FINISHES"]) ? $arResult["FINISHES"] : array();
$featureTags = isset($arResult["FEATURE_TAGS"]) && is_array($arResult["FEATURE_TAGS"]) ? $arResult["FEATURE_TAGS"] : array();
$ranges = isset($arResult["RANGES"]) && is_array($arResult["RANGES"]) ? $arResult["RANGES"] : array();
$flats = isset($arResult["FLATS"]) && is_array($arResult["FLATS"]) ? $arResult["FLATS"] : array();
$count = isset($arResult["COUNT"]) ? (int)$arResult["COUNT"] : count($flats);

$pluralize = static function ($value) {
    $value = abs((int)$value);
    $mod10 = $value % 10;
    $mod100 = $value % 100;

    if ($mod10 === 1 && $mod100 !== 11) {
        return "квартира";
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return "квартиры";
    }

    return "квартир";
};

$payload = array(
    "projects" => $projects,
    "flats" => $flats,
    "ranges" => $ranges,
    "count" => $count,
    "projects_page_url" => isset($arResult["PROJECTS_PAGE_URL"]) ? (string)$arResult["PROJECTS_PAGE_URL"] : "/projects/",
    "catalog_page_url" => isset($arResult["CATALOG_PAGE_URL"]) ? (string)$arResult["CATALOG_PAGE_URL"] : "/apartments/",
);
$payloadJson = str_replace("</", "<\\/", json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$renderProjectDropdown = static function ($dropdownIdPrefix) use ($projects) {
    ?>
    <div class="filter__dropdown">
      <button class="filter__dropdown-menu-btn" type="button">Выберите ЖК</button>
      <div class="filter__dropdown-content">
        <?php foreach ($projects as $project): ?>
          <?php
          $projectCode = isset($project["code"]) ? (string)$project["code"] : "";
          $projectName = isset($project["name"]) ? (string)$project["name"] : "";
          $projectCount = isset($project["count"]) ? (int)$project["count"] : 0;
          if ($projectCode === "" || $projectName === "") {
              continue;
          }
          ?>
          <div class="input_field<?= $projectCount <= 0 ? " is-disabled" : "" ?>">
            <input
              class="custom-checkbox"
              type="checkbox"
              id="<?= htmlspecialcharsbx($dropdownIdPrefix . "-" . $projectCode) ?>"
              data-sync-group="project"
              data-sync-value="<?= htmlspecialcharsbx($projectCode) ?>"
              <?= $projectCount <= 0 ? "disabled" : "" ?>
            >
            <label for="<?= htmlspecialcharsbx($dropdownIdPrefix . "-" . $projectCode) ?>"><?= htmlspecialcharsbx($projectName) ?></label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
};

$renderRoomPills = static function () use ($rooms) {
    ?>
    <div class="filter__rooms">
      <?php foreach ($rooms as $room): ?>
        <?php
        $roomKey = isset($room["key"]) ? (string)$room["key"] : "";
        $roomLabel = isset($room["label"]) ? (string)$room["label"] : "";
        if ($roomKey === "" || $roomLabel === "") {
            continue;
        }
        ?>
        <span
          class="filter__room"
          data-sync-group="rooms"
          data-sync-value="<?= htmlspecialcharsbx($roomKey) ?>"
        ><?= htmlspecialcharsbx($roomLabel) ?></span>
      <?php endforeach; ?>
    </div>
    <?php
};

$renderRange = static function ($rangeKey, $label, array $range, $namePrefix) {
    $actualMin = isset($range["actual_min"]) ? (float)$range["actual_min"] : 0.0;
    $actualMax = isset($range["actual_max"]) ? (float)$range["actual_max"] : 0.0;
    $renderMin = isset($range["render_min"]) ? (float)$range["render_min"] : $actualMin;
    $renderMax = isset($range["render_max"]) ? (float)$range["render_max"] : $actualMax;
    $step = isset($range["step"]) ? (float)$range["step"] : 1.0;
    $precision = isset($range["precision"]) ? (int)$range["precision"] : ($step < 1 ? 2 : 0);
    ?>
    <div class="filter filter--range filter--<?= htmlspecialcharsbx($rangeKey) ?>">
      <span class="filter__label"><?= htmlspecialcharsbx($label) ?></span>
      <div class="filter__range">
        <div class="filter__range-text">
          <span>От</span>
          <span class="filter__muted" data-range-value="<?= htmlspecialcharsbx($namePrefix) ?>-from"><?= htmlspecialcharsbx(number_format($actualMin, $precision, ".", " ")) ?></span>
        </div>
        <div class="filter__range-text">
          <span>До</span>
          <span class="filter__muted" data-range-value="<?= htmlspecialcharsbx($namePrefix) ?>-to"><?= htmlspecialcharsbx(number_format($actualMax, $precision, ".", " ")) ?></span>
        </div>
        <div class="filter__range-track">
          <div
            class="range-slider"
            data-range="<?= htmlspecialcharsbx($namePrefix) ?>"
            data-min="<?= htmlspecialcharsbx((string)$renderMin) ?>"
            data-max="<?= htmlspecialcharsbx((string)$renderMax) ?>"
            data-start="<?= htmlspecialcharsbx((string)$actualMin) ?>"
            data-end="<?= htmlspecialcharsbx((string)$actualMax) ?>"
            data-step="<?= htmlspecialcharsbx((string)$step) ?>"
          ></div>
          <input type="hidden" name="<?= htmlspecialcharsbx($namePrefix) ?>_from" data-range-input="<?= htmlspecialcharsbx($namePrefix) ?>-from" />
          <input type="hidden" name="<?= htmlspecialcharsbx($namePrefix) ?>_to" data-range-input="<?= htmlspecialcharsbx($namePrefix) ?>-to" />
        </div>
      </div>
    </div>
    <?php
};

$renderCheckboxGroup = static function ($label, $groupName, array $options, $idPrefix) {
    if (empty($options)) {
        return;
    }
    ?>
    <div class="filter">
      <span class="filter__label"><?= htmlspecialcharsbx($label) ?></span>
      <div class="filter__checkboxes">
        <?php foreach ($options as $option): ?>
          <?php
          $key = isset($option["key"]) ? (string)$option["key"] : "";
          $itemLabel = isset($option["label"]) ? (string)$option["label"] : "";
          $itemCount = isset($option["count"]) ? (int)$option["count"] : 0;
          if ($key === "" || $itemLabel === "") {
              continue;
          }
          ?>
          <label class="filter__checkbox<?= $itemCount <= 0 ? " is-disabled" : "" ?>">
            <input
              class="custom-checkbox"
              type="checkbox"
              id="<?= htmlspecialcharsbx($idPrefix . "-" . $key) ?>"
              data-sync-group="<?= htmlspecialcharsbx($groupName) ?>"
              data-sync-value="<?= htmlspecialcharsbx($key) ?>"
              <?= $itemCount <= 0 ? "disabled" : "" ?>
            >
            <span><?= htmlspecialcharsbx($itemLabel) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
};
?>

<section class="catalog" data-apartment-catalog>
  <script type="application/json" data-apartment-filter-payload><?= $payloadJson ?></script>

  <div class="breadcrumbs-wrap">
    <div class="container">
      <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
    </div>
  </div>

  <div class="container">
    <h1 class="catalog__title">Выбор квартиры</h1>

    <div class="catalog__filters">
      <div class="filters">
        <div class="filters__controls">
          <div class="filter filter--select">
            <span class="filter__label">ЖК</span>
            <?php $renderProjectDropdown("catalog-project"); ?>
          </div>

          <div class="filter filter--rooms">
            <span class="filter__label">Комнатность</span>
            <?php $renderRoomPills(); ?>
          </div>

          <?php $renderRange("price", "Укажите стоимость, р.", isset($ranges["price"]) ? $ranges["price"] : array(), "price"); ?>
          <?php $renderRange("floors", "Этаж", isset($ranges["floor"]) ? $ranges["floor"] : array(), "floors"); ?>
        </div>

        <div class="filters__actions">
          <button class="btn btn--outline" type="button" data-filters-open>
            <svg width="23" height="23" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M2.73828 0V22.5M11.1192 0V22.5M20.024 0V22.5" stroke="currentColor"/>
              <path d="M4.73828 4V7H0.5V4H4.73828Z" fill="currentColor" stroke="currentColor"/>
              <path d="M13.2385 15V18H9.00024V15H13.2385Z" fill="currentColor" stroke="currentColor"/>
              <path d="M22.2385 4V7H18.0002V4H22.2385Z" fill="currentColor" stroke="currentColor"/>
            </svg>
            Все фильтры
          </button>
          <button class="btn btn--outline" type="button" data-catalog-reset hidden>Сбросить</button>
          <div class="filters__summary" data-catalog-summary>Найдено <?= (int)$count ?> <?= htmlspecialcharsbx($pluralize($count)) ?></div>
        </div>
      </div>

      <div class="filters-popup" aria-hidden="true">
        <div class="filters-popup__overlay" data-filters-close></div>
        <div class="filters-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="catalog-filters-title">
          <div class="filters-popup__header">
            <h3 class="filters-popup__title" id="catalog-filters-title">Все фильтры</h3>
            <button class="filters-popup__close" type="button" aria-label="Закрыть" data-filters-close>×</button>
          </div>

          <div class="filters-popup__grid">
            <div class="filters-popup__col">
              <div class="filter filter--select">
                <span class="filter__label">ЖК</span>
                <?php $renderProjectDropdown("catalog-popup-project"); ?>
              </div>

              <div class="filter filter--rooms">
                <span class="filter__label">Кол-во комнат</span>
                <?php $renderRoomPills(); ?>
              </div>

              <?php $renderRange("price", "Укажите стоимость, р.", isset($ranges["price"]) ? $ranges["price"] : array(), "price"); ?>
              <?php $renderRange("square", "Укажите площадь, м²", isset($ranges["area"]) ? $ranges["area"] : array(), "square"); ?>
            </div>

            <div class="filters-popup__col">
              <?php $renderRange("height", "Высота потолков, м", isset($ranges["ceiling"]) ? $ranges["ceiling"] : array(), "height"); ?>
              <?php $renderRange("floor", "Этаж", isset($ranges["floor"]) ? $ranges["floor"] : array(), "floors"); ?>
              <?php $renderCheckboxGroup("Статус", "status", $statuses, "catalog-popup-status"); ?>
              <?php $renderCheckboxGroup("Отделка", "finish", $finishes, "catalog-popup-finish"); ?>
            </div>

            <div class="filters-popup__col">
              <?php $renderCheckboxGroup("Планировочные решения", "feature", $featureTags, "catalog-popup-feature"); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="catalog__results">
    <div class="container">
      <div class="catalog__count catalog__count--center" data-catalog-count>Найдено <?= (int)$count ?> <?= htmlspecialcharsbx($pluralize($count)) ?></div>
      <div class="catalog__toolbar">
        <div class="catalog__sort" data-sort-dropdown>
          <button class="catalog__sort-btn filter__dropdown-menu-btn" type="button" data-sort-toggle>
            По умолчанию
          </button>
          <div class="catalog__sort-menu filter__dropdown-content" data-sort-menu>
            <button class="catalog__sort-option is-active" type="button" data-sort-value="default">По умолчанию</button>
            <button class="catalog__sort-option" type="button" data-sort-value="price_asc">Стоимость по возрастанию</button>
            <button class="catalog__sort-option" type="button" data-sort-value="price_desc">Стоимость по убыванию</button>
            <button class="catalog__sort-option" type="button" data-sort-value="floor_asc">Этаж по возрастанию</button>
            <button class="catalog__sort-option" type="button" data-sort-value="floor_desc">Этаж по убыванию</button>
            <button class="catalog__sort-option" type="button" data-sort-value="area_desc">Площадь по убыванию</button>
          </div>
        </div>
        <div class="catalog__view">
          <button class="btn btn--primary btn--sm catalog__view-btn is-active" type="button" data-view="grid">Плиткой</button>
          <button class="btn btn--outline btn--sm catalog__view-btn" type="button" data-view="list">Списком</button>
        </div>
      </div>
    </div>

    <div class="container">
      <div class="catalog__empty" data-catalog-empty hidden>Квартиры не найдены. Измените параметры фильтра.</div>
      <div class="catalog-grid is-grid" data-view-container data-catalog-results></div>
    </div>
  </div>
</section>
