document.addEventListener("DOMContentLoaded", () => {
  const contactModal = document.querySelector('[data-contact-modal="contact"]');
  const contactModalTitle = contactModal ? contactModal.querySelector("[data-contact-modal-title]") : null;
  const defaultContactModalTitle = contactModalTitle ? contactModalTitle.textContent : "";
  const contactModalCloseButtons = contactModal
    ? contactModal.querySelectorAll("[data-contact-modal-close]")
    : [];
  const contactOpenButtons = document.querySelectorAll('[data-contact-open="contact"]');

  const openContactModal = (opts = {}) => {
    if (!contactModal) return;
    if (contactModalTitle) {
      const nextTitle = typeof opts.title === "string" && opts.title.trim() !== "" ? opts.title.trim() : defaultContactModalTitle;
      contactModalTitle.textContent = nextTitle;
    }
    contactModal.classList.add("is-open");
    contactModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");
  };

  const closeContactModal = () => {
    if (!contactModal) return;
    contactModal.classList.remove("is-open");
    contactModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");
  };

  if (contactModal) {
    contactOpenButtons.forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        openContactModal({ title: btn.dataset.contactTitle });
      });
    });

    contactModalCloseButtons.forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        closeContactModal();
      });
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && contactModal.classList.contains("is-open")) {
        closeContactModal();
      }
    });
  }

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
