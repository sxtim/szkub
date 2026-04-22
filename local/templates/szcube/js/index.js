document.addEventListener("DOMContentLoaded", () => {
  const cookieBanner = document.querySelector("[data-cookie-banner]");
  const cookieBannerAccept = document.querySelector("[data-cookie-banner-accept]");
  const cookieBannerStorageKey = "szcube_cookie_notice_accepted";

  const getCookieBannerAccepted = () => {
    try {
      return window.localStorage.getItem(cookieBannerStorageKey) === "Y";
    } catch (error) {
      return false;
    }
  };

  const setCookieBannerAccepted = () => {
    try {
      window.localStorage.setItem(cookieBannerStorageKey, "Y");
    } catch (error) {
      return;
    }
  };

  if (cookieBanner && !getCookieBannerAccepted()) {
    cookieBanner.hidden = false;
    requestAnimationFrame(() => {
      cookieBanner.classList.add("is-visible");
    });
  }

  if (cookieBanner && cookieBannerAccept) {
    cookieBannerAccept.addEventListener("click", () => {
      setCookieBannerAccepted();
      cookieBanner.classList.remove("is-visible");
      window.setTimeout(() => {
        cookieBanner.hidden = true;
      }, 220);
    });
  }

  const contactPhoneMasks = new WeakMap();
  const CONTACT_PHONE_PLACEHOLDER = "+7 XXX XXX-XX-XX";
  const CONTACT_PHONE_DIGITS_REQUIRED = 10;

  const normalizeContactPhoneMaskDigits = (value) => {
    let digits = String(value || "").replace(/\D+/g, "");

    if (digits.length === 11 && (digits.startsWith("7") || digits.startsWith("8"))) {
      digits = digits.slice(1);
    }

    if (digits.length > CONTACT_PHONE_DIGITS_REQUIRED) {
      digits = digits.slice(0, CONTACT_PHONE_DIGITS_REQUIRED);
    }

    return digits;
  };

  const normalizeContactPhone = (value) =>
    String(value || "")
      .replace(/[^\d+()\-\s]/g, "")
      .replace(/\s+/g, " ")
      .replace(/(?!^)\+/g, "")
      .trim();

  const countContactPhoneDigits = (value) => normalizeContactPhone(value).replace(/\D+/g, "").length;

  const getContactPhoneDigitsCount = (input) => {
    if (!input) {
      return 0;
    }

    const mask = contactPhoneMasks.get(input);
    if (mask) {
      const digits = String(mask.unmaskedValue || "").replace(/\D+/g, "");
      return Math.max(0, digits.length - 1);
    }

    return countContactPhoneDigits(input.value);
  };

  const getContactPhoneValue = (input) => {
    if (!input) {
      return "";
    }

    const mask = contactPhoneMasks.get(input);
    const rawValue = mask ? mask.value : input.value;
    return normalizeContactPhone(rawValue);
  };

  const setContactPhoneValue = (input, value) => {
    if (!input) {
      return;
    }

    const normalizedValue = normalizeContactPhone(value);
    const mask = contactPhoneMasks.get(input);
    if (mask) {
      const digits = normalizeContactPhoneMaskDigits(normalizedValue);
      if (!digits) {
        mask.value = "";
        return;
      }

      mask.unmaskedValue = `7${digits}`;
      return;
    }

    input.value = normalizedValue;
  };

  const setContactPhonePrefix = (input) => {
    const mask = contactPhoneMasks.get(input);
    if (!mask) {
      input.value = "+7 ";
      return;
    }

    input.value = "+7 ";
    mask.updateValue();
    window.requestAnimationFrame(() => {
      const cursorPosition = input.value.length;
      input.setSelectionRange(cursorPosition, cursorPosition);
    });
  };

  const initContactPhoneInput = (input) => {
    if (!input) {
      return;
    }

    input.setAttribute("placeholder", CONTACT_PHONE_PLACEHOLDER);

    if (typeof window.IMask === "function") {
      const mask = window.IMask(input, {
        mask: "+{7} 000 000-00-00",
        lazy: true,
        placeholderChar: "X",
        prepare: (appended, masked) => {
          const raw = String(appended || "");
          const digits = String(appended || "").replace(/\D+/g, "");
          if (!digits) {
            return "";
          }

          const currentDigits = String(masked.unmaskedValue || "").replace(/\D+/g, "");
          const editableDigitsCount = Math.max(0, currentDigits.length - 1);

          if (editableDigitsCount === 0 && (digits[0] === "7" || digits[0] === "8")) {
            return digits.slice(1);
          }

          return digits;
        },
      });
      contactPhoneMasks.set(input, mask);
    }

    input.addEventListener("focus", () => {
      const mask = contactPhoneMasks.get(input);
      if (!mask) {
        return;
      }

      if (getContactPhoneDigitsCount(input) === 0) {
        setContactPhonePrefix(input);
      }
    });

    input.addEventListener("keydown", (event) => {
      if (getContactPhoneDigitsCount(input) !== 0) {
        return;
      }

      if (event.key === "+" || event.key === "7" || event.key === "8") {
        event.preventDefault();
        setContactPhonePrefix(input);
      }
    });

    input.addEventListener("blur", () => {
      if (getContactPhoneDigitsCount(input) === 0) {
        const mask = contactPhoneMasks.get(input);
        if (mask) {
          mask.value = "";
          return;
        }
      }

      setContactPhoneValue(input, getContactPhoneValue(input));
    });
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
    leadNoteInput: form.querySelector('input[name="lead_note"]'),
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

  const setContactFormMeta = (form, { leadType, leadSource, leadNote } = {}) => {
    if (!form) return;

    const { leadTypeInput, leadSourceInput, leadNoteInput, pageUrlInput } = getContactFormParts(form);

    if (leadTypeInput && typeof leadType === "string" && leadType.trim() !== "") {
      leadTypeInput.value = leadType.trim();
    }

    if (leadSourceInput && typeof leadSource === "string" && leadSource.trim() !== "") {
      leadSourceInput.value = leadSource.trim();
    }

    if (leadNoteInput && typeof leadNote === "string") {
      leadNoteInput.value = leadNote.trim();
    }

    if (pageUrlInput) {
      pageUrlInput.value = window.location.href;
    }
  };

  const resetContactFormUi = (form, { resetFields = false } = {}) => {
    let preservedMeta = null;

    if (resetFields) {
      const { leadTypeInput, leadSourceInput, leadNoteInput } = getContactFormParts(form);
      preservedMeta = {
        leadType: leadTypeInput ? leadTypeInput.value : "",
        leadSource: leadSourceInput ? leadSourceInput.value : "",
        leadNote: leadNoteInput ? leadNoteInput.value : "",
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
    const normalizedPhone = phoneInput ? getContactPhoneValue(phoneInput) : "";
    const phoneDigitsCount = getContactPhoneDigitsCount(phoneInput);
    const consentChecked = consentInput ? Boolean(consentInput.checked) : false;

    if (!normalizedName) {
      errors.name = "Укажите имя.";
    } else if (normalizedName.length < 2) {
      errors.name = "Имя слишком короткое.";
    } else if (/\d/.test(normalizedName)) {
      errors.name = "Имя не должно содержать цифры.";
    }

    if (!normalizedPhone) {
      errors.phone = "Укажите телефон.";
    } else if (phoneDigitsCount !== CONTACT_PHONE_DIGITS_REQUIRED) {
      errors.phone = `Введите телефон в формате ${CONTACT_PHONE_PLACEHOLDER}.`;
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
    const { phoneInput } = getContactFormParts(form);
    initContactPhoneInput(phoneInput);

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
        setContactPhoneValue(phoneInput, validation.normalizedPhone);
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
        leadNote: (() => {
          const { leadNoteInput } = getContactFormParts(form);
          return leadNoteInput ? leadNoteInput.value : "";
        })(),
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
        leadNote: opts.leadNote || "",
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
    document.addEventListener("click", (event) => {
      const btn = event.target.closest('[data-contact-open="contact"]');
      if (!btn) {
        return;
      }

      event.preventDefault();
      openContactModal({
        title: btn.dataset.contactTitle,
        leadType: btn.dataset.contactType,
        leadSource: btn.dataset.contactSource,
        leadNote: btn.dataset.contactNote || "",
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
