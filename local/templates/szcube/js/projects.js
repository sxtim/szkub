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

  const updateNav = (instance = swiper) => {
    const total = instance?.slides?.length || benefitList.length || 1;
    const index = instance?.activeIndex ?? 0;

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
        init: function () {
          updateNav(this);
          resetActiveScroll();
        },
        slideChangeTransitionStart: function () {
          updateNav(this);
          resetActiveScroll();
        },
      },
    });

    updateNav(swiper);

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

const initProjectApartmentSelector = () => {
  const selectors = document.querySelectorAll("[data-project-selector]");
  if (!selectors.length) return;

  const moneyFormatter = new Intl.NumberFormat("ru-RU");

  const formatPrice = (value) => {
    const numeric = Number(value) || 0;
    if (numeric <= 0) return "";
    return `${moneyFormatter.format(Math.round(numeric))} ₽`;
  };

  const parsePayload = (value) => {
    if (!value) return null;
    try {
      return JSON.parse(value);
    } catch {
      return null;
    }
  };

  const parseBadges = (value) => {
    const parsed = parsePayload(value);
    if (!Array.isArray(parsed)) {
      return [];
    }

    return Array.from(
      new Set(
        parsed
          .map((item) => String(item ?? "").trim())
          .filter(Boolean)
      )
    );
  };

  selectors.forEach((root) => {
    const state = parsePayload(root.getAttribute("data-project-selector-state")) || {};
    const sceneView = root.querySelector('[data-selector-view="scene"]');
    const boardView = root.querySelector('[data-selector-view="board"]');
    const sceneStage = root.querySelector(".projects-selector__scene-stage");
    const sceneOverlay = root.querySelector("[data-scene-overlay]");
    const entrancePins = Array.from(root.querySelectorAll("[data-entrance-trigger]"));
    const scenePins = Array.from(
      root.querySelectorAll(".projects-selector__pin[data-entrance-modifier]")
    );
    const entranceCards = Array.from(root.querySelectorAll("[data-entrance-card]"));
    const entranceBoards = Array.from(root.querySelectorAll("[data-entrance-board]"));
    const entranceSwitches = Array.from(root.querySelectorAll("[data-selector-switch-entrance]"));
    const chooseButtons = Array.from(root.querySelectorAll("[data-selector-open-board]"));
    const closeSceneButtons = Array.from(root.querySelectorAll("[data-selector-close-scene-card]"));
    const backButton = root.querySelector("[data-selector-back]");
    const activeEntranceBadge = root.querySelector("[data-selector-active-entrance]");
    const lotCard = root.querySelector("[data-selector-lot-card]");
    const lotDetail = lotCard?.querySelector("[data-lot-detail]") || null;
    const lotProject = lotCard?.querySelector("[data-lot-project]") || null;
    const lotDelivery = lotCard?.querySelector("[data-lot-delivery]") || null;
    const lotImage = lotCard?.querySelector("[data-lot-image]") || null;
    const lotMeta = lotCard?.querySelector("[data-lot-meta]") || null;
    const lotPriceMain = lotCard?.querySelector("[data-lot-price-main]") || null;
    const lotPriceOld = lotCard?.querySelector("[data-lot-price-old]") || null;
    const lotBadges = lotCard?.querySelector("[data-lot-badges]") || null;
    const lotClose = lotCard?.querySelector("[data-selector-close-lot]") || null;
    const lots = Array.from(root.querySelectorAll("[data-flat-id]"));
    const sceneSections = Array.from(root.querySelectorAll("[data-section-overlay]"));
    const lotPlaceholderImage =
      "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==";

    let activeEntranceId =
      state.initialEntranceId || entrancePins[0]?.dataset.entranceTrigger || "";
    let currentView = "scene";
    let sceneCardFrame = 0;

    const desktopCardMedia = window.matchMedia("(max-width: 640px)");

    const setHoveredEntrance = (modifier) => {
      if (modifier) {
        root.dataset.hoveredEntrance = modifier;
        return;
      }

      delete root.dataset.hoveredEntrance;
    };

    const findPinByModifier = (modifier) =>
      entrancePins.find(
        (button) => button.dataset.entranceModifier === modifier
      ) || null;

    const hideSceneCards = () => {
      entranceCards.forEach((card) => {
        card.hidden = true;
        card.classList.remove("is-active");
        clearSceneCardPosition(card);
      });
    };

    const clearSceneCardPosition = (card) => {
      if (!card) return;

      delete card.dataset.cardSide;
      card.style.removeProperty("--card-left");
      card.style.removeProperty("--card-top");
      card.style.removeProperty("--card-transform");
    };

    const positionSceneCard = (entranceId) => {
      if (!sceneStage || desktopCardMedia.matches || currentView !== "scene") {
        return;
      }

      const pin = entrancePins.find(
        (button) => button.dataset.entranceTrigger === entranceId
      );
      const card = entranceCards.find(
        (item) => item.dataset.entranceCard === entranceId
      );

      if (!pin || !card || card.hidden || !card.classList.contains("is-active")) {
        return;
      }

      const stageRect = sceneStage.getBoundingClientRect();
      const pinRect = pin.getBoundingClientRect();
      const cardRect = card.getBoundingClientRect();

      if (!stageRect.width || !cardRect.width) {
        return;
      }

      const gap = 18;
      const padding = 12;
      const pinCenterX = pinRect.left + pinRect.width / 2 - stageRect.left;
      const pinTopY = pinRect.top - stageRect.top;
      const leftCandidate = pinRect.left - stageRect.left - gap - cardRect.width;
      const rightCandidate = pinRect.right - stageRect.left + gap;
      const maxLeft = stageRect.width - cardRect.width - padding;
      const pinStyles = window.getComputedStyle(pin);
      const forcedSide = (pinStyles.getPropertyValue("--card-side") || "").trim();
      const preferredSide =
        forcedSide === "left" || forcedSide === "right"
          ? forcedSide
          : pinCenterX < stageRect.width / 2
            ? "left"
            : "right";
      const cardOffsetX = parseFloat(pinStyles.getPropertyValue("--card-offset-x") || "0") || 0;
      const cardOffsetY = parseFloat(pinStyles.getPropertyValue("--card-offset-y") || "0") || 0;

      let side = preferredSide;
      let nextLeft = side === "left" ? leftCandidate : rightCandidate;

      if (side === "left" && nextLeft < padding && rightCandidate <= maxLeft) {
        side = "right";
        nextLeft = rightCandidate;
      } else if (
        side === "right" &&
        rightCandidate > maxLeft &&
        leftCandidate >= padding
      ) {
        side = "left";
        nextLeft = leftCandidate;
      }

      nextLeft = Math.min(Math.max(nextLeft + cardOffsetX, padding), maxLeft);

      const nextTop = Math.min(
        Math.max(pinTopY - 8 + cardOffsetY, padding),
        stageRect.height - cardRect.height - padding
      );

      card.dataset.cardSide = side;
      card.style.setProperty("--card-left", `${Math.round(nextLeft)}px`);
      card.style.setProperty("--card-top", `${Math.round(nextTop)}px`);
      card.style.setProperty("--card-transform", "none");
    };

    const scheduleActiveCardPosition = () => {
      if (!activeEntranceId) return;

      window.cancelAnimationFrame(sceneCardFrame);
      sceneCardFrame = window.requestAnimationFrame(() => {
        positionSceneCard(activeEntranceId);
      });
    };

    const clearSelectedLots = () => {
      lots.forEach((button) => {
        button.classList.remove("is-selected");
        button.removeAttribute("aria-current");
      });
    };

    const hideLotCard = () => {
      if (!lotCard) return;
      clearSelectedLots();
      lotCard.hidden = true;
      root.classList.remove("is-lot-open");
    };

    const showLotCard = (button) => {
      if (!lotCard || !button) return;

      clearSelectedLots();
      button.classList.add("is-selected");
      button.setAttribute("aria-current", "true");

      if (lotDetail) {
        lotDetail.href = button.dataset.flatUrl || "#";
      }

      if (lotProject) {
        lotProject.textContent = button.dataset.flatProject || "";
      }

      if (lotDelivery) {
        const delivery = button.dataset.flatDelivery || "";
        lotDelivery.textContent = delivery;
        lotDelivery.hidden = !delivery;
      }

      if (lotImage) {
        const imageSrc = button.dataset.flatImage || "";
        lotImage.src = imageSrc || lotPlaceholderImage;
        lotImage.alt = imageSrc
          ? button.dataset.flatImageAlt || button.dataset.flatTitle || "Планировка"
          : "";
        lotImage.hidden = !imageSrc;
      }

      if (lotMeta) {
        const metaParts = [];
        const rooms = button.dataset.flatRooms || "";
        const area = button.dataset.flatArea || "";
        const floorDisplay = button.dataset.flatFloorDisplay || "";

        if (rooms) metaParts.push(rooms);
        if (area) metaParts.push(`${area} м²`);
        if (floorDisplay) {
          metaParts.push(floorDisplay);
        }

        lotMeta.textContent = metaParts.join(" • ");
        lotMeta.hidden = metaParts.length === 0;
      }

      if (lotPriceMain) {
        const priceMain = formatPrice(button.dataset.flatPrice);
        lotPriceMain.textContent = priceMain;
        lotPriceMain.hidden = !priceMain;
      }

      if (lotPriceOld) {
        const priceOld = formatPrice(button.dataset.flatPriceOld);
        lotPriceOld.textContent = priceOld;
        lotPriceOld.hidden = !priceOld;
      }

      if (lotBadges) {
        const badges = parseBadges(button.dataset.flatBadges);
        lotBadges.innerHTML = badges
          .map(
            (badge) =>
              `<span class="apartment-card__badge">${badge
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;")}</span>`
          )
          .join("");
        lotBadges.hidden = badges.length === 0;
      }

      lotCard.hidden = false;
      root.classList.add("is-lot-open");
    };

    const syncActiveEntrance = () => {
      if (!activeEntranceId) return;

      entrancePins.forEach((button) => {
        button.classList.toggle(
          "is-active",
          button.dataset.entranceTrigger === activeEntranceId
        );
      });

      entranceCards.forEach((card) => {
        const isActive = card.dataset.entranceCard === activeEntranceId;
        card.classList.toggle("is-active", isActive);
        card.hidden = !isActive || currentView !== "scene";

        if (!isActive || currentView !== "scene" || desktopCardMedia.matches) {
          clearSceneCardPosition(card);
        }
      });

      entranceBoards.forEach((board) => {
        const isActive = board.dataset.entranceBoard === activeEntranceId;
        board.classList.toggle("is-active", isActive);
        board.hidden = !isActive || currentView !== "board";
      });

      entranceSwitches.forEach((button) => {
        button.classList.toggle(
          "is-active",
          button.dataset.selectorSwitchEntrance === activeEntranceId
        );
      });

      if (activeEntranceBadge) {
        const activePin = entrancePins.find(
          (button) => button.dataset.entranceTrigger === activeEntranceId
        );
        activeEntranceBadge.textContent = activePin?.textContent?.trim() || "";

        if (activePin?.dataset.entranceModifier) {
          root.dataset.activeEntrance = activePin.dataset.entranceModifier;
        } else {
          delete root.dataset.activeEntrance;
        }
      }

      if (currentView === "scene" && !desktopCardMedia.matches) {
        scheduleActiveCardPosition();
      }
    };

    const setView = (view) => {
      currentView = view === "board" ? "board" : "scene";
      if (sceneView) sceneView.hidden = currentView !== "scene";
      if (boardView) boardView.hidden = currentView !== "board";
      syncActiveEntrance();

      if (currentView === "scene") {
        setHoveredEntrance("");
        hideLotCard();
      }
    };

    const openSceneCard = (entranceId) => {
      activeEntranceId = entranceId || activeEntranceId;
      setView("scene");
    };

    const openBoard = (entranceId) => {
      activeEntranceId = entranceId || activeEntranceId;
      setView("board");
    };

    const openInitialLot = () => {
      if (!state.initialFlatCode || currentView !== "board") {
        return;
      }

      const targetLot = lots.find(
        (button) => button.dataset.flatCode === state.initialFlatCode
      );

      if (targetLot) {
        showLotCard(targetLot);
      }
    };

    scenePins.forEach((button) => {
      const modifier = button.dataset.entranceModifier || "";

      if (modifier) {
        button.addEventListener("mouseenter", () => {
          setHoveredEntrance(modifier);
        });

        button.addEventListener("mouseleave", () => {
          setHoveredEntrance("");
        });

        button.addEventListener("focus", () => {
          setHoveredEntrance(modifier);
        });

        button.addEventListener("blur", () => {
          setHoveredEntrance("");
        });
      }
    });

    entrancePins.forEach((button) => {
      button.addEventListener("click", () => {
        openSceneCard(button.dataset.entranceTrigger || "");
      });
    });

    sceneSections.forEach((shape) => {
      const modifier = shape.dataset.sectionOverlay || "";
      if (!modifier) {
        return;
      }

      shape.addEventListener("mouseenter", () => {
        setHoveredEntrance(modifier);
      });

      shape.addEventListener("mouseleave", () => {
        setHoveredEntrance("");
      });

      shape.addEventListener("click", () => {
        const matchedPin = findPinByModifier(modifier);
        openSceneCard(matchedPin?.dataset.entranceTrigger || activeEntranceId);
      });
    });

    chooseButtons.forEach((button) => {
      button.addEventListener("click", () => {
        openBoard(button.dataset.selectorOpenBoard || "");
      });
    });

    closeSceneButtons.forEach((button) => {
      button.addEventListener("click", () => {
        hideSceneCards();
      });
    });

    entranceSwitches.forEach((button) => {
      button.addEventListener("click", () => {
        activeEntranceId = button.dataset.selectorSwitchEntrance || activeEntranceId;
        syncActiveEntrance();
        hideLotCard();
      });
    });

    backButton?.addEventListener("click", () => {
      openSceneCard(activeEntranceId);
    });

    lots.forEach((button) => {
      button.addEventListener("click", () => {
        showLotCard(button);
      });
    });

    lotClose?.addEventListener("click", () => {
      hideLotCard();
    });

    root.addEventListener("click", (event) => {
      if (currentView === "scene") {
        const clickedSceneStage = event.target.closest(".projects-selector__scene-stage");

        if (
          event.target.closest("[data-entrance-trigger]") ||
          event.target.closest("[data-entrance-card]") ||
          event.target.closest("[data-section-overlay]") ||
          !clickedSceneStage
        ) {
          return;
        }

        hideSceneCards();
        return;
      }

      if (currentView !== "board" || lotCard?.hidden) {
        return;
      }

      const clickedBoardStage = event.target.closest(".projects-selector__board-stage");

      if (
        event.target.closest("[data-flat-id]") ||
        event.target.closest("[data-selector-lot-card]") ||
        !clickedBoardStage
      ) {
        return;
      }

      hideLotCard();
    });

    document.addEventListener("click", (event) => {
      const target = event.target;
      if (!(target instanceof Element)) {
        return;
      }

      if (!root.contains(target)) {
        if (currentView === "scene") {
          hideSceneCards();
          return;
        }

        if (currentView === "board") {
          hideLotCard();
        }
      }
    });

    setView(state.initialView === "board" ? "board" : "scene");
    hideSceneCards();
    openInitialLot();

    window.addEventListener("resize", () => {
      if (currentView === "scene") {
        scheduleActiveCardPosition();
      }
    });
  });
};

const initProjectsPage = () => {
  initProjectApartmentSelector();
  initBenefitsModal();
  initConstructionSlider();
  initConstructionModal();
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initProjectsPage);
} else {
  initProjectsPage();
}
