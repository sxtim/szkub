(() => {
  const escapeHtml = (value) =>
    String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const uniqueValues = (values) => Array.from(new Set((Array.isArray(values) ? values : []).filter(Boolean)));

  const readRangeValue = (root, key, fallback) => {
    const input = root.querySelector(`[data-range-input="${key}"]`);
    const value = input ? Number(input.value) : Number.NaN;
    return Number.isFinite(value) ? value : fallback;
  };

  const getStorageArray = (storageKey) => {
    try {
      const raw = window.localStorage.getItem(storageKey);
      if (!raw) {
        return [];
      }

      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  };

  const setStorageArray = (storageKey, items) => {
    try {
      window.localStorage.setItem(storageKey, JSON.stringify(items));
    } catch (error) {
      // noop
    }
  };

  const favoritesEndpoint = "/local/ajax/favorites.php";

  const favoriteKey = (type, id) => {
    const normalizedType = String(type ?? "").trim();
    const normalizedId = String(id ?? "").trim();
    return normalizedType && normalizedId ? `${normalizedType}:${normalizedId}` : "";
  };

  const favoriteKeyFromButton = (button) => {
    if (!(button instanceof HTMLElement)) {
      return "";
    }

    return button.dataset.favoriteKey || favoriteKey(button.dataset.favoriteType, button.dataset.favoriteId);
  };

  const getFavoriteButtons = (root = document) =>
    Array.from(root.querySelectorAll("[data-favorite-type][data-favorite-id]"));

  const setFavoriteButtonState = (button, active) => {
    if (!(button instanceof HTMLElement)) {
      return;
    }

    button.classList.toggle("is-active", Boolean(active));
    button.setAttribute("aria-pressed", active ? "true" : "false");
    button.setAttribute("aria-label", active ? "Убрать из избранного" : "В избранное");
    button.setAttribute("title", active ? "Убрать из избранного" : "В избранное");
  };

  const syncFavoriteButtons = (type, id, active, root = document) => {
    const key = favoriteKey(type, id);
    if (!key) {
      return;
    }

    getFavoriteButtons(root).forEach((button) => {
      if (favoriteKeyFromButton(button) === key) {
        setFavoriteButtonState(button, active);
      }
    });
  };

  const setFavoritesCount = (count) => {
    const normalizedCount = Math.max(0, Number(count) || 0);
    document.querySelectorAll("[data-favorites-count]").forEach((counter) => {
      counter.textContent = String(normalizedCount);
      counter.hidden = normalizedCount <= 0;
    });
    document.querySelectorAll("[data-favorites-link]").forEach((link) => {
      link.setAttribute("aria-label", normalizedCount > 0 ? `Избранное, ${normalizedCount}` : "Избранное");
    });
  };

  const requestFavorites = async (action, data = {}) => {
    const body = new URLSearchParams();
    body.set("action", action);

    Object.entries(data).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        value.forEach((item) => body.append(`${key}[]`, String(item)));
        return;
      }

      body.set(key, String(value ?? ""));
    });

    const response = await fetch(favoritesEndpoint, {
      method: "POST",
      body,
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
    });
    const payload = await response.json();

    if (!response.ok || !payload || payload.success !== true) {
      throw new Error((payload && payload.message) || "Favorites request failed");
    }

    return payload;
  };

  const hydrateFavorites = async (root = document) => {
    const keys = uniqueValues(getFavoriteButtons(root).map(favoriteKeyFromButton));

    try {
      const payload = await requestFavorites("state", { items: keys });
      Object.values(payload.items || {}).forEach((item) => {
        syncFavoriteButtons(item.entity_type, item.entity_id, item.in_favorite, root);
      });
      setFavoritesCount(payload.count || 0);
      return payload;
    } catch (error) {
      return null;
    }
  };

  const toggleFavoriteButton = async (button) => {
    if (!(button instanceof HTMLElement) || button.classList.contains("is-loading")) {
      return null;
    }

    const type = button.dataset.favoriteType || "";
    const id = button.dataset.favoriteId || "";
    const key = favoriteKey(type, id);
    if (!key) {
      return null;
    }

    const previousState = button.classList.contains("is-active");
    const nextState = !previousState;
    button.classList.add("is-loading");
    syncFavoriteButtons(type, id, nextState);

    try {
      const payload = await requestFavorites("toggle", {
        entity_type: type,
        entity_id: id,
      });
      const item = payload.item || {};
      syncFavoriteButtons(item.entity_type || type, item.entity_id || id, Boolean(item.in_favorite));
      setFavoritesCount(payload.count || 0);
      document.dispatchEvent(
        new CustomEvent("szcube:favorites-changed", {
          detail: {
            key,
            entityType: item.entity_type || type,
            entityId: item.entity_id || id,
            inFavorite: Boolean(item.in_favorite),
            count: payload.count || 0,
          },
        })
      );
      return payload;
    } catch (error) {
      syncFavoriteButtons(type, id, previousState);
      return null;
    } finally {
      getFavoriteButtons().forEach((item) => {
        if (favoriteKeyFromButton(item) === key) {
          item.classList.remove("is-loading");
        }
      });
    }
  };

  const initFavorites = () => {
    hydrateFavorites(document);

    document.addEventListener("szcube:favorites-changed", (event) => {
      if (event.detail && typeof event.detail.count !== "undefined") {
        setFavoritesCount(event.detail.count);
      }
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initFavorites);
  } else {
    initFavorites();
  }

  window.szcubeCatalogShared = {
    escapeHtml,
    uniqueValues,
    readRangeValue,
    getStorageArray,
    setStorageArray,
    getFavoriteButtons,
    hydrateFavorites,
    toggleFavoriteButton,
    syncFavoriteButtons,
    setFavoritesCount,
    requestFavorites,
  };
})();
