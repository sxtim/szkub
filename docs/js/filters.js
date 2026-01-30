const formatNumber = (value, step) => {
  const rounded = step < 1 ? value : Math.round(value / step) * step;
  return Math.round(rounded).toLocaleString("ru-RU");
};

const initRangeSliders = () => {
  const sliders = document.querySelectorAll(".range-slider");

  sliders.forEach((slider) => {
    const min = Number(slider.dataset.min);
    const max = Number(slider.dataset.max);
    const start = Number(slider.dataset.start);
    const end = Number(slider.dataset.end);
    const step = Number(slider.dataset.step || 1);

    if (Number.isNaN(min) || Number.isNaN(max) || Number.isNaN(start) || Number.isNaN(end)) {
      return;
    }

    if (!window.noUiSlider) {
      return;
    }

    window.noUiSlider.create(slider, {
      start: [start, end],
      connect: true,
      step,
      range: { min, max },
    });

    const fromEl = document.querySelector(`[data-range-value="${slider.dataset.range}-from"]`);
    const toEl = document.querySelector(`[data-range-value="${slider.dataset.range}-to"]`);
    const fromInput = document.querySelector(`[data-range-input="${slider.dataset.range}-from"]`);
    const toInput = document.querySelector(`[data-range-input="${slider.dataset.range}-to"]`);

    slider.noUiSlider.on("update", (values) => {
      const fromVal = Number(values[0]);
      const toVal = Number(values[1]);

      if (fromEl) {
        fromEl.textContent = formatNumber(fromVal, step);
      }
      if (toEl) {
        toEl.textContent = formatNumber(toVal, step);
      }
      if (fromInput) {
        fromInput.value = Math.round(fromVal).toString();
      }
      if (toInput) {
        toInput.value = Math.round(toVal).toString();
      }
    });
  });
};

document.addEventListener("DOMContentLoaded", initRangeSliders);
