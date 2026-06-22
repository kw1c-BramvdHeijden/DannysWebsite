export function bindEvents({
    refs,
    state,
    photos,
    competitions,
    teams,
    leaderboard,
    ui,
    t
}) {
    refs.navToggle?.addEventListener("click", () => {
        ui.setNavOpen(!refs.body.classList.contains("nav-open"));
    });

    refs.navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            const href = link.getAttribute("href") || "";
            if (href.indexOf("competities") !== -1) {
                teams.preloadUsers();
            }

            const targetId = link.getAttribute("href")?.replace("#", "");
            if (targetId) {
                ui.setCurrentNavLink(targetId);
            }

            if (window.innerWidth <= 920) {
                ui.closeMobileMenu();
            }
        });
    });

    refs.authToggles.forEach((toggle) => {
        toggle.addEventListener("click", (event) => {
            event.preventDefault();

            if (state.loggedIn) {
                ui.setLoggedIn(false);
            } else {
                ui.openAuthModal("login");
            }

            if (window.innerWidth <= 920) {
                ui.closeMobileMenu();
            }
        });
    });

    refs.authOpeners.forEach((opener) => {
        opener.addEventListener("click", (event) => {
            event.preventDefault();

            if (state.loggedIn) {
                return;
            }

            ui.openAuthModal(opener.dataset.authOpen);

            if (window.innerWidth <= 920) {
                ui.closeMobileMenu();
            }
        });
    });

    refs.lockedLoginButton?.addEventListener("click", () => {
        if (!state.loggedIn) {
            ui.openAuthModal("login");
        }
    });

    refs.authTabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            ui.setAuthMode(tab.dataset.authTab);
        });
    });

    refs.authSwitchButtons.forEach((button) => {
        button.addEventListener("click", () => {
            ui.setAuthMode(button.dataset.authSwitch);
        });
    });

    refs.authCloseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            ui.closeAuthModal();
        });
    });

    refs.authPasswordToggles.forEach((toggle) => {
        const input = toggle.closest(".auth-input-password")?.querySelector("input");
        if (input instanceof HTMLInputElement) {
            input.dataset.authInputOriginalType = input.type;
        }

        toggle.addEventListener("click", () => {
            ui.toggleAuthPassword(toggle);
        });
    });

    refs.authForms.forEach((form) => {
        form.addEventListener("submit", (event) => {
            ui.handleAuthFormSubmit(form, event);
        });
    });

    refs.langToggle?.addEventListener("click", () => {
        ui.setLanguageMenuOpen(!refs.langSwitcher?.classList.contains("is-open"));
    });

    refs.accountToggle?.addEventListener("click", () => {
        ui.setAccountMenuOpen(!refs.accountSwitcher?.classList.contains("is-open"));
    });

    refs.roleOptions.forEach((option) => {
        option.addEventListener("click", () => {
            if (!state.loggedIn || option.disabled) {
                return;
            }

            ui.setRole(option.dataset.roleOption);
            ui.closeAccountMenu();
        });
    });

    refs.langOptions.forEach((option) => {
        option.addEventListener("click", () => {
            ui.setLanguage(option.dataset.langOption);
            ui.closeLanguageMenu();
        });
    });

    refs.uploadTriggers.forEach((trigger) => {
        trigger.addEventListener("click", () => {
            if (!state.loggedIn || !refs.photoInput) {
                return;
            }

            refs.photoInput.value = "";
            refs.photoInput.click();
        });
    });

    refs.uploadChangeButton?.addEventListener("click", () => {
        if (!refs.photoInput) {
            return;
        }

        refs.photoInput.value = "";
        refs.photoInput.click();
    });

    refs.photoInput?.addEventListener("change", (event) => {
        const input = event.target;
        const [file] = input instanceof HTMLInputElement ? input.files || [] : [];

        if (!file || !state.loggedIn) {
            return;
        }

        const reader = new FileReader();
        reader.addEventListener("load", () => {
            const result = String(reader.result);

            if (refs.uploadModal && !refs.uploadModal.hidden && !state.pendingPhotoId) {
                photos.setPendingUploadImage(result, file.name);
                return;
            }

            photos.openUploadModal(result, file.name);
        });
        reader.readAsDataURL(file);
    });

    refs.uploadTitleInput?.addEventListener("input", () => {
        if (refs.uploadPreview) {
            refs.uploadPreview.alt = refs.uploadTitleInput.value.trim() || t("upload.previewAlt");
        }
    });

    refs.uploadCancelButtons.forEach((button) => {
        button.addEventListener("click", () => {
            photos.closeUploadModal();
        });
    });

    refs.competitionCreateButton?.addEventListener("click", () => {
        competitions.openCompetitionModal();
    });

    refs.competitionRequestButtons.forEach((button) => {
        button.addEventListener("click", () => {
            competitions.openCompetitionRequestModal();
        });
    });

    refs.competitionCancelButtons.forEach((button) => {
        button.addEventListener("click", () => {
            competitions.closeCompetitionModal();
        });
    });

    refs.teamOpenButtons.forEach((button) => {
        button.addEventListener("click", () => {
            teams.openTeamModal();
        });
    });

    refs.teamCloseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            teams.closeTeamModal();
        });
    });

    refs.teamUserToggle?.addEventListener("click", () => {
        teams.toggleUserMenu();
    });

    refs.leaderboardOpenButton?.addEventListener("click", () => {
        leaderboard.openLeaderboardModal();
    });

    refs.leaderboardTeamFilter?.addEventListener("change", (event) => {
        const nextValue = event.target instanceof HTMLSelectElement ? event.target.value : "all";
        state.leaderboardTeamFilter = nextValue || "all";
        leaderboard.renderLeaderboard();
    });

    refs.leaderboardCloseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            leaderboard.closeLeaderboardModal();
        });
    });

    refs.uploadForm?.addEventListener("submit", (event) => {
        photos.publishPendingPhoto(event);
    });

    refs.competitionForm?.addEventListener("submit", (event) => {
        competitions.saveCompetition(event);
    });

    refs.teamForm?.addEventListener("submit", (event) => {
        teams.submitTeam(event);
    });

    refs.competitionGrid?.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const editButton = target.closest("[data-competition-edit]");
        if (editButton) {
            competitions.openCompetitionModal(editButton.getAttribute("data-competition-edit"));
            return;
        }

        const deleteButton = target.closest("[data-competition-delete]");
        if (deleteButton) {
            competitions.deleteCompetition(deleteButton.getAttribute("data-competition-delete"));
        }
    });

    refs.competitionRequestsGrid?.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const acceptButton = target.closest("[data-competition-request-accept]");
        if (acceptButton) {
            competitions.acceptCompetitionRequest(acceptButton.getAttribute("data-competition-request-accept"));
            return;
        }

        const rejectButton = target.closest("[data-competition-request-reject]");
        if (rejectButton) {
            competitions.rejectCompetitionRequest(rejectButton.getAttribute("data-competition-request-reject"));
        }
    });

    refs.photoGrid?.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        const editButton = target.closest("[data-photo-edit]");
        if (editButton) {
            photos.openPhotoEditModal(editButton.getAttribute("data-photo-edit"));
            return;
        }

        const deleteButton = target.closest("[data-photo-delete]");
        if (!deleteButton) {
            return;
        }

        photos.deletePhoto(deleteButton.getAttribute("data-photo-delete"));
    });

    document.addEventListener("click", (event) => {
        const target = event.target;

        if (!(target instanceof Node)) {
            return;
        }

        if (refs.langSwitcher && !refs.langSwitcher.contains(target)) {
            ui.closeLanguageMenu();
        }

        if (refs.accountSwitcher && !refs.accountSwitcher.contains(target)) {
            ui.closeAccountMenu();
        }

        if (refs.authModal && !refs.authModal.hidden) {
            const dialog = refs.authModal.querySelector(".auth-modal-dialog");
            if (dialog && !dialog.contains(target) && target instanceof Element && target.hasAttribute("data-auth-close")) {
                ui.closeAuthModal();
            }
        }

        if (refs.teamModal && !refs.teamModal.hidden && refs.teamUserMenu && refs.teamUserToggle) {
            const clickedInMenu = refs.teamUserMenu.contains(target);
            const clickedToggle = refs.teamUserToggle.contains(target);
            if (!clickedInMenu && !clickedToggle) {
                teams.closeUserMenu();
            }
        }

        if (window.innerWidth <= 920 && refs.siteHeader && !refs.siteHeader.contains(target) && refs.body.classList.contains("nav-open")) {
            ui.closeMobileMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        if (refs.leaderboardModal && !refs.leaderboardModal.hidden) {
            leaderboard.closeLeaderboardModal();
            return;
        }

        if (refs.competitionModal && !refs.competitionModal.hidden) {
            competitions.closeCompetitionModal();
            return;
        }

        if (refs.teamModal && !refs.teamModal.hidden) {
            teams.closeTeamModal();
            return;
        }

        if (refs.authModal && !refs.authModal.hidden) {
            ui.closeAuthModal();
            return;
        }

        if (refs.uploadModal && !refs.uploadModal.hidden) {
            photos.closeUploadModal();
            return;
        }

        if (refs.langSwitcher?.classList.contains("is-open")) {
            ui.closeLanguageMenu();
        }

        if (refs.accountSwitcher?.classList.contains("is-open")) {
            ui.closeAccountMenu();
        }

        if (refs.body.classList.contains("nav-open")) {
            ui.closeMobileMenu();
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 920) {
            ui.closeMobileMenu();
        }

        ui.queueScrollSpyUpdate();
    });

    window.addEventListener("scroll", ui.queueScrollSpyUpdate, { passive: true });
    window.addEventListener("load", ui.updateActiveNavLink);
    window.addEventListener("hashchange", ui.queueScrollSpyUpdate);
}
