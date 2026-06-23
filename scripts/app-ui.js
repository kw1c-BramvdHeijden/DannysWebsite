export function createUiModule({
    refs,
    state,
    t,
    normalizeLanguage,
    normalizeRole,
    saveAuth,
    saveLanguage,
    renderPhotos,
    renderCompetitions,
    renderLeaderboard,
    syncUploadModalUI,
    syncCompetitionFormUI,
    closeUploadModal,
    closeCompetitionModal,
    closeTeamModal,
    syncTeamButtons,
    closeLeaderboardModal,
    canManageCompetitions,
    onAuthStateChanged = () => {}
}) {
    let languageMenuTimer = 0;
    let accountMenuTimer = 0;
    let authModalTimer = 0;
    let scrollSpyFrame = 0;

    const navSections = refs.navLinks
        .map((link) => {
            const targetId = link.getAttribute("href")?.replace("#", "");
            if (!targetId) {
                return null;
            }

            const section = document.getElementById(targetId);
            if (!section) {
                return null;
            }

            return {
                id: targetId,
                link,
                section
            };
        })
        .filter(Boolean);

    function getAuthApiUrl() {
        const script = document.querySelector("script[type='module'][src*='scripts/']");
        return script ? new URL("../api/auth.php", script.src).toString() : "api/auth.php";
    }

    async function requestAuth(payload) {
        const response = await fetch(getAuthApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.user) {
            throw new Error(result.error || t("auth.modal.feedback.loginInvalid"));
        }

        return result;
    }

    function getAccountName() {
        return state.user?.name || t("account.name");
    }

    function getAccountInitial() {
        return state.user?.initials || getAccountName().charAt(0).toUpperCase() || "A";
    }

    function setCurrentNavLink(targetId) {
        refs.navLinks.forEach((link) => {
            const isCurrent = link.getAttribute("href") === `#${targetId}`;
            link.classList.toggle("is-current", isCurrent);
        });
    }

    function updateActiveNavLink() {
        if (navSections.length === 0) {
            return;
        }

        const headerOffset = (refs.siteHeader?.offsetHeight || 0) + 120;
        const scrollMarker = window.scrollY + headerOffset;
        let activeSection = navSections[0];

        navSections.forEach((entry) => {
            if (entry.section.offsetTop <= scrollMarker) {
                activeSection = entry;
            }
        });

        const scrollBottom = window.scrollY + window.innerHeight;
        if (scrollBottom >= document.documentElement.scrollHeight - 4) {
            activeSection = navSections[navSections.length - 1];
        }

        setCurrentNavLink(activeSection.id);
    }

    function queueScrollSpyUpdate() {
        if (scrollSpyFrame) {
            return;
        }

        scrollSpyFrame = window.requestAnimationFrame(() => {
            scrollSpyFrame = 0;
            updateActiveNavLink();
        });
    }

    function syncNavToggleLabel() {
        if (!refs.navToggle) {
            return;
        }

        refs.navToggle.setAttribute("aria-label", refs.body.classList.contains("nav-open") ? t("nav.closeMenu") : t("nav.menu"));
    }

    function setNavOpen(isOpen) {
        refs.body.classList.toggle("nav-open", isOpen);

        if (refs.navToggle) {
            refs.navToggle.setAttribute("aria-expanded", String(isOpen));
        }

        if (!isOpen) {
            closeLanguageMenu();
            closeAccountMenu();
        }

        syncNavToggleLabel();
    }

    function closeMobileMenu() {
        setNavOpen(false);
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

    function closeLanguageMenu() {
        setLanguageMenuOpen(false);
    }

    function setAccountMenuOpen(isOpen) {
        if (!refs.accountSwitcher || !refs.accountToggle || !refs.accountMenu) {
            return;
        }

        if (isOpen && !state.loggedIn) {
            return;
        }

        clearTimeout(accountMenuTimer);
        refs.accountToggle.setAttribute("aria-expanded", String(isOpen));

        if (isOpen) {
            closeLanguageMenu();
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

    function syncLanguageUI() {
        if (refs.langCurrent) {
            refs.langCurrent.textContent = state.lang.toUpperCase();
        }

        if (refs.langToggle) {
            refs.langToggle.setAttribute("aria-label", t("lang.toggle"));
        }

        refs.langOptions.forEach((option) => {
            const optionLang = normalizeLanguage(option.dataset.langOption);
            option.textContent = t(`lang.option.${optionLang}`);
            option.classList.toggle("is-active", optionLang === state.lang);
        });
    }

    function syncAccountUI() {
        const roleKey = state.role === "admin" ? "account.roleAdmin" : "account.rolePlayer";

        const signupCtas = refs.signupCtas || (refs.signupCta ? [refs.signupCta] : []);
        signupCtas.forEach((cta) => {
            cta.hidden = state.loggedIn;
        });

        if (refs.accountSwitcher) {
            refs.accountSwitcher.hidden = !state.loggedIn;
        }

        if (refs.accountToggle) {
            refs.accountToggle.setAttribute("aria-label", t("account.menuLabel"));
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
            refs.accountRole.textContent = t(roleKey);
        }

        if (refs.accountAvatar) {
            refs.accountAvatar.textContent = getAccountInitial();
        }

        refs.roleOptions.forEach((option) => {
            option.classList.toggle("is-active", normalizeRole(option.dataset.roleOption) === state.role);
            option.disabled = !state.loggedIn;
            option.setAttribute("aria-disabled", String(!state.loggedIn));
        });

        if (refs.adminIndicator) {
            const isPublicGallery = refs.photoHub?.hasAttribute("data-photo-public") === true;
            refs.adminIndicator.hidden = isPublicGallery || !(state.loggedIn && state.role === "admin");
        }
    }

    function syncCompetitionAdminUI() {
        const canManage = canManageCompetitions();

        if (refs.competitionAdminIndicator) {
            refs.competitionAdminIndicator.hidden = !canManage;
        }

        if (refs.competitionCreateButton) {
            refs.competitionCreateButton.hidden = !canManage;
        }

        refs.competitionRequestButtons.forEach((button) => {
            button.hidden = !state.loggedIn || canManage;
        });
    }

    function syncAuthUI() {
        const authLabel = state.loggedIn ? t("auth.logout") : t("auth.login");

        refs.authToggles.forEach((toggle) => {
            toggle.textContent = authLabel;
        });

        if (refs.lockedLoginButton) {
            refs.lockedLoginButton.textContent = t("photos.lockedButton");
        }

        refs.uploadTriggers.forEach((trigger) => {
            trigger.classList.toggle("is-disabled", !state.loggedIn);
            trigger.setAttribute("aria-disabled", String(!state.loggedIn));
        });

        syncAccountUI();
        syncCompetitionAdminUI();
        syncTeamButtons();
    }

    function setAuthMode(mode) {
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
            refs.authModalKicker.textContent = t(nextMode === "signup" ? "auth.modal.signupKicker" : "auth.modal.loginKicker");
        }

        if (refs.authModalTitle) {
            refs.authModalTitle.textContent = t(nextMode === "signup" ? "auth.modal.signupTitle" : "auth.modal.loginTitle");
        }

        if (refs.authModalDescription) {
            refs.authModalDescription.textContent = t(nextMode === "signup" ? "auth.modal.signupDescription" : "auth.modal.loginDescription");
        }
    }

    function syncAuthModalUI() {
        const currentMode = refs.authForms.find((form) => form.classList.contains("is-active"))?.dataset.authForm === "signup" ? "signup" : "login";

        if (refs.authCloseButtons[0]) {
            refs.authCloseButtons[0].setAttribute("aria-label", t("auth.modal.close"));
        }

        refs.authTabs.forEach((tab) => {
            tab.textContent = t(tab.dataset.authTab === "signup" ? "auth.modal.tabSignup" : "auth.modal.tabLogin");
        });

        refs.authSwitchButtons.forEach((button) => {
            button.textContent = t(button.dataset.authSwitch === "signup" ? "auth.modal.tabSignup" : "auth.modal.tabLogin");
        });

        if (refs.authForgotPassword) {
            refs.authForgotPassword.textContent = t("auth.modal.forgotPassword");
        }

        if (refs.authSubmitLogin) {
            refs.authSubmitLogin.textContent = t("auth.modal.tabLogin");
        }

        if (refs.authSubmitSignup) {
            refs.authSubmitSignup.textContent = t("auth.signup");
        }

        if (refs.authSwitchCopyLogin) {
            refs.authSwitchCopyLogin.textContent = t("auth.modal.switchToSignupLead");
        }

        if (refs.authSwitchCopySignup) {
            refs.authSwitchCopySignup.textContent = t("auth.modal.switchToLoginLead");
        }

        if (refs.authLoginIdentityInput) {
            refs.authLoginIdentityInput.placeholder = t("auth.modal.identityPlaceholder");
        }

        if (refs.authLoginPasswordInput) {
            refs.authLoginPasswordInput.placeholder = t("auth.modal.passwordPlaceholder");
        }

        if (refs.authSignupNameInput) {
            refs.authSignupNameInput.placeholder = t("auth.modal.namePlaceholder");
        }

        if (refs.authSignupEmailInput) {
            refs.authSignupEmailInput.placeholder = t("auth.modal.emailPlaceholder");
        }

        if (refs.authSignupPasswordInput) {
            refs.authSignupPasswordInput.placeholder = t("auth.modal.passwordPlaceholder");
        }

        if (refs.authSignupPasswordConfirmInput) {
            refs.authSignupPasswordConfirmInput.placeholder = t("auth.modal.passwordConfirmPlaceholder");
        }

        refs.authPasswordToggles.forEach((toggle) => {
            const isActive = toggle.classList.contains("is-active");
            toggle.setAttribute("aria-label", t(isActive ? "auth.modal.hidePassword" : "auth.modal.showPassword"));
        });

        setAuthMode(currentMode);
    }

    function applyTranslations() {
        refs.html.lang = state.lang;
        document.title = t("meta.title");

        document.querySelectorAll("[data-i18n]").forEach((element) => {
            element.textContent = t(element.dataset.i18n);
        });

        document.querySelectorAll("[data-i18n-html]").forEach((element) => {
            element.innerHTML = t(element.dataset.i18nHtml);
        });

        document.querySelectorAll("[data-i18n-placeholder]").forEach((element) => {
            element.placeholder = t(element.dataset.i18nPlaceholder);
        });

        document.querySelectorAll("[data-i18n-aria-label]").forEach((element) => {
            element.setAttribute("aria-label", t(element.dataset.i18nAriaLabel));
        });

        document.querySelectorAll("[data-i18n-alt]").forEach((element) => {
            element.setAttribute("alt", t(element.dataset.i18nAlt));
        });

        syncNavToggleLabel();
        syncLanguageUI();
        syncAuthUI();
        syncAuthModalUI();
        syncUploadModalUI();
        syncCompetitionFormUI();
        renderLeaderboard();
        renderCompetitions();
        renderPhotos();

        if (refs.uploadPreview && state.pendingUpload && !refs.uploadTitleInput?.value.trim()) {
            refs.uploadPreview.alt = t("upload.previewAlt");
        }
    }

    function setLanguage(language) {
        state.lang = normalizeLanguage(language);
        saveLanguage(state.lang);
        applyTranslations();
    }

    async function setRole(role) {
        const nextRole = normalizeRole(role);

        if (nextRole === "player") {
            state.role = "player";
            closeCompetitionModal();
            if (state.loggedIn && state.user) {
                saveAuth({
                    loggedIn: true,
                    role: state.role,
                    accountRole: state.accountRole || state.role,
                    user: state.user
                });
            }
            syncAccountUI();
            syncCompetitionAdminUI();
            renderCompetitions();
            renderPhotos();
            closeAccountMenu();
            return;
        }

        if (nextRole === "admin" && state.accountRole !== "admin") {
            window.alert("U bent niet gemachtigd als admin");
            closeAccountMenu();
            return;
        }

        state.role = nextRole;

        if (state.role !== "admin") {
            closeCompetitionModal();
        }

        if (state.loggedIn && state.user) {
            saveAuth({
                loggedIn: true,
                role: state.role,
                accountRole: state.accountRole || state.role,
                user: state.user
            });
        }

        syncAccountUI();
        syncCompetitionAdminUI();
        renderCompetitions();
        renderPhotos();
    }

    function setLoggedIn(loggedIn) {
        state.loggedIn = Boolean(loggedIn);
        refs.body.classList.toggle("is-logged-in", state.loggedIn);

        if (!state.loggedIn) {
            state.role = "player";
            state.accountRole = "player";
            state.user = null;
            saveAuth(null);
            closeAuthModal();
            closeUploadModal();
            closeCompetitionModal();
            closeTeamModal();
            closeLeaderboardModal();
            closeAccountMenu();
        }

        syncAuthUI();
        renderLeaderboard();
        renderCompetitions();
        renderPhotos();
        onAuthStateChanged(state.loggedIn);
    }

    function setAuthenticatedUser(user, role = "player") {
        const displayName = user?.name?.trim() || t("account.name");

        state.role = normalizeRole(role);
        state.accountRole = normalizeRole(role);
        state.user = {
            id: user?.id || displayName.toLowerCase().replace(/\s+/g, "-"),
            name: displayName,
            initials: user?.initials || displayName.charAt(0).toUpperCase() || "A"
        };

        saveAuth({
            loggedIn: true,
            role: state.role,
            accountRole: state.accountRole,
            user: state.user
        });

        setLoggedIn(true);
        closeAuthModal();
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

        window.setTimeout(() => {
            if (!refs.authModal || refs.authModal.hidden) {
                return;
            }

            const activeForm = refs.authForms.find((form) => form.dataset.authForm === (mode === "signup" ? "signup" : "login"));
            const firstInput = activeForm?.querySelector("input");
            firstInput?.focus();
        }, 120);
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

            refs.authForms.forEach((form) => {
                form.reset();
            });

            refs.authFeedbackElements.forEach((element) => {
                element.textContent = "";
                element.classList.remove("is-success");
            });

            refs.authPasswordToggles.forEach((toggle) => {
                toggle.classList.remove("is-active");
                const icon = toggle.querySelector("i");
                if (icon) {
                    icon.className = "fa-regular fa-eye";
                }
            });

            refs.authForms.forEach((form) => {
                form.querySelectorAll("input[type='text'], input[type='email']").forEach((input) => {
                    input.value = input.value.trim();
                });

                form.querySelectorAll("input[type='text'], input[type='email'], input[type='password']").forEach((input) => {
                    if (input instanceof HTMLInputElement && input.dataset.authInputOriginalType) {
                        input.type = input.dataset.authInputOriginalType;
                    }
                });
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
        toggle.setAttribute("aria-label", t(revealPassword ? "auth.modal.hidePassword" : "auth.modal.showPassword"));

        if (icon) {
            icon.className = revealPassword ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
        }
    }

    async function handleAuthFormSubmit(form, event) {
        event.preventDefault();

        const mode = form.dataset.authForm === "signup" ? "signup" : "login";
        const feedback = refs.authFeedbackElements.find((element) => element.dataset.authFeedback === mode);
        const submitButton = form.querySelector("button[type='submit']");
        if (feedback) {
            feedback.textContent = "";
            feedback.classList.remove("is-success");
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        if (mode === "login") {
            const identityInput = form.querySelector("[data-auth-login-identity]");
            const passwordInput = form.querySelector("[data-auth-login-password]");
            const identity = identityInput instanceof HTMLInputElement ? identityInput.value.trim() : "";
            const password = passwordInput instanceof HTMLInputElement ? passwordInput.value.trim() : "";

            if (!identity || !password) {
                if (feedback) {
                    feedback.textContent = t("auth.modal.feedback.loginMissing");
                }
                if (submitButton) {
                    submitButton.disabled = false;
                }
                return;
            }

            try {
                const result = await requestAuth({
                    action: "login",
                    identity,
                    password
                });
                setAuthenticatedUser(result.user, result.role);
            } catch (error) {
                if (feedback) {
                    feedback.textContent = error.message;
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
            return;
        }

        const nameInput = form.querySelector("[data-auth-signup-name]");
        const emailInput = form.querySelector("[data-auth-signup-email]");
        const passwordInput = form.querySelector("[data-auth-signup-password]");
        const confirmInput = form.querySelector("[data-auth-signup-password-confirm]");
        const emailNotificationsInput = form.querySelector("[data-auth-signup-email-notifications]");
        const name = nameInput instanceof HTMLInputElement ? nameInput.value.trim() : "";
        const email = emailInput instanceof HTMLInputElement ? emailInput.value.trim() : "";
        const password = passwordInput instanceof HTMLInputElement ? passwordInput.value : "";
        const passwordConfirm = confirmInput instanceof HTMLInputElement ? confirmInput.value : "";
        const emailNotifications = emailNotificationsInput instanceof HTMLInputElement ? emailNotificationsInput.checked : false;

        if (!name || !email || !password || !passwordConfirm) {
            if (feedback) {
                feedback.textContent = t("auth.modal.feedback.signupMissing");
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
            return;
        }

        if (password.length < 6) {
            if (feedback) {
                feedback.textContent = t("auth.modal.feedback.passwordShort");
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
            return;
        }

        if (password !== passwordConfirm) {
            if (feedback) {
                feedback.textContent = t("auth.modal.feedback.passwordMismatch");
            }
            if (submitButton) {
                submitButton.disabled = false;
            }
            return;
        }

        try {
            const result = await requestAuth({
                action: "signup",
                name,
                email,
                password,
                emailNotifications
            });
            setAuthenticatedUser(result.user, result.role);
        } catch (error) {
            if (feedback) {
                feedback.textContent = error.message;
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }

    return {
        applyTranslations,
        setCurrentNavLink,
        updateActiveNavLink,
        queueScrollSpyUpdate,
        syncNavToggleLabel,
        setNavOpen,
        closeMobileMenu,
        setLanguageMenuOpen,
        closeLanguageMenu,
        setAccountMenuOpen,
        closeAccountMenu,
        setLanguage,
        setRole,
        setLoggedIn,
        setAuthMode,
        openAuthModal,
        closeAuthModal,
        toggleAuthPassword,
        handleAuthFormSubmit
    };
}
