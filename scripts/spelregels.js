import { translations } from "./app-translations.js";

const STORAGE_KEY = "boules_language";
const AUTH_STORAGE_KEY = "boules_auth";
const normalizeLanguage = (language) => language === "en" ? "en" : "nl";
const normalizeRole = (role) => role === "admin" ? "admin" : "player";
let currentLanguage = normalizeLanguage(localStorage.getItem(STORAGE_KEY));
let languageMenuTimer = 0;
let accountMenuTimer = 0;
let authModalTimer = 0;

const translate = (key) => translations[currentLanguage]?.[key] || translations.nl[key] || key;

function readStoredAuth() {
  try {
    const storedValue = JSON.parse(localStorage.getItem(AUTH_STORAGE_KEY) || "null");
    const user = storedValue?.user;

    if (!storedValue || storedValue.loggedIn !== true || !user || typeof user !== "object") {
      return {
        loggedIn: false,
        role: "player",
        user: null,
      };
    }

    const displayName = typeof user.name === "string" && user.name.trim() ? user.name.trim() : translate("account.name");

    return {
      loggedIn: true,
      role: normalizeRole(storedValue.role),
      user: {
        id: typeof user.id === "string" ? user.id : displayName.toLowerCase().replace(/\s+/g, "-"),
        name: displayName,
        initials: typeof user.initials === "string" && user.initials.trim() ? user.initials.trim() : displayName.charAt(0).toUpperCase(),
      },
    };
  } catch (error) {
    console.error(`Could not load ${AUTH_STORAGE_KEY}`, error);
    return {
      loggedIn: false,
      role: "player",
      user: null,
    };
  }
}

function saveStoredAuth() {
  if (!state.loggedIn || !state.user) {
    localStorage.removeItem(AUTH_STORAGE_KEY);
    return;
  }

  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify({
    loggedIn: true,
    role: normalizeRole(state.role),
    user: state.user,
  }));
}

const state = readStoredAuth();

const refs = {
  body: document.body,
  html: document.documentElement,
  navToggle: document.querySelector(".nav-toggle"),
  navLinks: Array.from(document.querySelectorAll(".main-nav a")),
  langSwitcher: document.querySelector("[data-lang-switcher]"),
  langToggle: document.querySelector("[data-lang-toggle]"),
  langMenu: document.querySelector("[data-lang-menu]"),
  langCurrent: document.querySelector("[data-lang-current]"),
  langOptions: Array.from(document.querySelectorAll("[data-lang-option]")),
  authToggles: Array.from(document.querySelectorAll("[data-auth-toggle]")),
  authOpeners: Array.from(document.querySelectorAll("[data-auth-open]")),
  authModal: document.querySelector("[data-auth-modal]"),
  signupCtas: Array.from(document.querySelectorAll("[data-auth-open='signup']")),
  accountSwitcher: document.querySelector("[data-account-switcher]"),
  accountToggle: document.querySelector("[data-account-toggle]"),
  accountMenu: document.querySelector("[data-account-menu]"),
  accountName: document.querySelector("[data-account-name]"),
  accountRole: document.querySelector("[data-account-role]"),
  accountAvatar: document.querySelector(".account-avatar"),
  roleOptions: Array.from(document.querySelectorAll("[data-role-option]")),
  authCloseButtons: Array.from(document.querySelectorAll("[data-auth-close]")),
  authTabs: Array.from(document.querySelectorAll("[data-auth-tab]")),
  authForms: Array.from(document.querySelectorAll("[data-auth-form]")),
  authSwitchButtons: Array.from(document.querySelectorAll("[data-auth-switch]")),
  authFeedbackElements: Array.from(document.querySelectorAll("[data-auth-feedback]")),
  authPasswordToggles: Array.from(document.querySelectorAll("[data-auth-password-toggle]")),
  authModalKicker: document.querySelector("[data-auth-kicker]"),
  authModalTitle: document.querySelector("[data-auth-title]"),
  authModalDescription: document.querySelector("[data-auth-description]"),
  authForgotPassword: document.querySelector("[data-auth-forgot-password]"),
  authSubmitLogin: document.querySelector("[data-auth-submit-login]"),
  authSubmitSignup: document.querySelector("[data-auth-submit-signup]"),
  authSwitchCopyLogin: document.querySelector("[data-auth-switch-copy-login]"),
  authSwitchCopySignup: document.querySelector("[data-auth-switch-copy-signup]"),
};

