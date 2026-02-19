document.addEventListener("click", (event) => {
  const tab = event.target.closest("[data-benefit-tab]");
  if (!tab) return;

  const tabsContainer = tab.closest(".projects-benefits__tabs");
  if (!tabsContainer) return;

  const benefitsSection = tab.closest(".projects-benefits");
  const benefitsBody = benefitsSection?.querySelector("[data-benefits-body]");
  const items = benefitsBody?.querySelectorAll("[data-benefit-category]") || [];

  const tabs = tabsContainer.querySelectorAll("[data-benefit-tab]");
  tabs.forEach((button) => {
    const isActive = button === tab;
    button.classList.toggle("is-active", isActive);
    button.setAttribute("aria-selected", isActive ? "true" : "false");
  });

  const activeCategory = tab.dataset.benefitTab;
  items.forEach((item) => {
    const category = item.dataset.benefitCategory;
    const isVisible = activeCategory === "all" || category === activeCategory;
    item.toggleAttribute("hidden", !isVisible);
  });
});

const initBenefitsModal = () => {
  const modalWrap = document.querySelector("[data-benefit-modal]");
  if (!modalWrap) return;

  const titleEl = modalWrap.querySelector("[data-modal-title]");
  const titleMobileEl = modalWrap.querySelector("[data-modal-title-mobile]");
  const textEl = modalWrap.querySelector("[data-modal-text]");
  const imageEl = modalWrap.querySelector("[data-modal-image]");
  const slideEl = modalWrap.querySelector(".projects-benefit-modal__slide");
  const paginationEl = modalWrap.querySelector("[data-modal-pagination]");
  const prevBtn = modalWrap.querySelector("[data-modal-prev]");
  const nextBtn = modalWrap.querySelector("[data-modal-next]");

  let lastActiveElement = null;
  let benefitList = [];
  let currentIndex = 0;

  const getScrollbarWidth = () =>
    window.innerWidth - document.documentElement.clientWidth;

  const lockBodyScroll = () => {
    const scrollY = window.scrollY || window.pageYOffset || 0;
    const scrollbarWidth = Math.max(0, getScrollbarWidth());

    document.body.style.paddingRight = `${scrollbarWidth}px`;
    document.body.dataset.scrollY = String(scrollY);
    document.body.style.top = `-${scrollY}px`;
    document.body.classList.add("scroll-lock");
  };

  const unlockBodyScroll = () => {
    const savedScrollY = parseInt(document.body.dataset.scrollY || "0", 10) || 0;

    document.body.style.paddingRight = "";
    document.body.style.top = "";
    document.body.classList.remove("scroll-lock");
    delete document.body.dataset.scrollY;

    window.scrollTo(0, savedScrollY);
  };

  const getVisibleBenefits = () => {
    const cards = document.querySelectorAll(
      ".projects-benefits__item:not([hidden]) .projects-benefit-card[data-benefit]"
    );
    const list = [];
    cards.forEach((card) => {
      const payload = card.getAttribute("data-benefit");
      if (!payload) return;
      try {
        list.push(JSON.parse(payload));
      } catch {
        // ignore invalid payload
      }
    });
    return list;
  };

  const updateNav = () => {
    const total = benefitList.length || 1;
    const index = Math.min(Math.max(currentIndex, 0), total - 1);
    currentIndex = index;

    if (paginationEl) paginationEl.textContent = `${index + 1} / ${total}`;

    const isPrevDisabled = index <= 0;
    const isNextDisabled = index >= total - 1;

    if (prevBtn instanceof HTMLButtonElement) prevBtn.disabled = isPrevDisabled;
    if (nextBtn instanceof HTMLButtonElement) nextBtn.disabled = isNextDisabled;
  };

  const fillModal = (benefit) => {
    if (titleEl) titleEl.textContent = benefit.title || "";
    if (titleMobileEl) titleMobileEl.textContent = benefit.title || "";
    if (textEl) {
      const content = benefit.content || "";
      if (content) {
        textEl.innerHTML = content;
      } else {
        textEl.textContent = benefit.description || "";
      }
    }
    if (imageEl) {
      imageEl.style.backgroundImage = benefit.image ? `url('${benefit.image}')` : "";
    }
  };

  const renderByIndex = (index) => {
    currentIndex = index;
    const benefit = benefitList[currentIndex];
    if (benefit) fillModal(benefit);
    if (slideEl instanceof HTMLElement) slideEl.scrollTop = 0;
    updateNav();
  };

  const openModal = (benefit, list) => {
    lastActiveElement =
      document.activeElement instanceof HTMLElement ? document.activeElement : null;
    benefitList = Array.isArray(list) && list.length ? list : [benefit];

    const foundIndex = benefitList.findIndex((b) => b?.id === benefit?.id);
    currentIndex = foundIndex >= 0 ? foundIndex : 0;

    renderByIndex(currentIndex);
    modalWrap.hidden = false;
    lockBodyScroll();

    const closeBtn = modalWrap.querySelector("[data-modal-close]");
    if (closeBtn instanceof HTMLElement) closeBtn.focus();
  };

  const closeModal = () => {
    if (modalWrap.hidden) return;
    modalWrap.hidden = true;
    unlockBodyScroll();

    if (lastActiveElement) lastActiveElement.focus();
    lastActiveElement = null;
  };

  document.addEventListener("click", (event) => {
    if (event.target === modalWrap && !modalWrap.hidden) {
      closeModal();
      return;
    }

    const close = event.target.closest("[data-modal-close]");
    if (close && !modalWrap.hidden) {
      event.preventDefault();
      closeModal();
      return;
    }

    const prev = event.target.closest("[data-modal-prev]");
    if (prev && !modalWrap.hidden) {
      event.preventDefault();
      if (currentIndex > 0) renderByIndex(currentIndex - 1);
      return;
    }

    const next = event.target.closest("[data-modal-next]");
    if (next && !modalWrap.hidden) {
      event.preventDefault();
      if (currentIndex < benefitList.length - 1) renderByIndex(currentIndex + 1);
      return;
    }

    const benefitCard = event.target.closest(".projects-benefit-card");
    if (!benefitCard) return;

    const payload = benefitCard.getAttribute("data-benefit");
    if (!payload) return;

    try {
      const benefit = JSON.parse(payload);
      openModal(benefit, getVisibleBenefits());
    } catch {
      // ignore invalid payload
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !modalWrap.hidden) {
      event.preventDefault();
      closeModal();
    }
    if (event.key === "ArrowLeft" && !modalWrap.hidden) {
      event.preventDefault();
      if (currentIndex > 0) renderByIndex(currentIndex - 1);
    }
    if (event.key === "ArrowRight" && !modalWrap.hidden) {
      event.preventDefault();
      if (currentIndex < benefitList.length - 1) renderByIndex(currentIndex + 1);
    }
  });
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initBenefitsModal);
} else {
  initBenefitsModal();
}
