<div class="filters">
  <div class="filters__controls">
    <div class="filter filter--select">
      <span class="filter__label">Город и ЖК</span>
      <?php $dropdownIdPrefix = "short-city"; ?>
      <?php require __DIR__ . "/_city-dropdown.php"; ?>
    </div>

    <div class="filter filter--rooms">
      <span class="filter__label">Комнатность</span>
      <div class="filter__rooms">
        <span class="filter__room" data-sync-group="rooms" data-sync-value="studio">Студия</span>
        <span class="filter__room" data-sync-group="rooms" data-sync-value="1">1к</span>
        <span class="filter__room" data-sync-group="rooms" data-sync-value="2">2к</span>
        <span class="filter__room" data-sync-group="rooms" data-sync-value="3">3к</span>
        <span class="filter__room" data-sync-group="rooms" data-sync-value="4plus">4+</span>
      </div>
    </div>

    <div class="filter filter--range filter--price">
      <span class="filter__label">Укажите стоимость, р.</span>
      <div class="filter__range">
        <div class="filter__range-text">
          <span>От</span>
          <span class="filter__muted" data-range-value="price-from">5 109 880</span>
        </div>
        <div class="filter__range-text">
          <span>До</span>
          <span class="filter__muted" data-range-value="price-to">15 680 000</span>
        </div>
        <div class="filter__range-track">
          <div
            class="range-slider"
            data-range="price"
            data-min="0"
            data-max="20000000"
            data-start="5109880"
            data-end="15680000"
            data-step="1000"
          ></div>
          <input type="hidden" name="price_from" data-range-input="price-from" />
          <input type="hidden" name="price_to" data-range-input="price-to" />
        </div>
      </div>
    </div>

    <div class="filter filter--range filter--floors">
      <span class="filter__label">Этажность</span>
      <div class="filter__range">
        <div class="filter__range-text">
          <span>От</span>
          <span class="filter__muted" data-range-value="floors-from">1</span>
        </div>
        <div class="filter__range-text">
          <span>До</span>
          <span class="filter__muted" data-range-value="floors-to">16</span>
        </div>
        <div class="filter__range-track">
          <div
            class="range-slider"
            data-range="floors"
            data-min="1"
            data-max="16"
            data-start="1"
            data-end="16"
            data-step="1"
          ></div>
          <input type="hidden" name="floor_from" data-range-input="floors-from" />
          <input type="hidden" name="floor_to" data-range-input="floors-to" />
        </div>
      </div>
    </div>
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
    <button class="btn btn--primary" type="button">Выбрать квартиру</button>
  </div>
</div>
