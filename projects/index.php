<?php
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проекты");
?>

<div class="breadcrumbs-wrap">
  <div class="container">
    <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
  </div>
</div>

<section class="projects">
  <div class="container">
    <h1 class="section-title"><?php $APPLICATION->ShowTitle(false); ?></h1>

    <div class="projects__cards">
      <a class="project-card" href="/projects/kollekciya/">
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
      </a>

      <a class="project-card" href="/projects/vertical/">
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
          </div>
        </div>
      </a>

      <a class="project-card" href="/projects/krasnoznamennaya/">
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
          </div>
        </div>
      </a>
    </div>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
