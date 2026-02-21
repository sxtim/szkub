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

  const modalEl = modalWrap.querySelector(".projects-modal");
  const swiperEl = modalWrap.querySelector("[data-modal-swiper]");
  const swiperWrapperEl = modalWrap.querySelector("[data-modal-wrapper]");
  const paginationEl = modalWrap.querySelector("[data-modal-pagination]");
  const prevBtn = modalWrap.querySelector("[data-modal-prev]");
  const nextBtn = modalWrap.querySelector("[data-modal-next]");

  if (!modalEl || !swiperEl || !swiperWrapperEl) return;

  let lastActiveElement = null;
  let benefitList = [];
  let swiper = null;
  let isClosing = false;
  let openCleanupTimer = null;

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

  const clearTransitionClasses = () => {
    if (openCleanupTimer) {
      window.clearTimeout(openCleanupTimer);
      openCleanupTimer = null;
    }

    modalWrap.classList.remove(
      "overlay-appear-enter",
      "overlay-appear-enter-active",
      "overlay-appear-enter-to",
      "overlay-appear-leave",
      "overlay-appear-leave-active",
      "overlay-appear-leave-to"
    );
    modalEl.classList.remove(
      "modal-enter",
      "modal-enter-active",
      "modal-enter-to",
      "modal-leave",
      "modal-leave-active",
      "modal-leave-to"
    );
    swiperEl.classList.remove(
      "fade-content-enter",
      "fade-content-enter-active",
      "fade-content-enter-to",
      "fade-content-leave",
      "fade-content-leave-active",
      "fade-content-leave-to"
    );
  };

  const playOpenTransition = () => {
    clearTransitionClasses();
    modalWrap.classList.add("overlay-appear-enter");
    modalEl.classList.add("modal-enter");
    swiperEl.classList.add("fade-content-enter");

    modalWrap.hidden = false;

    void modalWrap.offsetHeight;

    requestAnimationFrame(() => {
      modalWrap.classList.add("overlay-appear-enter-active");
      modalEl.classList.add("modal-enter-active");
      swiperEl.classList.add("fade-content-enter-active");

      modalWrap.classList.remove("overlay-appear-enter");
      modalWrap.classList.add("overlay-appear-enter-to");
      modalEl.classList.remove("modal-enter");
      modalEl.classList.add("modal-enter-to");
      swiperEl.classList.remove("fade-content-enter");
      swiperEl.classList.add("fade-content-enter-to");
    });

    openCleanupTimer = window.setTimeout(() => {
      modalWrap.classList.remove("overlay-appear-enter-active", "overlay-appear-enter-to");
      modalEl.classList.remove("modal-enter-active", "modal-enter-to");
      swiperEl.classList.remove("fade-content-enter-active", "fade-content-enter-to");
      openCleanupTimer = null;
    }, 450);
  };

  const playCloseTransition = () => {
    clearTransitionClasses();
    modalWrap.classList.add("overlay-appear-leave", "overlay-appear-leave-active");
    modalEl.classList.add("modal-leave", "modal-leave-active");
    swiperEl.classList.add("fade-content-leave", "fade-content-leave-active");

    void modalWrap.offsetHeight;

    requestAnimationFrame(() => {
      modalWrap.classList.add("overlay-appear-leave-to");
      modalEl.classList.add("modal-leave-to");
      swiperEl.classList.add("fade-content-leave-to");
    });
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

  const destroySwiper = () => {
    if (swiper) {
      swiper.destroy(true, true);
      swiper = null;
    }
  };

  const updateNav = () => {
    const total = swiper?.slides?.length || benefitList.length || 1;
    const index = swiper?.activeIndex ?? 0;

    if (paginationEl) paginationEl.textContent = `${index + 1} / ${total}`;
    if (prevBtn instanceof HTMLButtonElement) prevBtn.disabled = index <= 0;
    if (nextBtn instanceof HTMLButtonElement) nextBtn.disabled = index >= total - 1;
  };

  const resetActiveScroll = () => {
    const activeScroll = modalWrap.querySelector(
      ".swiper-slide-active .projects-benefit-modal__scroll"
    );
    if (activeScroll instanceof HTMLElement) activeScroll.scrollTop = 0;
  };

  const createSlide = (benefit) => {
    const slide = document.createElement("div");
    slide.className = "swiper-slide projects-benefit-modal__slide";
    if (benefit?.id != null) slide.dataset.benefitId = String(benefit.id);

    const scroll = document.createElement("div");
    scroll.className = "projects-benefit-modal__scroll";
    slide.append(scroll);

    const mobileTitle = document.createElement("h4");
    mobileTitle.className =
      "projects-benefit-modal__title projects-benefit-modal__title--mobile";
    mobileTitle.textContent = benefit?.title || "";
    scroll.append(mobileTitle);

    const imageWrap = document.createElement("div");
    imageWrap.className = "projects-benefit-modal__imageWrap";
    const image = document.createElement("div");
    image.className = "projects-benefit-modal__image";
    if (benefit?.image) {
      image.style.backgroundImage = `url('${benefit.image}')`;
    }
    imageWrap.append(image);
    scroll.append(imageWrap);

    const descriptionWrap = document.createElement("div");
    descriptionWrap.className = "projects-benefit-modal__descriptionWrapper";

    const desktopTitle = document.createElement("h6");
    desktopTitle.className =
      "projects-benefit-modal__title projects-benefit-modal__title--desktop";
    desktopTitle.textContent = benefit?.title || "";
    descriptionWrap.append(desktopTitle);

    const text = document.createElement("div");
    text.className = "projects-benefit-modal__text";
    const content = benefit?.content || "";
    if (content) {
      text.innerHTML = content;
    } else {
      text.textContent = benefit?.description || "";
    }
    descriptionWrap.append(text);
    scroll.append(descriptionWrap);

    return slide;
  };

  const mountSlides = (list) => {
    swiperWrapperEl.textContent = "";
    list.forEach((benefit) => {
      swiperWrapperEl.append(createSlide(benefit));
    });
  };

  const initSwiper = (initialIndex = 0) => {
    destroySwiper();

    if (typeof window.Swiper !== "function") return null;

    swiper = new window.Swiper(swiperEl, {
      initialSlide: initialIndex,
      speed: 500,
      slidesPerView: 1,
      spaceBetween: 0,
      allowTouchMove: true,
      resistanceRatio: 0,
      watchOverflow: true,
      on: {
        init: () => {
          updateNav();
          resetActiveScroll();
        },
        slideChangeTransitionStart: () => {
          updateNav();
          resetActiveScroll();
        },
      },
    });

    return swiper;
  };

  const openModal = (benefit, list) => {
    if (isClosing) return;

    lastActiveElement =
      document.activeElement instanceof HTMLElement ? document.activeElement : null;
    benefitList = Array.isArray(list) && list.length ? list : [benefit];

    const initialIndex = Math.max(
      0,
      benefitList.findIndex((b) => b?.id === benefit?.id)
    );

    mountSlides(benefitList);
    lockBodyScroll();
    playOpenTransition();
    initSwiper(initialIndex);

    const closeBtn = modalWrap.querySelector("[data-modal-close]");
    if (closeBtn instanceof HTMLElement) closeBtn.focus();
  };

  const closeModal = () => {
    if (modalWrap.hidden || isClosing) return;
    isClosing = true;

    playCloseTransition();

    window.setTimeout(() => {
      modalWrap.hidden = true;
      clearTransitionClasses();
      destroySwiper();
      swiperWrapperEl.textContent = "";
      benefitList = [];
      unlockBodyScroll();

      if (lastActiveElement) lastActiveElement.focus();
      lastActiveElement = null;
      isClosing = false;
    }, 350);
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
      swiper?.slidePrev();
      return;
    }

    const next = event.target.closest("[data-modal-next]");
    if (next && !modalWrap.hidden) {
      event.preventDefault();
      swiper?.slideNext();
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
      swiper?.slidePrev();
    }
    if (event.key === "ArrowRight" && !modalWrap.hidden) {
      event.preventDefault();
      swiper?.slideNext();
    }
  });
};

