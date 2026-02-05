<?php
define("TENDERS_PAGE", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Тендеры — КУБ");
?>

<section class="tenders-intro">
  <div class="container">
    <div class="tenders-intro__grid">
      <div class="tenders-intro__content">
        <h1 class="tenders-intro__title">Тендеры девелопера “КУБ”</h1>
        <div class="tenders-intro__text">
          <p>Девелоперская компания “КУБ” внимательно и осознанно подходит к выбору подрядчиков и поставщиков. Для нас важно не только соблюдение сроков, но и ответственность за результат.</p>
          <p>В компании выстроена системная работа с отраслевыми партнёрами: от этапа отбора и тендерных процедур до взаимодействия в процессе строительства. Мы заинтересованы в командах и специалистах, которые разделяют наш подход к качеству, умеют работать по стандартам.</p>
          <p>”КУБ” расширяет портфель проектов и масштабы строительства, поэтому мы открыты к сотрудничеству с подрядными организациями готовыми стать частью наших проектов и вносить вклад в создание современных, продуманных и качественных жилых объектов.</p>
        </div>
      </div>
      <div class="tenders-intro__media">
        <img src="<?=SITE_TEMPLATE_PATH?>/img/tenders/tenders-intro.webp" alt="">
      </div>
    </div>
  </div>
</section>

<section class="consulting-form">
  <div class="container">
    <div class="consulting-form__bg">
      <img src="<?=SITE_TEMPLATE_PATH?>/img/consulting/consult-bg.webp" alt="">
    </div>
    <div class="consulting-form__box">
      <div class="consulting-form__content">
        <h2 class="consulting-form__title">Подрядчикам и поставщикам</h2>
        <p class="consulting-form__text">
          Станьте партнером уже сегодня Пришлите заявку и начните сотрудничество
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
