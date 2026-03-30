<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$apartmentCard = isset($apartmentCard) && is_array($apartmentCard) ? $apartmentCard : array();
$cardUrl = isset($apartmentCard["url"]) ? trim((string)$apartmentCard["url"]) : "";
$projectName = isset($apartmentCard["project_name"]) ? trim((string)$apartmentCard["project_name"]) : "";
$projectDelivery = isset($apartmentCard["project_delivery"]) ? trim((string)$apartmentCard["project_delivery"]) : "";
$roomsLabel = isset($apartmentCard["rooms_label"]) ? trim((string)$apartmentCard["rooms_label"]) : "Квартира";
$listMeta = isset($apartmentCard["list_meta"]) ? trim((string)$apartmentCard["list_meta"]) : "";
$priceTotal = isset($apartmentCard["price_total_formatted"]) ? trim((string)$apartmentCard["price_total_formatted"]) : "";
$priceOld = isset($apartmentCard["price_old_formatted"]) ? trim((string)$apartmentCard["price_old_formatted"]) : "";
$statusLabel = isset($apartmentCard["status_label"]) ? trim((string)$apartmentCard["status_label"]) : "";
$planImage = isset($apartmentCard["plan_image"]) ? trim((string)$apartmentCard["plan_image"]) : "";
$planAlt = isset($apartmentCard["plan_alt"]) ? trim((string)$apartmentCard["plan_alt"]) : $roomsLabel;
$badges = isset($apartmentCard["badges"]) && is_array($apartmentCard["badges"]) ? $apartmentCard["badges"] : array();
$boardUrl = isset($apartmentCard["board_url"]) ? trim((string)$apartmentCard["board_url"]) : "";
?>
<article class="apartment-card apartment-similar__card"<?= $cardUrl !== "" ? ' data-card-url="' . htmlspecialcharsbx($cardUrl) . '"' : "" ?> tabindex="0" role="link">
  <div class="apartment-card__head">
    <div>
      <?php if ($projectName !== ""): ?>
      <span class="apartment-card__project"><?= htmlspecialcharsbx($projectName) ?></span>
      <?php endif; ?>
      <?php if ($projectDelivery !== ""): ?>
      <span class="apartment-card__date">Сдача <?= htmlspecialcharsbx($projectDelivery) ?></span>
      <?php endif; ?>
    </div>
    <div class="apartment-card__actions">
      <?php if ($boardUrl !== ""): ?>
      <button
        class="apartment-card__action apartment-card__board"
        type="button"
        data-board-url="<?= htmlspecialcharsbx($boardUrl) ?>"
        aria-label="Показать на шахматке"
        title="Показать на шахматке"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M3.5 3.5H7.25V7.25H3.5V3.5Z" stroke="currentColor" stroke-width="1.2"/>
          <path d="M10.75 3.5H14.5V7.25H10.75V3.5Z" stroke="currentColor" stroke-width="1.2"/>
          <path d="M3.5 10.75H7.25V14.5H3.5V10.75Z" stroke="currentColor" stroke-width="1.2"/>
          <path d="M10.75 10.75H14.5V14.5H10.75V10.75Z" stroke="currentColor" stroke-width="1.2"/>
        </svg>
      </button>
      <?php endif; ?>
      <button class="apartment-card__action apartment-card__fav" type="button" aria-label="В избранное" title="В избранное">
        <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
  </div>

  <div class="apartment-card__plan">
    <?php if ($planImage !== ""): ?>
    <img class="apartment-card__plan-image" src="<?= htmlspecialcharsbx($planImage) ?>" alt="<?= htmlspecialcharsbx($planAlt) ?>" loading="lazy" />
    <?php endif; ?>
  </div>

  <div class="apartment-card__meta"><?= htmlspecialcharsbx($listMeta) ?></div>

  <div class="apartment-card__price">
    <?php if ($priceTotal !== ""): ?>
    <span class="apartment-card__price-main"><?= htmlspecialcharsbx($priceTotal) ?></span>
    <?php endif; ?>
    <?php if ($priceOld !== ""): ?>
    <span class="apartment-card__price-old"><?= htmlspecialcharsbx($priceOld) ?></span>
    <?php endif; ?>
  </div>

  <?php if (!empty($badges)): ?>
  <div class="apartment-card__badges">
    <?php foreach ($badges as $badge): ?>
      <?php $badge = trim((string)$badge); ?>
      <?php if ($badge === "") { continue; } ?>
      <span class="apartment-card__badge"><?= htmlspecialcharsbx($badge) ?></span>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="apartment-card__list">
    <div class="apartment-card__summary">
      <div class="apartment-card__rooms"><?= htmlspecialcharsbx($roomsLabel) ?></div>
      <div class="apartment-card__area"><?= htmlspecialcharsbx($listMeta) ?></div>
    </div>
    <div class="apartment-card__delivery">
      <?php if ($projectName !== ""): ?>
      <div class="apartment-card__delivery-project"><?= htmlspecialcharsbx($projectName) ?></div>
      <?php endif; ?>
      <?php if ($projectDelivery !== ""): ?>
      <div>Сдача <?= htmlspecialcharsbx($projectDelivery) ?></div>
      <?php endif; ?>
    </div>
    <div class="apartment-card__list-price"><?= htmlspecialcharsbx($priceTotal) ?></div>
    <?php if ($statusLabel !== ""): ?>
    <span class="apartment-card__label"><?= htmlspecialcharsbx($statusLabel) ?></span>
    <?php else: ?>
    <span></span>
    <?php endif; ?>
    <div class="apartment-card__icons">
      <?php if ($boardUrl !== ""): ?>
      <button
        class="apartment-card__icon apartment-card__action apartment-card__board"
        type="button"
        data-board-url="<?= htmlspecialcharsbx($boardUrl) ?>"
        aria-label="Показать на шахматке"
        title="Показать на шахматке"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M3.5 3.5H7.25V7.25H3.5V3.5Z" stroke="currentColor" stroke-width="1.2"/>
          <path d="M10.75 3.5H14.5V7.25H10.75V3.5Z" stroke="currentColor" stroke-width="1.2"/>
          <path d="M3.5 10.75H7.25V14.5H3.5V10.75Z" stroke="currentColor" stroke-width="1.2"/>
          <path d="M10.75 10.75H14.5V14.5H10.75V10.75Z" stroke="currentColor" stroke-width="1.2"/>
        </svg>
      </button>
      <?php endif; ?>
      <button class="apartment-card__icon apartment-card__action apartment-card__fav" type="button" aria-label="В избранное" title="В избранное">
        <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
  </div>
</article>
