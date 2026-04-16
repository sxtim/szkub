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

const catalogNormalizeBadges = (badges) => {
  if (!Array.isArray(badges)) {
    return [];
  }

  return Array.from(
    new Set(
      badges
        .map((badge) => String(badge ?? "").trim())
        .filter(Boolean)
    )
  );
};

const catalogRenderBadges = (badges) => {
  const normalizedBadges = catalogNormalizeBadges(badges);
  if (!normalizedBadges.length) {
    return "";
  }

  return `
    <div class="apartment-card__badges">
      ${normalizedBadges
        .map((badge) => `<span class="apartment-card__badge">${catalogEscapeHtml(badge)}</span>`)
        .join("")}
    </div>
  `;
};

const catalogUniqueValues = (values) => Array.from(new Set(values.filter(Boolean)));

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

const catalogNumberDiffers = (value, fallback) => {
  if (typeof value !== "number" || !Number.isFinite(value)) {
    return false;
  }
  if (typeof fallback !== "number" || !Number.isFinite(fallback)) {
    return true;
  }

  return Math.abs(value - fallback) > 0.0001;
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
    if (typeof items === "string") {
      items = items.split(",");
    }

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
    projects: normalizeValues(typeof value.projects !== "undefined" ? value.projects : value.project),
    entrances: normalizeValues(typeof value.entrances !== "undefined" ? value.entrances : value.entrance),
    types: normalizeValues(typeof value.types !== "undefined" ? value.types : value.type),
    rooms: normalizeValues(value.rooms),
    statuses: normalizeValues(typeof value.statuses !== "undefined" ? value.statuses : value.status),
    finishes: normalizeValues(typeof value.finishes !== "undefined" ? value.finishes : value.finish),
    features: normalizeValues(typeof value.features !== "undefined" ? value.features : value.feature),
    priceFrom: normalizeNumber(typeof value.priceFrom !== "undefined" ? value.priceFrom : value.price_from),
    priceTo: normalizeNumber(typeof value.priceTo !== "undefined" ? value.priceTo : value.price_to),
    floorFrom: normalizeNumber(typeof value.floorFrom !== "undefined" ? value.floorFrom : value.floor_from),
    floorTo: normalizeNumber(typeof value.floorTo !== "undefined" ? value.floorTo : value.floor_to),
    areaFrom: normalizeNumber(typeof value.areaFrom !== "undefined" ? value.areaFrom : value.area_from),
    areaTo: normalizeNumber(typeof value.areaTo !== "undefined" ? value.areaTo : value.area_to),
    ceilingFrom: normalizeNumber(typeof value.ceilingFrom !== "undefined" ? value.ceilingFrom : value.ceiling_from),
    ceilingTo: normalizeNumber(typeof value.ceilingTo !== "undefined" ? value.ceilingTo : value.ceiling_to),
  };
};

const catalogHasCriteria = (state) => {
  if (!state) {
    return false;
  }

  const arrays = ["projects", "entrances", "rooms", "statuses", "finishes", "features"];
  if (Array.isArray(state.types) && state.types.length > 0) {
    return true;
  }
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
  if (raw) {
    return {
      state: catalogNormalizeState(raw),
      legacy: true,
    };
  }

  const state = catalogNormalizeState({
    project: params.get("project"),
    entrance: params.get("entrance"),
    type: params.get("type"),
    rooms: params.get("rooms"),
    status: params.get("status"),
    finish: params.get("finish"),
    feature: params.get("feature"),
    price_from: params.get("price_from"),
    price_to: params.get("price_to"),
    floor_from: params.get("floor_from"),
    floor_to: params.get("floor_to"),
    area_from: params.get("area_from"),
    area_to: params.get("area_to"),
    ceiling_from: params.get("ceiling_from"),
    ceiling_to: params.get("ceiling_to"),
  });

  return {
    state: catalogHasCriteria(state) ? state : null,
    legacy: false,
  };
};

