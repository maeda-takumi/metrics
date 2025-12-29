// DOM読み込み後に実行
document.addEventListener("DOMContentLoaded", () => {
    console.log("scripts.js loaded!");

    const formatNumber = (value) => {
        if (value === null || value === undefined || value === "") {
            return "—";
        }
        try {
            return new Intl.NumberFormat("ja-JP").format(Number(value));
        } catch (e) {
            return String(value);
        }
    };
    const extractVideoId = (value) => {
        if (!value) return "";
        const str = String(value);
        const match = str.match(/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{11})/);
        if (match) return match[1];
        if (/^[A-Za-z0-9_-]{11}$/.test(str)) return str;
        return "";
    };

    const safeJson = async (response) => {
        try {
            return await response.json();
        } catch (e) {
            return null;
        }
    };

    const setStatus = (el, message, isError = false) => {
        if (!el) return;
        el.textContent = message;
        el.classList.toggle("error", Boolean(isError));
    };

    const buildVideoItem = (video, performerId) => {
        const videoId = extractVideoId(video.video_id || video.video_tag || "");
        const videoUrl = videoId ? `https://www.youtube.com/watch?v=${videoId}` : "";
        const thumbUrl = videoId ? `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg` : "";

        const li = document.createElement("li");
        li.className = "video-item";
        if (videoId) li.dataset.videoId = videoId;
        if (video.id) li.dataset.videoPrimaryId = video.id;

        const content = document.createElement("div");
        content.className = "video-content";

        const titleRow = document.createElement("div");
        titleRow.className = "video-title-row";
        const title = document.createElement("h4");
        title.textContent = video.video_tag || "動画タグなし";
        titleRow.appendChild(title);

        if (video.video_id) {
            const badge = document.createElement("span");
            badge.className = "badge-subtle";
            badge.textContent = `video_id: ${video.video_id}`;
            titleRow.appendChild(badge);
        }

        content.appendChild(titleRow);

        const linkP = document.createElement("p");
        linkP.className = "video-link";
        if (videoUrl) {
            const link = document.createElement("a");
            link.href = videoUrl;
            link.target = "_blank";
            link.rel = "noopener noreferrer";
            link.textContent = videoUrl;
            linkP.appendChild(link);
        } else {
            linkP.classList.add("muted");
            linkP.textContent = "YouTube の動画 ID が不明です";
        }
        content.appendChild(linkP);

        const metrics = document.createElement("dl");
        metrics.className = "video-metrics";

        const metricKeys = ["view_7h", "view_12h", "view_24h", "view_48h", "view_month"];
        metricKeys.forEach((key) => {
            const wrapper = document.createElement("div");
            const dt = document.createElement("dt");
            dt.textContent = key;
            const dd = document.createElement("dd");
            dd.textContent = formatNumber(video[key]);
            wrapper.appendChild(dt);
            wrapper.appendChild(dd);
            metrics.appendChild(wrapper);
        });

        const viewRealWrapper = document.createElement("div");
        const viewRealDt = document.createElement("dt");
        viewRealDt.textContent = "view_real";
        const viewRealDd = document.createElement("dd");
        viewRealDd.className = "video-view-real";
        if (videoId) {
            viewRealDd.dataset.videoViewReal = videoId;
        }
        viewRealDd.textContent = formatNumber(video.view_real);
        viewRealWrapper.appendChild(viewRealDt);
        viewRealWrapper.appendChild(viewRealDd);
        metrics.appendChild(viewRealWrapper);

        content.appendChild(metrics);

        li.appendChild(content);

        const viewAction = document.createElement("div");
        viewAction.className = "view-action";

        if (thumbUrl && videoUrl) {
            const thumbLink = document.createElement("a");
            thumbLink.className = "video-thumb";
            thumbLink.href = videoUrl;
            thumbLink.target = "_blank";
            thumbLink.rel = "noopener noreferrer";
            const img = document.createElement("img");
            img.src = thumbUrl;
            img.alt = video.video_tag || "YouTube サムネイル";
            img.loading = "lazy";
            thumbLink.appendChild(img);
            viewAction.appendChild(thumbLink);
        } else {
            const placeholder = document.createElement("div");
            placeholder.className = "placeholder-avatar";
            placeholder.textContent = "No Thumbnail";
            viewAction.appendChild(placeholder);
        }

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.className = "button danger";
        deleteButton.dataset.action = "delete-video";
        deleteButton.dataset.performerId = performerId;
        if (video.id) deleteButton.dataset.videoPrimaryId = video.id;
        if (videoId) deleteButton.dataset.videoId = videoId;
        deleteButton.textContent = "削除";
        viewAction.appendChild(deleteButton);

        li.appendChild(viewAction);

        return li;
    };

    const renderVideoList = (videos = [], performerId) => {
        if (!performerId) return;
        const list = document.querySelector(`[data-video-list][data-performer-id="${performerId}"]`);
        if (!list) return;
        const emptyState = document.querySelector("[data-video-empty]");
        list.innerHTML = "";

        if (!videos.length) {
            if (emptyState) {
                emptyState.style.display = "block";
            }
            return;
        }

        if (emptyState) {
            emptyState.remove();
        }

        videos.forEach((video) => {
            list.appendChild(buildVideoItem(video, performerId));
        });
    };

    const refreshVideoList = async (performerId, statusEl = null) => {
        if (!performerId) return;
        setStatus(statusEl, "一覧を更新しています…");
        try {
            const response = await fetch(`api/videos_list.php?performer_id=${encodeURIComponent(performerId)}`);
            const data = await safeJson(response);
            if (!response.ok) {
                const message = data?.error || `HTTP error ${response.status}`;
                setStatus(statusEl, message, true);
                return;
            }
            renderVideoList(data?.videos || [], performerId);
            setStatus(statusEl, "一覧を更新しました。");
        } catch (error) {
            console.error("動画一覧の更新に失敗しました", error);
            setStatus(statusEl, "一覧の更新に失敗しました。時間をおいて再度お試しください。", true);
        }
    };
    
    
    const viewCountForms = document.querySelectorAll(".view-count-form");

    viewCountForms.forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();
            const statusEl = form.parentElement.querySelector(".view-status");
            if (!statusEl) return;

            statusEl.textContent = "リクエストを送信中...";

            try {
                const response = await fetch(form.action || window.location.pathname, {
                    method: "POST",
                    body: new FormData(form),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error ${response.status}`);
                }

                const data = await response.json();
                if (data.status === "queued") {
                    const videoId = data.video_id || "";
                    statusEl.textContent = `動画ID ${videoId} のビュー取得を開始しました。`;
                } else if (data.message) {
                    statusEl.textContent = data.message;
                } else {
                    statusEl.textContent = "ビュー数取得の結果を受信できませんでした。";
                }
            } catch (error) {
                console.error("ビュー数取得リクエストに失敗しました", error);
                statusEl.textContent = "リクエストに失敗しました。時間をおいて再度お試しください。";
            }
        });
    });
    
    const modalToggles = document.querySelectorAll("[data-action=\"open-video-modal\"]");
    const modals = document.querySelectorAll("[data-modal]");

    const openModal = (modal) => {
        if (!modal) return;
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
    };

    const closeModal = (modal) => {
        if (!modal) return;
        modal.classList.remove("is-open");
        modal.setAttribute("aria-hidden", "true");
    };

    modalToggles.forEach((trigger) => {
        trigger.addEventListener("click", () => {
            const targetSelector = trigger.dataset.target;
            if (!targetSelector) return;
            const modal = document.querySelector(targetSelector);
            openModal(modal);
        });
    });

    modals.forEach((modal) => {
        modal.addEventListener("click", (event) => {
            const target = event.target;
            if (target instanceof Element && (target.hasAttribute("data-modal-close") || target.closest("[data-modal-close]"))) {
                closeModal(modal);
            }
        });
    });

    const videoCreateForm = document.querySelector("[data-video-create-form]");
    if (videoCreateForm) {
        videoCreateForm.addEventListener("submit", async (event) => {
            event.preventDefault();
            const performerId = videoCreateForm.dataset.performerId;
            const statusEl = videoCreateForm.querySelector("[data-video-form-status]");
            const modal = videoCreateForm.closest("[data-modal]");

            const payload = {
                performer_id: performerId ? Number(performerId) : null,
                video_tag: videoCreateForm.video_tag.value.trim(),
                video_id: videoCreateForm.video_id.value.trim(),
            };

            setStatus(statusEl, "送信しています…");

            try {
                const response = await fetch("api/videos_create.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(payload),
                });

                const data = await safeJson(response);

                if (!response.ok) {
                    const message = data?.error || `HTTP error ${response.status}`;
                    setStatus(statusEl, message, true);
                    return;
                }

                setStatus(statusEl, data?.message || "動画を追加しました。");
                videoCreateForm.reset();
                closeModal(modal);

                const listStatus = document.querySelector("[data-video-list-status]");
                refreshVideoList(payload.performer_id, listStatus);
            } catch (error) {
                console.error("動画の追加に失敗しました", error);
                setStatus(statusEl, "追加に失敗しました。時間をおいて再度お試しください。", true);
            }
        });
    }

    document.addEventListener("click", async (event) => {
        const target = event.target.closest("[data-action=\"delete-video\"]");
        if (!target) return;

        const performerId = target.dataset.performerId;
        const videoId = target.dataset.videoId || null;
        const primaryId = target.dataset.videoPrimaryId || null;
        const listStatus = document.querySelector("[data-video-list-status]");

        if (!performerId) {
            setStatus(listStatus, "performer_id が見つかりません。", true);
            return;
        }

        const confirmed = window.confirm("この動画を削除してもよろしいですか？");
        if (!confirmed) return;

        setStatus(listStatus, "削除しています…");

        try {
            const response = await fetch("api/videos_delete.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    performer_id: Number(performerId),
                    id: primaryId ? Number(primaryId) : null,
                    video_id: videoId,
                }),
            });

            const data = await safeJson(response);

            if (!response.ok) {
                const message = data?.error || `HTTP error ${response.status}`;
                setStatus(listStatus, message, true);
                return;
            }

            setStatus(listStatus, data?.message || "動画を削除しました。");

            const videoItem = target.closest(".video-item");
            if (videoItem) {
                videoItem.remove();
                const list = document.querySelector(`[data-video-list][data-performer-id="${performerId}"]`);
                if (list && list.children.length === 0) {
                    refreshVideoList(Number(performerId), listStatus);
                }
            } else {
                refreshVideoList(Number(performerId), listStatus);
            }
        } catch (error) {
            console.error("動画の削除に失敗しました", error);
            setStatus(listStatus, "削除に失敗しました。時間をおいて再度お試しください。", true);
        }
    });
    const performerButtons = document.querySelectorAll("[data-action=\"fetch-performer-views\"]");

    const updateViewReal = (updates = []) => {
        updates.forEach(({ video_id, view_count }) => {
            if (!video_id) return;
            const target = document.querySelector(`[data-video-view-real=\"${video_id}\"]`);
            if (target) {
                target.textContent = formatNumber(view_count);
            }
        });
    };

    performerButtons.forEach((button) => {
        button.addEventListener("click", async () => {
            const performerId = button.dataset.performerId;
            const statusEl = document.querySelector("[data-performer-view-status]");

            if (!performerId || !statusEl) {
                console.warn("performer_id or status element not found");
                return;
            }

            button.disabled = true;
            statusEl.textContent = "YouTube から再生数を取得しています…";
            statusEl.classList.remove("error");

            try {
                const response = await fetch("api/performer_views.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ performer_id: Number(performerId) }),
                });

                let data = null;
                try {
                    data = await response.json();
                } catch (e) {
                    // ignore JSON parse errors for now
                }

                if (!response.ok) {
                    const message = data?.error || `HTTP error ${response.status}`;
                    if (response.status === 429 && data?.retry_after) {
                        statusEl.textContent = `${message}（${data.retry_after} 秒後に再試行できます）`;
                    } else {
                        statusEl.textContent = message;
                    }
                    statusEl.classList.add("error");
                    return;
                }

                updateViewReal(data?.updated || []);

                if (data?.errors?.length) {
                    const firstError = data.errors[0].message || "一部の動画でエラーが発生しました。";
                    statusEl.textContent = `完了（警告あり）: ${firstError}`;
                    statusEl.classList.add("error");
                } else if ((data?.updated || []).length) {
                    statusEl.textContent = "再生数を更新しました。";
                } else {
                    statusEl.textContent = "再生数の更新対象が見つかりませんでした。";
                }
            } catch (error) {
                console.error("ビュー数取得に失敗しました", error);
                statusEl.textContent = "再生数の取得に失敗しました。時間をおいて再度お試しください。";
                statusEl.classList.add("error");
            } finally {
                button.disabled = false;
            }
        });
    });
});
