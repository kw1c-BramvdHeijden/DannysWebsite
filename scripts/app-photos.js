export function createPhotosModule({
    refs,
    state,
    t,
    localeMap,
    getLocalizedText,
    sanitizeFilename,
    generateRecordId,
    savePhotos
}) {
    let uploadModalTimer = 0;

    function getPhotosApiUrl() {
        const script = document.querySelector("script[src$='scripts/index.js']");
        return script ? new URL("../api/photos.php", script.src).toString() : "api/photos.php";
    }

    async function persistPhoto(photo) {
        const response = await fetch(getPhotosApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "create",
                photo
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || !result.photo) {
            throw new Error(result.error || "Foto kon niet worden opgeslagen.");
        }

        return result.photo;
    }

    async function deletePersistedPhoto(photoId) {
        const response = await fetch(getPhotosApiUrl(), {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                action: "delete",
                id: photoId
            })
        });

        const result = await response.json().catch(() => ({}));
        if (!response.ok || result.deleted !== true) {
            throw new Error(result.error || "Foto kon niet worden verwijderd.");
        }
    }

    function canDeletePhoto(photo) {
        if (!state.loggedIn) {
            return false;
        }

        if (state.role === "admin") {
            return true;
        }

        return Boolean(photo.ownerId) && photo.ownerId === state.user?.id;
    }

    function canEditPhoto(photo) {
        return state.loggedIn && Boolean(photo.ownerId) && photo.ownerId === state.user?.id;
    }

    function buildPhotoCollection() {
        const limit = Number(refs.photoHub?.dataset.photoLimit || 0);
        const photos = state.photos
            .slice()
            .sort((left, right) => right.createdAt - left.createdAt);

        return limit > 0 ? photos.slice(0, limit) : photos;
    }

    function formatPhotoTime(createdAt) {
        const photoDate = new Date(createdAt);
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        const photoDay = new Date(photoDate.getFullYear(), photoDate.getMonth(), photoDate.getDate());

        if (photoDay.getTime() === today.getTime()) {
            return t("photos.time.today");
        }

        if (photoDay.getTime() === yesterday.getTime()) {
            return t("photos.time.yesterday");
        }

        return new Intl.DateTimeFormat(localeMap[state.lang], {
            day: "numeric",
            month: "short",
            year: photoDate.getFullYear() === now.getFullYear() ? undefined : "numeric"
        }).format(photoDate);
    }

    function createPhotoCard(photo) {
        const card = document.createElement("article");
        card.className = "photo-card";
        const isPublicGallery = refs.photoHub?.hasAttribute("data-photo-public") === true;

        if (!isPublicGallery && (canEditPhoto(photo) || canDeletePhoto(photo))) {
            const actions = document.createElement("div");
            actions.className = "photo-card-actions";

            if (canEditPhoto(photo)) {
                const editButton = document.createElement("button");
                editButton.type = "button";
                editButton.className = "photo-action-button photo-edit-button";
                editButton.dataset.photoEdit = String(photo.id);
                editButton.setAttribute("aria-label", t("photos.edit"));
                editButton.innerHTML = `<i class="fa-solid fa-pen"></i><span>${t("photos.edit")}</span>`;
                actions.appendChild(editButton);
            }

            if (canDeletePhoto(photo)) {
                const deleteButton = document.createElement("button");
                deleteButton.type = "button";
                deleteButton.className = "photo-delete-button";
                deleteButton.dataset.photoDelete = String(photo.id);
                deleteButton.setAttribute("aria-label", t("photos.delete"));
                deleteButton.innerHTML = `<i class="fa-solid fa-trash"></i><span>${t("photos.delete")}</span>`;
                actions.appendChild(deleteButton);
            }

            card.appendChild(actions);
        }

        const image = document.createElement("img");
        image.className = "photo-card-image";
        image.src = photo.image;
        image.alt = getLocalizedText(photo.title, state.lang) || t("upload.previewAlt");

        const copy = document.createElement("div");
        copy.className = "photo-card-copy";

        const meta = document.createElement("div");
        meta.className = "photo-card-meta";

        const author = document.createElement("span");
        author.className = "photo-card-author";
        author.textContent = photo.author || t("photos.authorUnknown");

        const time = document.createElement("span");
        time.className = "photo-card-time";
        time.textContent = formatPhotoTime(photo.createdAt);

        const title = document.createElement("h3");
        title.className = "photo-card-title";
        title.textContent = getLocalizedText(photo.title, state.lang) || "";

        meta.append(author, time);
        copy.append(meta, title);

        const descriptionText = getLocalizedText(photo.description, state.lang);
        if (descriptionText) {
            const description = document.createElement("p");
            description.className = "photo-card-description";
            description.textContent = descriptionText;
            copy.appendChild(description);
        }

        card.append(image, copy);

        return card;
    }

    function renderPhotos() {
        if (!refs.photoGrid) {
            return;
        }

        const isPublicGallery = refs.photoHub?.hasAttribute("data-photo-public") === true;
        const canViewGallery = state.loggedIn || isPublicGallery;

        if (refs.photoLocked) {
            refs.photoLocked.hidden = canViewGallery;
        }

        if (refs.photoFeed) {
            refs.photoFeed.hidden = !canViewGallery;
        }

        refs.photoGrid.innerHTML = "";

        if (!canViewGallery) {
            return;
        }

        const photos = buildPhotoCollection();
        if (photos.length === 0) {
            const emptyState = document.createElement("div");
            emptyState.className = "photo-empty";

            const message = document.createElement("p");
            message.textContent = t("photos.empty");

            emptyState.appendChild(message);
            refs.photoGrid.appendChild(emptyState);
            return;
        }

        photos.forEach((photo) => {
            refs.photoGrid.appendChild(createPhotoCard(photo));
        });
    }

    function getStoredPhotoById(photoId) {
        return state.photos.find((photo) => String(photo.id) === String(photoId)) || null;
    }

    function syncUploadModalUI() {
        const isEditing = Boolean(state.pendingPhotoId);

        if (refs.uploadKickerText) {
            refs.uploadKickerText.textContent = t(isEditing ? "upload.editKicker" : "upload.kicker");
        }

        if (refs.uploadTitleText) {
            refs.uploadTitleText.textContent = t(isEditing ? "upload.editTitle" : "upload.title");
        }

        if (refs.uploadDescriptionText) {
            refs.uploadDescriptionText.textContent = t(isEditing ? "upload.editDescription" : "upload.description");
        }

        if (refs.uploadSubmitLabel) {
            refs.uploadSubmitLabel.textContent = t(isEditing ? "upload.saveChanges" : "upload.submit");
        }

        if (refs.uploadChangeButton) {
            refs.uploadChangeButton.hidden = isEditing;
        }
    }

    function setPendingUploadImage(imageData, fileName) {
        const previousSuggestedTitle = sanitizeFilename(state.pendingUpload?.fileName || "");
        const nextSuggestedTitle = sanitizeFilename(fileName);
        const currentTitle = refs.uploadTitleInput?.value.trim() || "";

        state.pendingUpload = {
            ...(state.pendingUpload || {}),
            image: imageData,
            fileName
        };

        if (refs.uploadPreview) {
            refs.uploadPreview.src = imageData;
            refs.uploadPreview.alt = currentTitle || t("upload.previewAlt");
        }

        if (refs.uploadTitleInput && (!currentTitle || currentTitle === previousSuggestedTitle)) {
            refs.uploadTitleInput.value = nextSuggestedTitle;
        }
    }

    function resetPendingUpload() {
        state.pendingUpload = null;
        state.pendingPhotoId = null;

        if (refs.uploadPreview) {
            refs.uploadPreview.src = "";
            refs.uploadPreview.alt = "";
        }

        if (refs.uploadForm) {
            refs.uploadForm.reset();
        }

        if (refs.photoInput) {
            refs.photoInput.value = "";
        }

        syncUploadModalUI();
    }

    function openUploadModal(imageData, fileName) {
        if (!refs.uploadModal || !refs.uploadPreview || !refs.uploadTitleInput || !refs.uploadDescriptionInput) {
            return;
        }

        clearTimeout(uploadModalTimer);

        state.pendingPhotoId = null;
        state.pendingUpload = null;
        setPendingUploadImage(imageData, fileName);
        refs.uploadDescriptionInput.value = "";
        syncUploadModalUI();

        refs.uploadModal.hidden = false;
        refs.body.classList.add("upload-modal-open");

        requestAnimationFrame(() => {
            refs.uploadModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.uploadModal && !refs.uploadModal.hidden) {
                refs.uploadTitleInput.focus();
                refs.uploadTitleInput.select();
            }
        }, 120);
    }

    function openPhotoEditModal(photoId) {
        const photo = getStoredPhotoById(photoId);
        if (!photo || !canEditPhoto(photo) || !refs.uploadModal || !refs.uploadPreview || !refs.uploadTitleInput || !refs.uploadDescriptionInput) {
            return;
        }

        clearTimeout(uploadModalTimer);

        state.pendingPhotoId = String(photo.id);
        state.pendingUpload = {
            image: photo.image,
            fileName: ""
        };

        refs.uploadPreview.src = photo.image;
        refs.uploadPreview.alt = getLocalizedText(photo.title, state.lang) || t("upload.previewAlt");
        refs.uploadTitleInput.value = getLocalizedText(photo.title, state.lang);
        refs.uploadDescriptionInput.value = getLocalizedText(photo.description, state.lang);
        syncUploadModalUI();

        refs.uploadModal.hidden = false;
        refs.body.classList.add("upload-modal-open");

        requestAnimationFrame(() => {
            refs.uploadModal.classList.add("is-open");
        });

        window.setTimeout(() => {
            if (refs.uploadModal && !refs.uploadModal.hidden) {
                refs.uploadTitleInput.focus();
                refs.uploadTitleInput.select();
            }
        }, 120);
    }

    function closeUploadModal() {
        if (!refs.uploadModal || refs.uploadModal.hidden) {
            resetPendingUpload();
            return;
        }

        clearTimeout(uploadModalTimer);
        refs.uploadModal.classList.remove("is-open");
        refs.body.classList.remove("upload-modal-open");

        uploadModalTimer = window.setTimeout(() => {
            refs.uploadModal.hidden = true;
            resetPendingUpload();
        }, 220);
    }

    async function publishPendingPhoto(event) {
        event.preventDefault();

        if (!state.pendingUpload || !state.loggedIn || !state.user) {
            closeUploadModal();
            return;
        }

        const title = refs.uploadTitleInput?.value.trim() || "";
        if (!title) {
            refs.uploadTitleInput?.focus();
            return;
        }

        const description = refs.uploadDescriptionInput?.value.trim() || "";

        if (state.pendingPhotoId) {
            state.photos = state.photos.map((photo) => (
                String(photo.id) === String(state.pendingPhotoId)
                    ? {
                        ...photo,
                        title,
                        description,
                        image: state.pendingUpload.image
                    }
                    : photo
            ));
        } else {
            const photo = {
                id: generateRecordId(),
                author: state.user.name,
                ownerId: state.user.id,
                title,
                description,
                createdAt: Date.now(),
                image: state.pendingUpload.image
            };

            state.photos.unshift(photo);
            savePhotos(state.photos);
            renderPhotos();
            closeUploadModal();

            try {
                const savedPhoto = await persistPhoto(photo);
                state.photos = state.photos.map((entry) => (
                    String(entry.id) === String(photo.id) ? savedPhoto : entry
                ));
                savePhotos(state.photos);
                renderPhotos();
            } catch (error) {
                window.alert(error.message);
            }
            return;
        }

        savePhotos(state.photos);
        renderPhotos();
        closeUploadModal();
    }

    async function deletePhoto(photoId) {
        const photo = getStoredPhotoById(photoId);
        if (!photo || !canDeletePhoto(photo)) {
            return;
        }

        if (!window.confirm(t("photos.deleteConfirm"))) {
            return;
        }

        try {
            await deletePersistedPhoto(photoId);
            state.photos = state.photos.filter((entry) => String(entry.id) !== String(photoId));
            savePhotos(state.photos);
            renderPhotos();
        } catch (error) {
            window.alert(error.message);
        }
    }

    return {
        renderPhotos,
        syncUploadModalUI,
        setPendingUploadImage,
        openUploadModal,
        openPhotoEditModal,
        closeUploadModal,
        publishPendingPhoto,
        deletePhoto
    };
}