function syncNavToggleLabel() {
  if (!refs.navToggle) {
    return;
  }

  refs.navToggle.setAttribute("aria-label", refs.body.classList.contains("nav-open") ? translate("nav.closeMenu") : translate("nav.menu"));
}

function setNavOpen(isOpen) {
  refs.body.classList.toggle("nav-open", isOpen);

  if (refs.navToggle) {
    refs.navToggle.setAttribute("aria-expanded", String(isOpen));
  }

  syncNavToggleLabel();
}

function setLanguageMenuOpen(isOpen) {
  if (!refs.langSwitcher || !refs.langToggle || !refs.langMenu) {
    return;
  }

  clearTimeout(languageMenuTimer);
  refs.langToggle.setAttribute("aria-expanded", String(isOpen));

  if (isOpen) {
    closeAccountMenu();
    refs.langMenu.hidden = false;
    requestAnimationFrame(() => {
      refs.langSwitcher.classList.add("is-open");
    });
    return;
  }

  refs.langSwitcher.classList.remove("is-open");
  languageMenuTimer = window.setTimeout(() => {
    if (!refs.langSwitcher.classList.contains("is-open")) {
      refs.langMenu.hidden = true;
    }
  }, 180);
}

function setAccountMenuOpen(isOpen) {
  if (!refs.accountSwitcher || !refs.accountToggle || !refs.accountMenu || !state.loggedIn) {
    return;
  }

  clearTimeout(accountMenuTimer);
  refs.accountToggle.setAttribute("aria-expanded", String(isOpen));

  if (isOpen) {
    setLanguageMenuOpen(false);
    refs.accountMenu.hidden = false;
    requestAnimationFrame(() => {
      refs.accountSwitcher.classList.add("is-open");
    });
    return;
  }

  refs.accountSwitcher.classList.remove("is-open");
  accountMenuTimer = window.setTimeout(() => {
    if (!refs.accountSwitcher.classList.contains("is-open")) {
      refs.accountMenu.hidden = true;
    }
  }, 180);
}

function closeAccountMenu() {
  setAccountMenuOpen(false);
}

function getAccountName() {
  return state.user?.name || translate("account.name");
}

function getAccountInitial() {
  return state.user?.initials || getAccountName().charAt(0).toUpperCase() || "A";
}

function syncAccountUI() {
  refs.signupCtas.forEach((cta) => {
    cta.hidden = state.loggedIn;
  });

  if (refs.accountSwitcher) {
    refs.accountSwitcher.hidden = !state.loggedIn;
  }

  if (refs.accountToggle) {
    refs.accountToggle.setAttribute("aria-label", translate("account.menuLabel"));
  }

  if (!state.loggedIn && refs.accountSwitcher && refs.accountMenu && refs.accountToggle) {
    refs.accountSwitcher.classList.remove("is-open");
    refs.accountMenu.hidden = true;
    refs.accountToggle.setAttribute("aria-expanded", "false");
  }

  if (refs.accountName) {
    refs.accountName.textContent = getAccountName();
  }

  if (refs.accountRole) {
    refs.accountRole.textContent = translate(state.role === "admin" ? "account.roleAdmin" : "account.rolePlayer");
  }

  if (refs.accountAvatar) {
    refs.accountAvatar.textContent = getAccountInitial();
  }

  refs.roleOptions.forEach((option) => {
    const optionRole = normalizeRole(option.dataset.roleOption);
    option.classList.toggle("is-active", optionRole === state.role);
    option.disabled = !state.loggedIn;
    option.setAttribute("aria-disabled", String(!state.loggedIn));
  });
}

function setLoggedIn(loggedIn) {
  state.loggedIn = Boolean(loggedIn);
  refs.body.classList.toggle("is-logged-in", state.loggedIn);

  if (!state.loggedIn) {
    state.role = "player";
    state.user = null;
    closeAuthModal();
    closeAccountMenu();
  }

  saveStoredAuth();
  syncAuthUI();
}

function setRole(role) {
  if (!state.loggedIn) {
    return;
  }

  state.role = normalizeRole(role);
  saveStoredAuth();
  syncAccountUI();
}

