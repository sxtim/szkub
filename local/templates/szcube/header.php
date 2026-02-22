<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

$asset = Asset::getInstance();
$asset->addCss(SITE_TEMPLATE_PATH . "/css/main.css");
$asset->addCss(SITE_TEMPLATE_PATH . "/css/contact-form.css");
$asset->addCss(SITE_TEMPLATE_PATH . "/css/accordion.css");
if (defined("CONSULTING_PAGE") && CONSULTING_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/consulting.css");
}
if (defined("TENDERS_PAGE") && TENDERS_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/consulting.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/tenders.css");
}
if (defined("CATALOG_PAGE") && CATALOG_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/catalog.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/catalog.js");
}
if (defined("PROJECTS_PAGE") && PROJECTS_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/vendor/swiper-bundle.min.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/projects.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/vendor/swiper-bundle.min.js");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/projects.js");
}
if (
    (defined("NEWS_PAGE") && NEWS_PAGE === true)
    || (defined("PROMOTIONS_PAGE") && PROMOTIONS_PAGE === true)
) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/article.css");
}
if (defined("FOOTER_FLAT") && FOOTER_FLAT === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/footer-flat.css");
}
$asset->addJs(SITE_TEMPLATE_PATH . "/js/vendor/nouislider.min.js");
$asset->addJs(SITE_TEMPLATE_PATH . "/js/filters.js");
$asset->addJs(SITE_TEMPLATE_PATH . "/js/index.js");
$asset->addJs(SITE_TEMPLATE_PATH . "/js/accordion.js");
$asset->addString('<link rel="preconnect" href="https://fonts.googleapis.com">');
$asset->addString('<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>');
$asset->addString('<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">');
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php $APPLICATION->ShowTitle(); ?></title>
    <?php $APPLICATION->ShowHead(); ?>
  </head>
  <body>
    <?php $APPLICATION->ShowPanel(); ?>
    <div class="page">
      <header class="header">
        <div class="container header__inner">
          <a class="logo" href="/">
            <img
              src="<?=SITE_TEMPLATE_PATH?>/img/figma-f0c695eb-93ca-42c4-81da-32adaa050abc.svg"
              alt="КУБ"
            />
          </a>
          <nav class="nav">
            <ul class="nav__list">
              <li><a href="/projects/">Проекты</a></li>
              <li><a href="/apartments/">Квартиры</a></li>
              <li><a href="#commerce">Коммерция</a></li>
              <li><a href="/consulting/">Консалтинг</a></li>
              <li><a href="/tenders/">Тендеры</a></li>
              <li><a href="#company">О компании</a></li>
              <li><a href="#contacts">Контакты</a></li>
              <li class="nav__more">
                <button
                  class="nav__more-btn"
                  type="button"
                  aria-haspopup="true"
                  aria-expanded="false"
                >
                  Еще
                </button>
                <ul class="nav__dropdown">
                  <li><a href="#promo">Акции</a></li>
                  <li><a href="#news">Новости</a></li>
                  <li><a href="#mortgage">Ипотека</a></li>
                </ul>
              </li>
            </ul>
          </nav>
          <div class="header__actions">
            <div class="header__phone">
              <img
                src="<?=SITE_TEMPLATE_PATH?>/img/figma-5ce40d0d-8a0e-4c43-b274-c7aeeb5d600e.svg"
                alt=""
              />
              <a href="tel:+7(473) 300-68-87">+7(473) 300-68-87</a>
            </div>
            <button class="btn btn--light" type="button" data-contact-open="contact" data-contact-title="Заказать обратный звонок">Заказать звонок</button>
          </div>
          <button class="mobile-nav-btn" type="button" aria-label="Открыть меню">
            <span class="nav-icon"></span>
          </button>
        </div>
        <div class="mobile-nav">
          <ul class="mobile-nav__list">
            <li><a href="/projects/">Проекты</a></li>
            <li><a href="/apartments/">Квартиры</a></li>
            <li><a href="#commerce">Коммерция</a></li>
            <li><a href="/consulting/">Консалтинг</a></li>
            <li><a href="/tenders/">Тендеры</a></li>
            <li><a href="#promo">Акции</a></li>
            <li><a href="#company">О компании</a></li>
            <li><a href="#mortgage">Ипотека</a></li>
            <li><a href="#contacts">Контакты</a></li>
            <li>
              <a class="mobile-nav__phone" href="tel:+7(473) 300-68-87">+7(473) 300-68-87</a>
            </li>
            <li>
              <button class="btn btn--light" type="button" data-contact-open="contact" data-contact-title="Заказать обратный звонок">Заказать звонок</button>
            </li>
          </ul>
        </div>
      </header>
      <main class="main">
