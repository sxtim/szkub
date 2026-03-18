const catalogFormatPrice = (value) => {
  const numeric = Number(value) || 0;
  if (numeric <= 0) {
    return "";
  }

  return `${new Intl.NumberFormat("ru-RU").format(Math.round(numeric))} ₽`;
};

const catalogEscapeHtml = (value) =>
  String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

const catalogUniqueValues = (values) => Array.from(new Set(values.filter(Boolean)));

const catalogPluralize = (count) => {
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

const catalogParsePayload = (root) => {
  const payloadEl = root.querySelector("[data-apartment-filter-payload]");
  if (!payloadEl) {
    return null;
  }

  try {
    return JSON.parse(payloadEl.textContent || "{}");
  } catch (error) {
    console.error("Catalog payload parse error", error);
    return null;
  }
};

const catalogReadRangeValue = (root, key, fallback) => {
  const input = root.querySelector(`[data-range-input="${key}"]`);
  const value = input ? Number(input.value) : Number.NaN;
  return Number.isFinite(value) ? value : fallback;
};

const catalogNormalizeState = (value) => {
  if (typeof value === "string") {
    const trimmed = value.trim();
    if (!trimmed) {
      return null;
    }

    try {
      value = JSON.parse(trimmed);
    } catch {
      return null;
    }
  }

  if (!value || typeof value !== "object") {
    return null;
  }

  const normalizeValues = (items) => {
    if (!Array.isArray(items)) {
      return [];
    }

    return catalogUniqueValues(items.map((item) => String(item || "").trim()));
  };

  const normalizeNumber = (item) => {
    if (item === null || item === "" || typeof item === "undefined") {
      return null;
    }
    const number = Number(item);
    return Number.isFinite(number) ? number : null;
  };

  return {
    projects: normalizeValues(value.projects),
    rooms: normalizeValues(value.rooms),
    statuses: normalizeValues(value.statuses),
    finishes: normalizeValues(value.finishes),
    features: normalizeValues(value.features),
    priceFrom: normalizeNumber(value.priceFrom),
    priceTo: normalizeNumber(value.priceTo),
    floorFrom: normalizeNumber(value.floorFrom),
    floorTo: normalizeNumber(value.floorTo),
    areaFrom: normalizeNumber(value.areaFrom),
    areaTo: normalizeNumber(value.areaTo),
    ceilingFrom: normalizeNumber(value.ceilingFrom),
    ceilingTo: normalizeNumber(value.ceilingTo),
  };
};

const catalogHasCriteria = (state) => {
  if (!state) {
    return false;
  }

  const arrays = ["projects", "rooms", "statuses", "finishes", "features"];
  if (arrays.some((key) => Array.isArray(state[key]) && state[key].length > 0)) {
    return true;
  }

  const ranges = [
    "priceFrom",
    "priceTo",
    "floorFrom",
    "floorTo",
    "areaFrom",
    "areaTo",
    "ceilingFrom",
    "ceilingTo",
  ];
  return ranges.some((key) => state[key] !== null && typeof state[key] !== "undefined");
};

const catalogStateFromQuery = () => {
  const params = new URLSearchParams(window.location.search);
  const raw = params.get("apartment_filter");
  return catalogNormalizeState(raw);
};

const catalogBuildState = (root, payload) => {
  const projects = catalogUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="project"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const rooms = catalogUniqueValues(
    Array.from(root.querySelectorAll('.filter__room.is-active[data-sync-group="rooms"]')).map(
      (pill) => pill.dataset.syncValue || ""
    )
  );
  const statuses = catalogUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="status"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const finishes = catalogUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="finish"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const features = catalogUniqueValues(
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
    priceFrom: catalogReadRangeValue(root, "price-from", ranges.price?.actual_min ?? 0),
    priceTo: catalogReadRangeValue(root, "price-to", ranges.price?.actual_max ?? 0),
    floorFrom: catalogReadRangeValue(root, "floors-from", ranges.floor?.actual_min ?? 0),
    floorTo: catalogReadRangeValue(root, "floors-to", ranges.floor?.actual_max ?? 0),
    areaFrom: catalogReadRangeValue(root, "square-from", ranges.area?.actual_min ?? 0),
    areaTo: catalogReadRangeValue(root, "square-to", ranges.area?.actual_max ?? 0),
    ceilingFrom: catalogReadRangeValue(root, "height-from", ranges.ceiling?.actual_min ?? 0),
    ceilingTo: catalogReadRangeValue(root, "height-to", ranges.ceiling?.actual_max ?? 0),
  };
};

const catalogMatchesFlat = (flat, state) => {
  if (state.projects.length && !state.projects.includes(flat.project_code)) {
    return false;
  }
  if (state.rooms.length && !state.rooms.includes(flat.rooms_bucket)) {
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
  if (flat.price_total > 0 && (flat.price_total < state.priceFrom || flat.price_total > state.priceTo)) {
    return false;
  }
  if (flat.floor > 0 && (flat.floor < state.floorFrom || flat.floor > state.floorTo)) {
    return false;
  }
  if (flat.area_total > 0 && (flat.area_total + 0.0001 < state.areaFrom || flat.area_total - 0.0001 > state.areaTo)) {
    return false;
  }
  if (flat.ceiling > 0 && (flat.ceiling + 0.0001 < state.ceilingFrom || flat.ceiling - 0.0001 > state.ceilingTo)) {
    return false;
  }

  return true;
};

const catalogUpdateDropdownLabels = (root) => {
  root.querySelectorAll(".filter__dropdown").forEach((dropdown) => {
    const button = dropdown.querySelector(".filter__dropdown-menu-btn");
    const content = dropdown.querySelector(".filter__dropdown-content");
    if (!button || !content) {
      return;
    }

    const defaultText = button.dataset.defaultText || button.textContent.trim();
    button.dataset.defaultText = defaultText;

    const selected = Array.from(content.querySelectorAll('input[type="checkbox"]:checked'))
      .map((checkbox) =>
        checkbox.closest(".input_field")?.querySelector("label")?.textContent.trim()
      )
      .filter(Boolean);

    button.textContent = selected.length ? selected.join(", ") : defaultText;
  });
};

const catalogSyncCheckboxGroup = (root, group, values) => {
  const allowed = new Set(values || []);

  root.querySelectorAll(`.custom-checkbox[data-sync-group="${group}"]`).forEach((checkbox) => {
    const checked = allowed.has(checkbox.dataset.syncValue || "");
    checkbox.checked = checked;

    const field = checkbox.closest(".input_field");
    if (field) {
      field.classList.toggle("selected", checked);
    }

    const label = checkbox.closest(".filter__checkbox");
    if (label) {
      label.classList.toggle("selected", checked);
    }
  });
};

const catalogSyncRoomPills = (root, values) => {
  const selectedValues = new Set(Array.isArray(values) ? values : []);
  root.querySelectorAll('.filter__room[data-sync-group="rooms"]').forEach((pill) => {
    pill.classList.toggle("is-active", selectedValues.has(pill.dataset.syncValue || ""));
  });
};

const catalogSetRange = (root, rangeName, fromValue, toValue, fallbackMin, fallbackMax) => {
  const from = typeof fromValue === "number" ? fromValue : fallbackMin;
  const to = typeof toValue === "number" ? toValue : fallbackMax;

  root.querySelectorAll(`.range-slider[data-range="${rangeName}"]`).forEach((slider) => {
    if (slider.noUiSlider) {
      slider.noUiSlider.set([from, to]);
    }
  });

  root.querySelectorAll(`[data-range-input="${rangeName}-from"]`).forEach((input) => {
    input.value = String(from);
  });
  root.querySelectorAll(`[data-range-input="${rangeName}-to"]`).forEach((input) => {
    input.value = String(to);
  });
};

const catalogApplyState = (root, payload, state) => {
  if (!state) {
    return;
  }

  catalogSyncCheckboxGroup(root, "project", state.projects);
  catalogSyncCheckboxGroup(root, "status", state.statuses);
  catalogSyncCheckboxGroup(root, "finish", state.finishes);
  catalogSyncCheckboxGroup(root, "feature", state.features);
  catalogSyncRoomPills(root, state.rooms);

  const ranges = payload.ranges || {};
  catalogSetRange(root, "price", state.priceFrom, state.priceTo, ranges.price?.actual_min ?? 0, ranges.price?.actual_max ?? 0);
  catalogSetRange(root, "floors", state.floorFrom, state.floorTo, ranges.floor?.actual_min ?? 0, ranges.floor?.actual_max ?? 0);
  catalogSetRange(root, "square", state.areaFrom, state.areaTo, ranges.area?.actual_min ?? 0, ranges.area?.actual_max ?? 0);
  catalogSetRange(root, "height", state.ceilingFrom, state.ceilingTo, ranges.ceiling?.actual_min ?? 0, ranges.ceiling?.actual_max ?? 0);

  catalogUpdateDropdownLabels(root);
};

const catalogSortFlats = (flats, sortValue) => {
  const items = [...flats];

  const compareNumbers = (left, right, key, direction = "asc") => {
    const a = Number(left[key]) || 0;
    const b = Number(right[key]) || 0;
    return direction === "asc" ? a - b : b - a;
  };

  switch (sortValue) {
    case "price_asc":
      items.sort((left, right) => compareNumbers(left, right, "price_total", "asc"));
      break;
    case "price_desc":
      items.sort((left, right) => compareNumbers(left, right, "price_total", "desc"));
      break;
    case "floor_asc":
      items.sort((left, right) => compareNumbers(left, right, "floor", "asc"));
      break;
    case "floor_desc":
      items.sort((left, right) => compareNumbers(left, right, "floor", "desc"));
      break;
    case "area_desc":
      items.sort((left, right) => compareNumbers(left, right, "area_total", "desc"));
      break;
    default:
      break;
  }

  return items;
};

const catalogBuildMeta = (flat) => {
  const parts = [];
  if (flat.rooms_label) {
    parts.push(flat.rooms_label);
  }
  if (flat.area_total) {
    parts.push(`${flat.area_total} м²`);
  }
  if (flat.floor) {
    parts.push(flat.house_floors ? `${flat.floor}/${flat.house_floors} этаж` : `${flat.floor} этаж`);
  }

  return parts.join(" • ");
};

const catalogBuildListMeta = (flat) => {
  const parts = [];
  if (flat.area_total) {
    parts.push(`${flat.area_total} м²`);
  }
  if (flat.floor) {
    parts.push(flat.house_floors ? `${flat.floor}/${flat.house_floors} этаж` : `${flat.floor} этаж`);
  }

  return parts.join(" • ");
};

const catalogRenderCard = (flat) => {
  const meta = catalogBuildMeta(flat);
  const listMeta = catalogBuildListMeta(flat);
  const badge = flat.badge || flat.status_label || "";
  const listLabel = flat.status_label || badge || "";
  const planAlt = flat.plan_alt || flat.rooms_label || "Планировка";

  return `
    <article class="apartment-card" data-card-url="${catalogEscapeHtml(flat.url)}" tabindex="0" role="link">
      <div class="apartment-card__head">
        <div>
          <span class="apartment-card__project">${catalogEscapeHtml(flat.project_name)}</span>
          ${flat.project_delivery ? `<span class="apartment-card__date">Сдача ${catalogEscapeHtml(flat.project_delivery)}</span>` : ""}
        </div>
        <button class="apartment-card__fav" type="button" aria-label="В избранное">
          <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>

      <div class="apartment-card__plan">
        ${flat.plan_image ? `<img class="apartment-card__plan-image" src="${catalogEscapeHtml(flat.plan_image)}" alt="${catalogEscapeHtml(planAlt)}" loading="lazy" />` : ""}
      </div>

      <div class="apartment-card__meta">${catalogEscapeHtml(meta)}</div>

      <div class="apartment-card__price">
        <span class="apartment-card__price-main">${catalogEscapeHtml(catalogFormatPrice(flat.price_total))}</span>
        ${flat.price_old > 0 ? `<span class="apartment-card__price-old">${catalogEscapeHtml(catalogFormatPrice(flat.price_old))}</span>` : ""}
      </div>

      ${badge ? `<span class="apartment-card__badge">${catalogEscapeHtml(badge)}</span>` : ""}

      <div class="apartment-card__list">
        <div class="apartment-card__summary">
          <div class="apartment-card__rooms">${catalogEscapeHtml(flat.rooms_label || "Квартира")}</div>
          <div class="apartment-card__area">${catalogEscapeHtml(listMeta)}</div>
        </div>
        <div class="apartment-card__delivery">
          ${flat.project_name ? `<div class="apartment-card__delivery-project">${catalogEscapeHtml(flat.project_name)}</div>` : ""}
          ${flat.project_delivery ? `<div>Сдача ${catalogEscapeHtml(flat.project_delivery)}</div>` : ""}
        </div>
        <div class="apartment-card__list-price">${catalogEscapeHtml(catalogFormatPrice(flat.price_total))}</div>
        ${listLabel ? `<span class="apartment-card__label">${catalogEscapeHtml(listLabel)}</span>` : `<span></span>`}
        <div class="apartment-card__icons">
          <button class="apartment-card__icon apartment-card__fav" type="button" aria-label="В избранное">
            <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </article>
  `;
};

const initCatalogView = () => {
  const viewContainer = document.querySelector("[data-view-container]");
  const viewButtons = document.querySelectorAll(".catalog__view-btn");

  const setView = (view) => {
    if (!viewContainer || !viewButtons.length) {
      return;
    }

    viewContainer.classList.toggle("is-list", view === "list");
    viewContainer.classList.toggle("is-grid", view === "grid");
    viewButtons.forEach((button) => {
      const isActive = button.dataset.view === view;
      button.classList.toggle("is-active", isActive);
      button.classList.toggle("btn--primary", isActive);
      button.classList.toggle("btn--outline", !isActive);
    });
    localStorage.setItem("catalogView", view);
  };

  if (viewContainer && viewButtons.length) {
    const saved = localStorage.getItem("catalogView");
    setView(saved === "list" ? "list" : "grid");
    viewButtons.forEach((button) => {
      button.addEventListener("click", () => setView(button.dataset.view));
    });
  }
};

const initCatalogSort = (onChange) => {
  const sortDropdown = document.querySelector("[data-sort-dropdown]");
  const sortToggle = sortDropdown?.querySelector("[data-sort-toggle]");
  const sortMenu = sortDropdown?.querySelector("[data-sort-menu]");
  const sortOptions = sortDropdown?.querySelectorAll(".catalog__sort-option") ?? [];

  const closeSortMenu = () => {
    if (!sortToggle || !sortMenu) {
      return;
    }
    sortMenu.classList.remove("active");
    sortToggle.classList.remove("open");
  };

  const openSortMenu = () => {
    if (!sortToggle || !sortMenu) {
      return;
    }
    sortMenu.classList.add("active");
    sortToggle.classList.add("open");
  };

  if (sortDropdown && sortToggle && sortMenu && sortOptions.length) {
    const activeOption =
      sortDropdown.querySelector(".catalog__sort-option.is-active") || sortOptions[0];
    sortToggle.textContent = activeOption.textContent.trim();

    sortToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      if (sortMenu.classList.contains("active")) {
        closeSortMenu();
        return;
      }
      openSortMenu();
    });

    sortOptions.forEach((option) => {
      option.addEventListener("click", (event) => {
        event.preventDefault();
        sortOptions.forEach((item) => item.classList.remove("is-active"));
        option.classList.add("is-active");
        sortToggle.textContent = option.textContent.trim();
        closeSortMenu();
        if (typeof onChange === "function") {
          onChange(option.dataset.sortValue || "default");
        }
      });
    });

    document.addEventListener("click", (event) => {
      if (!sortDropdown.contains(event.target)) {
        closeSortMenu();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeSortMenu();
      }
    });
  }

  return () =>
    document.querySelector(".catalog__sort-option.is-active")?.dataset.sortValue || "default";
};

