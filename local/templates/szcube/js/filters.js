const formatNumber = (value, step) => {
  const rounded = step < 1 ? value : Math.round(value / step) * step;
  const decimals = step < 1 ? ((step.toString().split(".")[1] || "").length || 2) : 0;
  return Number(rounded).toLocaleString("ru-RU", {
    minimumFractionDigits: 0,
    maximumFractionDigits: decimals,
  });
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

      const eventTarget = fromInputs[0] || toInputs[0];
      if (eventTarget) {
        eventTarget.dispatchEvent(new Event("input", { bubbles: true }));
      }

      syncingRanges.delete(rangeKey);
    });

    slider.noUiSlider.on("set", () => {
      const eventTarget = fromInputs[0] || toInputs[0];
      if (eventTarget) {
        eventTarget.dispatchEvent(new Event("change", { bubbles: true }));
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

    const syncCheckboxes = (sourceCheckbox) => {
      const syncGroup = sourceCheckbox.dataset.syncGroup;
      const syncValue = sourceCheckbox.dataset.syncValue;
      if (!syncGroup || !syncValue) return;

      document
        .querySelectorAll(
          `.filter__dropdown .custom-checkbox[data-sync-group="${syncGroup}"][data-sync-value="${syncValue}"]`
        )
        .forEach((checkbox) => {
          checkbox.checked = sourceCheckbox.checked;
          const field = checkbox.closest(".input_field");
          if (field) {
            field.classList.toggle("selected", checkbox.checked);
          }
        });

      dropdowns.forEach((item) => {
        const itemBtn = item.querySelector(".filter__dropdown-menu-btn");
        const itemContent = item.querySelector(".filter__dropdown-content");
        if (!itemBtn || !itemContent) return;

        const itemDefaultText = itemBtn.dataset.defaultText || itemBtn.textContent.trim();
        itemBtn.dataset.defaultText = itemDefaultText;

        const selected = Array.from(itemContent.querySelectorAll('input[type="checkbox"]:checked'))
          .map((checkbox) =>
            checkbox.closest(".input_field")?.querySelector("label")?.textContent.trim()
          )
          .filter(Boolean);

        itemBtn.textContent = selected.length ? selected.join(", ") : itemDefaultText;
      });
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
      if (checkbox.disabled || field.classList.contains("is-disabled")) return;
      const clickedControl = event.target.closest('input[type="checkbox"], label');
      if (!clickedControl) {
        checkbox.checked = !checkbox.checked;
      }
      field.classList.toggle("selected", checkbox.checked);
      updateButtonText();
      syncCheckboxes(checkbox);
      checkbox.dispatchEvent(new Event("input", { bubbles: true }));
      checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    });

    btn.dataset.defaultText = defaultText;
    updateButtonText();
  });

  document.addEventListener("click", () => closeAll());
};

const initFilterPills = () => {
  document.querySelectorAll(".filter__rooms").forEach((container) => {
    container.addEventListener("click", (event) => {
      const pill = event.target.closest(".filter__room");
      if (!pill || !container.contains(pill)) return;
      if (pill.classList.contains("is-disabled")) return;
      const nextState = !pill.classList.contains("is-active");

      const syncGroup = pill.dataset.syncGroup;
      const syncValue = pill.dataset.syncValue;
      if (!syncGroup || !syncValue) return;

      document
        .querySelectorAll(`.filter__room[data-sync-group="${syncGroup}"][data-sync-value="${syncValue}"]`)
        .forEach((item) => {
          item.classList.toggle("is-active", nextState);
        });

      pill.dispatchEvent(new Event("input", { bubbles: true }));
      pill.dispatchEvent(new Event("change", { bubbles: true }));
    });
  });
};

const apartmentFilterParsePayload = (root) => {
  const payloadEl = root.querySelector("[data-apartment-filter-payload]");
  if (!payloadEl) return null;

  try {
    return JSON.parse(payloadEl.textContent || "{}");
  } catch (error) {
    console.error("Apartment filter payload parse error", error);
    return null;
  }
};

const apartmentFilterUniqueValues = (values) => Array.from(new Set(values.filter(Boolean)));

const apartmentFilterReadRangeValue = (root, key, fallback) => {
  const input = root.querySelector(`[data-range-input="${key}"]`);
  const value = input ? Number(input.value) : Number.NaN;
  return Number.isFinite(value) ? value : fallback;
};

const apartmentFilterPluralize = (count) => {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) {
    return "квартира";
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    return "квартиры";
  }
  return "квартир";
};

