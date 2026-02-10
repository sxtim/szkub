const formatNumber = (value, step) => {
  const rounded = step < 1 ? value : Math.round(value / step) * step;
  return Math.round(rounded).toLocaleString("ru-RU");
};

const initRangeSliders = () => {
  const sliders = document.querySelectorAll(".range-slider");
  const syncingRanges = new Set();

  sliders.forEach((slider) => {
    if (slider.noUiSlider) {
      return;
    }
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

    const fromEls = document.querySelectorAll(
      `[data-range-value="${slider.dataset.range}-from"]`
    );
    const toEls = document.querySelectorAll(
      `[data-range-value="${slider.dataset.range}-to"]`
    );
    const fromInputs = document.querySelectorAll(
      `[data-range-input="${slider.dataset.range}-from"]`
    );
    const toInputs = document.querySelectorAll(
      `[data-range-input="${slider.dataset.range}-to"]`
    );

    slider.noUiSlider.on("update", (values) => {
      const rangeKey = slider.dataset.range;
      if (syncingRanges.has(rangeKey)) {
        return;
      }
      syncingRanges.add(rangeKey);

      const fromVal = Number(values[0]);
      const toVal = Number(values[1]);

      fromEls.forEach((el) => {
        el.textContent = formatNumber(fromVal, step);
      });
      toEls.forEach((el) => {
        el.textContent = formatNumber(toVal, step);
      });
      fromInputs.forEach((input) => {
        input.value = fromVal.toString();
      });
      toInputs.forEach((input) => {
        input.value = toVal.toString();
      });

      document
        .querySelectorAll(`.range-slider[data-range="${rangeKey}"]`)
        .forEach((target) => {
          if (target === slider || !target.noUiSlider) return;
          const current = target.noUiSlider.get();
          if (Array.isArray(current) && current.join() === values.join()) {
            return;
          }
          target.noUiSlider.set(values);
        });

      syncingRanges.delete(rangeKey);
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
    initRangeSliders();
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
      if (isOpen) {
        content.classList.remove("active");
        btn.classList.remove("open");
        return;
      }
      closeAll(content);
      content.classList.add("active");
      btn.classList.add("open");
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

const initFilterPills = () => {
  document.querySelectorAll(".filter__rooms").forEach((container) => {
    container.addEventListener("click", (event) => {
      const pill = event.target.closest(".filter__room");
      if (!pill || !container.contains(pill)) return;
      pill.classList.toggle("is-active");
    });
  });
};

document.addEventListener("DOMContentLoaded", () => {
  initRangeSliders();
  initFiltersPopup();
  initFilterDropdowns();
  initFilterPills();
});
