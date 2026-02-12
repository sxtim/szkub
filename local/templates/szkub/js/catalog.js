document.addEventListener("DOMContentLoaded", () => {
  const sortDropdown = document.querySelector("[data-sort-dropdown]");
  const sortToggle = sortDropdown?.querySelector("[data-sort-toggle]");
  const sortMenu = sortDropdown?.querySelector("[data-sort-menu]");
  const sortOptions = sortDropdown?.querySelectorAll(".catalog__sort-option") ?? [];

  const closeSortMenu = () => {
    if (!sortToggle || !sortMenu) return;
    sortMenu.classList.remove("active");
    sortToggle.classList.remove("open");
  };

  const openSortMenu = () => {
    if (!sortToggle || !sortMenu) return;
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

  const viewContainer = document.querySelector("[data-view-container]");
  const viewButtons = document.querySelectorAll(".catalog__view-btn");

  const setView = (view) => {
    if (!viewContainer || !viewButtons.length) return;
    viewContainer.classList.toggle("is-list", view === "list");
    viewContainer.classList.toggle("is-grid", view === "grid");
    viewButtons.forEach((btn) => {
      const isActive = btn.dataset.view === view;
      btn.classList.toggle("is-active", isActive);
      btn.classList.toggle("btn--primary", isActive);
      btn.classList.toggle("btn--outline", !isActive);
    });
    localStorage.setItem("catalogView", view);
  };

  if (viewContainer && viewButtons.length) {
    const saved = localStorage.getItem("catalogView");
    setView(saved === "list" ? "list" : "grid");
    viewButtons.forEach((btn) => {
      btn.addEventListener("click", () => setView(btn.dataset.view));
    });
  }

  document.querySelectorAll(".apartment-card__fav").forEach((button) => {
    button.addEventListener("click", () => {
      button.classList.toggle("is-active");
    });
  });
});
