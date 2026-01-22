document.addEventListener("DOMContentLoaded", () => {
  const navBtn = document.querySelector(".mobile-nav-btn");
  const nav = document.querySelector(".mobile-nav");
  const menuIcon = document.querySelector(".nav-icon");
  const extraCards = document.querySelectorAll(".extra-card");

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

  extraCards.forEach((card) => {
    const overlay = card.querySelector(".extra-card__overlay");
    if (!overlay) {
      return;
    }

    card.addEventListener("mouseenter", () => {
      card.classList.remove("extra-card--reset");
    });

    card.addEventListener("mouseleave", () => {
      card.classList.remove("extra-card--reset");
      void overlay.offsetWidth;
      card.classList.add("extra-card--reset");
    });

    overlay.addEventListener("animationend", () => {
      card.classList.remove("extra-card--reset");
    });
  });
});
