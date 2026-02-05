<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("КУБ — сайт");
?>
        <section class="hero">
  <div class="container">
    <div class="hero__top">
      <article class="hero-main">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-2a4f429a-dd52-4323-ae0a-3f1bc0404ebc.png"
          alt="ЖК Вертикаль"
        />
        <div class="hero-main__overlay">
          <p class="hero-main__title">ЖК Вертикаль старт продаж</p>
          <p class="hero-main__subtitle">квартиры с видом на водохранилище</p>
        </div>
      </article>

      <div class="hero-aside">
        <article class="hero-card">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/figma-d19d0bcf-14ae-4fb3-a3dc-4363edabe21a.png"
            alt="Семейная ипотека"
          />
          <div class="hero-card__label">
            Семейная ипотека<br />от 3%
          </div>
        </article>
        <article class="hero-card">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/figma-d19d0bcf-14ae-4fb3-a3dc-4363edabe21a.png"
            alt="Квартира в рассрочку"
          />
          <div class="hero-card__label hero-card__label--tall">
            Квартира в рассрочку<br />без первоначального взноса
          </div>
        </article>
      </div>
    </div>

    <div class="hero__bottom">
      <article class="hero-banner">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-a17e7efd-457c-4226-a2bf-b3322ae1ecbe.png"
          alt="Варианты отделки"
        />
        <div class="hero-banner__label">Варианты отделки</div>
      </article>
      <article class="hero-banner">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-e344372e-8660-419b-8537-aa79b5b36e9f.jpg"
          alt="Спецусловия по коммерции"
        />
        <div class="hero-banner__label">Спецусловия по коммерции</div>
      </article>
    </div>
  </div>
