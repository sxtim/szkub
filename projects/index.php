<?php
define("PROJECTS_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проекты — КУБ");
?>

<section class="projects-page">
  <div class="container">
    <h1 class="section-title">Проекты</h1>

    <div class="projects-about">
      <div class="projects-about__grid">
        <div class="projects-about__media">
          <img
            src="<?=SITE_TEMPLATE_PATH?>/img/projects/div.image-lazy__image.jpg"
            alt="Проект — изображение"
            loading="lazy"
          />
        </div>

        <h2 class="projects-about__title">
          <span class="projects-about__title-accent">ЖК «Название»</span>
          Ваша суперсила у моря
        </h2>

        <div class="projects-about__content">
          <div class="projects-about__text">
            <p>
              Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quis
              nemo explicabo, perferendis dignissimos reprehenderit dolorum
              accusamus provident, doloribus vero eum, aliquid tempore.
            </p>
            <p>
              Lorem ipsum dolor sit amet consectetur adipisicing elit. Fuga
              possimus eveniet laudantium, porro tempora repellendus. Nisi
              accusamus, nobis eos sunt eaque aspernatur.
            </p>
          </div>

          <ul class="projects-about__features">
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Высокая ликвидность</div>
              <div class="projects-about__feature-value">Бизнес‑класс, мультиформат</div>
            </li>
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Благоустройство</div>
              <div class="projects-about__feature-value">Двор‑парк и зоны отдыха</div>
            </li>
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Сервис</div>
              <div class="projects-about__feature-value">Поддержка 24/7</div>
            </li>
            <li class="projects-about__feature">
              <div class="projects-about__feature-label">Инфраструктура</div>
              <div class="projects-about__feature-value">Школы, сад и магазины рядом</div>
            </li>
          </ul>
        </div>
      </div>
    </div>

    <section class="projects-genplan" aria-label="Генплан проекта">
      <div class="projects-genplan__viewport">
        <div class="projects-genplan__scene">
          <img
            class="projects-genplan__image"
            src="<?=SITE_TEMPLATE_PATH?>/img/projects/image_15.jpg"
            alt="Генплан проекта"
            loading="lazy"
          />

          <div class="projects-genplan__overlay" aria-hidden="true">
            <div class="projects-genplan__overlay-inner">
              <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/projects/Group.svg")?>
            </div>
          </div>

          <div class="projects-genplan__pins">
            <button class="projects-genplan__pin" type="button" style="top:26%;left:73%;">
              <span class="projects-genplan__pin-label">1 литер | 1 очередь</span>
              <svg class="projects-genplan__pin-icon" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M5.43301 7.25C5.24056 7.58333 4.75944 7.58333 4.56699 7.25L1.10289 1.25C0.910436 0.916669 1.151 0.500001 1.5359 0.500001L8.4641 0.5C8.849 0.5 9.08956 0.916666 8.89711 1.25L5.43301 7.25Z" fill="#009EAE" stroke="currentColor"></path>
              </svg>
            </button>
          </div>

          <button class="projects-genplan__filter" type="button">Фильтр</button>
        </div>
      </div>
    </section>

    <section class="projects-benefits" aria-label="Преимущества проекта">
      <h2 class="projects-benefits__title">Преимущества</h2>

      <div class="projects-benefits__tabs" role="tablist" aria-label="Категории преимуществ">
        <button class="btn btn--sm projects-benefits__tab is-active" type="button" role="tab" aria-selected="true" data-benefit-tab="all">Все</button>
        <button class="btn btn--sm projects-benefits__tab" type="button" role="tab" aria-selected="false" data-benefit-tab="finish">Отделка</button>
        <button class="btn btn--sm projects-benefits__tab" type="button" role="tab" aria-selected="false" data-benefit-tab="location">Локация</button>
        <button class="btn btn--sm projects-benefits__tab" type="button" role="tab" aria-selected="false" data-benefit-tab="landscape">Благоустройство</button>
        <button class="btn btn--sm projects-benefits__tab" type="button" role="tab" aria-selected="false" data-benefit-tab="infrastructure">Инфраструктура</button>
        <button class="btn btn--sm projects-benefits__tab" type="button" role="tab" aria-selected="false" data-benefit-tab="facade">Фасад и материалы</button>
        <button class="btn btn--sm projects-benefits__tab" type="button" role="tab" aria-selected="false" data-benefit-tab="layouts">Планировки</button>
      </div>

      <?php
      $benefitImage = SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg";
      $benefits = array(
        array(
          "category" => "location",
          "label" => "Локация",
          "title" => "Кудепста — жизнь в уникальном природном окружении",
          "description" => "До ключевых точек города — быстро, при этом рядом зелёные зоны и тихие улицы.",
          "content" => "<p>До ключевых точек города — быстро, при этом рядом зелёные зоны и тихие улицы.</p><p>Окружение проекта задаёт ритм жизни: больше прогулок, меньше суеты.</p><p>И здесь создана среда для комфорта:</p><ul><li>близость ключевой инфраструктуры</li><li>зелёные зоны и приватные дворы</li><li>тихие улицы без транзитного трафика</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "landscape",
          "label" => "Благоустройство",
          "title" => "Двор‑парк и зоны отдыха для всех возрастов",
          "description" => "Пространства для прогулок, отдыха и спорта — продумано для ежедневного комфорта.",
          "content" => "<p>Пространства для прогулок, отдыха и спорта — продумано для ежедневного комфорта.</p><p>Всё устроено так, чтобы во двор хотелось выходить каждый день.</p><p>И здесь создана среда для комфорта:</p><ul><li>зоны тихого отдыха и активностей</li><li>подсветка и навигация</li><li>озеленение и уютные маршруты</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "infrastructure",
          "label" => "Инфраструктура",
          "title" => "Школы, сад и магазины рядом с домом",
          "description" => "Всё необходимое для семьи — в пешей доступности или в нескольких минутах на авто.",
          "content" => "<p>Всё необходимое для семьи — в пешей доступности или в нескольких минутах на авто.</p><p>Повседневные задачи решаются быстрее — остаётся больше времени на себя.</p><p>И здесь создана среда для комфорта:</p><ul><li>сад, школы и секции рядом</li><li>магазины и сервисы у дома</li><li>удобные маршруты и остановки</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "finish",
          "label" => "Отделка",
          "title" => "Современные решения отделки и инженерии",
          "description" => "Материалы и системы подбираются так, чтобы интерьер оставался актуальным годами.",
          "content" => "<p>Материалы и системы подбираются так, чтобы интерьер оставался актуальным годами.</p><p>Рациональные планировочные решения + комфортные инженерные сценарии.</p><p>И здесь создана среда для комфорта:</p><ul><li>качественные материалы отделки</li><li>инженерные системы для тишины и микроклимата</li><li>решения, упрощающие быт</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "facade",
          "label" => "Фасад и материалы",
          "title" => "Фасады, которые сохраняют эстетику и тепло",
          "description" => "Долговечные материалы, аккуратные узлы и выразительная архитектурная пластика.",
          "content" => "<p>Долговечные материалы, аккуратные узлы и выразительная архитектурная пластика.</p><p>Фасад работает на образ проекта и на комфорт внутри дома.</p><p>И здесь создана среда для комфорта:</p><ul><li>сбалансированная архитектура</li><li>материалы с хорошими характеристиками</li><li>внимание к деталям</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "layouts",
          "label" => "Планировки",
          "title" => "Функциональные планировки без лишних метров",
          "description" => "Логичные сценарии жизни: хранение, кухня‑гостиная, приватные зоны — всё на месте.",
          "content" => "<p>Логичные сценарии жизни: хранение, кухня‑гостиная, приватные зоны — всё на месте.</p><p>Планировки проектировались под реальные потребности семьи.</p><p>И здесь создана среда для комфорта:</p><ul><li>удобные пропорции помещений</li><li>места для хранения</li><li>гибкие варианты расстановки мебели</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "landscape",
          "label" => "Благоустройство",
          "title" => "Террасы и общественные пространства для отдыха",
          "description" => "Места, куда приятно выйти вечером: видовые точки, озеленение и приватные уголки.",
          "content" => "<p>Места, куда приятно выйти вечером: видовые точки, озеленение и приватные уголки.</p><p>Небольшие сценарии отдыха — прямо у дома.</p><p>И здесь создана среда для комфорта:</p><ul><li>террасы и зоны отдыха</li><li>озеленение и малые формы</li><li>приватные уголки для чтения и общения</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "location",
          "label" => "Локация",
          "title" => "Транспортная доступность без шума магистралей",
          "description" => "Удобные выезды и маршруты — при этом дом остаётся в спокойной среде района.",
          "content" => "<p>Удобные выезды и маршруты — при этом дом остаётся в спокойной среде района.</p><p>Сбалансированное расположение: близко к городу, но без лишнего шума.</p><p>И здесь создана среда для комфорта:</p><ul><li>удобные подъезды и развязки</li><li>спокойные улицы рядом с домом</li><li>понятные маршруты до важных точек</li></ul>",
          "image" => $benefitImage,
        ),
        array(
          "category" => "infrastructure",
          "label" => "Инфраструктура",
          "title" => "Сервисы и бытовые сценарии рядом",
          "description" => "Кофе, аптека, спорт, доставка — повседневные задачи решаются быстрее.",
          "content" => "<p>Кофе, аптека, спорт, доставка — повседневные задачи решаются быстрее.</p><p>Всё рядом, чтобы не тратить время на дорогу по мелочам.</p><p>И здесь создана среда для комфорта:</p><ul><li>сервисы в шаговой доступности</li><li>простые маршруты для повседневных дел</li><li>пространства для встреч и ожидания</li></ul>",
          "image" => $benefitImage,
        ),
      );
      ?>

      <div class="projects-benefits__body" data-benefits-body>
        <ul class="projects-benefits__list">
          <?php foreach ($benefits as $benefitIndex => $benefit): ?>
            <?php
              $benefitPayload = array(
                "id" => $benefitIndex,
                "category" => $benefit["category"],
                "label" => $benefit["label"],
                "title" => $benefit["title"],
                "description" => $benefit["description"],
                "content" => $benefit["content"],
                "image" => $benefit["image"],
              );
            ?>
            <li class="projects-benefits__item" data-benefit-category="<?=htmlspecialchars($benefit["category"], ENT_QUOTES)?>">
              <article
                class="projects-benefit-card"
                data-benefit="<?=htmlspecialchars(json_encode($benefitPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES)?>"
              >
                <div class="projects-benefit-card__tags">
                  <span class="projects-benefit-card__tag"><?=htmlspecialchars($benefit["label"])?></span>
                </div>

                <div class="projects-benefit-card__image" style="background-image: url('<?=htmlspecialchars($benefit["image"], ENT_QUOTES)?>');"></div>

                <div class="projects-benefit-card__info">
                  <div class="projects-benefit-card__text">
                    <h3 class="projects-benefit-card__title"><?=htmlspecialchars($benefit["title"])?></h3>
                    <p class="projects-benefit-card__description"><?=htmlspecialchars($benefit["description"])?></p>
                  </div>

                  <button class="projects-benefit-card__more" type="button" aria-label="Подробнее">
                    <svg class="projects-benefit-card__more-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
                    </svg>
                  </button>
                </div>
              </article>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
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

