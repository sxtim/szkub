<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Главная");
$APPLICATION->SetPageProperty("title", "СЗ КУБ — застройщик в Воронеже | Официальный сайт");
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>
<h1 class="visually-hidden">СЗ КУБ — застройщик в Воронеже</h1>
<?php
$homeHeroBannersIblockType = "content";
$homeHeroBannersIblockCode = "home_banners";
$homeHeroBannersIblockId = 0;
if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
  $iblockRes = CIBlock::GetList(
    array(),
    array(
      "TYPE" => $homeHeroBannersIblockType,
      "=CODE" => $homeHeroBannersIblockCode,
      "ACTIVE" => "Y",
    ),
    false
  );
  if ($iblock = $iblockRes->Fetch()) {
    $homeHeroBannersIblockId = (int)$iblock["ID"];
  }
}
?>
<? if ($homeHeroBannersIblockId > 0): ?>
  <?$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "home_hero_banners",
    array(
      "IBLOCK_TYPE" => $homeHeroBannersIblockType,
      "IBLOCK_ID" => $homeHeroBannersIblockId,
      "NEWS_COUNT" => "10",
      "SORT_BY1" => "SORT",
      "SORT_ORDER1" => "ASC",
      "SORT_BY2" => "ACTIVE_FROM",
      "SORT_ORDER2" => "DESC",
      "FIELD_CODE" => array(
        0 => "NAME",
        1 => "PREVIEW_TEXT",
        2 => "PREVIEW_PICTURE",
        3 => "",
      ),
      "PROPERTY_CODE" => array(
        0 => "SLOT",
        1 => "LINK_URL",
        2 => "LINK_TARGET",
        3 => "",
      ),
      "CHECK_DATES" => "Y",
      "CACHE_TYPE" => "A",
      "CACHE_TIME" => "36000000",
      "CACHE_FILTER" => "N",
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
      "DISPLAY_NAME" => "N",
      "DISPLAY_PICTURE" => "N",
      "DISPLAY_PREVIEW_TEXT" => "N",
      "PARENT_SECTION" => "",
      "PARENT_SECTION_CODE" => "",
      "STRICT_SECTION_CHECK" => "N",
      "DETAIL_URL" => "",
      "DISPLAY_TOP_PAGER" => "N",
      "DISPLAY_BOTTOM_PAGER" => "N",
      "PAGER_SHOW_ALWAYS" => "N",
      "PAGER_TEMPLATE" => "",
    ),
    false
  );?>
