document.addEventListener("DOMContentLoaded", () => {
  const root = document.querySelector("[data-mortgage-calculator]");

  if (!root || !window.noUiSlider) {
    return;
  }

  const priceSlider = root.querySelector('[data-calculator-slider="price"]');
  const downPercentSlider = root.querySelector('[data-calculator-slider="down-percent"]');
  const termSlider = root.querySelector('[data-calculator-slider="term"]');
  const programButtons = Array.from(root.querySelectorAll("[data-program-code]"));
  const contactButton = root.querySelector(".mortgage-calculator__cta");

  if (!priceSlider || !downPercentSlider || !termSlider || !programButtons.length) {
    return;
  }

  const config = {
    price: {
      min: 3000000,
      max: 30000000,
      step: 100000,
      start: 10000000,
    },
    downPercent: {
      min: 20,
      max: 80,
      step: 1,
      start: 30,
    },
    term: {
      min: 1,
      max: 30,
      step: 1,
      start: 20,
    },
  };

  const formatCurrency = (value) =>
    `${Math.round(value).toLocaleString("ru-RU")} ₽`;

  const formatYears = (value) => {
    const years = Math.round(value);
    const mod10 = years % 10;
    const mod100 = years % 100;

    let suffix = "лет";
    if (mod10 === 1 && mod100 !== 11) {
      suffix = "год";
    } else if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
      suffix = "года";
    }

    return `${years} ${suffix}`;
  };

  const formatPercent = (value) =>
    `${Number(value).toLocaleString("ru-RU", {
      minimumFractionDigits: Number.isInteger(value) ? 0 : 1,
      maximumFractionDigits: 1,
    })}%`;

  const readSliderValue = (slider) => {
    const value = slider.noUiSlider.get();
    return Array.isArray(value) ? Number(value[0]) : Number(value);
  };

  const setText = (selector, value) => {
    const node = root.querySelector(selector);
    if (node) {
      node.textContent = value;
    }
  };

  const renderValues = () => {
    const price = readSliderValue(priceSlider);
    const downPercent = readSliderValue(downPercentSlider);
    const termYears = readSliderValue(termSlider);
    const activeProgram = root.querySelector("[data-program-code].is-active");
    const rate = activeProgram ? Number(activeProgram.dataset.programRate) : 21;
    const programLabel = activeProgram ? activeProgram.dataset.programLabel || "Обычная ипотека" : "Обычная ипотека";

    const downPayment = Math.round((price * downPercent) / 100);
    const loanAmount = Math.max(price - downPayment, 0);
    const months = Math.max(Math.round(termYears) * 12, 1);
    const monthlyRate = rate / 12 / 100;
    const monthlyPayment =
      loanAmount > 0
        ? (loanAmount * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -months))
        : 0;
    const totalPayment = monthlyPayment * months;
    const overpayment = Math.max(totalPayment - loanAmount, 0);

    setText('[data-calculator-value="price"]', formatCurrency(price));
    setText('[data-calculator-value="down-payment"]', formatCurrency(downPayment));
    setText('[data-calculator-value="down-percent"]', formatPercent(downPercent));
    setText('[data-calculator-value="term"]', formatYears(termYears));

    setText('[data-calculator-limit="price-min"]', `от ${formatCurrency(config.price.min)}`);
    setText('[data-calculator-limit="price-max"]', `до ${formatCurrency(config.price.max)}`);
    setText(
      '[data-calculator-limit="down-min"]',
      `от ${formatCurrency((price * config.downPercent.min) / 100)}`
    );
    setText(
      '[data-calculator-limit="down-max"]',
      `до ${formatCurrency((price * config.downPercent.max) / 100)}`
    );
    setText('[data-calculator-limit="term-min"]', `от ${formatYears(config.term.min)}`);
    setText('[data-calculator-limit="term-max"]', `до ${formatYears(config.term.max)}`);

    setText('[data-calculator-result="program-label"]', programLabel);
    setText('[data-calculator-result="rate-inline"]', formatPercent(rate));
    setText('[data-calculator-result="monthly-payment"]', formatCurrency(monthlyPayment));
    setText('[data-calculator-result="interest-rate"]', formatPercent(rate));
    setText('[data-calculator-result="loan-amount"]', formatCurrency(loanAmount));
    setText('[data-calculator-result="overpayment"]', formatCurrency(overpayment));
    setText('[data-calculator-result="total-payment"]', formatCurrency(totalPayment));

    if (contactButton) {
      contactButton.dataset.contactNote =
        `${programLabel}; ставка ${formatPercent(rate)}; стоимость ${formatCurrency(price)}; ` +
        `первый взнос ${formatCurrency(downPayment)} (${formatPercent(downPercent)}); ` +
        `срок ${formatYears(termYears)}; ежемесячный платеж ${formatCurrency(monthlyPayment)}`;
    }
  };

  window.noUiSlider.create(priceSlider, {
    start: [config.price.start],
    connect: [true, false],
    step: config.price.step,
    range: { min: config.price.min, max: config.price.max },
  });

  window.noUiSlider.create(downPercentSlider, {
    start: [config.downPercent.start],
    connect: [true, false],
    step: config.downPercent.step,
    range: { min: config.downPercent.min, max: config.downPercent.max },
  });

  window.noUiSlider.create(termSlider, {
    start: [config.term.start],
    connect: [true, false],
    step: config.term.step,
    range: { min: config.term.min, max: config.term.max },
  });

  [priceSlider, downPercentSlider, termSlider].forEach((slider) => {
    slider.noUiSlider.on("update", renderValues);
  });

  programButtons.forEach((button) => {
    button.addEventListener("click", () => {
      programButtons.forEach((item) => {
        const isActive = item === button;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-selected", isActive ? "true" : "false");
      });

      renderValues();
    });
  });

  renderValues();
});