</section>

        <section class="projects" id="projects">
  <div class="container">
    <h2 class="section-title">Проекты</h2>
    <div class="filters">
      <div class="filters__controls">
        <div class="filter filter--select">
          <span class="filter__label">Город и ЖК</span>
          <div class="filter__field">Выберите город и ЖК</div>
        </div>

        <div class="filter filter--rooms">
          <span class="filter__label">Комнатность</span>
          <div class="filter__rooms">
            <span class="filter__room">Студия</span>
            <span class="filter__room">1к</span>
            <span class="filter__room">2к</span>
            <span class="filter__room">3к</span>
            <span class="filter__room">4+</span>
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
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/figma-79c1f9fd-6c0a-4f8c-b28d-1eb500e82390.svg"
            alt=""
          />
          Все фильтры
        </button>
        <button class="btn btn--primary" type="button">Выбрать квартиру</button>
      </div>
    </div>

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
              <div class="filter__dropdown">
                <div class="filter__dropdown-menu-btn" type="button">Выберите город и ЖК</div>
                <div class="filter__dropdown-content">
                  <div class="input_field">
                    <input class="custom-checkbox" type="checkbox" id="city-1">
                    <label for="city-1">ЖК Вертикаль</label>
                  </div>
                  <div class="input_field">
                    <input class="custom-checkbox" type="checkbox" id="city-2">
                    <label for="city-2">ЖК Коллекция</label>
                  </div>
                  <div class="input_field">
                    <input class="custom-checkbox" type="checkbox" id="city-3">
                    <label for="city-3">ЖК Краснознаменная</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="filter filter--rooms">
              <span class="filter__label">Кол-во комнат</span>
              <div class="filter__rooms">
                <span class="filter__room">Студия</span>
                <span class="filter__room">1</span>
                <span class="filter__room">2</span>
                <span class="filter__room">3</span>
                <span class="filter__room">4+</span>
              </div>
            </div>

            <div class="filter filter--range filter--price">
              <span class="filter__label">Укажите стоимость, р</span>
              <div class="filter__range">
                <div class="filter__range-text">
                  <span>От</span>
                  <span class="filter__muted">4 251 780</span>
                </div>
                <div class="filter__range-text">
                  <span>До</span>
                  <span class="filter__muted">44 825 780</span>
                </div>
                <div class="filter__range-track">
                  <div class="range-slider"></div>
                </div>
              </div>
            </div>

            <div class="filter filter--range filter--square">
              <span class="filter__label">Укажите площадь, м²</span>
              <div class="filter__range">
                <div class="filter__range-text">
                  <span>От</span>
                  <span class="filter__muted">17.63</span>
                </div>
                <div class="filter__range-text">
                  <span>До</span>
                  <span class="filter__muted">222.4</span>
                </div>
                <div class="filter__range-track">
                  <div class="range-slider"></div>
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
                  <span class="filter__muted">0</span>
                </div>
                <div class="filter__range-text">
                  <span>До</span>
                  <span class="filter__muted">3.3</span>
                </div>
                <div class="filter__range-track">
                  <div class="range-slider"></div>
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
                  <span class="filter__muted">1</span>
                </div>
                <div class="filter__range-text">
                  <span>До</span>
                  <span class="filter__muted">25</span>
                </div>
                <div class="filter__range-track">
                  <div class="range-slider"></div>
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

    <div class="projects__cards">
      <article class="project-card">
        <div class="project-card__image">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/photo_5467741080506797884_y.jpg"
            alt="Коллекция"
          />
          <div class="project-card__tags">
            <span class="tag tag--solid">Бизнес</span>
            <span class="tag tag--outline">Скидки 5%</span>
          </div>
        </div>
        <div class="project-card__content">
          <div class="project-card__details">
            <span class="project-card__label">Жилой комплекс</span>
            <h3 class="project-card__title">Коллекция</h3>
            <span class="project-card__meta">г. Воронеж, ул. Жилина 7</span>
            <span class="project-card__meta">
              Срок сдачи <strong>III квартал 2026г.</strong>
            </span>
            <div class="project-card__sale">
              <span class="project-card__sale-label">В продаже:</span>
              <div class="project-card__rooms">
                <span class="project-card__room">Студия</span>
                <span class="project-card__room">1к</span>
                <span class="project-card__room">2к</span>
                <span class="project-card__room">3к</span>
              </div>
              
            </div>
            
          </div>
          <div class="project-card__footer">
          <span class="project-card__sale-count">173 квартиры</span>
            <span class="project-card__price">от 6 538 000 р.</span>
          </div>
        </div>
      </article>

      <article class="project-card">
        <div class="project-card__image">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/figma-6c3f203f-be9a-4001-ab97-edc7f3b4a9e3.png"
            alt="Вертикаль"
          />
          <div class="project-card__tags">
            <span class="tag tag--solid">Комфорт +</span>
            <span class="tag tag--outline">527 квартир</span>
          </div>
        </div>
        <div class="project-card__content">
          <div class="project-card__details">
            <span class="project-card__label">Жилой комплекс</span>
            <h3 class="project-card__title">Вертикаль</h3>
            <span class="project-card__meta">г. Воронеж, ул. Фронтовая 5</span>
            
            <span class="project-card__meta">
              Срок сдачи <strong>III квартал 2027г.</strong>
            </span>
            <div class="project-card__sale">
              <span class="project-card__sale-label">В продаже:</span>
              <div class="project-card__rooms">
                <span class="project-card__room">Студия</span>
                <span class="project-card__room">1к</span>
                <span class="project-card__room">2к</span>
                <span class="project-card__room">3к</span>
              </div>
              
            </div>
      
          </div>
          <div class="project-card__footer">
           <span class="project-card__sale-count">Скоро продажи</span>
            <!-- <span class="project-card__price">от 7 538 000 р.</span> -->
          </div>
        </div>
      </article>

      <article class="project-card">
        <div class="project-card__image">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/figma-6c3f203f-be9a-4001-ab97-edc7f3b4a9e3.png"
            alt="Краснознаменная"
          />
          <div class="project-card__tags">
            <span class="tag tag--solid">Бизнес</span>
            <span class="tag tag--outline">Скоро</span>
          </div>
        </div>
        <div class="project-card__content">
          <div class="project-card__details">
            <span class="project-card__label">Жилой комплекс</span>
            <h3 class="project-card__title">Краснознаменная</h3>
            <span class="project-card__meta">г. Воронеж, ул. Краснознаменная</span>
            
            <span class="project-card__meta">
              Срок сдачи <strong>I квартал 2028г.</strong>
            </span>
            <div class="project-card__sale">
              <span class="project-card__sale-label">В продаже:</span>
              <div class="project-card__rooms">
                <span class="project-card__room">Студия</span>
                <span class="project-card__room">1к</span>
                <span class="project-card__room">2к</span>
                <span class="project-card__room">3к</span>
              </div>
              
            </div>
            
          </div>
          <div class="project-card__footer">
           <span class="project-card__sale-count">В проекте</span>
            <!-- <span class="project-card__price">от 8 000 000 р.</span> -->
          </div>
        </div>
      </article>
      
    </div>
  </div>
