(function () {
  const initApartmentSimilar = () => {
    const root = document.querySelector("[data-apartment-similar]");
    if (!root) {
      return;
    }

    const swiperEl = root.querySelector("[data-apartment-similar-swiper]");
    const prevEl = root.querySelector("[data-apartment-similar-prev]");
    const nextEl = root.querySelector("[data-apartment-similar-next]");

    if (swiperEl && typeof window.Swiper !== "undefined") {
      new window.Swiper(swiperEl, {
        slidesPerView: 1.15,
        spaceBetween: 16,
        navigation: {
          prevEl,
          nextEl,
        },
        breakpoints: {
          640: {
            slidesPerView: 2,
            spaceBetween: 20,
          },
          1024: {
            slidesPerView: 3,
            spaceBetween: 24,
          },
          1440: {
            slidesPerView: 4,
            spaceBetween: 24,
          },
        },
      });
    }

    root.addEventListener("click", (event) => {
      const boardButton = event.target.closest(".apartment-card__board");
      if (boardButton) {
        event.preventDefault();
        event.stopPropagation();
        const boardUrl = boardButton.getAttribute("data-board-url");
        if (boardUrl) {
          window.location.href = boardUrl;
        }
        return;
      }

      const favoriteButton = event.target.closest(".apartment-card__fav");
      if (favoriteButton) {
        event.preventDefault();
        event.stopPropagation();
        favoriteButton.classList.toggle("is-active");
        return;
      }

      const card = event.target.closest("[data-card-url]");
      if (!card) {
        return;
      }

      const url = card.getAttribute("data-card-url");
      if (url) {
        window.location.href = url;
      }
    });

    root.addEventListener("keydown", (event) => {
      if (event.key !== "Enter" && event.key !== " ") {
        return;
      }

      const card = event.target.closest("[data-card-url]");
      if (!card) {
        return;
      }

      event.preventDefault();
      const url = card.getAttribute("data-card-url");
      if (url) {
        window.location.href = url;
      }
    });
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initApartmentSimilar);
  } else {
    initApartmentSimilar();
  }
})();