function setAuthMode(mode = "login") {
  const nextMode = mode === "signup" ? "signup" : "login";

  refs.authTabs.forEach((tab) => {
    tab.classList.toggle("is-active", tab.dataset.authTab === nextMode);
  });

  refs.authForms.forEach((form) => {
    const isActive = form.dataset.authForm === nextMode;
    form.hidden = !isActive;
    form.classList.toggle("is-active", isActive);
  });

  refs.authFeedbackElements.forEach((element) => {
    element.textContent = "";
    element.classList.remove("is-success");
  });

  if (refs.authModalKicker) {
    refs.authModalKicker.textContent = translate(nextMode === "signup" ? "auth.modal.signupKicker" : "auth.modal.loginKicker");
  }

  if (refs.authModalTitle) {
    refs.authModalTitle.textContent = translate(nextMode === "signup" ? "auth.modal.signupTitle" : "auth.modal.loginTitle");
  }

  if (refs.authModalDescription) {
    refs.authModalDescription.textContent = translate(nextMode === "signup" ? "auth.modal.signupDescription" : "auth.modal.loginDescription");
  }
}

function syncAuthUI() {
  const authLabel = state.loggedIn ? translate("auth.logout") : translate("auth.login");

  refs.authToggles.forEach((toggle) => {
    toggle.textContent = authLabel;
  });

  if (refs.authCloseButtons[0]) {
    refs.authCloseButtons[0].setAttribute("aria-label", translate("auth.modal.close"));
  }

  refs.authTabs.forEach((tab) => {
    tab.textContent = translate(tab.dataset.authTab === "signup" ? "auth.modal.tabSignup" : "auth.modal.tabLogin");
  });

  refs.authSwitchButtons.forEach((button) => {
    button.textContent = translate(button.dataset.authSwitch === "signup" ? "auth.modal.tabSignup" : "auth.modal.tabLogin");
  });

  if (refs.authForgotPassword) {
    refs.authForgotPassword.textContent = translate("auth.modal.forgotPassword");
  }

  if (refs.authSubmitLogin) {
    refs.authSubmitLogin.textContent = translate("auth.modal.tabLogin");
  }

  if (refs.authSubmitSignup) {
    refs.authSubmitSignup.textContent = translate("auth.signup");
  }

  if (refs.authSwitchCopyLogin) {
    refs.authSwitchCopyLogin.textContent = translate("auth.modal.switchToSignupLead");
  }

  if (refs.authSwitchCopySignup) {
    refs.authSwitchCopySignup.textContent = translate("auth.modal.switchToLoginLead");
  }

  refs.authPasswordToggles.forEach((toggle) => {
    const input = toggle.closest(".auth-input-password")?.querySelector("input");
    const isVisible = input instanceof HTMLInputElement && input.type === "text";
    toggle.setAttribute("aria-label", translate(isVisible ? "auth.modal.hidePassword" : "auth.modal.showPassword"));
  });

  syncAccountUI();

  if (!state.loggedIn) {
    const currentMode = refs.authForms.find((form) => form.classList.contains("is-active"))?.dataset.authForm || "login";
    setAuthMode(currentMode);
  }
}

function openAuthModal(mode = "login") {
  if (!refs.authModal || state.loggedIn) {
    return;
  }

  clearTimeout(authModalTimer);
  setAuthMode(mode);
  refs.authModal.hidden = false;
  refs.body.classList.add("auth-modal-open");

  requestAnimationFrame(() => {
    refs.authModal.classList.add("is-open");
  });
}

function closeAuthModal() {
  if (!refs.authModal || refs.authModal.hidden) {
    return;
  }

  clearTimeout(authModalTimer);
  refs.authModal.classList.remove("is-open");
  refs.body.classList.remove("auth-modal-open");

  authModalTimer = window.setTimeout(() => {
    refs.authModal.hidden = true;
    refs.authForms.forEach((form) => form.reset());
    refs.authFeedbackElements.forEach((element) => {
      element.textContent = "";
      element.classList.remove("is-success");
    });
  }, 220);
}

function toggleAuthPassword(toggle) {
  const field = toggle.closest(".auth-input-password")?.querySelector("input");
  const icon = toggle.querySelector("i");

  if (!(field instanceof HTMLInputElement)) {
    return;
  }

  const revealPassword = field.type === "password";
  field.type = revealPassword ? "text" : "password";
  toggle.classList.toggle("is-active", revealPassword);
  toggle.setAttribute("aria-label", translate(revealPassword ? "auth.modal.hidePassword" : "auth.modal.showPassword"));

  if (icon) {
    icon.className = revealPassword ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
  }
}