<section class="purchase" id="mortgage">
  <div class="container">
    <h2 class="section-title">Способы покупки</h2>

    <div class="purchase__grid" role="list">
      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Ипотека</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/mortgage.svg")?>
        </div>
      </div>

      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Наличные</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/cash.svg")?>
        </div>
      </div>

      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Рассрочка</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/installment.svg")?>
        </div>
      </div>

      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Трейд-ин</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/tradein.svg")?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="construction" id="construction" aria-label="Ход строительства">
  <div class="container">
    <header class="construction__header">
      <h2 class="construction__title">Ход строительства</h2>
      <p class="construction__subtitle">Сдача в IV кв. 2026</p>
    </header>

	    <?php
	      $constructionImage = SITE_TEMPLATE_PATH . "/img/projects/image_15.jpg";
	      $constructionItems = array(
	        array(
	          "month" => "Октябрь 2025",
	          "date" => "октябрь 2025",
	          "description" => "Подготовка площадки и старт ключевых этапов. Публикуем реальные кадры без ретуши, чтобы видеть динамику работ.",
	          "images" => array_fill(0, 46, $constructionImage),
	          "image" => $constructionImage,
	        ),
	        array(
	          "month" => "Ноябрь 2025",
	          "date" => "ноябрь 2025",
	          "description" => "Продолжаем строительство: фиксируем прогресс на объекте и показываем ход работ с разных ракурсов.",
	          "images" => array_fill(0, 22, $constructionImage),
	          "image" => $constructionImage,
	        ),
	        array(
	          "month" => "Декабрь 2025",
	          "date" => "декабрь 2025",
	          "description" => "Итоги месяца: основные работы на площадке и общий прогресс. В галерее — серия свежих фотографий.",
	          "images" => array_fill(0, 58, $constructionImage),
	          "image" => $constructionImage,
	        ),
	        array(
	          "month" => "Январь 2026",
	          "date" => "январь 2026",
	          "description" => "Новый этап строительства: показываем текущее состояние и детали, которые важно видеть в динамике.",
	          "images" => array_fill(0, 37, $constructionImage),
	          "image" => $constructionImage,
	        ),
	        array(
	          "month" => "Февраль 2026",
	          "date" => "февраль 2026",
	          "description" => "Промежуточный фотоотчет: новые кадры с площадки и обновления по текущим работам.",
	          "images" => array_fill(0, 19, $constructionImage),
	          "image" => $constructionImage,
	        ),
	        array(
	          "month" => "Март 2026",
	          "date" => "март 2026",
	          "description" => "Ежемесячный отчет о ходе строительства. Внутри — фотографии с натуральных ракурсов и текущий прогресс.",
	          "images" => array_fill(0, 28, $constructionImage),
	          "image" => $constructionImage,
	        ),
	      );
	    ?>

    <div class="construction__slider">
      <div class="construction__swiper swiper" data-construction-swiper>
        <div class="swiper-wrapper">
          <?php foreach ($constructionItems as $item): ?>
            <div class="swiper-slide">
              <?php
                $constructionPayload = array(
                  "month" => $item["month"],
                  "date" => $item["date"],
                  "description" => $item["description"],
                  "images" => $item["images"],
                );
              ?>
              <article
                class="construction-card"
                data-construction="<?=htmlspecialchars(json_encode($constructionPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES)?>"
              >
                <div
                  class="construction-card__image"
                  style="background-image: url('<?=htmlspecialchars($item["image"], ENT_QUOTES)?>');"
                  aria-hidden="true"
                ></div>

	                <div class="construction-card__info">
	                  <div class="construction-card__meta">
	                    <h3 class="construction-card__month"><?=htmlspecialchars($item["month"])?></h3>
	                    <p class="construction-card__count"><?=htmlspecialchars(count($item["images"]) . " фото")?></p>
	                  </div>

                  <button class="construction-card__more" type="button" aria-label="Открыть фото">
                    <svg class="construction-card__more-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                      <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
                    </svg>
                  </button>
                </div>
              </article>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="construction__nav" aria-label="Навигация по ходу строительства">
        <div class="construction__controls" role="group" aria-label="Переключение месяцев">
          <button class="construction__navBtn" type="button" aria-label="Предыдущее" data-construction-prev>
            <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M4.00049 6.00024L7.00049 3.00024V9.00024L4.00049 6.00024Z" fill="currentColor"></path>
            </svg>
          </button>
          <button class="construction__navBtn" type="button" aria-label="Следующее" data-construction-next>
            <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
            </svg>
          </button>
        </div>

        <div class="construction__pagination" data-construction-pagination>1 / 6</div>
      </div>
    </div>
  </div>
