(function () {
  function initAboutCompanyProjectTabs() {
    var tabs = Array.prototype.slice.call(
      document.querySelectorAll("[data-about-company-project-tab]")
    );
    var panels = Array.prototype.slice.call(
      document.querySelectorAll("[data-about-company-project-panel]")
    );

    if (!tabs.length || !panels.length) {
      return;
    }

    function setActive(code) {
      tabs.forEach(function (tab) {
        var isActive = tab.getAttribute("data-target") === code;
        tab.classList.toggle("is-active", isActive);
        tab.setAttribute("aria-selected", isActive ? "true" : "false");
        tab.setAttribute("tabindex", isActive ? "0" : "-1");
      });

      panels.forEach(function (panel) {
        var isActive = panel.getAttribute("data-about-company-project-panel") === code;
        panel.hidden = !isActive;
        panel.classList.toggle("is-active", isActive);
      });
    }

    function moveFocus(currentIndex, direction) {
      var nextIndex = currentIndex + direction;

      if (nextIndex < 0) {
        nextIndex = tabs.length - 1;
      }

      if (nextIndex >= tabs.length) {
        nextIndex = 0;
      }

      tabs[nextIndex].focus();
      setActive(tabs[nextIndex].getAttribute("data-target"));
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener("click", function () {
        setActive(tab.getAttribute("data-target"));
      });

      tab.addEventListener("keydown", function (event) {
        if (event.key === "ArrowRight") {
          event.preventDefault();
          moveFocus(index, 1);
        }

        if (event.key === "ArrowLeft") {
          event.preventDefault();
          moveFocus(index, -1);
        }
      });
    });
  }

  function initAboutCompanyGallery() {
    var tracks = document.querySelectorAll("[data-about-company-gallery-track]");
    if (!tracks.length) {
      return;
    }

    function syncTrack(track) {
      var sequence = track.querySelector(".about-company-social-gallery__sequence");
      if (!sequence) {
        return;
      }

      track.style.setProperty(
        "--about-company-sequence-height",
        Math.ceil(sequence.getBoundingClientRect().height) + "px"
      );
    }

    function syncAllTracks() {
      tracks.forEach(syncTrack);
    }

    syncAllTracks();

    if ("ResizeObserver" in window) {
      var observer = new ResizeObserver(syncAllTracks);

      tracks.forEach(function (track) {
        var sequence = track.querySelector(".about-company-social-gallery__sequence");
        if (sequence) {
          observer.observe(sequence);
        }
      });
    } else {
      window.addEventListener("resize", syncAllTracks);
    }

    tracks.forEach(function (track) {
      track.querySelectorAll("img").forEach(function (image) {
        if (!image.complete) {
          image.addEventListener(
            "load",
            function () {
              syncTrack(track);
            },
            { once: true }
          );
        }
      });
    });
  }

  function initAboutCompanyPage() {
    initAboutCompanyProjectTabs();
    initAboutCompanyGallery();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAboutCompanyPage);
  } else {
    initAboutCompanyPage();
  }
})();
