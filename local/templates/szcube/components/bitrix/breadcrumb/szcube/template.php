<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult)) {
    return;
}
?>

<nav class="breadcrumbs" aria-label="Навигация">
    <ol class="breadcrumbs__list">
        <?php foreach ($arResult as $index => $item): ?>
            <?php
            $title = isset($item["TITLE"]) ? (string)$item["TITLE"] : "";
            $link = isset($item["LINK"]) ? (string)$item["LINK"] : "";
            $isLast = $index === (count($arResult) - 1);
            ?>
            <li class="breadcrumbs__item">
                <?php if ($link !== "" && !$isLast): ?>
                    <a class="breadcrumbs__link" href="<?= htmlspecialcharsbx($link) ?>"><?= htmlspecialcharsbx($title) ?></a>
                <?php else: ?>
                    <span class="breadcrumbs__current" aria-current="page"><?= htmlspecialcharsbx($title) ?></span>
                <?php endif; ?>

                <?php if (!$isLast): ?>
                    <span class="breadcrumbs__sep" aria-hidden="true">/</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>

