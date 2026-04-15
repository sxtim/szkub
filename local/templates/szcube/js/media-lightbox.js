(() => {
  const OPEN_CLASS = "is-media-lightbox-open";
  const ROOT_SELECTOR = "[data-media-lightbox]";

  let root = null;
  let dialog = null;
  let wrapperEl = null;
  let swiperEl = null;
  let prevEl = null;
  let nextEl = null;
  let paginationEl = null;
  let closeButtons = [];
  let swiper = null;
  let lastActiveElement = null;

  const closeIcon = `
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M5 5L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      <path d="M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    </svg>
  `;

  const normalizeItems = (items) =>
    Array.isArray(items)
      ? items
          .filter((item) => item && typeof item.src === "string" && item.src.trim() !== "")
          .map((item) => ({
            src: item.src.trim(),
            alt: typeof item.alt === "string" ? item.alt : "",
            caption: typeof item.caption === "string" ? item.caption : "",
          }))
      : [];

  const buildSlide = (item) => {
    const slide = document.createElement("div");
    slide.className = "swiper-slide";

    const figure = document.createElement("figure");
    figure.className = "media-lightbox__slide";

    const image = document.createElement("img");
    image.className = "media-lightbox__image";
    image.src = item.src;
    image.alt = item.alt;
    figure.append(image);

    const safeCaption = item.caption.trim();
    if (safeCaption !== "") {
      const caption = document.createElement("figcaption");
      caption.className = "media-lightbox__caption";
      caption.textContent = safeCaption;
      figure.append(caption);
    }

    slide.append(figure);
    return slide;
  };

  const setScrollLock = (enabled) => {
    document.documentElement.classList.toggle(OPEN_CLASS, enabled);
    document.body.classList.toggle(OPEN_CLASS, enabled);
  };

  const dispatch = (name, detail = {}) => {
    document.dispatchEvent(new CustomEvent(name, { detail }));
  };

  const destroySwiper = () => {
    if (swiper) {
      swiper.destroy(true, true);
      swiper = null;
    }

    if (wrapperEl) {
      wrapperEl.textContent = "";
    }
  };

  const handleKeydown = (event) => {
    if (!root || root.hidden) {
      return;
    }

    if (event.key === "Escape") {
      event.preventDefault();
      close();
      return;
    }

    if (event.key === "ArrowLeft") {
      event.preventDefault();
      swiper?.slidePrev();
      return;
    }

    if (event.key === "ArrowRight") {
      event.preventDefault();
      swiper?.slideNext();
    }
  };

  const ensureRoot = () => {
    if (root instanceof HTMLElement) {
      return root;
    }

    root = document.querySelector(ROOT_SELECTOR);
    if (!(root instanceof HTMLElement)) {
      root = document.createElement("div");
      root.className = "media-lightbox";
      root.hidden = true;
      root.setAttribute("data-media-lightbox", "");
      root.innerHTML = `
        <button class="media-lightbox__backdrop" type="button" aria-label="Закрыть просмотр" data-media-lightbox-close></button>
        <div class="media-lightbox__dialog" role="dialog" aria-modal="true" aria-label="Просмотр изображения">
          <button class="media-lightbox__close" type="button" aria-label="Закрыть" data-media-lightbox-close>
            ${closeIcon}
          </button>
          <div class="media-lightbox__stage">
            <button class="media-lightbox__nav swiper-button-prev" type="button" aria-label="Предыдущее изображение"></button>
            <div class="media-lightbox__swiper swiper">
              <div class="swiper-wrapper"></div>
            </div>
            <button class="media-lightbox__nav swiper-button-next" type="button" aria-label="Следующее изображение"></button>
            <div class="media-lightbox__footer">
              <div class="media-lightbox__pagination swiper-pagination"></div>
            </div>
          </div>
        </div>
      `;
      document.body.append(root);
    } else if (root.parentElement !== document.body) {
      document.body.append(root);
    }

    dialog = root.querySelector(".media-lightbox__dialog");
    swiperEl = root.querySelector(".media-lightbox__swiper.swiper");
    wrapperEl = root.querySelector(".media-lightbox__swiper .swiper-wrapper");
    prevEl = root.querySelector(".media-lightbox__nav.swiper-button-prev");
    nextEl = root.querySelector(".media-lightbox__nav.swiper-button-next");
    paginationEl = root.querySelector(".media-lightbox__pagination.swiper-pagination");
    closeButtons = Array.from(root.querySelectorAll("[data-media-lightbox-close]"));

    closeButtons.forEach((button) => {
      if (button.dataset.mediaLightboxBound === "1") {
        return;
      }
      button.dataset.mediaLightboxBound = "1";
      button.addEventListener("click", close);
    });

    if (root.dataset.mediaLightboxKeyBound !== "1") {
      document.addEventListener("keydown", handleKeydown);
      root.dataset.mediaLightboxKeyBound = "1";
    }

    return root;
  };

  function close() {
    if (!(root instanceof HTMLElement) || root.hidden) {
      return;
    }

    root.hidden = true;
    destroySwiper();
    setScrollLock(false);
    dispatch("media-lightbox:close", {
      trigger: lastActiveElement,
    });

    if (lastActiveElement instanceof HTMLElement) {
      lastActiveElement.focus();
    }

    lastActiveElement = null;
  }

  const open = ({
    items,
    initialIndex = 0,
    trigger = null,
    ariaLabel = "Просмотр изображения",
  } = {}) => {
    ensureRoot();

    if (
      !(root instanceof HTMLElement) ||
      !(dialog instanceof HTMLElement) ||
      !(swiperEl instanceof HTMLElement) ||
      !(wrapperEl instanceof HTMLElement) ||
      !(prevEl instanceof HTMLElement) ||
      !(nextEl instanceof HTMLElement) ||
      !(paginationEl instanceof HTMLElement) ||
      typeof window.Swiper !== "function"
    ) {
      return false;
    }

    const slides = normalizeItems(items);
    if (!slides.length) {
      return false;
    }

    const safeIndex = Math.min(
      Math.max(0, Number(initialIndex) || 0),
      slides.length - 1
    );

    lastActiveElement =
      trigger instanceof HTMLElement
        ? trigger
        : document.activeElement instanceof HTMLElement
          ? document.activeElement
          : null;

    destroySwiper();
    slides.forEach((item) => {
      wrapperEl.append(buildSlide(item));
    });

    dialog.setAttribute("aria-label", ariaLabel);
    root.hidden = false;
    setScrollLock(true);

    swiper = new window.Swiper(swiperEl, {
      initialSlide: safeIndex,
      slidesPerView: 1,
      spaceBetween: 0,
      speed: 500,
      watchOverflow: true,
      keyboard: {
        enabled: true,
        onlyInViewport: false,
      },
      navigation: {
        prevEl,
        nextEl,
      },
      pagination: {
        el: paginationEl,
        type: "fraction",
      },
    });

    requestAnimationFrame(() => {
      swiper?.update();
      swiper?.slideTo(safeIndex, 0, false);
    });

    dispatch("media-lightbox:open", {
      trigger: lastActiveElement,
      initialIndex: safeIndex,
      items: slides,
      ariaLabel,
    });

    return true;
  };

  const isOpen = () => root instanceof HTMLElement && !root.hidden;

  window.SzcubeMediaLightbox = {
    open,
    close,
    isOpen,
  };
})();
