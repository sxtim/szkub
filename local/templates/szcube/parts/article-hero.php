<?php
if (!isset($articleHero) || !is_array($articleHero)) {
    $articleHero = array();
}

$title = isset($articleHero["title"]) ? (string)$articleHero["title"] : "";
$text = isset($articleHero["text"]) && is_array($articleHero["text"]) ? $articleHero["text"] : array();
$list = isset($articleHero["list"]) && is_array($articleHero["list"]) ? $articleHero["list"] : array();
$image = isset($articleHero["image"]) ? (string)$articleHero["image"] : "";
$extra = isset($articleHeroExtra) ? (string)$articleHeroExtra : "";

$showCta = isset($articleHeroShowCta) ? (bool)$articleHeroShowCta : false;
?>

<section class="article-hero">
    <div class="container">
        <div class="article-hero__layout">
            <div class="article-hero__left">
                <h1 class="article-hero__title"><?= htmlspecialcharsbx($title) ?></h1>

                <? foreach ($text as $paragraph): ?>
                    <p class="article-hero__text"><?= htmlspecialcharsbx((string)$paragraph) ?></p>
                <? endforeach; ?>

                <? if (!empty($list)): ?>
                    <ol class="article-hero__list">
                        <? foreach ($list as $li): ?>
                            <li><?= htmlspecialcharsbx((string)$li) ?></li>
                        <? endforeach; ?>
                    </ol>
                <? endif; ?>

                <? if ($showCta): ?>
                    <div class="article-hero__cta">
                        <button class="btn btn--primary" type="button" data-contact-open="contact" data-contact-title="Получить консультацию" data-contact-type="consulting" data-contact-source="article_hero">Получить консультацию</button>
                    </div>
                <? endif; ?>

                <? if ($extra !== ""): ?>
                    <?= $extra ?>
                <? endif; ?>
            </div>

            <div class="article-hero__media">
                <? if ($image !== ""): ?>
                    <img src="<?= htmlspecialcharsbx($image) ?>" alt="<?= htmlspecialcharsbx($title) ?>">
                <? endif; ?>
            </div>
        </div>
    </div>
</section>