const apartmentFilterFlatFloorFrom = (flat) => {
  const value = Number(flat?.floor) || 0;
  return value > 0 ? value : 0;
};

const apartmentFilterFlatFloorTo = (flat) => {
  const floorFrom = apartmentFilterFlatFloorFrom(flat);
  const floorTo = Number(flat?.floor_to) || 0;
  return floorTo > floorFrom ? floorTo : floorFrom;
};

const apartmentFilterMatchesFloorRange = (flat, from, to) => {
  const floorFrom = apartmentFilterFlatFloorFrom(flat);
  const floorTo = apartmentFilterFlatFloorTo(flat);
  if (floorFrom <= 0) {
    return true;
  }
  if (typeof from === "number" && Number.isFinite(from) && floorTo + 0.0001 < from) {
    return false;
  }
  if (typeof to === "number" && Number.isFinite(to) && floorFrom - 0.0001 > to) {
    return false;
  }

  return true;
};

const apartmentFilterNumberDiffers = (value, fallback) => {
  if (typeof value !== "number" || !Number.isFinite(value)) {
    return false;
  }
  if (typeof fallback !== "number" || !Number.isFinite(fallback)) {
    return true;
  }

  return Math.abs(value - fallback) > 0.0001;
};

const apartmentFilterStateToQuery = (state, payload) => {
  const params = new URLSearchParams();
  const ranges = payload.ranges || {};

  const setCsv = (key, values) => {
    const normalized = apartmentFilterUniqueValues(
      (Array.isArray(values) ? values : []).map((item) => String(item || "").trim())
    );
    if (normalized.length) {
      params.set(key, normalized.join(","));
    }
  };

  setCsv("project", state.projects);
  setCsv("rooms", state.rooms);
  setCsv("status", state.statuses);
  setCsv("finish", state.finishes);
  setCsv("feature", state.features);

  if (apartmentFilterNumberDiffers(state.priceFrom, ranges.price?.actual_min ?? null)) {
    params.set("price_from", String(state.priceFrom));
  }
  if (apartmentFilterNumberDiffers(state.priceTo, ranges.price?.actual_max ?? null)) {
    params.set("price_to", String(state.priceTo));
  }
  if (apartmentFilterNumberDiffers(state.floorFrom, ranges.floor?.actual_min ?? null)) {
    params.set("floor_from", String(state.floorFrom));
  }
  if (apartmentFilterNumberDiffers(state.floorTo, ranges.floor?.actual_max ?? null)) {
    params.set("floor_to", String(state.floorTo));
  }
  if (apartmentFilterNumberDiffers(state.areaFrom, ranges.area?.actual_min ?? null)) {
    params.set("area_from", String(state.areaFrom));
  }
  if (apartmentFilterNumberDiffers(state.areaTo, ranges.area?.actual_max ?? null)) {
    params.set("area_to", String(state.areaTo));
  }
  if (apartmentFilterNumberDiffers(state.ceilingFrom, ranges.ceiling?.actual_min ?? null)) {
    params.set("ceiling_from", String(state.ceilingFrom));
  }
  if (apartmentFilterNumberDiffers(state.ceilingTo, ranges.ceiling?.actual_max ?? null)) {
    params.set("ceiling_to", String(state.ceilingTo));
  }

  return params.toString();
};

const apartmentFilterBuildState = (root, payload) => {
  const projects = apartmentFilterUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="project"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const rooms = apartmentFilterUniqueValues(
    Array.from(root.querySelectorAll('.filter__room.is-active[data-sync-group="rooms"]')).map(
      (pill) => pill.dataset.syncValue || ""
    )
  );
  const statuses = apartmentFilterUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="status"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const finishes = apartmentFilterUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="finish"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const features = apartmentFilterUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="feature"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );

  const ranges = payload.ranges || {};

  return {
    projects,
    rooms,
    statuses,
    finishes,
    features,
    priceFrom: apartmentFilterReadRangeValue(root, "price-from", ranges.price?.actual_min ?? 0),
    priceTo: apartmentFilterReadRangeValue(root, "price-to", ranges.price?.actual_max ?? 0),
    floorFrom: apartmentFilterReadRangeValue(root, "floors-from", ranges.floor?.actual_min ?? 0),
    floorTo: apartmentFilterReadRangeValue(root, "floors-to", ranges.floor?.actual_max ?? 0),
    areaFrom: apartmentFilterReadRangeValue(root, "square-from", ranges.area?.actual_min ?? 0),
    areaTo: apartmentFilterReadRangeValue(root, "square-to", ranges.area?.actual_max ?? 0),
    ceilingFrom: apartmentFilterReadRangeValue(root, "height-from", ranges.ceiling?.actual_min ?? 0),
    ceilingTo: apartmentFilterReadRangeValue(root, "height-to", ranges.ceiling?.actual_max ?? 0),
  };
};

