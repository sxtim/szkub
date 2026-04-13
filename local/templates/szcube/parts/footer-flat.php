      <?php
      $navLinks = function_exists("szcubeGetNavigationLinks") ? szcubeGetNavigationLinks() : array();
      $footerNewbuildings = isset($navLinks["footer_newbuildings"]) && is_array($navLinks["footer_newbuildings"]) ? $navLinks["footer_newbuildings"] : array();
      $footerRealty = isset($navLinks["footer_realty"]) && is_array($navLinks["footer_realty"]) ? $navLinks["footer_realty"] : array();
      $footerPurchase = isset($navLinks["footer_purchase"]) && is_array($navLinks["footer_purchase"]) ? $navLinks["footer_purchase"] : array();
      $footerClients = isset($navLinks["footer_clients"]) && is_array($navLinks["footer_clients"]) ? $navLinks["footer_clients"] : array();
      ?>
      <footer class="footer footer--flat">
        <div class="container footer__top">
          <div class="footer__main">
            <div class="footer__logo">
              <img
                src="<?=SITE_TEMPLATE_PATH?>/img/figma-c9c9ef2d-69bd-4f4c-b48c-1419b9401f81.svg"
                alt="КУБ"
              />
            </div>
            <ul class="footer__contacts">
              <li>
                <img class="footer__icon" src="<?=SITE_TEMPLATE_PATH?>/img/ic-loc.svg" alt="" />
                Воронеж, ул. Фридриха Энгельса,<br> дом 7а офис 201
              </li>
              <li>
                <img class="footer__icon" src="<?=SITE_TEMPLATE_PATH?>/img/ic-watch.svg" alt="" />
                пн‑пт: 9:00‑19:00<br />
                сб-вс: выходной <br />
              </li>
              <li>
                <img class="footer__icon" src="<?=SITE_TEMPLATE_PATH?>/img/mail.svg" alt="" />
                cube-develop@yandex.ru<br />
              </li>
              <li>
                <img class="footer__icon" src="<?=SITE_TEMPLATE_PATH?>/img/ic-phone.svg" alt="" />
                <a href="tel:+7(473) 300-68-87">+7(473) 300-68-87</a>
              </li>
            </ul>
            <div class="footer__main-bottom">
              <button class="btn btn--light footer__callback" type="button" data-contact-open="contact" data-contact-title="Заказать обратный звонок" data-contact-type="callback" data-contact-source="footer_flat">
                Перезвоните мне
              </button>
            </div>
          </div>
          <div class="footer__columns">
            <div class="footer__col">
              <div class="footer__title">Новостройки</div>
              <?php foreach ($footerNewbuildings as $item): ?>
                <a class="footer__link" href="<?=htmlspecialchars((string)$item["href"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?>"><?=htmlspecialchars((string)$item["label"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?></a>
              <?php endforeach; ?>
            </div>
            <div class="footer__col">
              <div class="footer__title">Недвижимость</div>
              <?php foreach ($footerRealty as $item): ?>
                <a class="footer__link" href="<?=htmlspecialchars((string)$item["href"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?>"><?=htmlspecialchars((string)$item["label"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?></a>
              <?php endforeach; ?>
            </div>
            <div class="footer__col">
              <div class="footer__title">Способы покупки</div>
              <?php foreach ($footerPurchase as $item): ?>
                <a class="footer__link" href="<?=htmlspecialchars((string)$item["href"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?>"><?=htmlspecialchars((string)$item["label"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?></a>
              <?php endforeach; ?>
            </div>
            <div class="footer__col">
              <div class="footer__title">Клиентам</div>
              <?php foreach ($footerClients as $item): ?>
                <a class="footer__link" href="<?=htmlspecialchars((string)$item["href"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?>"><?=htmlspecialchars((string)$item["label"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?></a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="footer__bottom">
          <div class="container footer__bottom-inner">
            <div class="footer__policy-block">
              <p class="footer__legal">
                Все права и материалы, публикуемые на сайте szcube.ru принадлежат ООО "СЗ КУБ" © 2026. Все права защищены
                Любая информация, представленная на данном сайте, носит исключительно информационный характер и ни при каких условиях не является публичной офертой, определяемой положениями статьи 437 ГК РФ
              </p>
              <div class="footer__policy-links">
                <a class="footer__policy" href="/privacy-policy/">Политика конфиденциальности</a>
                <a class="footer__policy" href="/personal-data-consent/">Согласие на обработку ПДн</a>
                <a class="footer__policy" href="/user-agreement/">Пользовательское соглашение</a>
                <a class="footer__policy" href="/cookie-policy/">Политика cookies</a>
              </div>
            </div>
            <div class="footer__social">
              <img src="<?=SITE_TEMPLATE_PATH?>/img/footer-social-1.svg" alt="VK" />
              <svg class="footer__social-icon footer__social-icon--max" viewBox="0 0 720 720" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="MAX">
                <path d="M350.4 9.6C141.8 20.5 4.1 184.1 12.8 390.4c3.8 90.3 40.1 168 48.7 253.7 2.2 22.2-4.2 49.6 21.4 59.3 31.5 11.9 79.8-8.1 106.2-26.4 9-6.1 17.6-13.2 24.2-22 27.3 18.1 53.2 35.6 85.7 43.4 143.1 34.3 299.9-44.2 369.6-170.3C799.6 291.2 622.5-4.6 350.4 9.6ZM269.4 504c-11.3 8.8-22.2 20.8-34.7 27.7-18.1 9.7-23.7-.4-30.5-16.4-21.4-50.9-24-137.6-11.5-190.9 16.8-72.5 72.9-136.3 150-143.1 78-6.9 150.4 32.7 183.1 104.2 72.4 159.1-112.9 316.2-256.4 218.6Z" />
              </svg>
            </div>
          </div>
        </div>
      </footer>