<? endif; ?>

        <section class="projects" id="projects">
  <div class="container">
    <h2 class="section-title">Проекты</h2>
    <?php
    $APPLICATION->IncludeComponent(
      "bitrix:catalog.smart.filter",
      "filters",
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

    <div class="projects__cards">
      <a class="project-card" href="https://xn--e1abhgabfaz8fye.xn--p1ai/">
        <div class="project-card__image">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/photo_5467741080506797884_y.jpg"
            alt="Коллекция"
          />
          <div class="project-card__tags">
            <span class="tag tag--solid">Бизнес</span>
            <span class="tag tag--outline">Скидки 10%</span>
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
                <span class="project-card__room">2е</span>
                <span class="project-card__room">3е</span>
                <span class="project-card__room">4к</span>
              </div>
              
            </div>
            
          </div>
          <div class="project-card__footer">
          <span class="project-card__sale-count">22 квартиры</span>
            <span class="project-card__price">от 6 756 809 р.</span>
          </div>
        </div>
      </a>

      <article class="project-card">
        <div class="project-card__image">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/figma-6c3f203f-be9a-4001-ab97-edc7f3b4a9e3.png"
            alt="Вертикаль"
          />
          <div class="project-card__tags">
            <span class="tag tag--solid">Комфорт +</span>
            <span class="tag tag--outline">110 квартир</span>
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

      <!-- <article class="project-card">
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
          <!-- </div> -->
        <!-- </div> -->
      <!-- </article> --> 
      
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
	        ?>
	        <? if ($homePromotionsIblockId > 0): ?>
	          <?$APPLICATION->IncludeComponent(
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
	              "CACHE_FILTER" => "N",
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
	          );?>
	        <? else: ?>
	          <section class="promo" id="promo">
	            <div class="container">
	              <h2 class="section-title">Акции</h2>
	            </div>
	          </section>
	        <? endif; ?>

	        <?php
	        $homeNewsIblockType = "content";
	        $homeNewsIblockCode = "news";
	        $homeNewsIblockId = 0;
	        if (class_exists("\\Bitrix\\Main\\Loader") && \Bitrix\Main\Loader::includeModule("iblock")) {
	          $iblockRes = CIBlock::GetList(
	            array(),
	            array(
	              "TYPE" => $homeNewsIblockType,
	              "=CODE" => $homeNewsIblockCode,
	              "ACTIVE" => "Y",
	            ),
	            false
	          );
	          if ($iblock = $iblockRes->Fetch()) {
	            $homeNewsIblockId = (int)$iblock["ID"];
	          }
	        }
	        ?>
	        <? if ($homeNewsIblockId > 0): ?>
	          <?$APPLICATION->IncludeComponent(
	            "bitrix:news.list",
	            "home_news",
	            array(
	              "IBLOCK_TYPE" => $homeNewsIblockType,
	              "IBLOCK_ID" => $homeNewsIblockId,
	              "NEWS_COUNT" => "3",
	              "SORT_BY1" => "ACTIVE_FROM",
	              "SORT_ORDER1" => "DESC",
	              "SORT_BY2" => "SORT",
	              "SORT_ORDER2" => "ASC",
	              "FIELD_CODE" => array(
	                0 => "NAME",
	                1 => "PREVIEW_TEXT",
	                2 => "PREVIEW_PICTURE",
	                3 => "DATE_ACTIVE_FROM",
	                4 => "",
	              ),
	              "PROPERTY_CODE" => array(),
	              "CHECK_DATES" => "Y",
	              "DETAIL_URL" => "/news/#ELEMENT_CODE#/",
	              "ACTIVE_DATE_FORMAT" => "d.m.Y",
	              "CACHE_TYPE" => "A",
	              "CACHE_TIME" => "36000000",
	              "CACHE_FILTER" => "N",
	              "CACHE_GROUPS" => "Y",
	              "SET_TITLE" => "N",
	              "SET_BROWSER_TITLE" => "N",
	              "SET_META_KEYWORDS" => "N",
	              "SET_META_DESCRIPTION" => "N",
	              "SET_LAST_MODIFIED" => "N",
	              "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
	              "ADD_SECTIONS_CHAIN" => "N",
	              "HIDE_LINK_WHEN_NO_DETAIL" => "N",
	              "DISPLAY_DATE" => "Y",
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
	          );?>
	        <? else: ?>
	          <section class="news" id="news">
	            <div class="container">
	              <h2 class="section-title">Новости</h2>
	            </div>
	          </section>
	        <? endif; ?>

        <section class="faq" id="company">
  <div class="container">
    <h2 class="section-title">Вопрос-ответ</h2>
      <div class="faq__grid">
      <div class="faq__list details-group">
        <details class="details" open>
          <summary class="details__summary">Что такое ДДУ и почему это безопасно для покупателя?</summary>
          <div class="details__content">
            <p>
              ДДУ — это договор долевого участия, который регулируется 214‑ФЗ. Покупатель приобретает квартиру на этапе
              строительства, а его средства размещаются на эскроу‑счёте в банке.
            </p>
            <p>Это означает:</p>
            <ul>
              <li>деньги хранятся в банке до ввода дома в эксплуатацию;</li>
              <li>застройщик получает их только после сдачи объекта;</li>
              <li>в случае форс‑мажора средства возвращаются покупателю.</li>
            </ul>
            <p>Таким образом, покупка по ДДУ — это законный и защищённый способ приобрести квартиру в новостройке.</p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Когда выгоднее покупать квартиру — на старте продаж или ближе к сдаче дома?</summary>
          <div class="details__content">
            <p>На старте продаж стоимость квадратного метра обычно минимальна. По мере готовности дома цена растёт.</p>
            <p>Преимущества покупки на раннем этапе:</p>
            <ul>
              <li>более выгодная цена;</li>
              <li>широкий выбор планировок и этажей;</li>
              <li>возможность заработать на росте стоимости недвижимости.</li>
            </ul>
            <p>Если же важен быстрый переезд, можно рассмотреть квартиры высокой степени готовности.</p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Можно ли купить квартиру в новостройке в ипотеку?</summary>
          <div class="details__content">
            <p>Да. Большинство банков сотрудничают с застройщиками и предлагают ипотечные программы на покупку по ДДУ.</p>
            <p>Доступны:</p>
            <ul>
              <li>семейная ипотека;</li>
              <li>льготные государственные программы;</li>
              <li>стандартные ипотечные продукты;</li>
              <li>рассрочка от застройщика.</li>
            </ul>
            <p>Специалисты отдела продаж помогают подобрать оптимальный вариант и сопровождают клиента на всех этапах сделки.</p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Как проходит процесс покупки квартиры по ДДУ?</summary>
          <div class="details__content">
            <p>Процесс состоит из нескольких этапов:</p>
            <ol>
              <li>Выбор квартиры и бронирование.</li>
              <li>Подготовка и подписание ДДУ.</li>
              <li>Открытие эскроу‑счёта в банке.</li>
              <li>Регистрация договора в Росреестре.</li>
              <li>Ожидание завершения строительства.</li>
              <li>Получение ключей и подписание акта приёма‑передачи.</li>
            </ol>
            <p>Весь процесс прозрачен и юридически защищён.</p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Застрахованы ли средства покупателя при покупке по ДДУ?</summary>
          <div class="details__content">
            <p>
              Да. Все средства размещаются на эскроу‑счёте в банке. До ввода дома в эксплуатацию застройщик не имеет к ним
              доступа.
            </p>
            <p>
              Если объект по каким‑либо причинам не будет завершён, банк возвращает деньги покупателю. Это один из ключевых
              механизмов защиты по 214‑ФЗ.
            </p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Можно ли использовать материнский капитал при покупке?</summary>
          <div class="details__content">
            <p>Да. Материнский капитал можно направить:</p>
            <ul>
              <li>на первоначальный взнос;</li>
              <li>на частичное досрочное погашение ипотеки;</li>
              <li>на оплату части стоимости квартиры.</li>
            </ul>
            <p>Специалисты отдела продаж проконсультируют, какой пакет документов нужно подготовить.</p>
          </div>
        </details>
        <details class="details">
          <summary class="details__summary">Какие документы получает покупатель после сдачи дома?</summary>
          <div class="details__content">
            <p>После ввода дома в эксплуатацию покупатель получает:</p>
            <ul>
              <li>акт приёма‑передачи квартиры;</li>
              <li>ключи;</li>
              <li>техническую документацию;</li>
              <li>документы для регистрации права собственности.</li>
            </ul>
            <p>После регистрации в Росреестре покупатель становится полноценным собственником недвижимости.</p>
          </div>
        </details>
      </div>

      <?php
      $contactFormLeadType = "callback";
      $contactFormLeadSource = "home_inline";
      include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/contact-form.php";
      unset($contactFormLeadType, $contactFormLeadSource);
      ?>
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