const apartmentFilterMatchesFlat = (flat, state) => {
  if (state.projects.length && !state.projects.includes(flat.project_code)) {
    return false;
  }
  if (state.rooms.length && !state.rooms.includes(flat.rooms_bucket)) {
    return false;
  }
  if (flat.price_total > 0 && (flat.price_total < state.priceFrom || flat.price_total > state.priceTo)) {
    return false;
  }
  if (!apartmentFilterMatchesFloorRange(flat, state.floorFrom, state.floorTo)) {
    return false;
  }
  if (flat.area_total > 0 && (flat.area_total + 0.0001 < state.areaFrom || flat.area_total - 0.0001 > state.areaTo)) {
    return false;
  }
  if (flat.ceiling > 0 && (flat.ceiling + 0.0001 < state.ceilingFrom || flat.ceiling - 0.0001 > state.ceilingTo)) {
    return false;
  }
  if (state.statuses.length && !state.statuses.includes(flat.status)) {
    return false;
  }
  if (state.finishes.length && !state.finishes.includes(flat.finish)) {
    return false;
  }
  if (state.features.length) {
    const tags = Array.isArray(flat.feature_tags) ? flat.feature_tags : [];
    if (!state.features.some((feature) => tags.includes(feature))) {
      return false;
    }
  }

  return true;
};

const initApartmentFilter = () => {
  const root = document.querySelector("[data-apartment-filter]");
  if (!root) return;

  const payload = apartmentFilterParsePayload(root);
  if (!payload || !Array.isArray(payload.flats)) return;

  const summaryEls = root.querySelectorAll("[data-apartment-filter-summary]");
  const submitButtons = root.querySelectorAll("[data-apartment-filter-submit]");
  if (!submitButtons.length) return;

  const projectIndex = {};
  (payload.projects || []).forEach((project) => {
    if (project?.code) {
      projectIndex[project.code] = project;
    }
  });

  const updateState = () => {
    const state = apartmentFilterBuildState(root, payload);
    const matches = payload.flats.filter((flat) => apartmentFilterMatchesFlat(flat, state));
    root.apartmentFilterState = state;
    root.apartmentFilterMatches = matches;

    const count = matches.length;
    submitButtons.forEach((submitButton) => {
      submitButton.disabled = count <= 0;
      if (count <= 0) {
        submitButton.textContent = "Квартиры не найдены";
      } else if (count === 1) {
        submitButton.textContent = "Выбрать квартиру";
      } else {
        submitButton.textContent = `Показать ${count} ${apartmentFilterPluralize(count)}`;
      }
    });

    summaryEls.forEach((summaryEl) => {
      summaryEl.textContent = count > 0
        ? `Найдено ${count} ${apartmentFilterPluralize(count)}`
        : "Квартиры не найдены";
    });
  };

  root.addEventListener("change", updateState);
  root.addEventListener("input", updateState);
  root.addEventListener("click", (event) => {
    const button = event.target.closest("[data-apartment-filter-submit]");
    if (!button || !root.contains(button)) {
      return;
    }

    event.preventDefault();

    const state = apartmentFilterBuildState(root, payload);
    const matches = payload.flats.filter((flat) => apartmentFilterMatchesFlat(flat, state));
    if (!matches.length) {
      return;
    }

    if (matches.length === 1 && matches[0].url) {
      window.location.href = matches[0].url;
      return;
    }

    if (payload.catalog_page_url) {
      const queryString = apartmentFilterStateToQuery(state, payload);
      const glue = payload.catalog_page_url.includes("?") ? "&" : "?";
      window.location.href = queryString
        ? `${payload.catalog_page_url}${glue}${queryString}`
        : payload.catalog_page_url;
      return;
    }

    if (payload.projects_page_url) {
      window.location.href = payload.projects_page_url;
    }
  });

  updateState();
};

document.addEventListener("DOMContentLoaded", () => {
  initRangeSliders();
  initFiltersPopup();
  initFilterDropdowns();
  initFilterPills();
  initApartmentFilter();
});
