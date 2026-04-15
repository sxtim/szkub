const BENEFITS_PREVIEW_LIMIT = 3;

const scrollBenefitTabIntoView = (tab) => {
  if (!(tab instanceof Element)) return;
  tab.scrollIntoView({
    behavior: "smooth",
    block: "nearest",
    inline: "center",
  });
};

const getBenefitsSection = (element) =>
  element instanceof Element ? element.closest(".projects-benefits") : null;

const getBenefitsActiveCategory = (benefitsSection) => {
  if (!(benefitsSection instanceof Element)) return "all";
  const activeTab = benefitsSection.querySelector(
    ".projects-benefits__tab.is-active[data-benefit-tab]"
  );
  if (!(activeTab instanceof HTMLElement)) return "all";
  return activeTab.dataset.benefitTab || "all";
};

const applyBenefitsSectionState = (benefitsSection) => {
  if (!(benefitsSection instanceof HTMLElement)) return;

  const benefitsBody = benefitsSection.querySelector("[data-benefits-body]");
  if (!(benefitsBody instanceof HTMLElement)) return;

  const items = Array.from(
    benefitsBody.querySelectorAll(".projects-benefits__item[data-benefit-category]")
  );
  const activeCategory = getBenefitsActiveCategory(benefitsSection);
  const isExpanded = benefitsSection.dataset.benefitsExpanded === "1";

  const categoryItems = items.filter((item) => {
    const category = item.dataset.benefitCategory || "all";
    return activeCategory === "all" || category === activeCategory;
  });

  categoryItems.forEach((item, index) => {
    const isVisible = isExpanded || index < BENEFITS_PREVIEW_LIMIT;
    item.toggleAttribute("hidden", !isVisible);
  });

  items.forEach((item) => {
    if (categoryItems.includes(item)) return;
    item.setAttribute("hidden", "");
  });

  const remaining = Math.max(0, categoryItems.length - BENEFITS_PREVIEW_LIMIT);
  const actions = benefitsSection.querySelector("[data-benefits-actions]");
  const moreButton = benefitsSection.querySelector("[data-benefits-more]");

  if (!(actions instanceof HTMLElement) || !(moreButton instanceof HTMLButtonElement)) {
    return;
  }

  if (!isExpanded && remaining > 0) {
    moreButton.textContent = `+ ${remaining} преимуществ`;
    actions.hidden = false;
  } else if (isExpanded && categoryItems.length > BENEFITS_PREVIEW_LIMIT) {
    moreButton.textContent = "Скрыть";
    actions.hidden = false;
  } else {
    moreButton.textContent = "";
    actions.hidden = true;
  }
};

const scrollBenefitsSectionIntoView = (benefitsSection) => {
  if (!(benefitsSection instanceof HTMLElement)) return;

  const header = document.querySelector(".header");
  const headerOffset =
    header instanceof HTMLElement ? header.getBoundingClientRect().height : 0;
  const top =
    benefitsSection.getBoundingClientRect().top + window.scrollY - headerOffset - 16;

  window.scrollTo({
    top: Math.max(0, top),
    behavior: "smooth",
  });
};

const updateBenefitsTabsOverflowState = (tabsContainer) => {
  if (!(tabsContainer instanceof HTMLElement)) return;

  const overflowWidth = tabsContainer.scrollWidth - tabsContainer.clientWidth;
  const isScrollable = overflowWidth > 4;
  const scrollLeft = tabsContainer.scrollLeft;

  tabsContainer.classList.toggle("is-scrollable", isScrollable);
  tabsContainer.classList.toggle("is-at-start", !isScrollable || scrollLeft <= 2);
  tabsContainer.classList.toggle("is-at-end", !isScrollable || scrollLeft >= overflowWidth - 2);
};

const initBenefitsTabs = () => {
  const benefitsSections = document.querySelectorAll(".projects-benefits");
  const tabsContainers = document.querySelectorAll(".projects-benefits__tabs");

  tabsContainers.forEach((tabsContainer) => {
    if (!(tabsContainer instanceof HTMLElement)) return;

    updateBenefitsTabsOverflowState(tabsContainer);
    tabsContainer.addEventListener(
      "scroll",
      () => {
        updateBenefitsTabsOverflowState(tabsContainer);
      },
      { passive: true }
    );
  });

  benefitsSections.forEach((benefitsSection) => {
    if (!(benefitsSection instanceof HTMLElement)) return;
    benefitsSection.dataset.benefitsExpanded = "0";
    applyBenefitsSectionState(benefitsSection);
  });

  window.addEventListener("resize", () => {
    tabsContainers.forEach((tabsContainer) => {
      updateBenefitsTabsOverflowState(tabsContainer);
    });
  });
};

document.addEventListener("click", (event) => {
  const tab = event.target.closest("[data-benefit-tab]");
  if (!tab) return;

  const tabsContainer = tab.closest(".projects-benefits__tabs");
  if (!tabsContainer) return;

  const benefitsSection = tab.closest(".projects-benefits");

  const tabs = tabsContainer.querySelectorAll("[data-benefit-tab]");
  tabs.forEach((button) => {
    const isActive = button === tab;
    button.classList.toggle("is-active", isActive);
    button.setAttribute("aria-selected", isActive ? "true" : "false");
  });

  if (benefitsSection instanceof HTMLElement) {
    benefitsSection.dataset.benefitsExpanded = "0";
    applyBenefitsSectionState(benefitsSection);
  }

  requestAnimationFrame(() => {
    scrollBenefitTabIntoView(tab);
    updateBenefitsTabsOverflowState(tabsContainer);
  });
});