const initConstructionSlider = () => {
  const swiperEl = document.querySelector("[data-construction-swiper]");
  if (!swiperEl) return;
  if (typeof window.Swiper !== "function") return;

  const section = swiperEl.closest(".construction");
  const paginationEl = section?.querySelector("[data-construction-pagination]");
  const prevBtn = section?.querySelector("[data-construction-prev]");
  const nextBtn = section?.querySelector("[data-construction-next]");

  const updateNav = (instance) => {
    const total = instance?.slides?.length || 1;
    const index = instance?.activeIndex ?? 0;

    if (paginationEl instanceof HTMLElement) {
      paginationEl.textContent = `${index + 1} / ${total}`;
    }

    if (prevBtn instanceof HTMLButtonElement) {
      prevBtn.disabled = instance ? instance.isBeginning : true;
    }
    if (nextBtn instanceof HTMLButtonElement) {
      nextBtn.disabled = instance ? instance.isEnd : true;
    }
  };

  const swiper = new window.Swiper(swiperEl, {
    speed: 500,
    slidesPerView: 3.15,
    spaceBetween: 16,
    allowTouchMove: true,
    resistanceRatio: 0,
    watchOverflow: true,
    breakpoints: {
      0: {
        slidesPerView: 1.05,
        spaceBetween: 12,
      },
      641: {
        slidesPerView: 2.1,
        spaceBetween: 16,
      },
      1025: {
        slidesPerView: 3.15,
        spaceBetween: 16,
      },
    },
    on: {
      init: function () {
        updateNav(this);
      },
      slideChange: function () {
        updateNav(this);
      },
      resize: function () {
        updateNav(this);
      },
    },
  });

  if (prevBtn instanceof HTMLButtonElement) {
    prevBtn.addEventListener("click", () => swiper.slidePrev());
  }

  if (nextBtn instanceof HTMLButtonElement) {
    nextBtn.addEventListener("click", () => swiper.slideNext());
  }

  updateNav(swiper);
};

