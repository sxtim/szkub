<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$modalId = isset($arResult["MODAL_ID"]) ? (string)$arResult["MODAL_ID"] : "contact";
$title = isset($arResult["TITLE"]) ? (string)$arResult["TITLE"] : "Остались вопросы?";

$contactFormId = "contact-form-modal";
$contactFormTitle = $title;
$contactFormTitleAttr = 'data-contact-modal-title';
$contactFormLeadType = "callback";
$contactFormLeadSource = "modal";
?>

<div class="contact-modal" data-contact-modal="<?= htmlspecialcharsbx($modalId) ?>" aria-hidden="true">
  <div class="contact-modal__overlay" data-contact-modal-close></div>

  <div class="contact-modal__dialog" role="dialog" aria-modal="true" aria-label="<?= htmlspecialcharsbx($title) ?>">
    <button class="contact-modal__close" type="button" aria-label="Закрыть" data-contact-modal-close>
      <span aria-hidden="true">×</span>
    </button>

    <div class="contact-modal__content">
      <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/contact-form.php"; ?>
    </div>
  </div>
</div>
