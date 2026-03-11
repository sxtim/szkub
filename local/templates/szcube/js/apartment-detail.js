document.addEventListener("DOMContentLoaded", () => {
  const galleryRoot = document.querySelector("[data-apartment-gallery]");
  const swiperEl = document.querySelector("[data-apartment-swiper]");
  const prevButton = document.querySelector("[data-apartment-prev]");
  const nextButton = document.querySelector("[data-apartment-next]");
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
        galleryRoot?.classList.toggle("is-zoomed");
        button.classList.toggle("is-active");
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
        window.print();
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
});
