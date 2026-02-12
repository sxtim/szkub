<div class="filters-popup" aria-hidden="true">
  <div class="filters-popup__overlay" data-filters-close></div>
  <div class="filters-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="filters-title">
    <div class="filters-popup__header">
      <h3 class="filters-popup__title" id="filters-title">Все фильтры</h3>
      <button class="filters-popup__close" type="button" aria-label="Закрыть" data-filters-close>×</button>
    </div>
    <div class="filters-popup__grid">
      <div class="filters-popup__col">
        <div class="filter filter--select">
          <span class="filter__label">Город и ЖК</span>
          <?php $dropdownIdPrefix = "popup-city"; ?>
          <?php require __DIR__ . "/_city-dropdown.php"; ?>
        </div>

        <div class="filter filter--rooms">
          <span class="filter__label">Кол-во комнат</span>
          <div class="filter__rooms">
            <span class="filter__room" data-sync-group="rooms" data-sync-value="studio">Студия</span>
            <span class="filter__room" data-sync-group="rooms" data-sync-value="1">1к</span>
            <span class="filter__room" data-sync-group="rooms" data-sync-value="2">2к</span>
            <span class="filter__room" data-sync-group="rooms" data-sync-value="3">3к</span>
            <span class="filter__room" data-sync-group="rooms" data-sync-value="4plus">4+</span>
          </div>
        </div>

        <div class="filter filter--range filter--price">
          <span class="filter__label">Укажите стоимость, р</span>
          <div class="filter__range">
            <div class="filter__range-text">
              <span>От</span>
              <span class="filter__muted" data-range-value="price-from">4 251 780</span>
            </div>
            <div class="filter__range-text">
              <span>До</span>
              <span class="filter__muted" data-range-value="price-to">44 825 780</span>
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

        <div class="filter filter--range filter--square">
          <span class="filter__label">Укажите площадь, м²</span>
          <div class="filter__range">
            <div class="filter__range-text">
              <span>От</span>
              <span class="filter__muted" data-range-value="square-from">17.63</span>
            </div>
            <div class="filter__range-text">
              <span>До</span>
              <span class="filter__muted" data-range-value="square-to">222.4</span>
            </div>
            <div class="filter__range-track">
              <div
                class="range-slider"
                data-range="square"
                data-min="10"
                data-max="250"
                data-start="17.63"
                data-end="222.4"
                data-step="0.1"
              ></div>
              <input type="hidden" name="square_from" data-range-input="square-from" />
              <input type="hidden" name="square_to" data-range-input="square-to" />
            </div>
          </div>
        </div>
      </div>

      <div class="filters-popup__col">
        <div class="filter filter--range filter--height">
          <span class="filter__label">Высота потолков, м</span>
          <div class="filter__range">
            <div class="filter__range-text">
              <span>От</span>
              <span class="filter__muted" data-range-value="height-from">0</span>
            </div>
            <div class="filter__range-text">
              <span>До</span>
              <span class="filter__muted" data-range-value="height-to">3.3</span>
            </div>
            <div class="filter__range-track">
              <div
                class="range-slider"
                data-range="height"
                data-min="0"
                data-max="5"
                data-start="2.5"
                data-end="3.3"
                data-step="0.1"
              ></div>
              <input type="hidden" name="height_from" data-range-input="height-from" />
              <input type="hidden" name="height_to" data-range-input="height-to" />
            </div>
          </div>
        </div>

        <div class="filter filter--balcony">
          <span class="filter__label">Балкон</span>
          <div class="filter__rooms">
            <span class="filter__room">Балкон</span>
            <span class="filter__room">Лоджия</span>
            <span class="filter__room">Терраса</span>
            <span class="filter__room">Без балкона/лоджии</span>
          </div>
        </div>

        <div class="filter filter--range filter--floor">
          <span class="filter__label">Этаж</span>
          <div class="filter__range">
            <div class="filter__range-text">
              <span>От</span>
              <span class="filter__muted" data-range-value="floors-from">1</span>
            </div>
            <div class="filter__range-text">
              <span>До</span>
              <span class="filter__muted" data-range-value="floors-to">25</span>
            </div>
            <div class="filter__range-track">
              <div
                class="range-slider"
                data-range="floors"
                data-min="1"
                data-max="25"
                data-start="1"
                data-end="25"
                data-step="1"
              ></div>
              <input type="hidden" name="floor_from" data-range-input="floors-from" />
              <input type="hidden" name="floor_to" data-range-input="floors-to" />
            </div>
          </div>
          <div class="filter__rooms">
            <span class="filter__room">Не первый</span>
            <span class="filter__room">Не последний</span>
          </div>
        </div>
      </div>

      <div class="filters-popup__col">
        <div class="filter filter--planning">
          <span class="filter__label">Планировочные решения</span>
          <div class="filter__checkboxes">
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Мастер спальня</span>
            </label>
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Гардероб</span>
            </label>
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Витражное остекление</span>
            </label>
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Кухонный гарнитур</span>
            </label>
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Высокие потолки</span>
            </label>
          </div>
        </div>

        <div class="filter filter--finish">
          <span class="filter__label">Отделка</span>
          <div class="filter__checkboxes">
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Предчистовая</span>
            </label>
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Черновая</span>
            </label>
            <label class="filter__checkbox">
              <input class="custom-checkbox" type="checkbox">
              <span>Чистовая</span>
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
