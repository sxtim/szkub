<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
  die();
}

if (!isset($dropdownIdPrefix) || $dropdownIdPrefix === "") {
  $dropdownIdPrefix = "city";
}
?>
<div class="filter__dropdown">
  <div class="filter__dropdown-menu-btn" type="button">Выберите город и ЖК</div>
  <div class="filter__dropdown-content">
    <div class="input_field">
      <input class="custom-checkbox" type="checkbox" id="<?= htmlspecialchars($dropdownIdPrefix) ?>-1" data-sync-group="city" data-sync-value="vertical">
      <label for="<?= htmlspecialchars($dropdownIdPrefix) ?>-1">ЖК Вертикаль</label>
    </div>
    <div class="input_field">
      <input class="custom-checkbox" type="checkbox" id="<?= htmlspecialchars($dropdownIdPrefix) ?>-2" data-sync-group="city" data-sync-value="collection">
      <label for="<?= htmlspecialchars($dropdownIdPrefix) ?>-2">ЖК Коллекция</label>
    </div>
    <div class="input_field">
      <input class="custom-checkbox" type="checkbox" id="<?= htmlspecialchars($dropdownIdPrefix) ?>-3" data-sync-group="city" data-sync-value="krasnoznamennaya">
      <label for="<?= htmlspecialchars($dropdownIdPrefix) ?>-3">ЖК Краснознаменная</label>
    </div>
  </div>
</div>
