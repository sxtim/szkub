<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Page\Asset;

$asset = Asset::getInstance();
$navLinks = function_exists("szcubeGetNavigationLinks") ? szcubeGetNavigationLinks() : array();
$getNavLink = static function ($key, $default = "") use ($navLinks) {
    return isset($navLinks[$key]) ? (string)$navLinks[$key] : (string)$default;
};
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
if (
    (defined("PARKING_PAGE") && PARKING_PAGE === true)
    || (defined("STOREROOMS_PAGE") && STOREROOMS_PAGE === true)
) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/catalog.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/parking.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/parking-catalog.js");
}
if (defined("COMMERCIAL_PAGE") && COMMERCIAL_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/catalog.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/parking.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/catalog.js");
}
if (
    (defined("APARTMENT_DETAIL_PAGE") && APARTMENT_DETAIL_PAGE === true)
    || (defined("COMMERCIAL_DETAIL_PAGE") && COMMERCIAL_DETAIL_PAGE === true)
) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/vendor/swiper-bundle.min.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/apartment-detail.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/catalog.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/project-benefits.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/apartment-similar.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/purchase.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/vendor/swiper-bundle.min.js");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/apartment-detail.js");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/apartment-similar.js");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/project-benefits.js");
}
if (defined("PROJECTS_PAGE") && PROJECTS_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/vendor/swiper-bundle.min.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/projects.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/project-benefits.css");
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/purchase.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/vendor/swiper-bundle.min.js");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/project-benefits.js");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/projects.js");
}
if (defined("ABOUT_COMPANY_PAGE") && ABOUT_COMPANY_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/about-company.css");
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/about-company.js");
}
if (defined("MORTGAGE_PAGE") && MORTGAGE_PAGE === true) {
    $asset->addCss(SITE_TEMPLATE_PATH . "/css/mortgage.css");
}
if (defined("MORTGAGE_CALCULATOR_PAGE") && MORTGAGE_CALCULATOR_PAGE === true) {
    $asset->addJs(SITE_TEMPLATE_PATH . "/js/mortgage-calculator.js");
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

if (!defined("ERROR_404")) {
    $canonicalUrl = trim((string)$APPLICATION->GetPageProperty("canonical"));
    if ($canonicalUrl === "") {
        $requestUri = (string)($_SERVER["REQUEST_URI"] ?? "/");
        $canonicalPath = (string)parse_url($requestUri, PHP_URL_PATH);
        if ($canonicalPath === "") {
            $canonicalPath = "/";
        }
        $canonicalUrl = "https://szcube.ru" . $canonicalPath;
    } elseif (strpos($canonicalUrl, "http://") !== 0 && strpos($canonicalUrl, "https://") !== 0) {
        if ($canonicalUrl === "" || $canonicalUrl[0] !== "/") {
            $canonicalUrl = "/" . ltrim($canonicalUrl, "/");
        }
        $canonicalUrl = "https://szcube.ru" . $canonicalUrl;
    }

    $asset->addString(
        '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . '">',
        true
    );
}
?>
<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php $APPLICATION->ShowTitle(); ?></title>
    <?php $APPLICATION->ShowHead(); ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
      (function(m,e,t,r,i,k,a){
          m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
          m[i].l=1*new Date();
          for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
          k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
      })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=108149201', 'ym');

      ym(108149201, 'init', {
          ssr: true,
          webvisor: true,
          clickmap: true,
          accurateTrackBounce: true,
          trackLinks: true
      });
    </script>
    <!-- /Yandex.Metrika counter -->
  </head>
  <body>
    <noscript><div><img src="https://mc.yandex.ru/watch/108149201" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
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
              <li><a href="<?=$getNavLink("projects", "/projects/")?>">Проекты</a></li>
              <li><a href="<?=$getNavLink("apartments", "/apartments/")?>">Квартиры</a></li>
              <li><a href="<?=$getNavLink("commerce", "/commerce/")?>">Коммерция</a></li>
              <li><a href="<?=$getNavLink("consulting", "/consulting/")?>">Консалтинг</a></li>
              <li><a href="<?=$getNavLink("tenders", "/tenders/")?>">Тендеры</a></li>
              <li><a href="<?=$getNavLink("about_company", "/about-company/")?>">О компании</a></li>
              <li><a href="<?=$getNavLink("contacts", "/#contacts")?>">Контакты</a></li>
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
                  <li><a href="<?=$getNavLink("promotions", "/promotions/")?>">Акции</a></li>
                  <li><a href="<?=$getNavLink("news", "/news/")?>">Новости</a></li>
                  <li><a href="<?=$getNavLink("parking", "/parking/")?>">Паркинг</a></li>
                  <li><a href="<?=$getNavLink("storerooms", "/storerooms/")?>">Кладовые</a></li>
                  <li><a href="<?=$getNavLink("mortgage", "/mortgage/")?>">Ипотека</a></li>
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
            <button class="btn btn--light" type="button" data-contact-open="contact" data-contact-title="Заказать обратный звонок" data-contact-type="callback" data-contact-source="header">Заказать звонок</button>
          </div>
          <button class="mobile-nav-btn" type="button" aria-label="Открыть меню">
            <span class="nav-icon"></span>
          </button>
        </div>
        <div class="mobile-nav">
          <ul class="mobile-nav__list">
            <li><a href="<?=$getNavLink("projects", "/projects/")?>">Проекты</a></li>
            <li><a href="<?=$getNavLink("apartments", "/apartments/")?>">Квартиры</a></li>
            <li><a href="<?=$getNavLink("commerce", "/commerce/")?>">Коммерция</a></li>
            <li><a href="<?=$getNavLink("consulting", "/consulting/")?>">Консалтинг</a></li>
            <li><a href="<?=$getNavLink("tenders", "/tenders/")?>">Тендеры</a></li>
            <li><a href="<?=$getNavLink("promotions", "/promotions/")?>">Акции</a></li>
            <li><a href="<?=$getNavLink("news", "/news/")?>">Новости</a></li>
            <li><a href="<?=$getNavLink("parking", "/parking/")?>">Паркинг</a></li>
            <li><a href="<?=$getNavLink("storerooms", "/storerooms/")?>">Кладовые</a></li>
            <li><a href="<?=$getNavLink("about_company", "/about-company/")?>">О компании</a></li>
            <li><a href="<?=$getNavLink("mortgage", "/mortgage/")?>">Ипотека</a></li>
            <li><a href="<?=$getNavLink("contacts", "/#contacts")?>">Контакты</a></li>
            <li>
              <a class="mobile-nav__phone" href="tel:+7(473) 300-68-87">+7(473) 300-68-87</a>
            </li>
            <li>
              <button class="btn btn--light" type="button" data-contact-open="contact" data-contact-title="Заказать обратный звонок" data-contact-type="callback" data-contact-source="header_mobile">Заказать звонок</button>
            </li>
          </ul>
        </div>
      </header>
      <main class="main">
