<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}
?>

<section class="purchase" id="mortgage">
  <div class="container">
    <h2 class="section-title">Способы покупки</h2>

    <div class="purchase__grid" role="list">
      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Ипотека</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/mortgage.svg")?>
        </div>
      </div>

      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Наличные</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/cash.svg")?>
        </div>
      </div>

      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Рассрочка</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/installment.svg")?>
        </div>
      </div>

      <div class="purchase-card" role="listitem">
        <h3 class="purchase-card__title">Трейд-ин</h3>
        <div class="purchase-card__icon" aria-hidden="true">
          <?=file_get_contents($_SERVER["DOCUMENT_ROOT"] . SITE_TEMPLATE_PATH . "/img/purchase/tradein.svg")?>
        </div>
      </div>
    </div>
  </div>
</section>