const catalogStateToQuery = (state, payload) => {
  const params = new URLSearchParams();
  const ranges = payload.ranges || {};

  const setCsv = (key, values) => {
    const normalized = catalogUniqueValues(
      (Array.isArray(values) ? values : []).map((item) => String(item || "").trim())
    );
    if (normalized.length) {
      params.set(key, normalized.join(","));
    }
  };

  setCsv("project", state.projects);
  setCsv("entrance", state.entrances);
  setCsv("type", state.types);
  setCsv("rooms", state.rooms);
  setCsv("status", state.statuses);
  setCsv("finish", state.finishes);
  setCsv("feature", state.features);

  if (catalogNumberDiffers(state.priceFrom, ranges.price?.render_min ?? null)) {
    params.set("price_from", String(state.priceFrom));
  }
  if (catalogNumberDiffers(state.priceTo, ranges.price?.render_max ?? null)) {
    params.set("price_to", String(state.priceTo));
  }
  if (catalogNumberDiffers(state.floorFrom, ranges.floor?.render_min ?? null)) {
    params.set("floor_from", String(state.floorFrom));
  }
  if (catalogNumberDiffers(state.floorTo, ranges.floor?.render_max ?? null)) {
    params.set("floor_to", String(state.floorTo));
  }
  if (catalogNumberDiffers(state.areaFrom, ranges.area?.render_min ?? null)) {
    params.set("area_from", String(state.areaFrom));
  }
  if (catalogNumberDiffers(state.areaTo, ranges.area?.render_max ?? null)) {
    params.set("area_to", String(state.areaTo));
  }
  if (catalogNumberDiffers(state.ceilingFrom, ranges.ceiling?.render_min ?? null)) {
    params.set("ceiling_from", String(state.ceilingFrom));
  }
  if (catalogNumberDiffers(state.ceilingTo, ranges.ceiling?.render_max ?? null)) {
    params.set("ceiling_to", String(state.ceilingTo));
  }

  return params.toString();
};