</section>

<section class="projects-docs" aria-label="Документация">
  <div class="container">
    <ul class="projects-docs__list">
      <li class="projects-docs__card">
        <div class="projects-docs__cardText">
          <h3 class="projects-docs__cardTitle">Документы</h3>
          <p class="projects-docs__cardSubtitle">Земельный участок</p>
        </div>
        <div class="projects-docs__cardIcon" aria-hidden="true">
          <svg class="projects-docs__iconItem" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.66667 4.00016V2.00016C4.66667 1.82335 4.7369 1.65378 4.86193 1.52876C4.98695 1.40373 5.15652 1.3335 5.33333 1.3335H13.3333C13.5101 1.3335 13.6797 1.40373 13.8047 1.52876C13.9298 1.65378 14 1.82335 14 2.00016V11.3335C14 11.5103 13.9298 11.6799 13.8047 11.8049C13.6797 11.9299 13.5101 12.0002 13.3333 12.0002H11.3333V14.0002C11.3333 14.3682 11.0333 14.6668 10.662 14.6668H2.67133C2.58342 14.6674 2.49626 14.6505 2.41488 14.6172C2.3335 14.584 2.25949 14.535 2.19711 14.473C2.13472 14.4111 2.0852 14.3374 2.05137 14.2563C2.01754 14.1751 2.00009 14.0881 2 14.0002L2.002 4.66683C2.002 4.29883 2.302 4.00016 2.67333 4.00016H4.66667ZM6 4.00016H11.3333V10.6668H12.6667V2.66683H6V4.00016ZM4.66667 7.3335V8.66683H8.66667V7.3335H4.66667ZM4.66667 10.0002V11.3335H8.66667V10.0002H4.66667Z" fill="currentColor"></path>
          </svg>
          <svg class="projects-docs__iconItem projects-docs__iconItemMobile" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
          </svg>
        </div>
      </li>
      <li class="projects-docs__card">
        <div class="projects-docs__cardText">
          <h3 class="projects-docs__cardTitle">Документы</h3>
          <p class="projects-docs__cardSubtitle">Проектные</p>
        </div>
        <div class="projects-docs__cardIcon" aria-hidden="true">
          <svg class="projects-docs__iconItem" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.66667 4.00016V2.00016C4.66667 1.82335 4.7369 1.65378 4.86193 1.52876C4.98695 1.40373 5.15652 1.3335 5.33333 1.3335H13.3333C13.5101 1.3335 13.6797 1.40373 13.8047 1.52876C13.9298 1.65378 14 1.82335 14 2.00016V11.3335C14 11.5103 13.9298 11.6799 13.8047 11.8049C13.6797 11.9299 13.5101 12.0002 13.3333 12.0002H11.3333V14.0002C11.3333 14.3682 11.0333 14.6668 10.662 14.6668H2.67133C2.58342 14.6674 2.49626 14.6505 2.41488 14.6172C2.3335 14.584 2.25949 14.535 2.19711 14.473C2.13472 14.4111 2.0852 14.3374 2.05137 14.2563C2.01754 14.1751 2.00009 14.0881 2 14.0002L2.002 4.66683C2.002 4.29883 2.302 4.00016 2.67333 4.00016H4.66667ZM6 4.00016H11.3333V10.6668H12.6667V2.66683H6V4.00016ZM4.66667 7.3335V8.66683H8.66667V7.3335H4.66667ZM4.66667 10.0002V11.3335H8.66667V10.0002H4.66667Z" fill="currentColor"></path>
          </svg>
          <svg class="projects-docs__iconItem projects-docs__iconItemMobile" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
          </svg>
        </div>
      </li>
      <li class="projects-docs__card">
        <div class="projects-docs__cardText">
          <h3 class="projects-docs__cardTitle">Документы</h3>
          <p class="projects-docs__cardSubtitle">Разрешительные</p>
        </div>
        <div class="projects-docs__cardIcon" aria-hidden="true">
          <svg class="projects-docs__iconItem" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4.66667 4.00016V2.00016C4.66667 1.82335 4.7369 1.65378 4.86193 1.52876C4.98695 1.40373 5.15652 1.3335 5.33333 1.3335H13.3333C13.5101 1.3335 13.6797 1.40373 13.8047 1.52876C13.9298 1.65378 14 1.82335 14 2.00016V11.3335C14 11.5103 13.9298 11.6799 13.8047 11.8049C13.6797 11.9299 13.5101 12.0002 13.3333 12.0002H11.3333V14.0002C11.3333 14.3682 11.0333 14.6668 10.662 14.6668H2.67133C2.58342 14.6674 2.49626 14.6505 2.41488 14.6172C2.3335 14.584 2.25949 14.535 2.19711 14.473C2.13472 14.4111 2.0852 14.3374 2.05137 14.2563C2.01754 14.1751 2.00009 14.0881 2 14.0002L2.002 4.66683C2.002 4.29883 2.302 4.00016 2.67333 4.00016H4.66667ZM6 4.00016H11.3333V10.6668H12.6667V2.66683H6V4.00016ZM4.66667 7.3335V8.66683H8.66667V7.3335H4.66667ZM4.66667 10.0002V11.3335H8.66667V10.0002H4.66667Z" fill="currentColor"></path>
          </svg>
          <svg class="projects-docs__iconItem projects-docs__iconItemMobile" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
          </svg>
        </div>
      </li>
    </ul>
  </div>