function applyTranslations() {
  refs.html.lang = currentLanguage;
  document.title = translate("rules.meta.title");

  document.querySelectorAll("[data-i18n]").forEach((element) => {
    element.textContent = translate(element.dataset.i18n);
  });

  document.querySelectorAll("[data-i18n-html]").forEach((element) => {
    element.innerHTML = translate(element.dataset.i18nHtml);
  });

  document.querySelectorAll("[data-i18n-aria-label]").forEach((element) => {
    element.setAttribute("aria-label", translate(element.dataset.i18nAriaLabel));
  });

  if (refs.langCurrent) {
    refs.langCurrent.textContent = currentLanguage.toUpperCase();
  }

  if (refs.langToggle) {
    refs.langToggle.setAttribute("aria-label", translate("lang.toggle"));
  }

  syncNavToggleLabel();
  syncAuthUI();

  refs.langOptions.forEach((option) => {
    const optionLanguage = normalizeLanguage(option.dataset.langOption);
    option.textContent = translate(`lang.option.${optionLanguage}`);
    option.classList.toggle("is-active", optionLanguage === currentLanguage);
  });
}

refs.langToggle?.addEventListener("click", () => {
  setLanguageMenuOpen(!refs.langSwitcher?.classList.contains("is-open"));
});

refs.langOptions.forEach((option) => {
  option.addEventListener("click", () => {
    currentLanguage = normalizeLanguage(option.dataset.langOption);
    localStorage.setItem(STORAGE_KEY, currentLanguage);
    applyTranslations();
    setLanguageMenuOpen(false);
  });
});

refs.navToggle?.addEventListener("click", () => {
  setNavOpen(!refs.body.classList.contains("nav-open"));
});

refs.navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    if (window.innerWidth <= 920) {
      setNavOpen(false);
    }
  });
});

refs.authToggles.forEach((toggle) => {
  toggle.addEventListener("click", (event) => {
    event.preventDefault();
    if (state.loggedIn) {
      setLoggedIn(false);
    } else {
      openAuthModal("login");
    }
    setNavOpen(false);
  });
});

refs.authOpeners.forEach((opener) => {
  opener.addEventListener("click", (event) => {
    event.preventDefault();
    openAuthModal(opener.dataset.authOpen);
    setNavOpen(false);
  });
});

refs.authCloseButtons.forEach((button) => {
  button.addEventListener("click", () => {
    closeAuthModal();
  });
});

refs.authTabs.forEach((tab) => {
  tab.addEventListener("click", () => {
    setAuthMode(tab.dataset.authTab);
  });
});

refs.authSwitchButtons.forEach((button) => {
  button.addEventListener("click", () => {
    setAuthMode(button.dataset.authSwitch);
  });
});

refs.authPasswordToggles.forEach((toggle) => {
  toggle.addEventListener("click", () => {
    toggleAuthPassword(toggle);
  });
});

refs.accountToggle?.addEventListener("click", () => {
  setAccountMenuOpen(!refs.accountSwitcher?.classList.contains("is-open"));
});

refs.roleOptions.forEach((option) => {
  option.addEventListener("click", () => {
    if (!state.loggedIn || option.disabled) {
      return;
    }

    setRole(option.dataset.roleOption);
    closeAccountMenu();
  });
});

refs.authForms.forEach((form) => {
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const feedback = form.querySelector("[data-auth-feedback]");
    if (feedback) {
      feedback.textContent = translate("auth.modal.feedback.backendPending");
    }
  });
});

document.addEventListener("click", (event) => {
  if (refs.langSwitcher && !refs.langSwitcher.contains(event.target)) {
    setLanguageMenuOpen(false);
  }

  if (refs.accountSwitcher && !refs.accountSwitcher.contains(event.target)) {
    closeAccountMenu();
  }
});

document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    closeAuthModal();
    closeAccountMenu();
    setNavOpen(false);
    setLanguageMenuOpen(false);
  }
});

applyTranslations();
setLoggedIn(state.loggedIn);
setNavOpen(false);
setLanguageMenuOpen(false);

const revealItems = document.querySelectorAll(".rule-card, .highlight-card, .illustration-card");

const observer = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        observer.unobserve(entry.target);
      }
    });
  },
  {
    threshold: 0.18,
  }
);

revealItems.forEach((item) => {
  item.classList.add("reveal");
  observer.observe(item);
});

const scoreBadge = document.querySelector("[data-target]");

if (scoreBadge) {
  const numberNode = scoreBadge.querySelector(".score-number");
  const target = Number(scoreBadge.dataset.target);
  let current = 0;

  const tick = () => {
    current += 1;
    numberNode.textContent = String(current);

    if (current < target) {
      window.setTimeout(tick, 80);
    }
  };

  const scoreObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          tick();
          scoreObserver.disconnect();
        }
      });
    },
    {
      threshold: 0.5,
    }
  );

  scoreObserver.observe(scoreBadge);
}
