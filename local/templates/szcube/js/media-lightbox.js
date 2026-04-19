(() => {
  const OPEN_CLASS = "is-media-lightbox-open";
  const PHOTO_SWIPE_URL = "/local/templates/szcube/js/vendor/photoswipe.esm.min.js";

  let PhotoSwipeClass = null;
  let photoSwipePromise = null;
  let pswp = null;
  let lastActiveElement = null;
  let lastOpenDetail = null;
  let isClosingFromApi = false;

  const normalizeItems = (items) =>
    Array.isArray(items)
      ? items
          .filter((item) => item && typeof item.src === "string" && item.src.trim() !== "")
          .map((item) => ({
            src: item.src.trim(),
            alt: typeof item.alt === "string" ? item.alt : "",
            caption: typeof item.caption === "string" ? item.caption : "",
            width: Number(item.width || item.w) || 0,
            height: Number(item.height || item.h) || 0,
          }))
      : [];

  const setScrollLock = (enabled) => {
    document.documentElement.classList.toggle(OPEN_CLASS, enabled);
    document.body.classList.toggle(OPEN_CLASS, enabled);
  };

  const dispatch = (name, detail = {}) => {
    document.dispatchEvent(new CustomEvent(name, { detail }));
  };

  const loadPhotoSwipe = () => {
    if (PhotoSwipeClass) {
      return Promise.resolve(PhotoSwipeClass);
    }

    if (!photoSwipePromise) {
      photoSwipePromise = import(PHOTO_SWIPE_URL).then((module) => {
        PhotoSwipeClass = module.default;
        return PhotoSwipeClass;
      });
    }

    return photoSwipePromise;
  };

  const getImageSize = (src) =>
    new Promise((resolve) => {
      const image = new Image();
      image.onload = () => {
        resolve({
          width: image.naturalWidth || 1600,
          height: image.naturalHeight || 1000,
        });
      };
      image.onerror = () => {
        resolve({ width: 1600, height: 1000 });
      };
      image.src = src;
    });

  const resolveItemSizes = async (items) => {
    const measuredItems = await Promise.all(
      items.map(async (item) => {
        if (item.width > 0 && item.height > 0) {
          return item;
        }

        const size = await getImageSize(item.src);
        return {
          ...item,
          width: size.width,
          height: size.height,
        };
      })
    );

    return measuredItems;
  };

  const updateCaption = () => {
    const root = pswp?.element;
    if (!(root instanceof HTMLElement)) {
      return;
    }

    const caption = root.querySelector("[data-media-lightbox-caption]");
    if (!(caption instanceof HTMLElement)) {
      return;
    }

    const text = pswp?.currSlide?.data?.caption || "";
    caption.textContent = text;
    caption.hidden = text.trim() === "";
  };

  const cleanup = () => {
    const detail = lastOpenDetail || {};
    const trigger = lastActiveElement;

    pswp = null;
    lastOpenDetail = null;
    setScrollLock(false);

    dispatch("media-lightbox:close", {
      ...detail,
      trigger,
    });

    if (trigger instanceof HTMLElement) {
      trigger.focus();
    }

    lastActiveElement = null;
    isClosingFromApi = false;
  };

  const registerCaption = (instance) => {
    instance.on("uiRegister", () => {
      instance.ui.registerElement({
        name: "mediaLightboxCaption",
        className: "media-lightbox__caption",
        appendTo: "root",
        order: 9,
        html: "",
        onInit: (element) => {
          element.setAttribute("data-media-lightbox-caption", "");
          element.hidden = true;
        },
      });
    });

    instance.on("change", updateCaption);
    instance.on("afterInit", updateCaption);
  };

  const createPhotoSwipe = (PhotoSwipe, slides, safeIndex, ariaLabel) => {
    const instance = new PhotoSwipe({
      dataSource: slides.map((item) => ({
        src: item.src,
        width: item.width,
        height: item.height,
        w: item.width,
        h: item.height,
        alt: item.alt,
        caption: item.caption,
      })),
      index: safeIndex,
      mainClass: "media-lightbox pswp--szcube",
      bgOpacity: 0.94,
      showHideAnimationType: "fade",
      returnFocus: false,
      trapFocus: true,
      initialZoomLevel: "fit",
      secondaryZoomLevel: "zoom",
      maxZoomLevel: 4,
      imageClickAction: "zoom",
      tapAction: "toggle-controls",
      bgClickAction: "close",
      paddingFn: (viewportSize) => ({
        top: viewportSize.x <= 640 ? 56 : 72,
        bottom: viewportSize.x <= 640 ? 132 : 92,
        left: viewportSize.x <= 640 ? 16 : 86,
        right: viewportSize.x <= 640 ? 16 : 86,
      }),
      arrowPrevTitle: "Предыдущее изображение",
      arrowNextTitle: "Следующее изображение",
      closeTitle: "Закрыть",
      zoomTitle: "Увеличить изображение",
      indexIndicatorSep: " / ",
      errorMsg: "Изображение не удалось загрузить",
    });

    instance.on("beforeOpen", () => {
      const root = instance.element;
      if (root instanceof HTMLElement) {
        root.setAttribute("aria-label", ariaLabel);
      }
      setScrollLock(true);
    });

    instance.on("destroy", cleanup);
    registerCaption(instance);

    return instance;
  };

  function close() {
    if (!pswp) {
      return;
    }

    isClosingFromApi = true;
    pswp.close();
  }

  const open = ({
    items,
    initialIndex = 0,
    trigger = null,
    ariaLabel = "Просмотр изображения",
  } = {}) => {
    const slides = normalizeItems(items);
    if (!slides.length) {
      return false;
    }

    const safeIndex = Math.min(
      Math.max(0, Number(initialIndex) || 0),
      slides.length - 1
    );

    if (pswp) {
      isClosingFromApi = true;
      pswp.destroy();
    }

    lastActiveElement =
      trigger instanceof HTMLElement
        ? trigger
        : document.activeElement instanceof HTMLElement
          ? document.activeElement
          : null;

    lastOpenDetail = {
      trigger: lastActiveElement,
      initialIndex: safeIndex,
      items: slides,
      ariaLabel,
    };

    Promise.all([loadPhotoSwipe(), resolveItemSizes(slides)])
      .then(([PhotoSwipe, measuredSlides]) => {
        if (pswp || !lastOpenDetail) {
          return;
        }

        pswp = createPhotoSwipe(PhotoSwipe, measuredSlides, safeIndex, ariaLabel);
        pswp.init();
        dispatch("media-lightbox:open", {
          trigger: lastActiveElement,
          initialIndex: safeIndex,
          items: measuredSlides,
          ariaLabel,
        });
      })
      .catch((error) => {
        console.error("Failed to open media lightbox", error);
        cleanup();
      });

    return true;
  };

  const isOpen = () => Boolean(pswp && !pswp.isDestroying);

  window.SzcubeMediaLightbox = {
    open,
    close,
    isOpen,
  };
})();
