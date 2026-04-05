<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$mortgageCalculatorPageCode = isset($mortgageCalculatorPageCode) && is_string($mortgageCalculatorPageCode) && $mortgageCalculatorPageCode !== ""
    ? $mortgageCalculatorPageCode
    : (isset($mortgageHeroPageCode) && is_string($mortgageHeroPageCode) ? $mortgageHeroPageCode : "");

$mortgageCalculatorPage = function_exists("szcubeGetPurchasePage")
    ? szcubeGetPurchasePage($mortgageCalculatorPageCode)
    : array();
$mortgageCalculatorPrograms = function_exists("szcubeGetMortgageCalculatorPrograms")
    ? szcubeGetMortgageCalculatorPrograms()
    : array();
$mortgageCalculatorBanks = function_exists("szcubeGetMortgageCalculatorBanks")
    ? szcubeGetMortgageCalculatorBanks()
    : array();

if ($mortgageCalculatorPageCode === "" || empty($mortgageCalculatorPage) || empty($mortgageCalculatorPrograms) || empty($mortgageCalculatorPage["show_calculator"])) {
    return;
}

$mortgageCalculatorFormatRate = static function ($value) {
    $value = (float)$value;
    if ((float)(int)$value === $value) {
        return (string)(int)$value;
    }

    return rtrim(rtrim(number_format($value, 2, ".", ""), "0"), ".");
};
?>

