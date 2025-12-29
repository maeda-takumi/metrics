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
