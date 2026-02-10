document.addEventListener("DOMContentLoaded", () => {
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