<section class="mortgage-calculator" id="calculator">
    <div class="container">
        <div class="mortgage-calculator__head">
            <h2 class="section-title mortgage-calculator__title"><?= htmlspecialcharsbx((string)$mortgageCalculatorPage["calculator_title"]) ?></h2>
            <?php if (trim((string)$mortgageCalculatorPage["calculator_subtitle"]) !== ""): ?>
                <p class="mortgage-calculator__subtitle">
                    <?= htmlspecialcharsbx((string)$mortgageCalculatorPage["calculator_subtitle"]) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="mortgage-calculator__card" data-mortgage-calculator>
            <div class="mortgage-calculator__programs" role="tablist" aria-label="Программы ипотеки">
                <?php foreach ($mortgageCalculatorPrograms as $index => $program): ?>
                    <?php $isActive = $index === 0; ?>
                    <button
                        class="mortgage-calculator__program<?= $isActive ? " is-active" : "" ?>"
                        type="button"
                        role="tab"
                        aria-selected="<?= $isActive ? "true" : "false" ?>"
                        data-program-code="<?= htmlspecialcharsbx((string)$program["code"]) ?>"
                        data-program-label="<?= htmlspecialcharsbx((string)$program["label"]) ?>"
                        data-program-rate="<?= htmlspecialcharsbx($mortgageCalculatorFormatRate($program["rate"])) ?>"
                    >
                        <span class="mortgage-calculator__program-name"><?= htmlspecialcharsbx((string)$program["label"]) ?></span>
                        <span class="mortgage-calculator__program-rate"><?= htmlspecialcharsbx($mortgageCalculatorFormatRate($program["rate"])) ?>%</span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="mortgage-calculator__controls">
                <div class="mortgage-calculator__control">
                    <div class="mortgage-calculator__control-head">
                        <span class="mortgage-calculator__label">Стоимость квартиры</span>
                        <div class="mortgage-calculator__value-box">
                            <output class="mortgage-calculator__value" data-calculator-value="price">10 000 000 ₽</output>
                        </div>
                    </div>
                    <div class="mortgage-calculator__slider" data-calculator-slider="price"></div>
                    <div class="mortgage-calculator__limits">
                        <span data-calculator-limit="price-min">от 3 000 000 ₽</span>
                        <span data-calculator-limit="price-max">до 30 000 000 ₽</span>
                    </div>
                </div>

                <div class="mortgage-calculator__control">
                    <div class="mortgage-calculator__control-head">
                        <span class="mortgage-calculator__label">Первоначальный взнос</span>
                        <div class="mortgage-calculator__value-box mortgage-calculator__value-box--split">
                            <output class="mortgage-calculator__value" data-calculator-value="down-payment">3 000 000 ₽</output>
                            <span class="mortgage-calculator__badge" data-calculator-value="down-percent">30%</span>
                        </div>
                    </div>
                    <div class="mortgage-calculator__slider" data-calculator-slider="down-percent"></div>
                    <div class="mortgage-calculator__limits">
                        <span data-calculator-limit="down-min">от 2 000 000 ₽</span>
                        <span data-calculator-limit="down-max">до 8 000 000 ₽</span>
                    </div>
                </div>

                <div class="mortgage-calculator__control">
                    <div class="mortgage-calculator__control-head">
                        <span class="mortgage-calculator__label">Срок кредита</span>
                        <div class="mortgage-calculator__value-box">
                            <output class="mortgage-calculator__value" data-calculator-value="term">20 лет</output>
                        </div>
                    </div>
                    <div class="mortgage-calculator__slider" data-calculator-slider="term"></div>
                    <div class="mortgage-calculator__limits">
                        <span data-calculator-limit="term-min">от 1 года</span>
                        <span data-calculator-limit="term-max">до 30 лет</span>
                    </div>
                </div>
            </div>

            <div class="mortgage-calculator__results-shell">
                <div class="mortgage-calculator__summary">
                    <div class="mortgage-calculator__summary-copy">
                        <span class="mortgage-calculator__summary-label" data-calculator-result="program-label">Обычная ипотека</span>
                        <span class="mortgage-calculator__summary-separator" aria-hidden="true"></span>
                        <span class="mortgage-calculator__summary-rate">ставка <span data-calculator-result="rate-inline">21%</span></span>
                    </div>

                    <button
                        class="btn btn--primary mortgage-calculator__cta"
                        type="button"
                        data-contact-open="contact"
                        data-contact-title="Ипотечный калькулятор: консультация"
                        data-contact-type="callback"
                        data-contact-source="mortgage_calculator"
                        data-contact-note=""
                    >
                        Получить консультацию
                    </button>
                </div>

                <div class="mortgage-calculator__results" role="list">
                    <div class="mortgage-calculator__result" role="listitem">
                        <span class="mortgage-calculator__result-label">Ежемесячный платеж</span>
                        <strong class="mortgage-calculator__result-value" data-calculator-result="monthly-payment">0 ₽</strong>
                    </div>
                    <div class="mortgage-calculator__result" role="listitem">
                        <span class="mortgage-calculator__result-label">Процентная ставка</span>
                        <strong class="mortgage-calculator__result-value" data-calculator-result="interest-rate">21%</strong>
                    </div>
                    <div class="mortgage-calculator__result" role="listitem">
                        <span class="mortgage-calculator__result-label">Сумма кредита</span>
                        <strong class="mortgage-calculator__result-value" data-calculator-result="loan-amount">0 ₽</strong>
                    </div>
                    <div class="mortgage-calculator__result" role="listitem">
                        <span class="mortgage-calculator__result-label">Переплата по кредиту</span>
                        <strong class="mortgage-calculator__result-value" data-calculator-result="overpayment">0 ₽</strong>
                    </div>
                    <div class="mortgage-calculator__result" role="listitem">
                        <span class="mortgage-calculator__result-label">Общая сумма выплат</span>
                        <strong class="mortgage-calculator__result-value" data-calculator-result="total-payment">0 ₽</strong>
                    </div>
                </div>
            </div>

            <div class="mortgage-calculator__banks">
                <div class="mortgage-calculator__banks-head">
                    <h3 class="mortgage-calculator__banks-title">Банки-партнеры</h3>
                    <p class="mortgage-calculator__banks-text">Поможем сопоставить ставки и подобрать рабочий сценарий сделки под ваш бюджет.</p>
                </div>

                <div class="mortgage-calculator__bank-list" role="list">
                    <?php foreach ($mortgageCalculatorBanks as $bank): ?>
                        <article class="mortgage-calculator__bank" role="listitem">
                            <span
                                class="mortgage-calculator__bank-mark"
                                style="--mortgage-bank-tone: <?= htmlspecialcharsbx((string)$bank["tone"]) ?>; --mortgage-bank-accent: <?= htmlspecialcharsbx((string)$bank["accent"]) ?>;"
                                aria-hidden="true"
                            >
                                <?= htmlspecialcharsbx((string)$bank["mark"]) ?>
                            </span>
                            <span class="mortgage-calculator__bank-name"><?= htmlspecialcharsbx((string)$bank["label"]) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
