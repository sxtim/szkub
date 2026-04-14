document.addEventListener("DOMContentLoaded", () => {
  const swiperEl = document.querySelector("[data-apartment-swiper]");
  const prevButton = document.querySelector("[data-apartment-prev]");
  const nextButton = document.querySelector("[data-apartment-next]");
  const zoomButton = document.querySelector('[data-apartment-action="zoom"]');
  const tabs = Array.from(document.querySelectorAll("[data-apartment-tab]"));
  const tabsScroller = document.querySelector(".apartment-hero__tabs");
  const paramsToggle = document.querySelector("[data-apartment-params-toggle]");
  const paramsBody = document.querySelector("[data-apartment-params]");
  const lightbox = document.querySelector("[data-apartment-lightbox]");
  const lightboxImage = document.querySelector("[data-apartment-lightbox-image]");
  const lightboxCaption = document.querySelector("[data-apartment-lightbox-caption]");
  const lightboxFigure = document.querySelector(".apartment-hero__lightbox-figure");
  const lightboxPrev = document.querySelector("[data-apartment-lightbox-prev]");
  const lightboxNext = document.querySelector("[data-apartment-lightbox-next]");
  const lightboxCloseButtons = Array.from(
    document.querySelectorAll("[data-apartment-lightbox-close]")
  );

  const scrollActiveTabIntoView = (index) => {
    const activeTab = tabs[index];

    if (!activeTab || !tabsScroller || tabsScroller.scrollWidth <= tabsScroller.clientWidth) {
      return;
    }

    const centeredLeft = activeTab.offsetLeft - ((tabsScroller.clientWidth - activeTab.offsetWidth) / 2);
    const maxLeft = tabsScroller.scrollWidth - tabsScroller.clientWidth;

    tabsScroller.scrollTo({
      left: Math.max(0, Math.min(centeredLeft, maxLeft)),
      behavior: "smooth",
    });
  };

  const setActiveTab = (index) => {
    tabs.forEach((tab, tabIndex) => {
      const isActive = tabIndex === index;
      tab.classList.toggle("is-active", isActive);
      tab.setAttribute("aria-selected", isActive ? "true" : "false");
    });

    scrollActiveTabIntoView(index);
  };

  let swiper = null;
  let isLightboxOpen = false;

  if (lightbox && document.body && lightbox.parentElement !== document.body) {
    document.body.appendChild(lightbox);
  }

  const getSlideData = () => {
    const slides = swiper?.slides?.length
      ? Array.from(swiper.slides)
      : Array.from(swiperEl?.querySelectorAll(".swiper-slide") || []);
    const total = slides.length || 1;
    const index = swiper?.activeIndex ?? 0;
    const slide = slides[index] || slides[0] || null;
    const image = slide?.querySelector("img") || null;
    const title = slide?.querySelector(".apartment-hero__slide-title")?.textContent?.trim() || "";

    return {
      index,
      total,
      src: image?.getAttribute("src") || "",
      alt: image?.getAttribute("alt") || "",
      title,
    };
  };

  const updateLightbox = () => {
    if (!lightbox || !lightboxImage) {
      return;
    }

    const slide = getSlideData();
    if (!slide.src) {
      return;
    }

    lightboxImage.src = slide.src;
    lightboxImage.alt = slide.alt || slide.title || document.title;
    if (lightboxFigure) {
      lightboxFigure.scrollTop = 0;
      lightboxFigure.scrollLeft = 0;
    }

    if (lightboxCaption) {
      const caption = slide.title || slide.alt;
      lightboxCaption.textContent = caption;
      lightboxCaption.hidden = !caption;
    }

    if (lightboxPrev instanceof HTMLButtonElement) {
      lightboxPrev.disabled = slide.index <= 0;
    }

    if (lightboxNext instanceof HTMLButtonElement) {
      lightboxNext.disabled = slide.index >= slide.total - 1;
    }
  };

  const closeLightbox = () => {
    if (!lightbox || lightbox.hidden) {
      return;
    }

    lightbox.hidden = true;
    isLightboxOpen = false;
    zoomButton?.classList.remove("is-active");
    document.documentElement.classList.remove("is-apartment-lightbox-open");
    document.body.classList.remove("is-apartment-lightbox-open");
  };

  const openLightbox = () => {
    if (!lightbox) {
      return;
    }

    updateLightbox();
    lightbox.hidden = false;
    isLightboxOpen = true;
    zoomButton?.classList.add("is-active");
    document.documentElement.classList.add("is-apartment-lightbox-open");
    document.body.classList.add("is-apartment-lightbox-open");
  };

  if (swiperEl && typeof window.Swiper !== "undefined") {
    swiper = new window.Swiper(swiperEl, {
      slidesPerView: 1,
      speed: 480,
      effect: "fade",
      fadeEffect: {
        crossFade: true,
      },
      navigation: {
        prevEl: prevButton,
        nextEl: nextButton,
      },
      on: {
        init(instance) {
          setActiveTab(instance.activeIndex);
        },
        slideChange(instance) {
          setActiveTab(instance.activeIndex);
          if (isLightboxOpen) {
            updateLightbox();
          }
        },
      },
    });
  }

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      if (!swiper) return;
      swiper.slideTo(index);
    });
  });

  document.querySelectorAll("[data-apartment-action]").forEach((button) => {
    const action = button.dataset.apartmentAction;

    button.addEventListener("click", async () => {
      if (action === "zoom") {
        if (isLightboxOpen) {
          closeLightbox();
          return;
        }

        openLightbox();
        return;
      }

      if (action === "favorite") {
        button.classList.toggle("is-active");
        return;
      }

      if (action === "share") {
        const url = window.location.href;
        if (navigator.share) {
          try {
            await navigator.share({
              title: document.title,
              url,
            });
            return;
          } catch (error) {
            // fall through to clipboard
          }
        }

        if (navigator.clipboard?.writeText) {
          try {
            await navigator.clipboard.writeText(url);
            button.classList.add("is-active");
            window.setTimeout(() => button.classList.remove("is-active"), 1200);
          } catch (error) {
            window.prompt("Скопируйте ссылку", url);
          }
          return;
        }

        window.prompt("Скопируйте ссылку", url);
        return;
      }

      if (action === "print") {
        const printUrl = button.dataset.printUrl?.trim();

        if (!printUrl) {
          return;
        }

        const printWindow = window.open(printUrl, "_blank", "noopener");
        if (!printWindow) {
          window.location.href = printUrl;
        }
      }
    });
  });

  if (paramsToggle && paramsBody) {
    paramsToggle.addEventListener("click", () => {
      const expanded = paramsToggle.getAttribute("aria-expanded") === "true";
      paramsToggle.setAttribute("aria-expanded", expanded ? "false" : "true");
      paramsBody.hidden = expanded;
    });
  }

  lightboxPrev?.addEventListener("click", () => swiper?.slidePrev());
  lightboxNext?.addEventListener("click", () => swiper?.slideNext());
  lightboxCloseButtons.forEach((button) => {
    button.addEventListener("click", closeLightbox);
  });

  document.addEventListener("keydown", (event) => {
    if (!isLightboxOpen) {
      return;
    }

    if (event.key === "Escape") {
      closeLightbox();
      return;
    }

    if (event.key === "ArrowLeft") {
      swiper?.slidePrev();
      return;
    }

    if (event.key === "ArrowRight") {
      swiper?.slideNext();
    }
  });
});
