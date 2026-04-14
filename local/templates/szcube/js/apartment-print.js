document.addEventListener("DOMContentLoaded", () => {
  const printPage = document.querySelector("[data-apartment-print-page]");
  if (!printPage) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  if (params.get("print") !== "Y") {
    return;
  }

  const triggerPrint = () => {
    window.setTimeout(() => {
      window.print();
    }, 280);
  };

  if (document.readyState === "complete") {
    triggerPrint();
    return;
  }

  window.addEventListener("load", triggerPrint, { once: true });
});
