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

        <div class="projects-benefit-modal__slide">
          <h4 class="projects-benefit-modal__title projects-benefit-modal__title--mobile" data-modal-title-mobile></h4>

          <div class="projects-benefit-modal__imageWrap">
            <div class="projects-benefit-modal__image" data-modal-image></div>
          </div>

          <div class="projects-benefit-modal__descriptionWrapper">
            <h6 class="projects-benefit-modal__title projects-benefit-modal__title--desktop" data-modal-title></h6>
            <div class="projects-benefit-modal__text" data-modal-text></div>
          </div>
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
