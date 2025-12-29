// DOM読み込み後に実行
document.addEventListener("DOMContentLoaded", () => {
    console.log("scripts.js loaded!");

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
});