document.addEventListener("click", (event) => {
  const moreButton = event.target.closest("[data-benefits-more]");
  if (!(moreButton instanceof HTMLButtonElement)) return;

  const benefitsSection = getBenefitsSection(moreButton);
  if (!(benefitsSection instanceof HTMLElement)) return;

  const isExpanded = benefitsSection.dataset.benefitsExpanded === "1";
  benefitsSection.dataset.benefitsExpanded = isExpanded ? "0" : "1";
  applyBenefitsSectionState(benefitsSection);

  if (isExpanded) {
    requestAnimationFrame(() => {
      scrollBenefitsSectionIntoView(benefitsSection);
    });
  }
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
  const modalCategoryEl = modalWrap.querySelector("[data-modal-category]");

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

  const getBenefitsFromCards = (cards) => {
    const list = [];
    cards.forEach((card) => {
      const payload = card.getAttribute("data-benefit");
      if (!payload) return;
      try {
        list.push(JSON.parse(payload));
      } catch {
      }
    });
    return list;
  };

  const getBenefitsByCategory = (category) => {
    const normalizedCategory =
      typeof category === "string" && category.trim() !== ""
        ? category.trim()
        : "all";
    const safeCategory =
      typeof CSS !== "undefined" && typeof CSS.escape === "function"
        ? CSS.escape(normalizedCategory)
        : normalizedCategory.replace(/"/g, '\\"');
    const cards = document.querySelectorAll(
      `.projects-benefits__item[data-benefit-category="${safeCategory}"] .projects-benefit-card[data-benefit]`
    );

    return getBenefitsFromCards(cards);
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
      imageWrap.dataset.modalImageZoom = "true";
      imageWrap.dataset.modalImageSrc = benefit.image;
      imageWrap.dataset.modalImageAlt = benefit?.title || "";
      imageWrap.dataset.modalImageCaption = benefit?.title || "";
      imageWrap.tabIndex = 0;
      imageWrap.setAttribute("role", "button");
      imageWrap.setAttribute("aria-label", "Открыть изображение");
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

  const setModalCategory = (title) => {
    if (!(modalCategoryEl instanceof HTMLElement)) return;
    modalCategoryEl.textContent = typeof title === "string" ? title : "";
    modalCategoryEl.hidden = modalCategoryEl.textContent.trim() === "";
  };

  const openModal = (benefit, list, categoryTitle = "") => {
    if (isClosing) return;

    lastActiveElement =
      document.activeElement instanceof HTMLElement ? document.activeElement : null;
    benefitList = Array.isArray(list) && list.length ? list : [benefit];
    setModalCategory(categoryTitle);

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
    window.SzcubeMediaLightbox?.close();

    playCloseTransition();

    window.setTimeout(() => {
      modalWrap.hidden = true;
      clearTransitionClasses();
      destroySwiper();
      swiperWrapperEl.textContent = "";
      benefitList = [];
      setModalCategory("");
      unlockBodyScroll();

      if (lastActiveElement) lastActiveElement.focus();
      lastActiveElement = null;
      isClosing = false;
    }, 350);
  };

  document.addEventListener("click", (event) => {
    const zoomTrigger = event.target.closest("[data-modal-image-zoom]");
    if (zoomTrigger && !modalWrap.hidden) {
      event.preventDefault();
      const items = benefitList
        .filter((benefit) => benefit?.image)
        .map((benefit) => ({
          id: benefit?.id != null ? String(benefit.id) : "",
          src: benefit.image,
          alt: benefit?.title || "",
          caption: benefit?.title || "",
        }));
      const slideEl = zoomTrigger.closest(".swiper-slide");
      const benefitId = slideEl?.getAttribute("data-benefit-id") || "";
      const initialIndex = Math.max(
        0,
        items.findIndex((item) => item.id === benefitId)
      );

      window.SzcubeMediaLightbox?.open({
        items,
        initialIndex,
        trigger: zoomTrigger,
        ariaLabel: "Увеличенное изображение преимущества",
      });
      return;
    }

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
      const benefitItem = benefitCard.closest(".projects-benefits__item[data-benefit-category]");
      const benefitCategory =
        benefitItem instanceof HTMLElement && benefitItem.dataset.benefitCategory
          ? benefitItem.dataset.benefitCategory
          : "all";
      const categoryTitle =
        benefitCard.querySelector(".projects-benefit-card__tag")?.textContent?.trim() || "";

      openModal(benefit, getBenefitsByCategory(benefitCategory), categoryTitle);
    } catch {
    }
  });

  document.addEventListener("keydown", (event) => {
    if (window.SzcubeMediaLightbox?.isOpen()) {
      return;
    }

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

  document.addEventListener("keydown", (event) => {
    if (!(event.target instanceof Element) || modalWrap.hidden) return;

    const zoomTrigger = event.target.closest("[data-modal-image-zoom]");
    if (!zoomTrigger) return;

    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      const items = benefitList
        .filter((benefit) => benefit?.image)
        .map((benefit) => ({
          id: benefit?.id != null ? String(benefit.id) : "",
          src: benefit.image,
          alt: benefit?.title || "",
          caption: benefit?.title || "",
        }));
      const slideEl = zoomTrigger.closest(".swiper-slide");
      const benefitId = slideEl?.getAttribute("data-benefit-id") || "";
      const initialIndex = Math.max(
        0,
        items.findIndex((item) => item.id === benefitId)
      );

      window.SzcubeMediaLightbox?.open({
        items,
        initialIndex,
        trigger: zoomTrigger,
        ariaLabel: "Увеличенное изображение преимущества",
      });
    }
  });
};

const initProjectBenefits = () => {
  initBenefitsTabs();
  initBenefitsModal();
};

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initProjectBenefits);
} else {
  initProjectBenefits();
}