const initApartmentCatalog = () => {
  const root = document.querySelector("[data-apartment-catalog]");
  if (!root) {
    return;
  }

  const payload = catalogParsePayload(root);
  if (!payload || !Array.isArray(payload.flats)) {
    return;
  }

  const resultsContainer = root.querySelector("[data-catalog-results]");
  const countEls = root.querySelectorAll("[data-catalog-count], [data-catalog-summary]");
  const emptyEl = root.querySelector("[data-catalog-empty]");
  const resetButton = root.querySelector("[data-catalog-reset]");

  if (!resultsContainer) {
    return;
  }

  let currentSort = "default";

  const render = () => {
    const state = catalogBuildState(root, payload);
    const matches = payload.flats.filter((flat) => catalogMatchesFlat(flat, state));
    const sortedMatches = catalogSortFlats(matches, currentSort);

    resultsContainer.innerHTML = sortedMatches.map(catalogRenderCard).join("");

    const count = sortedMatches.length;
    countEls.forEach((element) => {
      element.textContent = `Найдено ${count} ${catalogPluralize(count)}`;
    });

    if (emptyEl) {
      emptyEl.hidden = count > 0;
    }
    resultsContainer.hidden = count <= 0;
    if (resetButton) {
      resetButton.hidden = !catalogHasCriteria(state);
    }
  };

  initCatalogView();
  const getSortValue = initCatalogSort((sortValue) => {
    currentSort = sortValue;
    render();
  });
  currentSort = getSortValue();

  root.addEventListener("change", render);
  root.addEventListener("input", render);

  resultsContainer.addEventListener("click", (event) => {
    const favoriteButton = event.target.closest(".apartment-card__fav");
    if (favoriteButton) {
      favoriteButton.classList.toggle("is-active");
      event.preventDefault();
      event.stopPropagation();
      return;
    }

    const card = event.target.closest("[data-card-url]");
    if (!card) {
      return;
    }
    window.location.href = card.dataset.cardUrl || "#";
  });

  resultsContainer.addEventListener("keydown", (event) => {
    const card = event.target.closest("[data-card-url]");
    if (!card) {
      return;
    }
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      window.location.href = card.dataset.cardUrl || "#";
    }
  });

  resetButton?.addEventListener("click", () => {
    catalogApplyState(root, payload, {
      projects: [],
      rooms: [],
      statuses: [],
      finishes: [],
      features: [],
      priceFrom: payload.ranges?.price?.actual_min ?? null,
      priceTo: payload.ranges?.price?.actual_max ?? null,
      floorFrom: payload.ranges?.floor?.actual_min ?? null,
      floorTo: payload.ranges?.floor?.actual_max ?? null,
      areaFrom: payload.ranges?.area?.actual_min ?? null,
      areaTo: payload.ranges?.area?.actual_max ?? null,
      ceilingFrom: payload.ranges?.ceiling?.actual_min ?? null,
      ceilingTo: payload.ranges?.ceiling?.actual_max ?? null,
    });
    render();
  });

  window.requestAnimationFrame(() => {
    const initialState = catalogStateFromQuery();
    if (initialState) {
      catalogApplyState(root, payload, initialState);
      const cleanUrl = `${window.location.pathname}${window.location.hash || ""}`;
      window.history.replaceState(window.history.state, "", cleanUrl);
    } else {
      catalogUpdateDropdownLabels(root);
    }
    render();
  });
};

document.addEventListener("DOMContentLoaded", () => {
  initApartmentCatalog();
});
