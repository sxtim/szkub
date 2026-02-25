<?php
$contactFormId = isset($contactFormId) && is_string($contactFormId) && $contactFormId !== "" ? $contactFormId : "contact-form";
$contactFormTitle = isset($contactFormTitle) && is_string($contactFormTitle) && $contactFormTitle !== "" ? $contactFormTitle : "Остались вопросы?";
$contactFormTitleAttr = isset($contactFormTitleAttr) && is_string($contactFormTitleAttr) ? trim($contactFormTitleAttr) : "";
$contactFormEndpoint = isset($contactFormEndpoint) && is_string($contactFormEndpoint) && $contactFormEndpoint !== "" ? $contactFormEndpoint : "/local/ajax/contact-form.php";
$contactFormLeadType = isset($contactFormLeadType) && is_string($contactFormLeadType) && $contactFormLeadType !== "" ? $contactFormLeadType : "callback";
$contactFormLeadSource = isset($contactFormLeadSource) && is_string($contactFormLeadSource) && $contactFormLeadSource !== "" ? $contactFormLeadSource : "unknown";
$contactFormSuccessText = isset($contactFormSuccessText) && is_string($contactFormSuccessText) && $contactFormSuccessText !== "" ? $contactFormSuccessText : "Спасибо! Мы свяжемся с вами в ближайшее время.";
?>
<form
  class="contact-form"
  id="<?= htmlspecialcharsbx($contactFormId) ?>"
  method="post"
  action="<?= htmlspecialcharsbx($contactFormEndpoint) ?>"
  data-contact-form
  data-contact-success-text="<?= htmlspecialcharsbx($contactFormSuccessText) ?>"
  novalidate
>
  <?php if (function_exists("bitrix_sessid_post")) { bitrix_sessid_post(); } ?>
  <input type="hidden" name="lead_type" value="<?= htmlspecialcharsbx($contactFormLeadType) ?>" data-contact-meta="lead-type" />
  <input type="hidden" name="lead_source" value="<?= htmlspecialcharsbx($contactFormLeadSource) ?>" data-contact-meta="lead-source" />
  <input type="hidden" name="page_url" value="" data-contact-meta="page-url" />
  <h3 class="contact-form__title" <?= $contactFormTitleAttr !== "" ? htmlspecialcharsbx($contactFormTitleAttr) : "" ?>>
    <?= htmlspecialcharsbx($contactFormTitle) ?>
  </h3>
  <label class="contact-form__field">
    <span class="contact-form__label">Ваше имя</span>
    <input class="contact-form__input" type="text" name="name" placeholder="Имя" autocomplete="name" required />
  </label>
  <label class="contact-form__field">
    <span class="contact-form__label">Телефон</span>
    <input class="contact-form__input" type="tel" name="phone" placeholder="+7 999 999 99 99" autocomplete="tel" inputmode="tel" required />
  </label>
  <button class="btn btn--primary contact-form__submit" type="submit">Отправить</button>
  <p class="contact-form__message" data-contact-form-message aria-live="polite" hidden></p>
  <label class="contact-form__agree" data-contact-consent-label>
    <input type="checkbox" name="consent" value="Y" checked required />
    Отправляя обращение, я принимаю условия Пользовательского соглашения и даю свое
    согласие на обработку моих персональных данных в соответствии с Политикой
    конфиденциальности
  </label>
</form>
