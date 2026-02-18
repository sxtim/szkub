      </main>
      <?php
      $footerFlat = defined('FOOTER_FLAT') && FOOTER_FLAT === true;
      $footerTemplate = $footerFlat ? 'footer-flat.php' : 'footer-default.php';
      require __DIR__ . '/parts/' . $footerTemplate;
      ?>
    </div>
  </body>
</html>