</section>

<section class="projects-call" aria-label="Связаться">
  <div class="container">
    <div class="projects-call__panel">
      <div class="projects-call__col">
        <div class="projects-call__tile projects-call__tile--dark">
          <div class="projects-call__text">
            <div class="projects-call__title">Получите консультацию</div>
            <div class="projects-call__subtitle">Заказать обратный звонок</div>
          </div>
          <div class="projects-call__btn projects-call__btn--white" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M8.9 11.1c1.3 2.5 3.5 4.7 6 6l2-2c.3-.3.8-.4 1.2-.2 1 .4 2.1.7 3.2.8.5.1.8.5.8 1v3.2c0 .5-.4 1-.9 1-10.1 0-18.3-8.2-18.3-18.3 0-.5.4-.9 1-.9H7c.5 0 .9.3 1 .8.1 1.1.4 2.2.8 3.2.2.4.1.9-.2 1.2l-2 2z" fill="currentColor"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="projects-call__col">
        <div class="projects-call__grid">
          <div class="projects-call__tile projects-call__tile--light">
            <div class="projects-call__text">
              <div class="projects-call__title">Напишите нам</div>
              <div class="projects-call__subtitle">в Telegram</div>
            </div>
            <div class="projects-call__btn projects-call__btn--light" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M20.9 4.3L2.9 11.4c-.8.3-.8 1.4.1 1.7l4.6 1.5 1.8 5.7c.2.7 1.1.8 1.5.2l2.7-3.6 4.9 3.6c.5.4 1.2.1 1.3-.6l3.1-15.1c.2-.9-.7-1.6-1.6-1.2zm-3 3.8l-8.6 7.7c-.2.2-.3.4-.3.7l-.2 1.8-.9-2.9 10.5-8c.3-.2.6.2.3.7z" fill="currentColor"/>
              </svg>
            </div>
          </div>

          <div class="projects-call__tile projects-call__tile--light">
            <div class="projects-call__text">
              <div class="projects-call__title">Напишите нам</div>
              <div class="projects-call__subtitle">в MAX</div>
            </div>
            <div class="projects-call__btn projects-call__btn--light" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 6.5C6 5.1 7.1 4 8.5 4h7C17.9 4 19 5.1 19 6.5v6.2c0 1.4-1.1 2.5-2.5 2.5H12l-4.5 3v-3H8.5C7.1 15.2 6 14.1 6 12.7V6.5z" fill="currentColor"/>
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
	          <span class="construction-modal__title-muted">ЖК «Название»</span>
	        </h4>

	        <p class="construction-modal__date" data-construction-modal-date></p>
	      </div>

	      <p class="construction-modal__text" data-construction-modal-text></p>
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

