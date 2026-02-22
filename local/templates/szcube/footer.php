      </main>
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
    </div>
  </body>
</html>