const catalogBuildState = (root, payload) => {
  const projects = catalogUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="project"]:checked')).map(
      (checkbox) => checkbox.dataset.syncValue || ""
    )
  );
  const types = catalogUniqueValues(
    Array.from(root.querySelectorAll('[data-sync-group="type"].is-active, .custom-checkbox[data-sync-group="type"]:checked')).map(
      (item) => item.dataset.syncValue || ""
    )
  );
  const entrances = catalogUniqueValues(
    Array.from(root.querySelectorAll('.custom-checkbox[data-sync-group="entrance"]:checked')).map(
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
    entrances,
    types,
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
    ceilingFrom: catalogReadRangeValue(root, "height-from", ranges.ceiling?.actual_min ?? null),
    ceilingTo: catalogReadRangeValue(root, "height-to", ranges.ceiling?.actual_max ?? null),
  };
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

const catalogSyncPills = (root, group, values) => {
  const selectedValues = new Set(Array.isArray(values) ? values : []);
  root.querySelectorAll(`.filter__room[data-sync-group="${group}"]`).forEach((pill) => {
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
  catalogSyncCheckboxGroup(root, "entrance", state.entrances);
  catalogSyncCheckboxGroup(root, "type", state.types);
  catalogSyncCheckboxGroup(root, "status", state.statuses);
  catalogSyncCheckboxGroup(root, "finish", state.finishes);
  catalogSyncCheckboxGroup(root, "feature", state.features);
  catalogSyncPills(root, "rooms", state.rooms);
  catalogSyncPills(root, "type", state.types);

  const ranges = payload.ranges || {};
  catalogSetRange(root, "price", state.priceFrom, state.priceTo, ranges.price?.actual_min ?? 0, ranges.price?.actual_max ?? 0);
  catalogSetRange(root, "floors", state.floorFrom, state.floorTo, ranges.floor?.actual_min ?? 0, ranges.floor?.actual_max ?? 0);
  catalogSetRange(root, "square", state.areaFrom, state.areaTo, ranges.area?.actual_min ?? 0, ranges.area?.actual_max ?? 0);
  catalogSetRange(root, "height", state.ceilingFrom, state.ceilingTo, ranges.ceiling?.actual_min ?? 0, ranges.ceiling?.actual_max ?? 0);

  catalogUpdateDropdownLabels(root);
};

const catalogBuildPrimaryMeta = (flat) => {
  const parts = [];
  if (flat.rooms_label) {
    parts.push(flat.rooms_label);
  }
  if (flat.area_total) {
    parts.push(`${flat.area_total} м²`);
  }

  return parts.join(" • ");
};

const catalogBuildSecondaryMeta = (flat) => {
  const parts = [];
  if (flat.entrance) {
    parts.push(`Подъезд ${flat.entrance}`);
  }
  if (flat.floor_short) {
    parts.push(flat.floor_short);
  }

  return parts.join(" • ");
};

const catalogBuildBoardUrl = (flat) => {
  if (flat && flat.board_enabled === false) {
    return "";
  }
  const baseUrl = flat.project_filter_url || flat.project_url || "/projects/";
  const url = new URL(baseUrl, window.location.origin);

  url.searchParams.set("selector_view", "board");
  if (flat.code) {
    url.searchParams.set("selector_flat", flat.code);
  }

  return `${url.pathname}${url.search}${url.hash}`;
};

const catalogStatusBadgeClass = (statusKey) => {
  switch (String(statusKey || "").trim()) {
    case "available":
      return " catalog-list-card__badge--available";
    case "booked":
      return " catalog-list-card__badge--booked";
    case "sold":
      return " catalog-list-card__badge--sold";
    default:
      return "";
  }
};

const catalogRenderListCardContent = (flat, boardUrl) => {
  const titleParts = [flat.list_title || flat.title || flat.rooms_label || "Лот"];
  if (flat.area_total) {
    titleParts.push(`${flat.area_total} м²`);
  }
  const title = catalogEscapeHtml(titleParts.filter(Boolean).join(" · "));
  const summarySubtitle = catalogEscapeHtml(flat.list_subtitle || (flat.project_name ? `ЖК ${flat.project_name}` : ""));
  const detailsTitle = catalogEscapeHtml(
    flat.list_details_title
      || ((flat.title && flat.rooms_label && flat.title !== flat.rooms_label)
        ? (flat.type_label || flat.rooms_label || "")
        : (flat.project_delivery ? `Сдача ${flat.project_delivery}` : ""))
  );
  const statusLabel = catalogEscapeHtml(flat.status_label || "");
  const priceTotal = catalogEscapeHtml(catalogFormatPrice(flat.price_total));
  const priceOld = flat.price_old > 0 ? catalogEscapeHtml(catalogFormatPrice(flat.price_old)) : "";
  const metaParts = Array.isArray(flat.list_meta_parts) ? flat.list_meta_parts.filter(Boolean) : [];
  const badges = catalogNormalizeBadges(flat.badges).slice(0, 2);

  if (flat.entrance) {
    metaParts.push(`Подъезд ${flat.entrance}`);
  }
  if (flat.floor_short) {
    metaParts.push(flat.floor_short);
  }

  const allBadges = badges.slice();
  if (statusLabel !== "") {
    allBadges.push({
      label: statusLabel,
      className: catalogStatusBadgeClass(flat.status),
    });
  }

  const badgesHtml = allBadges.length
    ? `<div class="catalog-list-card__badges">${allBadges
        .map((badge) => {
          if (typeof badge === "string") {
            return `<span class="catalog-list-card__badge">${catalogEscapeHtml(badge)}</span>`;
          }

          const className = badge && badge.className ? badge.className : "";
          const label = badge && badge.label ? badge.label : "";
          return `<span class="catalog-list-card__badge${className}">${catalogEscapeHtml(label)}</span>`;
        })
        .join("")}</div>`
    : "";

  const detailsHtml =
    detailsTitle || metaParts.length
      ? `<div class="catalog-list-card__details">
          ${detailsTitle ? `<div class="catalog-list-card__type">${detailsTitle}</div>` : ""}
          ${metaParts.length ? `<div class="catalog-list-card__meta">${catalogEscapeHtml(metaParts.join(" · "))}</div>` : ""}
        </div>`
      : `<div class="catalog-list-card__details" aria-hidden="true"></div>`;

  const actionLabel = catalogEscapeHtml(flat.list_action_label || "Подробнее");

  return `
      <div class="apartment-card__list">
        <div class="apartment-card__summary">
          <div class="apartment-card__rooms">${title}</div>
          ${summarySubtitle ? `<div class="apartment-card__area">${summarySubtitle}</div>` : ""}
          ${badgesHtml}
        </div>
        ${detailsHtml}
        <div class="catalog-list-card__price">
          <div class="apartment-card__list-price">${priceTotal}</div>
          ${priceOld ? `<div class="catalog-list-card__price-old">${priceOld}</div>` : ""}
        </div>
        <div class="catalog-list-card__actions">
          <button class="btn btn--primary catalog-list-card__primary" type="button">${actionLabel}</button>
        </div>
        <div class="apartment-card__icons">
          ${boardUrl ? `<button class="apartment-card__icon apartment-card__action apartment-card__board" type="button" data-board-url="${catalogEscapeHtml(boardUrl)}" aria-label="Показать на шахматке" title="Показать на шахматке">
            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M3.5 3.5H7.25V7.25H3.5V3.5Z" stroke="currentColor" stroke-width="1.2"/>
              <path d="M10.75 3.5H14.5V7.25H10.75V3.5Z" stroke="currentColor" stroke-width="1.2"/>
              <path d="M3.5 10.75H7.25V14.5H3.5V10.75Z" stroke="currentColor" stroke-width="1.2"/>
              <path d="M10.75 10.75H14.5V14.5H10.75V10.75Z" stroke="currentColor" stroke-width="1.2"/>
            </svg>
          </button>` : ""}
          <button class="apartment-card__icon apartment-card__action apartment-card__fav" type="button" aria-label="В избранное" title="В избранное">
            <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
  `;
};

const catalogRenderCard = (flat) => {
  const primaryMeta = catalogBuildPrimaryMeta(flat);
  const secondaryMeta = catalogBuildSecondaryMeta(flat);
  const badges = catalogNormalizeBadges(flat.badges);
  const planAlt = flat.plan_alt || flat.rooms_label || "Планировка";
  const boardUrl = catalogBuildBoardUrl(flat);
  const articleClasses = "apartment-card catalog-list-card";
  const boardAction = boardUrl ? `
    <button
      class="apartment-card__action apartment-card__board"
      type="button"
      data-board-url="${catalogEscapeHtml(boardUrl)}"
      aria-label="Показать на шахматке"
      title="Показать на шахматке"
    >
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M3.5 3.5H7.25V7.25H3.5V3.5Z" stroke="currentColor" stroke-width="1.2"/>
        <path d="M10.75 3.5H14.5V7.25H10.75V3.5Z" stroke="currentColor" stroke-width="1.2"/>
        <path d="M3.5 10.75H7.25V14.5H3.5V10.75Z" stroke="currentColor" stroke-width="1.2"/>
        <path d="M10.75 10.75H14.5V14.5H10.75V10.75Z" stroke="currentColor" stroke-width="1.2"/>
      </svg>
    </button>
  ` : "";

  return `
    <article class="${articleClasses}" data-card-url="${catalogEscapeHtml(flat.url)}" tabindex="0" role="link">
      <div class="apartment-card__head">
        <div>
          <span class="apartment-card__project">${catalogEscapeHtml(flat.project_name)}</span>
          ${flat.project_delivery ? `<span class="apartment-card__date">Сдача ${catalogEscapeHtml(flat.project_delivery)}</span>` : ""}
        </div>
        <div class="apartment-card__actions">
          ${boardAction}
          <button class="apartment-card__action apartment-card__fav" type="button" aria-label="В избранное" title="В избранное">
            <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="apartment-card__plan">
        ${flat.plan_image ? `<img class="apartment-card__plan-image" src="${catalogEscapeHtml(flat.plan_image)}" alt="${catalogEscapeHtml(planAlt)}" loading="lazy" />` : ""}
      </div>

      ${primaryMeta ? `<div class="apartment-card__meta">${catalogEscapeHtml(primaryMeta)}</div>` : ""}
      ${secondaryMeta ? `<div class="apartment-card__meta apartment-card__meta--secondary">${catalogEscapeHtml(secondaryMeta)}</div>` : ""}

      <div class="apartment-card__price">
        <span class="apartment-card__price-main">${catalogEscapeHtml(catalogFormatPrice(flat.price_total))}</span>
        ${flat.price_old > 0 ? `<span class="apartment-card__price-old">${catalogEscapeHtml(catalogFormatPrice(flat.price_old))}</span>` : ""}
      </div>

      ${catalogRenderBadges(badges)}

      ${catalogRenderListCardContent(flat, boardUrl)}
    </article>
  `;
};

const initCatalogView = () => {
  const viewContainer = document.querySelector("[data-view-container]");
  const viewButtons = document.querySelectorAll(".catalog__view-btn");
  const desktopOnlyViewToggle = window.matchMedia("(max-width: 1200px)");

  const setView = (view, options = {}) => {
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

    if (options.persist !== false) {
      localStorage.setItem("catalogView", view);
    }
  };

  const syncViewForViewport = () => {
    if (!viewContainer || !viewButtons.length) {
      return;
    }

    if (desktopOnlyViewToggle.matches) {
      setView("grid", { persist: false });
      return;
    }

    const saved = localStorage.getItem("catalogView");
    setView(saved === "list" ? "list" : "grid", { persist: false });
  };

  if (viewContainer && viewButtons.length) {
    syncViewForViewport();
    viewButtons.forEach((button) => {
      button.addEventListener("click", () => {
        if (desktopOnlyViewToggle.matches) {
          setView("grid", { persist: false });
          return;
        }

        setView(button.dataset.view);
      });
    });
    desktopOnlyViewToggle.addEventListener("change", syncViewForViewport);
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
  const emptyEl = root.querySelector("[data-catalog-empty]");
  const resetButton = root.querySelector("[data-catalog-reset]");
  const popupSubmitButton = root.querySelector("[data-catalog-filter-submit]");

  if (!resultsContainer) {
    return;
  }

  const initialQuery = catalogStateFromQuery();
  let currentSort = payload.current_sort || "default";
  let isReady = false;
  const render = () => {
    resultsContainer.innerHTML = payload.flats
      .map((flat) => catalogRenderCard(flat))
      .join("");

    const count = Array.isArray(payload.flats) ? payload.flats.length : 0;
    if (emptyEl) {
      emptyEl.hidden = count > 0;
    }
    resultsContainer.hidden = count <= 0;
    if (resetButton) {
      resetButton.hidden = !catalogHasCriteria(catalogBuildState(root, payload));
    }
  };

  const navigateWithState = (sortValue = currentSort) => {
    const state = catalogBuildState(root, payload);
    const queryString = catalogStateToQuery(state, payload);
    const params = new URLSearchParams(queryString);

    if (sortValue && sortValue !== "default") {
      params.set("sort", sortValue);
    } else {
      params.delete("sort");
    }

    params.delete("PAGEN_1");

    const targetUrl = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ""}${window.location.hash || ""}`;
    window.location.href = targetUrl;
  };

  initCatalogView();
  const getSortValue = initCatalogSort((sortValue) => {
    currentSort = sortValue;
    if (isReady) {
      navigateWithState(sortValue);
    }
  });
  currentSort = payload.current_sort || getSortValue();

  root.addEventListener("change", (event) => {
    if (!isReady) {
      return;
    }

    if (event.target.closest(".filters-popup__dialog")) {
      return;
    }

    if (
      event.target.matches(".custom-checkbox, [data-range-input]")
      || event.target.closest(".filter__room")
    ) {
      navigateWithState();
    }
  });

  root.addEventListener("input", (event) => {
    if (!isReady) {
      return;
    }

    if (event.target.closest(".filters-popup__dialog")) {
      return;
    }

    if (event.target.closest(".filter__room")) {
      navigateWithState();
    }
  });

  if (popupSubmitButton) {
    popupSubmitButton.addEventListener("click", () => {
      if (!isReady) {
        return;
      }

      navigateWithState();
    });
  }

  resultsContainer.addEventListener("click", (event) => {
    const boardButton = event.target.closest(".apartment-card__board");
    if (boardButton) {
      event.preventDefault();
      event.stopPropagation();
      window.location.href = boardButton.dataset.boardUrl || "#";
      return;
    }

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
    if (
      event.target.closest(".apartment-card__board") ||
      event.target.closest(".apartment-card__fav")
    ) {
      return;
    }

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
    window.location.href = `${window.location.pathname}${window.location.hash || ""}`;
  });

  window.requestAnimationFrame(() => {
    if (initialQuery.state) {
      catalogApplyState(root, payload, initialQuery.state);
    } else {
      catalogUpdateDropdownLabels(root);
    }
    isReady = true;
    render();
  });
};

document.addEventListener("DOMContentLoaded", () => {
  initApartmentCatalog();
});
