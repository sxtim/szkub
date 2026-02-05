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

const initFiltersPopup = () => {
  const popup = document.querySelector(".filters-popup");
  if (!popup) return;

  const openBtn = document.querySelector("[data-filters-open]");
  const closeButtons = popup.querySelectorAll("[data-filters-close]");

  const openPopup = () => {
    popup.classList.add("is-open");
    popup.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");
  };

  const closePopup = () => {
    popup.classList.remove("is-open");
    popup.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");
  };

  if (openBtn) {
    openBtn.addEventListener("click", (event) => {
      event.preventDefault();
      openPopup();
    });
  }

  closeButtons.forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.preventDefault();
      closePopup();
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && popup.classList.contains("is-open")) {
      closePopup();
    }
  });
};

const initFilterDropdowns = () => {
  const dropdowns = document.querySelectorAll(".filter__dropdown");
  if (!dropdowns.length) return;

  const closeAll = (except) => {
    dropdowns.forEach((dropdown) => {
      const btn = dropdown.querySelector(".filter__dropdown-menu-btn");
      const content = dropdown.querySelector(".filter__dropdown-content");
      if (!btn || !content || content === except) return;
      content.classList.remove("active");
      btn.classList.remove("open");
    });
  };

  dropdowns.forEach((dropdown) => {
    const btn = dropdown.querySelector(".filter__dropdown-menu-btn");
    const content = dropdown.querySelector(".filter__dropdown-content");
    if (!btn || !content) return;

    const defaultText = btn.textContent.trim();

    const updateButtonText = () => {
      const selected = Array.from(
        content.querySelectorAll('input[type="checkbox"]:checked')
      )
        .map((checkbox) =>
          checkbox.closest(".input_field")?.querySelector("label")?.textContent.trim()
        )
        .filter(Boolean);

      btn.textContent = selected.length ? selected.join(", ") : defaultText;
    };

    btn.addEventListener("click", (event) => {
      event.stopPropagation();
      const isOpen = content.classList.contains("active");
      closeAll(content);
      if (!isOpen) {
        content.classList.add("active");
        btn.classList.add("open");
      }
    });

    content.addEventListener("click", (event) => {
      event.stopPropagation();
      const field = event.target.closest(".input_field");
      if (!field) return;
      const checkbox = field.querySelector('input[type="checkbox"]');
      if (!checkbox) return;
      const clickedControl = event.target.closest('input[type="checkbox"], label');
      if (!clickedControl) {
        checkbox.checked = !checkbox.checked;
      }
      field.classList.toggle("selected", checkbox.checked);
      updateButtonText();
    });

    updateButtonText();
  });

  document.addEventListener("click", () => closeAll());
};

document.addEventListener("DOMContentLoaded", () => {
  initRangeSliders();
  initFiltersPopup();
  initFilterDropdowns();
});
