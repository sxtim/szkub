<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$mortgageHeroPageCode = isset($mortgageHeroPageCode) && is_string($mortgageHeroPageCode) && $mortgageHeroPageCode !== ""
    ? $mortgageHeroPageCode
    : "mortgage";

$mortgageHeroPages = array(
    "mortgage" => array(
        "tab_label" => "Ипотека",
        "url" => "/mortgage/",
        "title" => "Квартира в ипотеку",
        "paragraphs" => array(
            "Поможем оформить заявку на ипотеку так, чтобы ее одобрили. Работаем с банками-партнерами и подбираем программу под ваш первый взнос, срок и комфортный ежемесячный платеж.",
            "Подскажем, как собрать пакет документов, рассчитать нагрузку и выбрать квартиру, которую удобно покупать не только по ставке, но и по общему сценарию сделки.",
        ),
        "primary_label" => "Оставить заявку",
        "primary_title" => "Ипотека: оставить заявку",
        "primary_note" => "Ипотека: заявка с первой секции страницы",
        "primary_source" => "mortgage_primary",
        "link_label" => "Выбрать квартиру в ипотеку",
        "link_url" => "/apartments/",
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-19-53.jpg",
        "image_alt" => "Подбор квартиры в ипотеку",
    ),
    "maternal-capital" => array(
        "tab_label" => "Материнский капитал",
        "url" => "/maternal-capital/",
        "title" => "Квартира с материнским капиталом",
        "paragraphs" => array(
            "Подскажем, как использовать материнский капитал в качестве первоначального взноса или для уменьшения кредитной нагрузки. Помогаем совместить господдержку с текущими банковскими программами.",
            "Покажем, какие форматы квартир подходят под семейный сценарий, и заранее разложим сделку по шагам, документам и срокам перечисления средств.",
        ),
        "primary_label" => "Оставить заявку",
        "primary_title" => "Материнский капитал: оставить заявку",
        "primary_note" => "Материнский капитал: заявка с первой секции страницы",
        "primary_source" => "maternal_capital_primary",
        "link_label" => "Подобрать квартиру с материнским капиталом",
        "link_url" => "/apartments/",
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-18-25.jpg",
        "image_alt" => "Покупка квартиры с материнским капиталом",
    ),
    "installment" => array(
        "tab_label" => "Рассрочка",
        "url" => "/installment/",
        "title" => "Покупка в рассрочку",
        "paragraphs" => array(
            "Если ипотека не подходит под текущий сценарий, предложим рассрочку на понятных условиях и соберем схему оплаты под ваш ритм сделки. Важно не просто сохранить бюджет, а выбрать удобный график без лишней нагрузки.",
            "Подскажем, на какие квартиры распространяется рассрочка, какой нужен первоначальный взнос и как лучше сочетать ее с текущими предложениями по проектам.",
        ),
        "primary_label" => "Оставить заявку",
        "primary_title" => "Рассрочка: оставить заявку",
        "primary_note" => "Рассрочка: заявка с первой секции страницы",
        "primary_source" => "installment_primary",
        "link_label" => "Выбрать квартиру в рассрочку",
        "link_url" => "/apartments/",
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-19-53.jpg",
        "image_alt" => "Покупка квартиры в рассрочку",
    ),
    "military-mortgage" => array(
        "tab_label" => "Военная ипотека",
        "url" => "/military-mortgage/",
        "title" => "Военная ипотека",
        "paragraphs" => array(
            "Расскажем, как использовать накопительно-ипотечную систему для покупки квартиры в проектах КУБ. Помогаем пройти путь от проверки программы до выбора подходящего лота и согласования сделки.",
            "Сверим условия банка, подскажем по пакету документов и соберем маршрут покупки так, чтобы все этапы были понятны заранее.",
        ),
        "primary_label" => "Оставить заявку",
        "primary_title" => "Военная ипотека: оставить заявку",
        "primary_note" => "Военная ипотека: заявка с первой секции страницы",
        "primary_source" => "military_mortgage_primary",
        "link_label" => "Посмотреть квартиры по военной ипотеке",
        "link_url" => "/apartments/",
        "image" => SITE_TEMPLATE_PATH . "/img/promotions/photo_2026-02-25_01-16-59.jpg",
        "image_alt" => "Военная ипотека в КУБ",
    ),
);

if (!isset($mortgageHeroPages[$mortgageHeroPageCode])) {
    $mortgageHeroPageCode = "mortgage";
}

$mortgageHeroCurrent = $mortgageHeroPages[$mortgageHeroPageCode];
?>

<section class="mortgage-hero">
    <div class="container">
        <div class="mortgage-hero__shell">
            <nav class="mortgage-tabs" aria-label="Программы покупки">
                <?php foreach ($mortgageHeroPages as $pageCode => $page): ?>
                    <?php $isActive = $pageCode === $mortgageHeroPageCode; ?>
                    <a
                        class="mortgage-tabs__link<?= $isActive ? " is-active" : "" ?>"
                        href="<?= htmlspecialcharsbx($page["url"]) ?>"
                        <?= $isActive ? ' aria-current="page"' : "" ?>
                    >
                        <?= htmlspecialcharsbx($page["tab_label"]) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="mortgage-hero__panel">
                <div class="mortgage-hero__card">
                    <div class="mortgage-hero__content">
                        <div class="mortgage-hero__intro">
                            <h1 class="mortgage-hero__title"><?= htmlspecialcharsbx($mortgageHeroCurrent["title"]) ?></h1>

                            <div class="mortgage-hero__text">
                            <?php foreach ($mortgageHeroCurrent["paragraphs"] as $paragraph): ?>
                                <?php if (!is_string($paragraph) || trim($paragraph) === "") { continue; } ?>
                                <p><?= htmlspecialcharsbx($paragraph) ?></p>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mortgage-hero__footer">
                        <div class="mortgage-hero__actions">
                            <button
                                    class="btn btn--primary mortgage-hero__button mortgage-hero__button--primary"
                                    type="button"
                                    data-contact-open="contact"
                                    data-contact-title="<?= htmlspecialcharsbx($mortgageHeroCurrent["primary_title"]) ?>"
                                    data-contact-type="callback"
                                    data-contact-source="<?= htmlspecialcharsbx($mortgageHeroCurrent["primary_source"]) ?>"
                                data-contact-note="<?= htmlspecialcharsbx($mortgageHeroCurrent["primary_note"]) ?>"
                            >
                                <?= htmlspecialcharsbx($mortgageHeroCurrent["primary_label"]) ?>
                            </button>
                        </div>

                        <a class="btn btn--outline mortgage-hero__link" href="<?= htmlspecialcharsbx($mortgageHeroCurrent["link_url"]) ?>">
                            <span><?= htmlspecialcharsbx($mortgageHeroCurrent["link_label"]) ?></span>
                        </a>
                        </div>
                    </div>

                    <div class="mortgage-hero__media">
                        <img
                            src="<?= htmlspecialcharsbx($mortgageHeroCurrent["image"]) ?>"
                            alt="<?= htmlspecialcharsbx($mortgageHeroCurrent["image_alt"]) ?>"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
