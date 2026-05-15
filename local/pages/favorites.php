<?php
define("FAVORITES_PAGE", true);
define("FOOTER_FLAT", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Избранное");
?>

<section class="favorites-page" data-favorites-page>
  <div class="breadcrumbs-wrap">
    <div class="container">
      <?php include $_SERVER["DOCUMENT_ROOT"] . "/local/templates/szcube/parts/breadcrumbs.php"; ?>
    </div>
  </div>

  <div class="container">
    <h1 class="catalog__title">Избранное</h1>
    <div class="favorites-page__empty" data-favorites-empty hidden>
      <p>В избранном пока ничего нет.</p>
      <div class="favorites-page__empty-actions">
        <a class="btn btn--primary favorites-page__empty-button" href="/apartments/">Выбрать квартиру</a>
        <a class="btn btn--primary favorites-page__empty-button" href="/commerce/">Выбрать коммерцию</a>
        <a class="btn btn--primary favorites-page__empty-button" href="/parking/">Выбрать парковку</a>
        <a class="btn btn--primary favorites-page__empty-button" href="/storerooms/">Выбрать кладовую</a>
      </div>
    </div>
    <div class="favorites-page__groups" data-favorites-groups></div>
  </div>
</section>

<script>
(function () {
  const groupLabels = {
    apartment: "Квартиры",
    commercial: "Коммерция",
    parking: "Паркинг",
    storeroom: "Кладовые",
  };
  const escapeHtml = (value) => window.szcubeCatalogShared.escapeHtml(value);

  const statusClass = (item) => {
    const status = String(item.status_key || "").trim();
    if (status === "available") {
      return " catalog-list-card__badge--available";
    }
    if (status === "booked") {
      return " catalog-list-card__badge--booked";
    }
    if (status === "sold") {
      return " catalog-list-card__badge--sold";
    }
    return "";
  };

  const canReserveCatalogItem = (item) => {
    const status = String(item.status_key || "").trim().toLowerCase();
    const label = String(item.status_label || "").trim().toLowerCase();
    return status !== "sold" && status !== "booked" && !/продан|забронир/u.test(label);
  };

  const reserveConfig = (item) => {
    if (item.entity_type === "storeroom") {
      return {
        leadType: "storeroom_reserve",
        leadSource: "storeroom_catalog",
        itemLabel: "Кладовка",
        typeLabel: "Формат",
      };
    }

    return {
      leadType: "parking_reserve",
      leadSource: "parking_catalog",
      itemLabel: "Паркинг",
      typeLabel: "Тип",
    };
  };

  const reserveButton = (item, detailsTitle, price) => {
    if (item.entity_type !== "parking" && item.entity_type !== "storeroom") {
      return "";
    }

    if (!canReserveCatalogItem(item)) {
      return `<span class="catalog-list-card__action-slot" aria-hidden="true"></span>`;
    }

    const config = reserveConfig(item);
    const note = [
      item.title ? `${config.itemLabel}: ${item.title}` : "",
      item.project_name ? `ЖК: ${item.project_name}` : "",
      detailsTitle ? `${config.typeLabel}: ${detailsTitle}` : "",
      price ? `Цена: ${price}` : "",
      item.status_label ? `Статус: ${item.status_label}` : "",
    ].filter(Boolean).join(" | ");

    return `
      <button
        class="btn btn--primary catalog-list-card__primary"
        type="button"
        data-contact-open="contact"
        data-contact-title="${escapeHtml(`Забронировать ${item.title || "лот"}`)}"
        data-contact-type="${escapeHtml(config.leadType)}"
        data-contact-source="${escapeHtml(config.leadSource)}"
        data-contact-note="${escapeHtml(note)}"
      >Забронировать</button>
    `;
  };

  const buildBoardUrl = (item) => {
    if (item.entity_type !== "apartment" || !item.code) {
      return "";
    }

    const baseUrl = item.project_filter_url || item.project_url || "/projects/";
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set("selector_view", "board");
    url.searchParams.set("selector_flat", item.code);

    return `${url.pathname}${url.search}${url.hash}`;
  };

  const renderItem = (item) => {
    const title = item.title || item.label || "Объект";
    const project = item.project_name ? `ЖК ${item.project_name}` : "";
    const detailsTitle = item.label || title;
    const metaParts = [item.project_delivery ? `Сдача ${item.project_delivery}` : "", item.status_label || ""].filter(Boolean);
    const price = item.price_total_formatted || "";
    const priceOld = item.price_old_formatted || "";
    const boardUrl = buildBoardUrl(item);
    const badges = Array.isArray(item.badges) ? item.badges.filter(Boolean).slice(0, 2) : [];
    const allBadges = badges.slice();
    if (item.status_label) {
      allBadges.push({
        label: item.status_label,
        className: statusClass(item),
      });
    }
    const badgesHtml = allBadges.length
      ? `<div class="catalog-list-card__badges">${allBadges
          .map((badge) => {
            if (typeof badge === "string") {
              return `<span class="catalog-list-card__badge">${escapeHtml(badge)}</span>`;
            }

            const className = badge && badge.className ? badge.className : "";
            const label = badge && badge.label ? badge.label : "";
            return `<span class="catalog-list-card__badge${className}">${escapeHtml(label)}</span>`;
          })
          .join("")}</div>`
      : "";
    const actionLabel = item.entity_type === "apartment" || item.entity_type === "commercial" ? "Подробнее" : "В каталог";

    return `
      <article class="apartment-card catalog-list-card">
        <div class="apartment-card__list">
          <div class="apartment-card__summary">
            <div class="apartment-card__rooms">${escapeHtml(title)}</div>
            ${project ? `<div class="apartment-card__area">${escapeHtml(project)}</div>` : ""}
            ${badgesHtml}
          </div>
          <div class="catalog-list-card__details">
            <div class="catalog-list-card__type">${escapeHtml(detailsTitle)}</div>
            ${metaParts.length ? `<div class="catalog-list-card__meta">${escapeHtml(metaParts.join(" · "))}</div>` : ""}
          </div>
          <div class="catalog-list-card__price">
            <div class="apartment-card__list-price">${escapeHtml(price)}</div>
            ${priceOld ? `<div class="catalog-list-card__price-old">${escapeHtml(priceOld)}</div>` : ""}
          </div>
          <div class="catalog-list-card__actions">
            ${item.entity_type === "parking" || item.entity_type === "storeroom"
              ? reserveButton(item, detailsTitle, price)
              : `<a class="btn btn--primary catalog-list-card__primary" href="${escapeHtml(item.url || "#")}">${escapeHtml(actionLabel)}</a>`}
          </div>
          <div class="apartment-card__icons">
            ${boardUrl ? `<button class="apartment-card__icon apartment-card__action apartment-card__board" type="button" data-board-url="${escapeHtml(boardUrl)}" aria-label="Показать на шахматке" title="Показать на шахматке">
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M3.5 3.5H7.25V7.25H3.5V3.5Z" stroke="currentColor" stroke-width="1.2"/>
                <path d="M10.75 3.5H14.5V7.25H10.75V3.5Z" stroke="currentColor" stroke-width="1.2"/>
                <path d="M3.5 10.75H7.25V14.5H3.5V10.75Z" stroke="currentColor" stroke-width="1.2"/>
                <path d="M10.75 10.75H14.5V14.5H10.75V10.75Z" stroke="currentColor" stroke-width="1.2"/>
              </svg>
            </button>` : ""}
            <button
              class="apartment-card__icon apartment-card__action apartment-card__fav is-active"
              type="button"
              data-favorite-type="${escapeHtml(item.entity_type)}"
              data-favorite-id="${escapeHtml(String(item.entity_id))}"
              data-favorite-key="${escapeHtml(item.key)}"
              aria-label="Убрать из избранного"
              aria-pressed="true"
              title="Убрать из избранного"
            >
              <svg width="13" height="12" viewBox="0 0 13 12" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M6.37256 1.89355C5.22588 0.557201 3.30974 0.144211 1.873 1.36791C0.436265 2.5916 0.233992 4.63754 1.36227 6.08483C2.30036 7.28811 5.13934 9.826 6.0698 10.6474C6.17387 10.7393 6.22593 10.7853 6.28666 10.8033C6.33962 10.8191 6.39761 10.8191 6.45063 10.8033C6.51136 10.7853 6.56336 10.7393 6.66749 10.6474C7.59796 9.826 10.4369 7.28811 11.375 6.08483C12.5033 4.63754 12.3257 2.57873 10.8642 1.36791C9.40281 0.157083 7.51925 0.557201 6.37256 1.89355Z" stroke="#8C8C8C" stroke-width="1.27452" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </div>
        </div>
      </article>
    `;
  };

  const renderPage = (root, payload) => {
    const groupsEl = root.querySelector("[data-favorites-groups]");
    const emptyEl = root.querySelector("[data-favorites-empty]");
    const groups = payload.groups || {};
    const orderedTypes = ["apartment", "commercial", "parking", "storeroom"];
    const html = orderedTypes
      .filter((type) => Array.isArray(groups[type]) && groups[type].length > 0)
      .map((type) => `
        <section class="favorites-page__group">
          <h2 class="favorites-page__group-title">${groupLabels[type]}</h2>
          <div class="favorites-page__list catalog-grid is-list">${groups[type].map(renderItem).join("")}</div>
        </section>
      `)
      .join("");

    groupsEl.innerHTML = html;
    groupsEl.hidden = html === "";
    emptyEl.hidden = html !== "";
    window.szcubeCatalogShared.setFavoritesCount(payload.count || 0);
  };

  const loadFavorites = async (root) => {
    try {
      const payload = await window.szcubeCatalogShared.requestFavorites("list");
      renderPage(root, payload);
    } catch (error) {
      renderPage(root, { groups: {}, count: 0 });
    }
  };

  const initFavoritesPage = () => {
    const root = document.querySelector("[data-favorites-page]");
    if (!root || !window.szcubeCatalogShared) {
      return;
    }

    loadFavorites(root);

    root.addEventListener("click", async (event) => {
      const boardButton = event.target.closest(".apartment-card__board");
      if (boardButton) {
        event.preventDefault();
        window.location.href = boardButton.dataset.boardUrl || "#";
        return;
      }

      const favoriteButton = event.target.closest("[data-favorite-type][data-favorite-id]");
      if (!favoriteButton) {
        return;
      }

      event.preventDefault();
      await window.szcubeCatalogShared.toggleFavoriteButton(favoriteButton);
      loadFavorites(root);
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initFavoritesPage);
  } else {
    initFavoritesPage();
  }
})();
</script>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
