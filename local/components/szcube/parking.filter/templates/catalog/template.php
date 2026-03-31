<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$projects = isset($arResult["PROJECTS"]) && is_array($arResult["PROJECTS"]) ? $arResult["PROJECTS"] : array();
$types = isset($arResult["TYPES"]) && is_array($arResult["TYPES"]) ? $arResult["TYPES"] : array();
$statuses = isset($arResult["STATUSES"]) && is_array($arResult["STATUSES"]) ? $arResult["STATUSES"] : array();
$ranges = isset($arResult["RANGES"]) && is_array($arResult["RANGES"]) ? $arResult["RANGES"] : array();
$parkings = isset($arResult["PARKINGS"]) && is_array($arResult["PARKINGS"]) ? $arResult["PARKINGS"] : array();
$count = isset($arResult["COUNT"]) ? (int)$arResult["COUNT"] : count($parkings);

$pluralize = static function ($value) {
    $value = abs((int)$value);
    $mod10 = $value % 10;
    $mod100 = $value % 100;

    if ($mod10 === 1 && $mod100 !== 11) {
        return "место";
    }
    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return "места";
    }

    return "мест";
};

$payload = array(
    "parkings" => $parkings,
    "ranges" => $ranges,
    "count" => $count,
);
$payloadJson = str_replace("</", "<\\/", json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$renderCheckboxDropdown = static function ($label, $defaultText, $groupName, array $options, $idPrefix) {
    if (empty($options)) {
        return;
    }
    ?>
    <div class="filter filter--select">
      <span class="filter__label"><?= htmlspecialcharsbx($label) ?></span>
      <div class="filter__dropdown">
        <button class="filter__dropdown-menu-btn" type="button"><?= htmlspecialcharsbx($defaultText) ?></button>
        <div class="filter__dropdown-content">
          <?php foreach ($options as $option): ?>
            <?php
            $key = isset($option["key"]) ? (string)$option["key"] : "";
            $itemLabel = isset($option["label"]) ? (string)$option["label"] : "";
            $itemCount = isset($option["count"]) ? (int)$option["count"] : 0;
            if ($key === "" || $itemLabel === "") {
                continue;
            }
            ?>
            <div class="input_field<?= $itemCount <= 0 ? " is-disabled" : "" ?>">
              <input
                class="custom-checkbox"
                type="checkbox"
                id="<?= htmlspecialcharsbx($idPrefix . "-" . $key) ?>"
                data-sync-group="<?= htmlspecialcharsbx($groupName) ?>"
                data-sync-value="<?= htmlspecialcharsbx($key) ?>"
                <?= $itemCount <= 0 ? "disabled" : "" ?>
              >
              <label for="<?= htmlspecialcharsbx($idPrefix . "-" . $key) ?>"><?= htmlspecialcharsbx($itemLabel) ?></label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
};

$renderRange = static function ($label, array $range, $namePrefix) {
    $actualMin = isset($range["actual_min"]) ? (float)$range["actual_min"] : 0.0;
    $actualMax = isset($range["actual_max"]) ? (float)$range["actual_max"] : 0.0;
    $renderMin = isset($range["render_min"]) ? (float)$range["render_min"] : $actualMin;
    $renderMax = isset($range["render_max"]) ? (float)$range["render_max"] : $actualMax;
    $step = isset($range["step"]) ? (float)$range["step"] : 1.0;
    $precision = isset($range["precision"]) ? (int)$range["precision"] : 0;
    ?>
    <div class="filter filter--range">
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
          <input type="hidden" name="<?= htmlspecialcharsbx($namePrefix) ?>_from" data-range-input="<?= htmlspecialcharsbx($namePrefix) ?>-from">
          <input type="hidden" name="<?= htmlspecialcharsbx($namePrefix) ?>_to" data-range-input="<?= htmlspecialcharsbx($namePrefix) ?>-to">
        </div>
      </div>
    </div>
    <?php
};
?>

<section class="catalog parking-catalog" data-parking-catalog>
  <script type="application/json" data-parking-filter-payload><?= $payloadJson ?></script>

  <div class="container">
    <div class="catalog__filters">
      <div class="filters">
        <div class="filters__controls">
          <?php $renderCheckboxDropdown("ЖК", "Выберите ЖК", "project", $projects, "parking-project"); ?>
          <?php $renderCheckboxDropdown("Тип места", "Выберите тип", "type", $types, "parking-type"); ?>
          <?php $renderRange("Стоимость, ₽", isset($ranges["price"]) ? $ranges["price"] : array(), "price"); ?>
          <?php $renderRange("Уровень", isset($ranges["level"]) ? $ranges["level"] : array(), "level"); ?>
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
          <button class="btn btn--outline" type="button" data-parking-reset hidden>Сбросить</button>
          <div class="filters__summary" data-parking-summary>Найдено <?= (int)$count ?> <?= htmlspecialcharsbx($pluralize($count)) ?></div>
        </div>
      </div>

      <div class="filters-popup" aria-hidden="true">
        <div class="filters-popup__overlay" data-filters-close></div>
        <div class="filters-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="parking-filters-title">
          <div class="filters-popup__header">
            <h3 class="filters-popup__title" id="parking-filters-title">Фильтр парковок</h3>
            <button class="filters-popup__close" type="button" aria-label="Закрыть" data-filters-close>×</button>
          </div>

          <div class="filters-popup__grid">
            <div class="filters-popup__col">
              <?php $renderCheckboxDropdown("ЖК", "Выберите ЖК", "project", $projects, "parking-popup-project"); ?>
              <?php $renderCheckboxDropdown("Тип места", "Выберите тип", "type", $types, "parking-popup-type"); ?>
            </div>
            <div class="filters-popup__col">
              <?php $renderCheckboxDropdown("Статус", "Выберите статус", "status", $statuses, "parking-popup-status"); ?>
              <?php $renderRange("Стоимость, ₽", isset($ranges["price"]) ? $ranges["price"] : array(), "price"); ?>
            </div>
            <div class="filters-popup__col">
              <?php $renderRange("Площадь, м²", isset($ranges["area"]) ? $ranges["area"] : array(), "area"); ?>
              <?php $renderRange("Уровень", isset($ranges["level"]) ? $ranges["level"] : array(), "level"); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="catalog__results">
    <div class="container">
      <div class="catalog__count catalog__count--center" data-parking-count>Найдено <?= (int)$count ?> <?= htmlspecialcharsbx($pluralize($count)) ?></div>
    </div>

    <div class="container">
      <div class="catalog__empty" data-parking-empty hidden>Парковочные места не найдены. Измените параметры фильтра.</div>
      <div class="catalog-grid is-list" data-parking-results></div>
    </div>
  </div>
</section>
