<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$catalogPageIntro = isset($catalogPageIntro) && is_array($catalogPageIntro) ? $catalogPageIntro : array();
$introText1 = trim((string)($catalogPageIntro["intro_text_1"] ?? ""));
$introText2 = trim((string)($catalogPageIntro["intro_text_2"] ?? ""));
$introImage = trim((string)($catalogPageIntro["intro_image"] ?? ""));
$introImageAlt = trim((string)($catalogPageIntro["intro_image_alt"] ?? ""));

if ($introText1 === "" && $introText2 === "" && $introImage === "") {
    return;
}
?>
<section class="parking-intro">
  <div class="container">
    <div class="parking-intro__card">
      <div class="parking-intro__copy">
        <div class="parking-intro__text">
          <?php if ($introText1 !== ""): ?>
            <p><?=htmlspecialchars($introText1, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?></p>
          <?php endif; ?>
          <?php if ($introText2 !== ""): ?>
            <p><?=htmlspecialchars($introText2, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?></p>
          <?php endif; ?>
        </div>
      </div>
      <?php if ($introImage !== ""): ?>
        <div class="parking-intro__media" aria-hidden="true">
          <img src="<?=htmlspecialchars($introImage, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?>" alt="<?=htmlspecialchars($introImageAlt, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8")?>" loading="lazy">
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
