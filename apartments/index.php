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
          <button class="btn btn--primary btn--sm" type="button">Плиткой</button>
          <button class="btn btn--outline btn--sm" type="button">Списком</button>
        </div>
      </div>
    </div>

    <div class="container">
      <?php $planSrc = SITE_TEMPLATE_PATH . "/img/apartments/" . rawurlencode("1 этаж 2е 92.8 с антресолью 1.jpg"); ?>
      <div class="catalog-grid">
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
          </article>
        <?php endfor; ?>
      </div>
    </div>
  </div>
</section>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