<div class="projects-modal-wrap modal-wrap-custom" data-benefit-modal hidden>
  <div class="projects-modal modal-wrap-container" role="dialog" aria-modal="true" aria-label="Преимущество проекта">
    <button class="projects-modal__close" type="button" aria-label="Закрыть" data-modal-close>
      <span class="projects-modal__close-text">
        <svg viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M1 1L9 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path>
          <path d="M9 1L1 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"></path>
        </svg>
      </span>
    </button>

    <div class="projects-benefit-modal">
      <div class="projects-benefit-modal__slider">
        <header class="projects-benefit-modal__header" aria-hidden="true">
          <h4 class="projects-benefit-modal__category" data-modal-category></h4>
        </header>

        <div class="projects-benefit-modal__swiper swiper" data-modal-swiper>
          <div class="swiper-wrapper" data-modal-wrapper></div>
        </div>

        <div class="projects-benefit-modal__nav" aria-label="Навигация по преимуществам">
          <div class="projects-benefit-modal__pagination" data-modal-pagination>1 / 1</div>

          <div class="projects-benefit-modal__controls" role="group" aria-label="Переключение преимуществ">
            <button class="projects-benefit-modal__navBtn" type="button" aria-label="Предыдущее" data-modal-prev>
              <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M4.00049 6.00024L7.00049 3.00024V9.00024L4.00049 6.00024Z" fill="currentColor"></path>
              </svg>
            </button>
            <button class="projects-benefit-modal__navBtn" type="button" aria-label="Следующее" data-modal-next>
              <svg viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M7.99951 6.00024L4.99951 9.00024V3.00024L7.99951 6.00024Z" fill="currentColor"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
