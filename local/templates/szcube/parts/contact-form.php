<?php
$contactFormId = isset($contactFormId) && is_string($contactFormId) && $contactFormId !== "" ? $contactFormId : "contact-form";
$contactFormTitle = isset($contactFormTitle) && is_string($contactFormTitle) && $contactFormTitle !== "" ? $contactFormTitle : "Остались вопросы?";
$contactFormTitleAttr = isset($contactFormTitleAttr) && is_string($contactFormTitleAttr) ? trim($contactFormTitleAttr) : "";
?>
<form class="contact-form" id="<?= htmlspecialcharsbx($contactFormId) ?>">
  <h3 class="contact-form__title" <?= $contactFormTitleAttr !== "" ? htmlspecialcharsbx($contactFormTitleAttr) : "" ?>>
    <?= htmlspecialcharsbx($contactFormTitle) ?>
  </h3>
  <label class="contact-form__field">
    <span class="contact-form__label">Ваше имя</span>
    <input class="contact-form__input" type="text" placeholder="Имя" />
  </label>
  <label class="contact-form__field">
    <span class="contact-form__label">Телефон</span>
    <input class="contact-form__input" type="tel" placeholder="+7 999 999 99 99" />
  </label>
  <button class="btn btn--primary contact-form__submit" type="button">Отправить</button>
  <label class="contact-form__agree">
    <input type="checkbox" checked />
    Отправляя обращение, я принимаю условия Пользовательского соглашения и даю свое
    согласие на обработку моих персональных данных в соответствии с Политикой
    конфиденциальности
  </label>
</form>
