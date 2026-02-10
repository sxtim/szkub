<?php
define("CATALOG_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Выбор квартиры");
?>

<section class="catalog">
  <div class="container">
    <h1 class="catalog__title">Выбор квартиры</h1>

    <div class="catalog__filters">
      <?php
      $APPLICATION->IncludeComponent(
        "bitrix:catalog.smart.filter",
        "projects",
        array(
          "IBLOCK_TYPE" => "",
          "IBLOCK_ID" => "",
          "FILTER_NAME" => "arrFilter",
          "CACHE_TYPE" => "A",
          "CACHE_TIME" => "36000000",
          "CACHE_GROUPS" => "Y",
          "SAVE_IN_SESSION" => "N",
          "PAGER_PARAMS_NAME" => "arrPager",
          "XML_EXPORT" => "N",
          "SECTION_ID" => "",
          "SECTION_CODE" => "",
          "SMART_FILTER_PATH" => "",
          "INSTANT_RELOAD" => "N"
        ),
        false
      );
      ?>
    </div>
  </div>

  <div class="catalog__results">
    <div class="container">
      <div class="catalog__count catalog__count--center">Нашлось 222 квартиры</div>
      <div class="catalog__toolbar">
        <button class="catalog__sort-btn" type="button">
          По умолчанию
        </button>
      <div class="catalog__view">
        <button class="btn btn--primary btn--sm catalog__view-btn is-active" type="button" data-view="grid">Плиткой</button>
        <button class="btn btn--outline btn--sm catalog__view-btn" type="button" data-view="list">Списком</button>
      </div>
    </div>
    </div>

    <div class="container">
      <?php $planSrc = SITE_TEMPLATE_PATH . "/img/apartments/" . rawurlencode("1 этаж 2е 92.8 с антресолью 1.jpg"); ?>
      <div class="catalog-grid is-grid" data-view-container>
        <?php for ($i = 0; $i < 4; $i++): ?>
          <article class="apartment-card">
            <div class="apartment-card__head">
              <div>
                <span class="apartment-card__project">ЖК Коллекция</span>
                <span class="apartment-card__date">Сдача 2 кв. 2027г.</span>
              </div>
              <button class="apartment-card__fav" type="button" aria-label="В избранное">
                <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>

            <div class="apartment-card__plan">
              <img class="apartment-card__plan-image" src="<?= $planSrc ?>" alt="Планировка" />
            </div>

            <div class="apartment-card__meta">
              Студия &nbsp;•&nbsp; 20,1 м² &nbsp;•&nbsp; 12 этаж из 12
            </div>

            <div class="apartment-card__price">
              <span class="apartment-card__price-main">5 700 000 ₽</span>
              <span class="apartment-card__price-old">6 700 000 ₽</span>
            </div>

            <span class="apartment-card__badge">Скидка 25%</span>

            <div class="apartment-card__list">
              <div class="apartment-card__summary">
                <div class="apartment-card__rooms">Студия</div>
                <div class="apartment-card__area">20,1 м²</div>
              </div>
              <div class="apartment-card__delivery">Сдача до II квартала 2027г.</div>
              <div class="apartment-card__list-price">5 700 000 ₽</div>
              <span class="apartment-card__label">Готовые квартиры</span>
              <div class="apartment-card__icons">
                <button class="apartment-card__icon apartment-card__fav" type="button" aria-label="В избранное">
                  <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                <button class="apartment-card__icon" type="button" aria-label="Поделиться">
                  <svg width="16" height="19" viewBox="0 0 16 19" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M9.65859 3.12484C9.65859 1.39904 11.064 0 12.7976 0C14.5313 0 15.9367 1.39904 15.9367 3.12484C15.9367 4.85065 14.5313 6.24969 12.7976 6.24969C11.9223 6.24969 11.1311 5.8928 10.5624 5.31861L6.21691 8.27725C6.25703 8.47692 6.27813 8.68316 6.27813 8.89381C6.27813 9.31098 6.19563 9.70977 6.04619 10.0743L10.811 13.2048C11.3518 12.7645 12.0437 12.4994 12.7976 12.4994C14.5313 12.4994 15.9367 13.8984 15.9367 15.6243C15.9367 17.35 14.5313 18.7491 12.7976 18.7491C11.064 18.7491 9.65859 17.35 9.65859 15.6243C9.65859 15.1722 9.75533 14.7419 9.92923 14.3534L5.20293 11.2482C4.65161 11.7274 3.92943 12.0186 3.13905 12.0186C1.4054 12.0186 0 10.6196 0 8.89381C0 7.16799 1.4054 5.76895 3.13905 5.76895C4.13595 5.76895 5.02337 6.23149 5.59796 6.95139L9.80942 4.08394C9.71146 3.7813 9.65859 3.45883 9.65859 3.12484Z" fill="currentColor"/>
                  </svg>
                </button>
              </div>
            </div>
          </article>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
