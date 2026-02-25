document.addEventListener("DOMContentLoaded", () => {
  const normalizeRuPhone = (value) => {
    const digits = String(value || "").replace(/\D+/g, "");
    if (!digits) return "";

    let normalizedDigits = digits;

    if (normalizedDigits.length === 10) {
      normalizedDigits = `7${normalizedDigits}`;
    } else if (normalizedDigits.length === 11 && normalizedDigits.startsWith("8")) {
      normalizedDigits = `7${normalizedDigits.slice(1)}`;
    }

    if (normalizedDigits.length !== 11 || !normalizedDigits.startsWith("7")) {
      return "";
    }

    return `+${normalizedDigits}`;
  };

  const getContactFormParts = (form) => ({
    nameInput: form.querySelector('input[name="name"]'),
    phoneInput: form.querySelector('input[name="phone"]'),
    consentInput: form.querySelector('input[name="consent"]'),
    consentLabel: form.querySelector("[data-contact-consent-label]"),
    submitButton: form.querySelector(".contact-form__submit"),
    messageEl: form.querySelector("[data-contact-form-message]"),
    leadTypeInput: form.querySelector('input[name="lead_type"]'),
    leadSourceInput: form.querySelector('input[name="lead_source"]'),
    pageUrlInput: form.querySelector('input[name="page_url"]'),
  });

  const setContactFormMessage = (form, text, state) => {
    const { messageEl } = getContactFormParts(form);
    if (!messageEl) return;

    if (!text) {
      messageEl.hidden = true;
      messageEl.textContent = "";
      messageEl.dataset.state = "";
      return;
    }

    messageEl.hidden = false;
    messageEl.textContent = text;
    messageEl.dataset.state = state;
  };

  const clearContactFormErrors = (form) => {
    const { nameInput, phoneInput, consentLabel } = getContactFormParts(form);

    [nameInput, phoneInput].forEach((input) => {
      if (!input) return;
      input.classList.remove("is-invalid");
      input.removeAttribute("aria-invalid");
    });

    if (consentLabel) {
      consentLabel.classList.remove("is-invalid");
    }
  };

  const applyContactFormErrors = (form, errors = {}) => {
    const { nameInput, phoneInput, consentLabel } = getContactFormParts(form);

    if (errors.name && nameInput) {
      nameInput.classList.add("is-invalid");
      nameInput.setAttribute("aria-invalid", "true");
    }

    if (errors.phone && phoneInput) {
      phoneInput.classList.add("is-invalid");
      phoneInput.setAttribute("aria-invalid", "true");
    }

    if (errors.consent && consentLabel) {
      consentLabel.classList.add("is-invalid");
    }
  };

  const setContactFormLoading = (form, isLoading) => {
    const { submitButton } = getContactFormParts(form);
    form.classList.toggle("is-loading", isLoading);
    if (submitButton) {
      submitButton.disabled = isLoading;
    }
  };

  const setContactFormMeta = (form, { leadType, leadSource } = {}) => {
    if (!form) return;

    const { leadTypeInput, leadSourceInput, pageUrlInput } = getContactFormParts(form);

    if (leadTypeInput && typeof leadType === "string" && leadType.trim() !== "") {
      leadTypeInput.value = leadType.trim();
    }

    if (leadSourceInput && typeof leadSource === "string" && leadSource.trim() !== "") {
      leadSourceInput.value = leadSource.trim();
    }

    if (pageUrlInput) {
      pageUrlInput.value = window.location.href;
    }
  };

  const resetContactFormUi = (form, { resetFields = false } = {}) => {
    let preservedMeta = null;

    if (resetFields) {
      const { leadTypeInput, leadSourceInput } = getContactFormParts(form);
      preservedMeta = {
        leadType: leadTypeInput ? leadTypeInput.value : "",
        leadSource: leadSourceInput ? leadSourceInput.value : "",
      };

      form.reset();
    }

    clearContactFormErrors(form);
    setContactFormLoading(form, false);
    setContactFormMessage(form, "", "");

    if (preservedMeta) {
      setContactFormMeta(form, preservedMeta);
    } else {
      setContactFormMeta(form);
    }
  };

  const validateContactForm = (form) => {
    const { nameInput, phoneInput, consentInput } = getContactFormParts(form);
    const errors = {};

    const normalizedName = nameInput ? nameInput.value.trim().replace(/\s+/g, " ") : "";
    const normalizedPhone = phoneInput ? normalizeRuPhone(phoneInput.value) : "";
    const consentChecked = consentInput ? Boolean(consentInput.checked) : false;

    if (!normalizedName) {
      errors.name = "Укажите имя.";
    } else if (normalizedName.length < 2) {
      errors.name = "Имя слишком короткое.";
    }

    if (!normalizedPhone) {
      errors.phone = "Укажите телефон в формате РФ.";
    }

    if (!consentChecked) {
      errors.consent = "Нужно согласие на обработку персональных данных.";
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors,
      normalizedName,
      normalizedPhone,
    };
  };

  const contactForms = document.querySelectorAll("[data-contact-form]");

  contactForms.forEach((form) => {
    setContactFormMeta(form);

    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      if (form.classList.contains("is-loading")) {
        return;
      }

      resetContactFormUi(form);

      const validation = validateContactForm(form);
      if (!validation.isValid) {
        applyContactFormErrors(form, validation.errors);
        setContactFormMessage(
          form,
          validation.errors.name || validation.errors.phone || validation.errors.consent || "Проверьте заполнение формы.",
          "error"
        );
        return;
      }

      const { nameInput, phoneInput, pageUrlInput } = getContactFormParts(form);
      if (nameInput) {
        nameInput.value = validation.normalizedName;
      }
      if (phoneInput) {
        phoneInput.value = validation.normalizedPhone;
      }
      if (pageUrlInput) {
        pageUrlInput.value = window.location.href;
      }

      setContactFormLoading(form, true);

      let response;
      let payload;

      try {
        response = await fetch(form.action, {
          method: "POST",
          body: new FormData(form),
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
        });

        payload = await response.json();
      } catch (error) {
        setContactFormMessage(form, "Не удалось отправить заявку. Проверьте соединение и попробуйте снова.", "error");
        setContactFormLoading(form, false);
        return;
      }

      setContactFormLoading(form, false);

      if (!response.ok || !payload || payload.success !== true) {
        applyContactFormErrors(form, payload && payload.errors ? payload.errors : {});
        setContactFormMessage(
          form,
          (payload && payload.message) || "Не удалось отправить заявку. Попробуйте позже.",
          "error"
        );
        return;
      }

      const { leadTypeInput, leadSourceInput } = getContactFormParts(form);
      const currentMeta = {
        leadType: leadTypeInput ? leadTypeInput.value : "",
        leadSource: leadSourceInput ? leadSourceInput.value : "",
      };

      form.reset();
      clearContactFormErrors(form);
      setContactFormMeta(form, currentMeta);
      setContactFormMessage(
        form,
        payload.message || form.dataset.contactSuccessText || "Спасибо! Мы свяжемся с вами в ближайшее время.",
        "success"
      );
    });
  });

  const contactModal = document.querySelector('[data-contact-modal="contact"]');
  const contactModalTitle = contactModal ? contactModal.querySelector("[data-contact-modal-title]") : null;
  const defaultContactModalTitle = contactModalTitle ? contactModalTitle.textContent : "";
  const contactModalCloseButtons = contactModal
    ? contactModal.querySelectorAll("[data-contact-modal-close]")
    : [];
  const contactOpenButtons = document.querySelectorAll('[data-contact-open="contact"]');
  const contactModalForm = contactModal ? contactModal.querySelector("[data-contact-form]") : null;

  const openContactModal = (opts = {}) => {
    if (!contactModal) return;

    if (contactModalTitle) {
      const nextTitle =
        typeof opts.title === "string" && opts.title.trim() !== "" ? opts.title.trim() : defaultContactModalTitle;
      contactModalTitle.textContent = nextTitle;
    }

    if (contactModalForm) {
      resetContactFormUi(contactModalForm, { resetFields: true });
      setContactFormMeta(contactModalForm, {
        leadType: opts.leadType || "callback",
        leadSource: opts.leadSource || "modal",
      });
    }

    contactModal.classList.add("is-open");
    contactModal.setAttribute("aria-hidden", "false");
    document.body.classList.add("no-scroll");
  };

  const closeContactModal = () => {
    if (!contactModal) return;
    contactModal.classList.remove("is-open");
    contactModal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("no-scroll");
  };

  if (contactModal) {
    contactOpenButtons.forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        openContactModal({
          title: btn.dataset.contactTitle,
          leadType: btn.dataset.contactType,
          leadSource: btn.dataset.contactSource,
        });
      });
    });

    contactModalCloseButtons.forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        closeContactModal();
      });
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && contactModal.classList.contains("is-open")) {
        closeContactModal();
      }
    });
  }

  const navBtn = document.querySelector(".mobile-nav-btn");
  const nav = document.querySelector(".mobile-nav");
  const menuIcon = document.querySelector(".nav-icon");
  const navMore = document.querySelector(".nav__more");
  const navMoreBtn = document.querySelector(".nav__more-btn");

  if (navBtn && nav && menuIcon) {
    const toggleNav = () => {
      nav.classList.toggle("mobile-nav--open");
      menuIcon.classList.toggle("nav-icon--active");
      document.body.classList.toggle("no-scroll");
    };

    navBtn.addEventListener("click", toggleNav);

    nav.addEventListener("click", (event) => {
      const link = event.target.closest("a");
      if (link) {
        toggleNav();
      }
    });
  }

  if (navMore && navMoreBtn) {
    const closeDropdown = () => {
      navMore.classList.remove("is-open");
      navMoreBtn.setAttribute("aria-expanded", "false");
    };

    navMoreBtn.addEventListener("click", (event) => {
      event.preventDefault();
      const isOpen = navMore.classList.toggle("is-open");
      navMoreBtn.setAttribute("aria-expanded", String(isOpen));
    });

    document.addEventListener("click", (event) => {
      if (!navMore.contains(event.target)) {
        closeDropdown();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        closeDropdown();
      }
    });
  }
});
