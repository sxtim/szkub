      </main>
      <?php if (!(defined('APARTMENT_DETAIL_PRINT_PAGE') && APARTMENT_DETAIL_PRINT_PAGE === true)): ?>
      <?php
      $footerFlat = defined('FOOTER_FLAT') && FOOTER_FLAT === true;
      $footerTemplate = $footerFlat ? 'footer-flat.php' : 'footer-default.php';
      require __DIR__ . '/parts/' . $footerTemplate;
      ?>

      <?php
      $APPLICATION->IncludeComponent(
        "szcube:contact.form",
        ".default",
        array(
          "MODAL_ID" => "contact",
          "TITLE" => "Остались вопросы?",
        ),
        false
      );
      ?>
      <div class="cookie-banner" data-cookie-banner hidden>
        <div class="cookie-banner__inner">
          <div class="cookie-banner__text">
            <?php
            $APPLICATION->IncludeComponent(
              "bitrix:main.include",
              "",
              array(
                "AREA_FILE_SHOW" => "file",
                "PATH" => SITE_DIR . "include/cookie-banner-content.php",
                "EDIT_TEMPLATE" => "",
              ),
              false
            );
            ?>
          </div>
          <button class="btn btn--primary cookie-banner__button" type="button" data-cookie-banner-accept>
            ОК
          </button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </body>
</html>
