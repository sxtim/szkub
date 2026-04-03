const parkingEscapeHtml = (value) =>
  String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

const parkingParsePayload = (root) => {
  const payloadEl = root.querySelector("[data-parking-filter-payload]");
  if (!payloadEl) {
    return null;
  }

  try {
    return JSON.parse(payloadEl.textContent || "{}");
  } catch (error) {
    console.error("Parking payload parse error", error);
    return null;
  }
};

const parkingUniqueValues = (values) => Array.from(new Set(values.filter(Boolean)));

const parkingPluralize = (count) => {
  const mod10 = count % 10;
  const mod100 = count % 100;

  if (mod10 === 1 && mod100 !== 11) {
    return "место";
  }
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) {
    return "места";
  }

  return "мест";
};

const parkingReadRangeValue = (root, key, fallback) => {
  const input = root.querySelector(`[data-range-input="${key}"]`);
  const value = input ? Number(input.value) : Number.NaN;
  return Number.isFinite(value) ? value : fallback;
};

const parkingGetFavorites = () => {
  try {
    const raw = window.localStorage.getItem("parking-favorites");
    if (!raw) {
      return [];
    }
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (error) {
    return [];
  }
};

const parkingSetFavorites = (items) => {
  try {
    window.localStorage.setItem("parking-favorites", JSON.stringify(items));
  } catch (error) {
    // noop
  }
};

const parkingStateToQuery = (state, payload) => {
  const params = new URLSearchParams();
  const ranges = payload.ranges || {};

  const setCsv = (key, values) => {
    const normalized = parkingUniqueValues(
      (Array.isArray(values) ? values : []).map((item) => String(item || "").trim())
    );
    if (normalized.length) {
      params.set(key, normalized.join(","));
    }
  };

  setCsv("project", state.projects);
  setCsv("type", state.types);
  setCsv("status", state.statuses);

  if (typeof state.priceFrom === "number" && state.priceFrom !== (ranges.price?.actual_min ?? state.priceFrom)) {
    params.set("price_from", String(state.priceFrom));
  }
  if (typeof state.priceTo === "number" && state.priceTo !== (ranges.price?.actual_max ?? state.priceTo)) {
    params.set("price_to", String(state.priceTo));
  }
  if (typeof state.areaFrom === "number" && state.areaFrom !== (ranges.area?.actual_min ?? state.areaFrom)) {
    params.set("area_from", String(state.areaFrom));
  }
  if (typeof state.areaTo === "number" && state.areaTo !== (ranges.area?.actual_max ?? state.areaTo)) {
    params.set("area_to", String(state.areaTo));
  }
  if (typeof state.levelFrom === "number" && state.levelFrom !== (ranges.level?.actual_min ?? state.levelFrom)) {
    params.set("level_from", String(state.levelFrom));
  }
  if (typeof state.levelTo === "number" && state.levelTo !== (ranges.level?.actual_max ?? state.levelTo)) {
    params.set("level_to", String(state.levelTo));
  }

  return params;
};

const parkingRenderCard = (parking, favoriteKeys) => {
  const title = parkingEscapeHtml(parking.title || "Парковочное место");
  const projectName = parkingEscapeHtml(parking.project_name || "");
  const typeLabel = parkingEscapeHtml(parking.type_label || "Паркинг");
  const statusLabel = parkingEscapeHtml(parking.status_label || "");
  const priceTotal = parkingEscapeHtml(parking.price_total_formatted || "");
  const priceOld = parkingEscapeHtml(parking.price_old_formatted || "");
  const levelLabel = parkingEscapeHtml(parking.level_label || "");
  const areaLabel = parkingEscapeHtml(parking.area_total_formatted || "");
  const metaParts = [levelLabel, areaLabel].filter(Boolean);
  const badges = Array.isArray(parking.badges) ? parking.badges.filter(Boolean).slice(0, 2) : [];
  const isFavorite = favoriteKeys.includes(parking.favorite_key);
  const reserveNote = [
    title ? `Паркинг: ${title}` : "",
    projectName ? `ЖК: ${projectName}` : "",
    typeLabel ? `Тип: ${typeLabel}` : "",
    levelLabel ? levelLabel : "",
    areaLabel ? `Площадь: ${areaLabel}` : "",
    priceTotal ? `Цена: ${priceTotal}` : "",
    statusLabel ? `Статус: ${statusLabel}` : "",
  ]
    .filter(Boolean)
    .join(" | ");
  const canReserve = parking.status_key !== "sold" && parking.status_key !== "booked";
  const isBooked = parking.status_key === "booked";
  const statusClass =
    parking.status_key === "available"
      ? " parking-card__badge--available"
      : parking.status_key === "booked"
      ? " parking-card__badge--booked"
      : parking.status_key === "sold"
        ? " parking-card__badge--sold"
        : "";
  const allBadges = badges.slice();

  if (statusLabel !== "") {
    allBadges.push({
      label: statusLabel,
      className: statusClass,
    });
  }

  const badgesHtml = allBadges.length
    ? `<div class="parking-card__badges">${allBadges
        .map((badge) => {
          if (typeof badge === "string") {
            return `<span class="parking-card__badge">${parkingEscapeHtml(badge)}</span>`;
          }

          const className = badge && badge.className ? badge.className : "";
          const label = badge && badge.label ? badge.label : "";
          return `<span class="parking-card__badge${className}">${parkingEscapeHtml(label)}</span>`;
        })
        .join("")}</div>`
    : "";

  return `
    <article class="apartment-card parking-card" data-favorite-key="${parkingEscapeHtml(parking.favorite_key || "")}">
      <div class="apartment-card__list">
        <div class="apartment-card__summary">
          <div class="apartment-card__rooms">${title}</div>
          <div class="apartment-card__area">ЖК ${projectName}</div>
          ${badgesHtml}
        </div>
        <div class="parking-card__details">
          <div class="parking-card__type">${typeLabel}</div>
          <div class="parking-card__meta">${parkingEscapeHtml(metaParts.join(" · "))}</div>
        </div>
        <div class="parking-card__price">
          <div class="apartment-card__list-price">${priceTotal}</div>
          ${priceOld ? `<div class="parking-card__price-old">${priceOld}</div>` : ""}
        </div>
        <div class="parking-card__actions">
          ${canReserve
            ? `<button class="btn btn--primary parking-card__reserve" type="button" data-contact-open="contact" data-contact-title="Забронировать ${title}" data-contact-type="parking_reserve" data-contact-source="parking_catalog" data-contact-note="${parkingEscapeHtml(reserveNote)}">Забронировать</button>`
            : `<span class="parking-card__action-slot" aria-hidden="true"></span>`}
        </div>
        <div class="apartment-card__icons">
          <button class="apartment-card__icon apartment-card__action apartment-card__fav${isFavorite ? " is-active" : ""}" type="button" aria-label="В избранное" title="В избранное">
            <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>
      </div>
    </article>
  `;
};

const initParkingCatalog = () => {
  document.querySelectorAll("[data-parking-catalog]").forEach((root) => {
    const payload = parkingParsePayload(root);
    if (!payload) {
      return;
    }

    const results = root.querySelector("[data-parking-results]");
    const empty = root.querySelector("[data-parking-empty]");
    const countEls = root.querySelectorAll("[data-parking-count], [data-parking-summary]");
    const resetBtn = root.querySelector("[data-parking-reset]");
    const allParkings = Array.isArray(payload.parkings) ? payload.parkings : [];
    let favoriteKeys = parkingGetFavorites();

    const getState = () => ({
      projects: parkingUniqueValues(
        Array.from(root.querySelectorAll('input[data-sync-group="project"]:checked')).map((input) => input.dataset.syncValue || "")
      ),
      types: parkingUniqueValues(
        Array.from(root.querySelectorAll('input[data-sync-group="type"]:checked')).map((input) => input.dataset.syncValue || "")
      ),
      statuses: parkingUniqueValues(
        Array.from(root.querySelectorAll('input[data-sync-group="status"]:checked')).map((input) => input.dataset.syncValue || "")
      ),
      priceFrom: parkingReadRangeValue(root, "price-from", payload.ranges?.price?.actual_min ?? 0),
      priceTo: parkingReadRangeValue(root, "price-to", payload.ranges?.price?.actual_max ?? 0),
      areaFrom: parkingReadRangeValue(root, "area-from", payload.ranges?.area?.actual_min ?? 0),
      areaTo: parkingReadRangeValue(root, "area-to", payload.ranges?.area?.actual_max ?? 0),
      levelFrom: parkingReadRangeValue(root, "level-from", payload.ranges?.level?.actual_min ?? 0),
      levelTo: parkingReadRangeValue(root, "level-to", payload.ranges?.level?.actual_max ?? 0),
    });

    const filterItems = (state) =>
      allParkings.filter((parking) => {
        if (state.projects.length && !state.projects.includes(parking.project_code)) {
          return false;
        }
        if (state.types.length && !state.types.includes(parking.type_key)) {
          return false;
        }
        if (state.statuses.length && !state.statuses.includes(parking.status_key)) {
          return false;
        }
        if (typeof state.priceFrom === "number" && Number(parking.price_total || 0) < state.priceFrom) {
          return false;
        }
        if (typeof state.priceTo === "number" && Number(parking.price_total || 0) > state.priceTo) {
          return false;
        }
        if (typeof state.areaFrom === "number" && Number(parking.area_total || 0) < state.areaFrom) {
          return false;
        }
        if (typeof state.areaTo === "number" && Number(parking.area_total || 0) > state.areaTo) {
          return false;
        }
        const levelValue = Number(parking.level || 0);
        if (typeof state.levelFrom === "number" && levelValue < state.levelFrom) {
          return false;
        }
        if (typeof state.levelTo === "number" && levelValue > state.levelTo) {
          return false;
        }

        return true;
      });

    const render = () => {
      const state = getState();
      const filtered = filterItems(state);
      results.innerHTML = filtered.map((item) => parkingRenderCard(item, favoriteKeys)).join("");
      empty.hidden = filtered.length > 0;
      results.hidden = filtered.length === 0;

      countEls.forEach((el) => {
        el.textContent = `Найдено ${filtered.length} ${parkingPluralize(filtered.length)}`;
      });

      const hasFilters = state.projects.length
        || state.types.length
        || state.statuses.length
        || state.priceFrom !== (payload.ranges?.price?.actual_min ?? state.priceFrom)
        || state.priceTo !== (payload.ranges?.price?.actual_max ?? state.priceTo)
        || state.areaFrom !== (payload.ranges?.area?.actual_min ?? state.areaFrom)
        || state.areaTo !== (payload.ranges?.area?.actual_max ?? state.areaTo)
        || state.levelFrom !== (payload.ranges?.level?.actual_min ?? state.levelFrom)
        || state.levelTo !== (payload.ranges?.level?.actual_max ?? state.levelTo);

      if (resetBtn) {
        resetBtn.hidden = !hasFilters;
      }

      const params = parkingStateToQuery(state, payload);
      const nextUrl = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ""}`;
      window.history.replaceState({}, "", nextUrl);
    };

    root.addEventListener("input", (event) => {
      if (
        event.target.matches('input[data-sync-group], [data-range-input]')
        || event.target.closest(".filter__room")
      ) {
        render();
      }
    });

    root.addEventListener("click", (event) => {
      const favButton = event.target.closest(".apartment-card__fav");
      if (favButton) {
        event.preventDefault();
        const card = favButton.closest("[data-favorite-key]");
        const favoriteKey = card?.dataset.favoriteKey || "";
        if (!favoriteKey) {
          return;
        }
        if (favoriteKeys.includes(favoriteKey)) {
          favoriteKeys = favoriteKeys.filter((item) => item !== favoriteKey);
        } else {
          favoriteKeys = [...favoriteKeys, favoriteKey];
        }
        parkingSetFavorites(favoriteKeys);
        favButton.classList.toggle("is-active", favoriteKeys.includes(favoriteKey));
      }
    });

    if (resetBtn) {
      resetBtn.addEventListener("click", () => {
        root.querySelectorAll('input[data-sync-group]').forEach((input) => {
          input.checked = false;
          const field = input.closest(".input_field");
          if (field) {
            field.classList.remove("selected");
          }
        });

        ["price", "area", "level"].forEach((rangeKey) => {
          const slider = root.querySelector(`.range-slider[data-range="${rangeKey}"]`);
          const range = payload.ranges?.[rangeKey];
          if (slider?.noUiSlider && range) {
            slider.noUiSlider.set([range.actual_min, range.actual_max]);
          }
        });

        root.querySelectorAll(".filter__dropdown").forEach((dropdown) => {
          const btn = dropdown.querySelector(".filter__dropdown-menu-btn");
          if (btn?.dataset.defaultText) {
            btn.textContent = btn.dataset.defaultText;
          }
        });

        render();
      });
    }

    render();
  });
};

document.addEventListener("DOMContentLoaded", initParkingCatalog);
