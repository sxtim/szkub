<?php
define("CONSULTING_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Консалтинг — КУБ");
?>

<section class="consulting-hero">
  <div class="container">
    <div class="consulting-hero__grid">
      <div class="consulting-hero__card">
        <div class="consulting-hero__content">
          <h1 class="consulting-hero__title">Строительный<br>консалтинг</h1>
          <p class="consulting-hero__text">
            Контроль строительного объекта требует опоры на факты и независимую экспертизу.
            «КУБ» обеспечивает прозрачность строительных процессов, помогая выявить риски,
            исключить перерасход бюджета и соблюсти сроки. Наш системный подход — защита качества актива.
          </p>
          <button class="consulting-hero__btn" type="button">Оставить заявку</button>
        </div>
      </div>
      <div class="consulting-hero__image">
        <img src="<?=SITE_TEMPLATE_PATH?>/img/consulting/hero.webp" alt="Строительный консалтинг">
      </div>
    </div>
  </div>
</section>

<section class="consulting-services">
  <div class="container">
    <h2 class="consulting-services__title">Услуги строительного консалтинга</h2>

    <div class="consulting-service">
      <div class="consulting-service__left">
        <h3 class="consulting-service__label">01&nbsp;&nbsp;Управление строительством</h3>
        <span class="consulting-service__line"></span>
      </div>
      <ul class="consulting-service__list">
        <li>Управление строительными проектами</li>
        <li>Технический заказчик</li>
        <li>Внедрение проектного управления</li>
      </ul>
      <ul class="consulting-service__list">
        <li>Внедрение стандартов НОСТРОЙ</li>
        <li>Организация тендеров в строительстве</li>
        <li>Аутсорсинг строительства</li>
      </ul>
    </div>

    <div class="consulting-service">
      <div class="consulting-service__left">
        <h3 class="consulting-service__label">02&nbsp;&nbsp;Сопровождение строительства</h3>
        <span class="consulting-service__line"></span>
      </div>
      <ul class="consulting-service__list">
        <li>Строительный контроль</li>
        <li>Приёмка строительных работ</li>
        <li>Техническое обследование зданий и сооружений</li>
        <li>Сюрвейинг для банков</li>
      </ul>
      <ul class="consulting-service__list">
        <li>Юридическое сопровождение проектов</li>
        <li>Технический надзор</li>
        <li>Контроль строительных работ</li>
      </ul>
    </div>

    <div class="consulting-service">
      <div class="consulting-service__left">
        <h3 class="consulting-service__label">03&nbsp;&nbsp;Строительная документация</h3>
        <span class="consulting-service__line"></span>
      </div>
      <ul class="consulting-service__list">
        <li>Аудит проектно‑сметной документации</li>
        <li>Разработка строительной документации</li>
        <li>Финансово‑строительный аудит</li>
      </ul>
      <ul class="consulting-service__list">
        <li>Договор строительного подряда</li>
        <li>СРО. Лицензирование. Сертификация</li>
      </ul>
    </div>
  </div>
</section>

<section class="consulting-form">
  <div class="container">
    <div class="consulting-form__bg">
      <img src="<?=SITE_TEMPLATE_PATH?>/img/consulting/Frame_444800.png" alt="">
    </div>
    <div class="consulting-form__box">
      <div class="consulting-form__content">
        <h2 class="consulting-form__title">Получить консультацию</h2>
        <p class="consulting-form__text">
          Оставьте ваши контакты для консультации. Мы свяжемся с вами в ближайшее время
        </p>
      </div>
      <div class="consulting-form__fields consulting-form__fields--no-title">
        <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szkub/parts/contact-form.php"; ?>
      </div>
    </div>
  </div>
</section>

<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
