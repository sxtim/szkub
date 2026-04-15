document.addEventListener("DOMContentLoaded", () => {
  const swiperEl = document.querySelector("[data-apartment-swiper]");
  const prevButton = document.querySelector("[data-apartment-prev]");
  const nextButton = document.querySelector("[data-apartment-next]");
  const zoomButton = document.querySelector('[data-apartment-action="zoom"]');
  const tabs = Array.from(document.querySelectorAll("[data-apartment-tab]"));
  const tabsScroller = document.querySelector(".apartment-hero__tabs");
  const paramsToggle = document.querySelector("[data-apartment-params-toggle]");
  const paramsBody = document.querySelector("[data-apartment-params]");

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

  const getLightboxSlides = () => {
    const slides = swiper?.slides?.length
      ? Array.from(swiper.slides)
      : Array.from(swiperEl?.querySelectorAll(".swiper-slide") || []);

    return slides
      .map((slide) => {
        const image = slide.querySelector("img");
        const title =
          slide.querySelector(".apartment-hero__slide-title")?.textContent?.trim() || "";

        const src = image?.getAttribute("src") || "";
        if (!src) {
          return null;
        }

        return {
          src,
          alt: image?.getAttribute("alt") || "",
          title,
        };
      })
      .filter(Boolean);
  };

  const closeLightbox = () => {
    if (!isLightboxOpen) {
      return;
    }

    window.SzcubeMediaLightbox?.close();
    isLightboxOpen = false;
    zoomButton?.classList.remove("is-active");
  };

  const openLightbox = () => {
    const slides = getLightboxSlides();
    if (!slides.length || !window.SzcubeMediaLightbox) {
      return;
    }

    const initialIndex = Math.min(Math.max(swiper?.activeIndex ?? 0, 0), slides.length - 1);

    const opened = window.SzcubeMediaLightbox.open({
      items: slides.map((slide) => ({
        src: slide.src,
        alt: slide.alt || slide.title || document.title,
        caption: slide.title || slide.alt || "",
      })),
      initialIndex,
      trigger: zoomButton,
      ariaLabel: "Просмотр изображения",
    });

    if (!opened) {
      return;
    }

    isLightboxOpen = true;
    zoomButton?.classList.add("is-active");
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

  document.addEventListener("media-lightbox:close", () => {
    if (!isLightboxOpen) {
      return;
    }

    isLightboxOpen = false;
    zoomButton?.classList.remove("is-active");
  });

});
