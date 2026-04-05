<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$mortgageHeroPageCode = isset($mortgageHeroPageCode) && is_string($mortgageHeroPageCode) && $mortgageHeroPageCode !== ""
    ? $mortgageHeroPageCode
    : "";

$mortgageHeroPages = function_exists("szcubeGetPurchasePages") ? szcubeGetPurchasePages() : array();
$mortgageHeroCurrent = function_exists("szcubeGetPurchasePage") ? szcubeGetPurchasePage($mortgageHeroPageCode) : array();

if ($mortgageHeroPageCode === "" || empty($mortgageHeroPages) || empty($mortgageHeroCurrent)) {
    return;
}
?>

<section class="mortgage-hero">
    <div class="container">
        <div class="mortgage-hero__shell">
            <nav class="mortgage-tabs" aria-label="Программы покупки">
                <?php foreach ($mortgageHeroPages as $page): ?>
                    <?php
                        $pageCode = isset($page["code"]) ? (string)$page["code"] : "";
                        $isActive = $pageCode === $mortgageHeroPageCode;
                    ?>
                    <a
                        class="mortgage-tabs__link<?= $isActive ? " is-active" : "" ?>"
                        href="<?= htmlspecialcharsbx((string)$page["url"]) ?>"
                        <?= $isActive ? ' aria-current="page"' : "" ?>
                    >
                        <?= htmlspecialcharsbx((string)$page["label"]) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mortgage-hero__panel">
                <div class="mortgage-hero__card">
                    <div class="mortgage-hero__content">
                        <div class="mortgage-hero__intro">
                            <h1 class="mortgage-hero__title"><?= htmlspecialcharsbx((string)$mortgageHeroCurrent["hero_title"]) ?></h1>

                            <div class="mortgage-hero__text">
                            <?php foreach ((array)$mortgageHeroCurrent["hero_paragraphs"] as $paragraph): ?>
                                <?php if (!is_string($paragraph) || trim($paragraph) === "") { continue; } ?>
                                <p><?= htmlspecialcharsbx($paragraph) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mortgage-hero__footer">
                        <div class="mortgage-hero__actions">
                            <?php if (trim((string)$mortgageHeroCurrent["primary_button_label"]) !== ""): ?>
                                <button
                                        class="btn btn--primary mortgage-hero__button mortgage-hero__button--primary"
                                        type="button"
                                        data-contact-open="contact"
                                        data-contact-title="<?= htmlspecialcharsbx((string)$mortgageHeroCurrent["primary_button_title"]) ?>"
                                        data-contact-type="callback"
                                        data-contact-source="<?= htmlspecialcharsbx((string)$mortgageHeroCurrent["primary_button_source"]) ?>"
                                    data-contact-note="<?= htmlspecialcharsbx((string)$mortgageHeroCurrent["primary_button_note"]) ?>"
                                >
                                    <?= htmlspecialcharsbx((string)$mortgageHeroCurrent["primary_button_label"]) ?>
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if (trim((string)$mortgageHeroCurrent["secondary_button_label"]) !== "" && trim((string)$mortgageHeroCurrent["secondary_button_url"]) !== ""): ?>
                            <a class="btn btn--outline mortgage-hero__link" href="<?= htmlspecialcharsbx((string)$mortgageHeroCurrent["secondary_button_url"]) ?>">
                                <span><?= htmlspecialcharsbx((string)$mortgageHeroCurrent["secondary_button_label"]) ?></span>
                            </a>
                        <?php endif; ?>
                        </div>
                    </div>

                    <div class="mortgage-hero__media">
                        <?php if (trim((string)$mortgageHeroCurrent["hero_image"]) !== ""): ?>
                            <img
                                src="<?= htmlspecialcharsbx((string)$mortgageHeroCurrent["hero_image"]) ?>"
                                alt="<?= htmlspecialcharsbx((string)$mortgageHeroCurrent["hero_image_alt"]) ?>"
                            >
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
