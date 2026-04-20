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
  let gestureHintEl = null;
  let gestureHintTimer = null;
  let desktopPanFrame = null;
  let desktopPanTarget = null;
  let desktopPanPoint = { x: 0.5, y: 0.5 };
  let lastTouchTap = { time: 0, x: 0, y: 0 };
  let mobileZoomState = {
    scale: 1,
    x: 0,
    y: 0,
    startX: 0,
    startY: 0,
    originX: 0,
    originY: 0,
    moved: false,
    isPanning: false,
  };

  const closeIcon = `
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M5 5L15 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      <path d="M15 5L5 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    </svg>
  `;

  const gestureHintIcon = `
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <path d="M6.5 6.5L3.5 3.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
      <path d="M11.5 11.5L14.5 14.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
      <path d="M11.5 6.5L14.5 3.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
      <path d="M6.5 11.5L3.5 14.5" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" />
      <path d="M7.25 7.25L5.25 7.25L5.25 5.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M10.75 7.25L12.75 7.25L12.75 5.25" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M7.25 10.75L5.25 10.75L5.25 12.75" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" />
      <path d="M10.75 10.75L12.75 10.75L12.75 12.75" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
  `;

  const isCoarsePointer = () => {
    const hasCoarsePointer =
      typeof window.matchMedia === "function" &&
      window.matchMedia("(pointer: coarse)").matches;
    const hasTouchPoints =
      typeof navigator !== "undefined" && Number(navigator.maxTouchPoints) > 0;

    return hasCoarsePointer || hasTouchPoints;
  };

  const supportsDesktopClickZoom = () => !isCoarsePointer();

  const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

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

    const zoomContainer = document.createElement("div");
    zoomContainer.className = "media-lightbox__zoom swiper-zoom-container";
    zoomContainer.dataset.mediaLightboxZoomTarget = "1";
    zoomContainer.dataset.canZoom = "0";
    zoomContainer.setAttribute("tabindex", "0");
    zoomContainer.setAttribute("role", "button");
    zoomContainer.setAttribute("aria-label", "Увеличить изображение");

    const image = document.createElement("img");
    image.className = "media-lightbox__image";
    image.src = item.src;
    image.alt = item.alt;
    image.decoding = "async";
    image.loading = "eager";
    zoomContainer.append(image);
    figure.append(zoomContainer);

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

  const clearGestureHintTimer = () => {
    if (gestureHintTimer) {
      window.clearTimeout(gestureHintTimer);
      gestureHintTimer = null;
    }
  };

  const hideGestureHint = () => {
    clearGestureHintTimer();
    if (gestureHintEl instanceof HTMLElement) {
      gestureHintEl.hidden = true;
      gestureHintEl.classList.remove("is-visible");
    }
  };

  const showGestureHint = () => {
    if (
      !(gestureHintEl instanceof HTMLElement) ||
      !isCoarsePointer() ||
      window.innerWidth > 768
    ) {
      hideGestureHint();
      return;
    }

    clearGestureHintTimer();
    gestureHintEl.hidden = false;
    requestAnimationFrame(() => {
      gestureHintEl.classList.add("is-visible");
    });
    gestureHintTimer = window.setTimeout(() => {
      hideGestureHint();
    }, 2800);
  };

  const getActiveSlide = () => {
    if (!swiper?.slides?.length) {
      return null;
    }

    return swiper.slides[swiper.activeIndex] instanceof HTMLElement
      ? swiper.slides[swiper.activeIndex]
      : null;
  };

  const getActiveZoomTarget = () =>
    getActiveSlide()?.querySelector("[data-media-lightbox-zoom-target]") || null;

  const getActiveZoomRatio = () => {
    const target = getActiveZoomTarget();
    if (!(target instanceof HTMLElement)) {
      return 1;
    }

    const ratio = Number(target.getAttribute("data-swiper-zoom")) || 1;
    return ratio > 1 ? ratio : 1;
  };

  const getZoomImage = (target = getActiveZoomTarget()) => {
    const image = target?.querySelector(".media-lightbox__image");
    return image instanceof HTMLImageElement ? image : null;
  };

  const isPointInsideImage = (target, x, y) => {
    const image = getZoomImage(target);
    if (!image) {
      return false;
    }

    const rect = image.getBoundingClientRect();
    return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
  };

  const isPointInsideElement = (element, x, y) => {
    if (!(element instanceof HTMLElement)) {
      return false;
    }

    const rect = element.getBoundingClientRect();
    return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
  };

  const getMobileScale = () => mobileZoomState.scale;

  const clampMobileZoomPosition = (target, scale, x, y) => {
    const image = getZoomImage(target);
    if (!(target instanceof HTMLElement) || !image) {
      return { x: 0, y: 0 };
    }

    const viewportWidth = target.clientWidth;
    const viewportHeight = target.clientHeight;
    const imageWidth = image.offsetWidth;
    const imageHeight = image.offsetHeight;

    if (!viewportWidth || !viewportHeight || !imageWidth || !imageHeight) {
      return { x: 0, y: 0 };
    }

    const scaledWidth = imageWidth * scale;
    const scaledHeight = imageHeight * scale;
    const maxX = Math.max(0, (scaledWidth - viewportWidth) / 2);
    const maxY = Math.max(0, (scaledHeight - viewportHeight) / 2);

    return {
      x: clamp(x, -maxX, maxX),
      y: clamp(y, -maxY, maxY),
    };
  };

  const applyMobileZoom = (target = getActiveZoomTarget(), animated = false) => {
    const image = getZoomImage(target);
    if (!(target instanceof HTMLElement) || !image) {
      return;
    }

    const { x, y } = clampMobileZoomPosition(
      target,
      mobileZoomState.scale,
      mobileZoomState.x,
      mobileZoomState.y
    );

    mobileZoomState.x = x;
    mobileZoomState.y = y;

    image.style.transitionDuration = animated ? "240ms" : "0ms";
    image.style.transform = `translate3d(${x.toFixed(2)}px, ${y.toFixed(2)}px, 0px) scale(${mobileZoomState.scale})`;
  };

  const resetMobileZoom = (animated = false) => {
    mobileZoomState = {
      scale: 1,
      x: 0,
      y: 0,
      startX: 0,
      startY: 0,
      originX: 0,
      originY: 0,
      moved: false,
      isPanning: false,
    };

    applyMobileZoom(getActiveZoomTarget(), animated);
  };

  const cancelDesktopPanFrame = () => {
    if (desktopPanFrame !== null) {
      window.cancelAnimationFrame(desktopPanFrame);
      desktopPanFrame = null;
    }
  };

  const resetDesktopPan = (target = null) => {
    cancelDesktopPanFrame();
    desktopPanTarget = null;
    desktopPanPoint = { x: 0.5, y: 0.5 };

    const targets =
      target instanceof HTMLElement
        ? [target]
        : Array.from(wrapperEl?.querySelectorAll("[data-media-lightbox-zoom-target]") || []);

    targets.forEach((zoomTarget) => {
      if (!(zoomTarget instanceof HTMLElement)) {
        return;
      }

      const image = getZoomImage(zoomTarget);
      if (image) {
        image.style.transform = "";
        image.style.transitionDuration = "";
      }
    });
  };

  const applyDesktopPan = () => {
    desktopPanFrame = null;

    const target = desktopPanTarget;
    if (
      !(target instanceof HTMLElement) ||
      !supportsDesktopClickZoom() ||
      target !== getActiveZoomTarget() ||
      !swiper?.zoom ||
      swiper.zoom.scale <= 1.02
    ) {
      return;
    }

    const image = getZoomImage(target);
    if (!image) {
      return;
    }

    const targetRect = target.getBoundingClientRect();
    const imageWidth = image.offsetWidth;
    const imageHeight = image.offsetHeight;
    const scale = swiper.zoom.scale;

    if (!targetRect.width || !targetRect.height || !imageWidth || !imageHeight) {
      return;
    }

    const scaledWidth = imageWidth * scale;
    const scaledHeight = imageHeight * scale;
    const maxTranslateX = Math.max(0, (scaledWidth - targetRect.width) / 2);
    const maxTranslateY = Math.max(0, (scaledHeight - targetRect.height) / 2);
    const translateX = (0.5 - desktopPanPoint.x) * maxTranslateX * 2;
    const translateY = (0.5 - desktopPanPoint.y) * maxTranslateY * 2;

    image.style.transitionDuration = "0ms";
    image.style.transform = `translate3d(${translateX.toFixed(2)}px, ${translateY.toFixed(2)}px, 0px) scale(${scale})`;
  };

  const requestDesktopPan = (target, point = desktopPanPoint) => {
    if (!(target instanceof HTMLElement) || !supportsDesktopClickZoom()) {
      return;
    }

    desktopPanTarget = target;
    desktopPanPoint = {
      x: clamp(point.x, 0, 1),
      y: clamp(point.y, 0, 1),
    };

    if (desktopPanFrame === null) {
      desktopPanFrame = window.requestAnimationFrame(applyDesktopPan);
    }
  };

  const fitZoomTargetToImage = (zoomTarget, image) => {
    if (isCoarsePointer()) {
      zoomTarget.style.width = "";
      zoomTarget.style.height = "";

      return {
        width: image.clientWidth || zoomTarget.clientWidth,
        height: image.clientHeight || zoomTarget.clientHeight,
      };
    }

    const naturalWidth = image.naturalWidth;
    const naturalHeight = image.naturalHeight;
    if (!naturalWidth || !naturalHeight) {
      return null;
    }

    zoomTarget.style.width = "";
    zoomTarget.style.height = "";

    const maxWidth =
      zoomTarget.parentElement?.clientWidth ||
      swiperEl?.clientWidth ||
      window.innerWidth;
    const maxHeight = parseFloat(window.getComputedStyle(zoomTarget).maxHeight);

    if (!maxWidth || !maxHeight) {
      return null;
    }

    const fitRatio = Math.min(maxWidth / naturalWidth, maxHeight / naturalHeight, 1.3);
    const width = Math.max(1, Math.round(naturalWidth * fitRatio));
    const height = Math.max(1, Math.round(naturalHeight * fitRatio));

    zoomTarget.style.width = `${width}px`;
    zoomTarget.style.height = `${height}px`;

    return { width, height };
  };

  const updateZoomAvailability = (slide) => {
    if (!(slide instanceof HTMLElement)) {
      return;
    }

    const zoomTarget = slide.querySelector("[data-media-lightbox-zoom-target]");
    const image = slide.querySelector(".media-lightbox__image");
    if (!(zoomTarget instanceof HTMLElement) || !(image instanceof HTMLImageElement)) {
      return;
    }

    const applyRatio = () => {
      const fitSize = fitZoomTargetToImage(zoomTarget, image);
      const renderedWidth = fitSize?.width || image.clientWidth;
      const renderedHeight = fitSize?.height || image.clientHeight;
      const naturalWidth = image.naturalWidth;
      const naturalHeight = image.naturalHeight;

      if (!renderedWidth || !renderedHeight || !naturalWidth || !naturalHeight) {
        return;
      }

      const widthRatio = naturalWidth / renderedWidth;
      const heightRatio = naturalHeight / renderedHeight;
      const nativeRatio = Math.max(widthRatio, heightRatio, 1);
      const fallbackRatio = isCoarsePointer() ? 2 : 1.75;
      const safeRatio = Math.min(4, Math.max(fallbackRatio, nativeRatio));
      const canZoom = safeRatio > 1.02;

      zoomTarget.setAttribute("data-swiper-zoom", safeRatio.toFixed(3));
      zoomTarget.dataset.nativeRatio = nativeRatio.toFixed(3);
      zoomTarget.dataset.canZoom = canZoom ? "1" : "0";
      slide.classList.toggle("is-zoomable", canZoom);
      zoomTarget.setAttribute(
        "aria-label",
        canZoom ? "Увеличить изображение" : "Изображение открыто в полном размере"
      );
    };

    if (image.complete) {
      applyRatio();
      return;
    }

    image.addEventListener("load", applyRatio, { once: true });
  };

  const prepareSlides = () => {
    if (!wrapperEl) {
      return;
    }

    Array.from(wrapperEl.children).forEach((slide) => {
      updateZoomAvailability(slide);
    });
  };

  const setZoomState = (scale = swiper?.zoom?.scale || 1) => {
    if (!(root instanceof HTMLElement)) {
      return;
    }

    const effectiveScale = isCoarsePointer() ? getMobileScale() : scale;
    const isZoomed = effectiveScale > 1.02;
    const activeSlide = getActiveSlide();
    const activeTarget = getActiveZoomTarget();
    const canZoom = activeTarget instanceof HTMLElement && activeTarget.dataset.canZoom === "1";

    root.classList.toggle("is-zoomed", isZoomed);
    root.classList.toggle("is-zoom-available", canZoom);

    Array.from(wrapperEl?.children || []).forEach((slide) => {
      if (!(slide instanceof HTMLElement)) {
        return;
      }

      const target = slide.querySelector("[data-media-lightbox-zoom-target]");
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const isActive = slide === activeSlide;
      target.setAttribute(
        "aria-label",
        isActive && canZoom
          ? isZoomed
            ? "Уменьшить изображение"
            : "Увеличить изображение"
          : target.dataset.canZoom === "1"
            ? "Увеличить изображение"
            : "Изображение открыто в полном размере"
      );
    });

    if (swiper) {
      swiper.allowTouchMove = !isZoomed;
    }

    if (isZoomed) {
      hideGestureHint();
    } else {
      resetDesktopPan();
    }
  };

  const toggleActiveZoom = () => {
    if (!swiper?.zoom) {
      return;
    }

    const activeTarget = getActiveZoomTarget();
    if (!(activeTarget instanceof HTMLElement) || activeTarget.dataset.canZoom !== "1") {
      return;
    }

    const targetRatio = getActiveZoomRatio();
    if (swiper.zoom.scale > 1.02) {
      swiper.zoom.out();
      return;
    }

    swiper.zoom.in(targetRatio);
  };

  const destroySwiper = () => {
    if (swiper) {
      swiper.destroy(true, true);
      swiper = null;
    }

    if (wrapperEl) {
      wrapperEl.textContent = "";
    }

    resetMobileZoom();
    resetDesktopPan();
    lastTouchTap = { time: 0, x: 0, y: 0 };
    hideGestureHint();
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

    const zoomTarget = event.target.closest?.("[data-media-lightbox-zoom-target]");
    if (
      zoomTarget &&
      (event.key === "Enter" || event.key === " ") &&
      supportsDesktopClickZoom()
    ) {
      event.preventDefault();
      toggleActiveZoom();
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
            <div class="media-lightbox__gesture-hint" hidden aria-hidden="true">
              <span class="media-lightbox__gesture-icon">${gestureHintIcon}</span>
              <span class="media-lightbox__gesture-text">Двойной тап для увеличения</span>
            </div>
          </div>
        </div>
      `;
      document.body.append(root);
    } else if (root.parentElement !== document.body) {
      document.body.append(root);
    }

    root.classList.toggle("is-touch", isCoarsePointer());

    dialog = root.querySelector(".media-lightbox__dialog");
    swiperEl = root.querySelector(".media-lightbox__swiper.swiper");
    wrapperEl = root.querySelector(".media-lightbox__swiper .swiper-wrapper");
    prevEl = root.querySelector(".media-lightbox__nav.swiper-button-prev");
    nextEl = root.querySelector(".media-lightbox__nav.swiper-button-next");
    paginationEl = root.querySelector(".media-lightbox__pagination.swiper-pagination");
    closeButtons = Array.from(root.querySelectorAll("[data-media-lightbox-close]"));
    gestureHintEl = root.querySelector(".media-lightbox__gesture-hint");

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

    if (root.dataset.mediaLightboxZoomBound !== "1") {
      root.addEventListener("click", (event) => {
        const zoomTarget = event.target.closest("[data-media-lightbox-zoom-target]");
        if (!(zoomTarget instanceof HTMLElement) || !supportsDesktopClickZoom()) {
          return;
        }

        if (zoomTarget.closest(".swiper-slide") !== getActiveSlide()) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        toggleActiveZoom();
      });
      root.dataset.mediaLightboxZoomBound = "1";
    }

    if (root.dataset.mediaLightboxBackdropBound !== "1") {
      root.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
          return;
        }

        const isInteractiveArea = target.closest(
          "[data-media-lightbox-zoom-target], [data-media-lightbox-close], .media-lightbox__nav, .media-lightbox__footer, .media-lightbox__caption, .media-lightbox__gesture-hint"
        );
        if (
          isCoarsePointer() &&
          target instanceof HTMLElement &&
          target.matches("[data-media-lightbox-zoom-target]") &&
          !isPointInsideImage(target, event.clientX, event.clientY)
        ) {
          close();
          return;
        }

        if (isInteractiveArea) {
          return;
        }

        if (target.closest("[data-media-lightbox]")) {
          close();
        }
      });
      root.dataset.mediaLightboxBackdropBound = "1";
    }

    if (root.dataset.mediaLightboxPanBound !== "1") {
      root.addEventListener("mousemove", (event) => {
        const zoomTarget = event.target.closest("[data-media-lightbox-zoom-target]");
        if (
          !(zoomTarget instanceof HTMLElement) ||
          !supportsDesktopClickZoom() ||
          zoomTarget.closest(".swiper-slide") !== getActiveSlide() ||
          !swiper?.zoom ||
          swiper.zoom.scale <= 1.02
        ) {
          return;
        }

        const rect = zoomTarget.getBoundingClientRect();
        if (!rect.width || !rect.height) {
          return;
        }

        requestDesktopPan(zoomTarget, {
          x: (event.clientX - rect.left) / rect.width,
          y: (event.clientY - rect.top) / rect.height,
        });
      });
      root.dataset.mediaLightboxPanBound = "1";
    }

    if (root.dataset.mediaLightboxTouchZoomBound !== "1") {
      // Mobile-only: keep viewport fixed and pan the image itself, not the overlay block.
      root.addEventListener(
        "touchstart",
        (event) => {
          if (!isCoarsePointer() || event.touches.length !== 1) {
            return;
          }

          const touch = event.touches[0];
          const zoomTarget = event.target.closest?.("[data-media-lightbox-zoom-target]");
          if (
            !(zoomTarget instanceof HTMLElement) ||
            zoomTarget.closest(".swiper-slide") !== getActiveSlide() ||
            getMobileScale() <= 1.02 ||
            !isPointInsideImage(zoomTarget, touch.clientX, touch.clientY)
          ) {
            mobileZoomState.isPanning = false;
            return;
          }

          mobileZoomState.isPanning = true;
          mobileZoomState.moved = false;
          mobileZoomState.startX = touch.clientX;
          mobileZoomState.startY = touch.clientY;
          mobileZoomState.originX = mobileZoomState.x;
          mobileZoomState.originY = mobileZoomState.y;
        },
        { passive: true }
      );

      root.addEventListener(
        "touchmove",
        (event) => {
          if (!isCoarsePointer() || !mobileZoomState.isPanning || event.touches.length !== 1) {
            return;
          }

          const zoomTarget = getActiveZoomTarget();
          if (!(zoomTarget instanceof HTMLElement)) {
            return;
          }

          const touch = event.touches[0];
          const deltaX = touch.clientX - mobileZoomState.startX;
          const deltaY = touch.clientY - mobileZoomState.startY;
          if (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4) {
            mobileZoomState.moved = true;
          }

          mobileZoomState.x = mobileZoomState.originX + deltaX;
          mobileZoomState.y = mobileZoomState.originY + deltaY;
          applyMobileZoom(zoomTarget, false);
          event.preventDefault();
        },
        { passive: false }
      );

      root.addEventListener(
        "touchend",
        (event) => {
          if (!isCoarsePointer() || event.changedTouches.length !== 1) {
            return;
          }

          const touch = event.changedTouches[0];
          const activeZoomTarget = getActiveZoomTarget();
          const hitTarget =
            document.elementFromPoint(touch.clientX, touch.clientY) || event.target;
          const zoomTarget = hitTarget.closest?.("[data-media-lightbox-zoom-target]");

          if (mobileZoomState.isPanning) {
            mobileZoomState.isPanning = false;
            if (mobileZoomState.moved) {
              mobileZoomState.moved = false;
              lastTouchTap = { time: 0, x: 0, y: 0 };
              return;
            }
          }

          if (
            activeZoomTarget instanceof HTMLElement &&
            isPointInsideElement(activeZoomTarget, touch.clientX, touch.clientY) &&
            !isPointInsideImage(activeZoomTarget, touch.clientX, touch.clientY)
          ) {
            event.preventDefault();
            lastTouchTap = { time: 0, x: 0, y: 0 };
            close();
            return;
          }

          const target = hitTarget;
          const isInteractiveArea =
            target instanceof Element &&
            target.closest(
              "[data-media-lightbox-zoom-target], [data-media-lightbox-close], .media-lightbox__nav, .media-lightbox__footer, .media-lightbox__caption, .media-lightbox__gesture-hint"
            );
          if (!zoomTarget && !isInteractiveArea && target instanceof Element && target.closest("[data-media-lightbox]")) {
            event.preventDefault();
            lastTouchTap = { time: 0, x: 0, y: 0 };
            close();
            return;
          }

          if (
            !(zoomTarget instanceof HTMLElement) ||
            zoomTarget.closest(".swiper-slide") !== getActiveSlide() ||
            zoomTarget.dataset.canZoom !== "1" ||
            !isPointInsideImage(zoomTarget, touch.clientX, touch.clientY)
          ) {
            lastTouchTap = { time: 0, x: 0, y: 0 };
            return;
          }

          const now = Date.now();
          const deltaTime = now - lastTouchTap.time;
          const deltaX = Math.abs(touch.clientX - lastTouchTap.x);
          const deltaY = Math.abs(touch.clientY - lastTouchTap.y);

          if (deltaTime > 0 && deltaTime < 320 && deltaX < 28 && deltaY < 28) {
            event.preventDefault();
            lastTouchTap = { time: 0, x: 0, y: 0 };
            if (getMobileScale() > 1.02) {
              resetMobileZoom(true);
            } else {
              mobileZoomState.scale = Math.min(3, getActiveZoomRatio());
              mobileZoomState.x = 0;
              mobileZoomState.y = 0;
              applyMobileZoom(zoomTarget, true);
            }
            setZoomState(swiper?.zoom?.scale || 1);
            return;
          }

          lastTouchTap = { time: now, x: touch.clientX, y: touch.clientY };
        },
        { passive: false }
      );
      root.dataset.mediaLightboxTouchZoomBound = "1";
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
    prepareSlides();

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
      zoom: {
        enabled: supportsDesktopClickZoom(),
        maxRatio: 4,
        minRatio: 1,
        toggle: false,
      },
      navigation: {
        prevEl,
        nextEl,
      },
      pagination: {
        el: paginationEl,
        type: "fraction",
      },
      on: {
        init(instance) {
          prepareSlides();
          resetMobileZoom();
          setZoomState(instance.zoom?.scale || 1);
          showGestureHint();
        },
        slideChangeTransitionStart(instance) {
          if (instance.zoom?.scale > 1.02) {
            resetDesktopPan();
            instance.zoom.out();
          }
        },
        slideChange(instance) {
          requestAnimationFrame(() => {
            prepareSlides();
            resetMobileZoom();
            setZoomState(instance.zoom?.scale || 1);
          });
        },
        zoomChange(instance, scale) {
          setZoomState(scale);
        },
      },
    });

    requestAnimationFrame(() => {
      swiper?.update();
      swiper?.slideTo(safeIndex, 0, false);
      prepareSlides();
      setZoomState(swiper?.zoom?.scale || 1);
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
