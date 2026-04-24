document.addEventListener("DOMContentLoaded", () => {
  const printPage = document.querySelector("[data-apartment-print-page]");
  if (!printPage) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  if (params.get("print") !== "Y") {
    return;
  }

  const printActionButtons = Array.from(document.querySelectorAll("[data-apartment-print-action]"));
  const datasetReturnUrl = printPage.dataset.returnUrl?.trim();

  const getFallbackReturnUrl = () => {
    if (datasetReturnUrl) {
      return datasetReturnUrl;
    }

    const nextUrl = new URL(window.location.href);
    nextUrl.searchParams.delete("print");
    return nextUrl.pathname + nextUrl.search + nextUrl.hash;
  };

  const closePrintPage = () => {
    const fallbackReturnUrl = getFallbackReturnUrl();
    const hasUsableHistory = window.history.length > 1 && document.referrer.indexOf(window.location.origin) === 0;

    if (hasUsableHistory) {
      window.history.back();
      return;
    }

    window.close();

    window.setTimeout(() => {
      if (!window.closed && fallbackReturnUrl) {
        window.location.href = fallbackReturnUrl;
      }
    }, 120);
  };

  printActionButtons.forEach((button) => {
    button.addEventListener("click", (event) => {
      const action = button.dataset.apartmentPrintAction;
      if (!action) {
        return;
      }

      if (action === "print") {
        event.preventDefault();
        window.print();
        return;
      }

      if (action === "close") {
        event.preventDefault();
        closePrintPage();
      }
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
      return;
    }

    closePrintPage();
  });
});
