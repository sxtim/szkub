document.addEventListener("DOMContentLoaded", () => {
  const navBtn = document.querySelector(".mobile-nav-btn");
  const nav = document.querySelector(".mobile-nav");
  const menuIcon = document.querySelector(".nav-icon");
  const navMore = document.querySelector(".nav__more");
  const navMoreBtn = document.querySelector(".nav__more-btn");

  if (navBtn && nav && menuIcon) {
    const toggleNav = () => {
      nav.classList.toggle("mobile-nav--open");
      menuIcon.classList.toggle("nav-icon--active");
      document.body.classList.toggle("no-scroll");
    };

    navBtn.addEventListener("click", toggleNav);

    nav.addEventListener("click", (event) => {
      const link = event.target.closest("a");
      if (link) {
        toggleNav();
      }
    });
  }

  if (navMore && navMoreBtn) {
    const closeDropdown = () => {
      navMore.classList.remove("is-open");
      navMoreBtn.setAttribute("aria-expanded", "false");
    };

    navMoreBtn.addEventListener("click", (event) => {
      event.preventDefault();
      const isOpen = navMore.classList.toggle("is-open");
      navMoreBtn.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (event) => {
      if (!navMore.contains(event.target)) {
        closeDropdown();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeDropdown();
      }
    });
  }
});