</section>

        <section class="extra" id="apartments">
  <div class="container">
    <h2 class="section-title">Кроме квартир</h2>
    <div class="extra__cards">
      <article class="extra-card">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-d19d0bcf-14ae-4fb3-a3dc-4363edabe21a.png"
          alt="Коммерция"
        />
        <h3 class="extra-card__title">Коммерция</h3>
        <div class="extra-card__overlay">
          <div class="extra-card__link">
            <img
              src="<?=SITE_TEMPLATE_PATH?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg"
              alt=""
            />
          </div>
        </div>
      </article>

      <article class="extra-card">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-683b8703-3ea0-4192-baac-c2b5ed21c8ba.png"
          alt="Паркинг"
        />
        <h3 class="extra-card__title">Паркинг</h3>
        <div class="extra-card__overlay">
          <div class="extra-card__link">
            <img
              src="<?=SITE_TEMPLATE_PATH?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg"
              alt=""
            />
          </div>
        </div>
      </article>

      <article class="extra-card">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-962f733c-d79a-402f-b82c-1e5b010739c3.png"
          alt="Кладовые"
        />
        <h3 class="extra-card__title">Кладовые</h3>
        <div class="extra-card__overlay">
          <div class="extra-card__link">
            <img
              src="<?=SITE_TEMPLATE_PATH?>/img/figma-c9a51b74-4033-4a0d-a682-d597c518fcf6.svg"
              alt=""
            />
          </div>
        </div>
      </article>
    </div>
  </div>
</section>

        <section class="promo" id="promo">
  <div class="container">
    <h2 class="section-title">Акции</h2>
    <div class="promo__cards">
      <article class="promo-card promo-card--left">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-8964cdee-e9c9-4b1f-9979-9cd074589984.png"
          alt="Новогодние скидки"
        />
        <div class="promo-card__overlay promo-card__overlay--full">
          <p>Выгодные скидки</p>
          <strong>до 15%</strong>
        </div>
      </article>

      <article class="promo-card promo-card--right">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-683b8703-3ea0-4192-baac-c2b5ed21c8ba.png"
          alt="Рассрочка"
        />
        <div class="promo-card__overlay promo-card__overlay--split"></div>
        <div class="promo-card__text promo-card__text--right">
          <p>Рассрочка</p>
          <strong>на 1 год</strong>
        </div>
       
          
        
      </article>
    </div>
  </div>
</section>

        <section class="news" id="news">
  <div class="container">
    <h2 class="section-title">Новости</h2>
    <div class="news__cards">
      <article class="news-card">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-2a4f429a-dd52-4323-ae0a-3f1bc0404ebc.png"
          alt="Новость"
        />
        <time datetime="2024-05-10">10.05.2024</time>
        <h3>Lorem ipsum dolor sit amet consectetur.</h3>
        <p>
          Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit amet
          consectetur.
        </p>
      </article>
      <article class="news-card">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-962f733c-d79a-402f-b82c-1e5b010739c3.png"
          alt="Новость"
        />
        <time datetime="2024-05-10">10.05.2024</time>
        <h3>Lorem ipsum dolor sit amet consectetur.</h3>
        <p>
          Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit amet
          consectetur.
        </p>
      </article>
      <article class="news-card">
        <img
          src="<?=SITE_TEMPLATE_PATH?>/img/figma-00c913e7-155b-407c-b698-3a6167b5fba3.png"
          alt="Новость"
        />
        <time datetime="2024-05-10">10.05.2024</time>
        <h3>Lorem ipsum dolor sit amet consectetur.</h3>
        <p>
          Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit amet
          consectetur.
        </p>
      </article>
    </div>
  </div>
</section>

        <section class="faq" id="company">
  <div class="container">
    <h2 class="section-title">Вопрос-ответ</h2>
    <div class="faq__grid">
      <div class="faq__list details-group">
        <details class="details" open>
          <summary class="details__summary">
            Lorem ipsum dolor sit amet consectetur.
          </summary>
          <div class="details__content">
            <p>
              Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit amet
              consectetur.Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit
              amet consectetur.Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor
              sit amet consectetur.Lorem ipsum dolor sit amet consectetur.
            </p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Lorem ipsum dolor sit amet consectetur.</summary>
          <div class="details__content">
            <p>
              Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit amet
              consectetur.Lorem ipsum dolor sit amet consectetur.
            </p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Lorem ipsum dolor sit amet consectetur.</summary>
          <div class="details__content">
            <p>
              Lorem ipsum dolor sit amet consectetur.Lorem ipsum dolor sit amet
              consectetur.Lorem ipsum dolor sit amet consectetur.
            </p>
          </div>
        </details>
      </div>

      <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szkub/parts/contact-form.php"; ?>
    </div>
  </div>
</section>

        <section class="contacts" id="contacts">
  <div class="contacts__map">
    <script
      type="text/javascript"
      charset="utf-8"
      async
      src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A63d5c308a514e6051740664513673798031dff7881c5e77acadec4c223fd286f&width=100%25&height=500&lang=ru_RU&scroll=false"
    ></script>
  </div>
</section>

      
<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
