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

  window.szcubeCatalogShared = {
    escapeHtml,
    uniqueValues,
    readRangeValue,
    getStorageArray,
    setStorageArray,
  };
})();
