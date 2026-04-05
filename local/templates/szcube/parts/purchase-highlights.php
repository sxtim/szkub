<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$purchaseHighlightsPageCode = isset($purchaseHighlightsPageCode) && is_string($purchaseHighlightsPageCode) && $purchaseHighlightsPageCode !== ""
    ? $purchaseHighlightsPageCode
    : "";

$purchaseHighlightsCards = function_exists("szcubeGetPurchaseCards")
    ? szcubeGetPurchaseCards($purchaseHighlightsPageCode)
    : array();

if ($purchaseHighlightsPageCode === "" || empty($purchaseHighlightsCards)) {
    return;
}
?>

<section class="purchase-highlights">
    <div class="container">
        <div class="purchase-highlights__grid" role="list">
            <?php foreach ($purchaseHighlightsCards as $card): ?>
                <?php
                    $cardLayout = isset($card["layout"]) && is_string($card["layout"]) ? trim($card["layout"]) : "default";
                    $cardClasses = array("purchase-highlights__card");
                    if ($cardLayout !== "") {
                        $cardClasses[] = "purchase-highlights__card--" . $cardLayout;
                    }
                ?>
                <article class="<?= htmlspecialcharsbx(implode(" ", $cardClasses)) ?>" role="listitem">
                    <div class="purchase-highlights__media">
                        <?php if (trim((string)$card["image"]) !== ""): ?>
                            <img
                                src="<?= htmlspecialcharsbx((string)$card["image"]) ?>"
                                alt="<?= htmlspecialcharsbx((string)$card["image_alt"]) ?>"
                                loading="lazy"
                            >
                        <?php endif; ?>
                    </div>
                    <div class="purchase-highlights__body">
                        <h2 class="purchase-highlights__title"><?= htmlspecialcharsbx((string)$card["title"]) ?></h2>
                        <?php if (trim((string)$card["text"]) !== ""): ?>
                            <p class="purchase-highlights__text"><?= htmlspecialcharsbx((string)$card["text"]) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>

        </div>
    </div>
</section>