const initConstructionModal = () => {
  const modalWrap = document.querySelector("[data-construction-modal]");
  if (!modalWrap) return;

  const modalEl = modalWrap.querySelector(".construction-modal");
  const swiperEl = modalWrap.querySelector("[data-construction-modal-swiper]");
  const swiperWrapperEl = modalWrap.querySelector("[data-construction-modal-wrapper]");
  const closeBtn = modalWrap.querySelector("[data-construction-modal-close]");
  const prevBtn = modalWrap.querySelector("[data-construction-modal-prev]");
  const nextBtn = modalWrap.querySelector("[data-construction-modal-next]");
  const currentEl = modalWrap.querySelector("[data-construction-modal-current]");
  const totalEl = modalWrap.querySelector("[data-construction-modal-total]");
  const dateEl = modalWrap.querySelector("[data-construction-modal-date]");
  const textEl = modalWrap.querySelector("[data-construction-modal-text]");

  if (!modalEl || !swiperEl || !swiperWrapperEl) return;

  let swiper = null;
  let lastActiveElement = null;
  let isClosing = false;
  let openCleanupTimer = null;

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

  const clearTransitionClasses = () => {
    if (openCleanupTimer) {
      window.clearTimeout(openCleanupTimer);
      openCleanupTimer = null;
    }

    modalWrap.classList.remove(
      "overlay-appear-enter",
      "overlay-appear-enter-active",
      "overlay-appear-enter-to",
      "overlay-appear-leave",
      "overlay-appear-leave-active",
      "overlay-appear-leave-to"
    );
    modalEl.classList.remove(
      "modal-enter",
      "modal-enter-active",
      "modal-enter-to",
      "modal-leave",
      "modal-leave-active",
      "modal-leave-to"
    );
  };

  const playOpenTransition = () => {
    clearTransitionClasses();
    modalWrap.classList.add("overlay-appear-enter");
    modalEl.classList.add("modal-enter");

    modalWrap.hidden = false;

    void modalWrap.offsetHeight;

    requestAnimationFrame(() => {
      modalWrap.classList.add("overlay-appear-enter-active");
      modalEl.classList.add("modal-enter-active");

      modalWrap.classList.remove("overlay-appear-enter");
      modalWrap.classList.add("overlay-appear-enter-to");
      modalEl.classList.remove("modal-enter");
      modalEl.classList.add("modal-enter-to");
    });

    openCleanupTimer = window.setTimeout(() => {
      modalWrap.classList.remove(
        "overlay-appear-enter-active",
        "overlay-appear-enter-to"
      );
      modalEl.classList.remove("modal-enter-active", "modal-enter-to");
      openCleanupTimer = null;
    }, 850);
  };

  const playCloseTransition = () => {
    clearTransitionClasses();
    modalWrap.classList.add("overlay-appear-leave", "overlay-appear-leave-active");
    modalEl.classList.add("modal-leave", "modal-leave-active");

    void modalWrap.offsetHeight;

    requestAnimationFrame(() => {
      modalWrap.classList.add("overlay-appear-leave-to");
      modalEl.classList.add("modal-leave-to");
    });
  };

  const destroySwiper = () => {
    if (swiper) {
      swiper.destroy(true, true);
      swiper = null;
    }
  };

  const updateNav = () => {
    const total = swiper?.slides?.length || 1;
    const index = swiper?.activeIndex ?? 0;

    if (currentEl instanceof HTMLElement) currentEl.textContent = String(index + 1);
    if (totalEl instanceof HTMLElement) totalEl.textContent = String(total);

    if (prevBtn instanceof HTMLButtonElement) {
      prevBtn.disabled = swiper ? swiper.isBeginning : true;
      prevBtn.classList.toggle(
        "swiper-button-disabled",
        prevBtn.disabled
      );
    }
    if (nextBtn instanceof HTMLButtonElement) {
      nextBtn.disabled = swiper ? swiper.isEnd : true;
      nextBtn.classList.toggle(
        "swiper-button-disabled",
        nextBtn.disabled
      );
    }
  };

  const createSlide = (image) => {
    const slide = document.createElement("div");
    slide.className = "swiper-slide";

    const media = document.createElement("div");
    media.className = "construction-modal__image";
    media.style.backgroundImage = `url('${image}')`;
    media.setAttribute("aria-hidden", "true");

    slide.append(media);
    return slide;
  };

  const mountSlides = (images) => {
    swiperWrapperEl.textContent = "";
    (images || []).forEach((image) => {
      if (!image) return;
      swiperWrapperEl.append(createSlide(image));
    });
  };

  const initSwiper = () => {
    destroySwiper();

    if (typeof window.Swiper !== "function") return null;

    swiper = new window.Swiper(swiperEl, {
      speed: 1000,
      slidesPerView: 1,
      spaceBetween: 0,
      allowTouchMove: true,
      resistanceRatio: 0.85,
      watchOverflow: true,
      on: {
        init: () => updateNav(),
        slideChangeTransitionStart: () => updateNav(),
      },
    });

    return swiper;
  };

  const openModal = (payload) => {
    if (isClosing) return;

    lastActiveElement =
      document.activeElement instanceof HTMLElement ? document.activeElement : null;

    if (dateEl instanceof HTMLElement) {
      dateEl.textContent = payload?.date || payload?.month || "";
    }

    if (textEl instanceof HTMLElement) {
      textEl.textContent = payload?.description || "";
    }

    mountSlides(payload?.images || []);
    lockBodyScroll();
    playOpenTransition();
    initSwiper();

    updateNav();
  };

  const closeModal = () => {
    if (modalWrap.hidden || isClosing) return;
    isClosing = true;

    playCloseTransition();

    window.setTimeout(() => {
      modalWrap.hidden = true;
      clearTransitionClasses();
      destroySwiper();
      swiperWrapperEl.textContent = "";
      unlockBodyScroll();

      if (lastActiveElement) lastActiveElement.focus();
      lastActiveElement = null;
      isClosing = false;
    }, 650);
  };

  document.addEventListener("click", (event) => {
    if (!modalWrap.hidden) {
      if (event.target === modalWrap) {
        closeModal();
        return;
      }

      const close = event.target.closest("[data-construction-modal-close]");
      if (close) {
        event.preventDefault();
        closeModal();
        return;
      }

      const prev = event.target.closest("[data-construction-modal-prev]");
      if (prev) {
        event.preventDefault();
        swiper?.slidePrev();
        return;
      }

      const next = event.target.closest("[data-construction-modal-next]");
      if (next) {
        event.preventDefault();
        swiper?.slideNext();
        return;
      }
    }

    const card = event.target.closest(".construction-card[data-construction]");
    if (!card) return;

    const payload = card.getAttribute("data-construction");
    if (!payload) return;

    try {
      openModal(JSON.parse(payload));
    } catch {
      // ignore invalid payload
    }
  });
};

const initProjectsPage = () => {
  initBenefitsModal();
  initConstructionSlider();
  initConstructionModal();
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initProjectsPage);
} else {
  initProjectsPage();
}
